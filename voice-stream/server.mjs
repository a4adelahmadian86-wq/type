import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {WebSocketServer} from 'ws';
import dotenv from 'dotenv';
import speech from '@google-cloud/speech';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
dotenv.config({path: path.resolve(__dirname, '../.env')});

const PORT = Number(process.env.VOICE_STREAM_PORT || 6002);
const HOST = process.env.VOICE_STREAM_HOST || '127.0.0.1';
const APP_KEY = String(process.env.APP_KEY || '');
const PROJECT_ID = String(process.env.GOOGLE_CLOUD_PROJECT || process.env.GOOGLE_PROJECT_ID || '');
const REGION = String(process.env.GOOGLE_SPEECH_REGION || 'us');
const MAX_FRAME = 24000;
const RESTART_MS = 4 * 60 * 30 * 1000;
const ALLOWED_ORIGINS = String(process.env.VOICE_STREAM_ORIGINS || 'http://127.0.0.1:8001,http://localhost:8001').split(',').map(v => v.trim()).filter(Boolean);

if (!APP_KEY) throw new Error('APP_KEY is required for Farast voice stream authentication.');
if (!PROJECT_ID) throw new Error('GOOGLE_CLOUD_PROJECT is required for Farast voice streaming.');

const clientOptions = {};
if (process.env.GOOGLE_APPLICATION_CREDENTIALS) clientOptions.keyFilename = process.env.GOOGLE_APPLICATION_CREDENTIALS;
if (process.env.GOOGLE_CLOUD_CREDENTIALS_JSON) {
  try { clientOptions.credentials = JSON.parse(process.env.GOOGLE_CLOUD_CREDENTIALS_JSON); } catch { throw new Error('GOOGLE_CLOUD_CREDENTIALS_JSON is invalid JSON.'); }
}

const SpeechClient = speech.v2?.SpeechClient;
if (!SpeechClient) throw new Error('Installed @google-cloud/speech package does not expose Speech V2.');

const speechClient = new SpeechClient(clientOptions);
const wss = new WebSocketServer({host: HOST, port: PORT, maxPayload: MAX_FRAME + 1024});

function decodeBase64Url(value) {
  const padded = value.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - value.length % 4) % 4);
  return Buffer.from(padded, 'base64').toString('utf8');
}

function verifyToken(token) {
  if (typeof token !== 'string' || !token.includes('.')) throw new Error('invalid_token');
  const [payload, signature] = token.split('.', 2);
  const expected = crypto.createHmac('sha256', APP_KEY).update(payload).digest('hex');
  if (signature.length !== expected.length || !crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expected))) throw new Error('invalid_signature');
  const data = JSON.parse(decodeBase64Url(payload));
  if (!data?.uid || !data?.locale || Number(data.exp) < Math.floor(Date.now() / 1000)) throw new Error('expired_token');
  return data;
}

function send(ws, message) {
  if (ws.readyState === ws.OPEN) ws.send(JSON.stringify(message));
}

function buildConfig(locale) {
  return {
    recognizer: `projects/${PROJECT_ID}/locations/${REGION}/recognizers/_`,
    streamingConfig: {
      config: {
        explicitDecodingConfig: {
          encoding: 'LINEAR16',
          sampleRateHertz: 16000,
          audioChannelCount: 1,
        },
        languageCodes: [locale],
        model: 'chirp_3',
        features: {enableAutomaticPunctuation: true},
      },
      streamingFeatures: {
        interimResults: true,
        enableVoiceActivityEvents: true,
      },
    },
  };
}

class Session {
  constructor(ws, auth) {
    this.ws = ws;
    this.auth = auth;
    this.stream = null;
    this.startedAt = 0;
    this.restartTimer = null;
    this.closed = false;
    this.audioSeconds = 0;
    this.lastFinal = '';
  }

  startStream() {
    if (this.closed) return;
    if (this.stream) {
      try { this.stream.end(); } catch {}
      this.stream = null;
    }

    const request = buildConfig(this.auth.locale);
    const streamFactory = typeof speechClient._streamingRecognize === 'function'
      ? speechClient._streamingRecognize.bind(speechClient)
      : speechClient.streamingRecognize.bind(speechClient);

    this.stream = streamFactory()
      .on('error', error => {
        if (this.closed) return;
        send(this.ws, {type: 'error', code: error?.code ?? 'provider_error', message: 'voice_provider_error'});
      })
      .on('data', response => {
        if (this.closed || !response?.results?.length) return;
        for (const result of response.results) {
          const alt = result?.alternatives?.[0];
          if (!alt?.transcript) continue;
          const transcript = String(alt.transcript).trim();
          if (!transcript) continue;
          if (result.isFinal) {
            this.lastFinal = transcript;
            send(this.ws, {
              type: 'final',
              text: transcript,
              confidence: Number(alt.confidence || 0),
              provider: 'google',
              model: 'chirp_3',
            });
          } else {
            send(this.ws, {
              type: 'interim',
              text: transcript,
              stability: Number(result.stability || 0),
              provider: 'google',
              model: 'chirp_3',
            });
          }
        }
      })
      .on('end', () => {
        if (!this.closed) send(this.ws, {type: 'stream_end'});
      });

    this.stream.write(request);
    this.startedAt = Date.now();
    clearTimeout(this.restartTimer);
    this.restartTimer = setTimeout(() => this.restart(), RESTART_MS);
    send(this.ws, {type: 'ready', provider: 'google', model: 'chirp_3'});
  }

  pushAudio(buffer) {
    if (this.closed || !this.stream) return;
    if (!Buffer.isBuffer(buffer) || buffer.length === 0) return;
    for (let offset = 0; offset < buffer.length; offset += MAX_FRAME) {
      const part = buffer.subarray(offset, Math.min(offset + MAX_FRAME, buffer.length));
      this.stream.write({audio: part});
      this.audioSeconds += part.length / (16000 * 2);
    }
  }

  restart() {
    if (this.closed) return;
    send(this.ws, {type: 'restarting'});
    this.startStream();
  }

  stop() {
    this.closed = true;
    clearTimeout(this.restartTimer);
    if (this.stream) {
      try { this.stream.end(); } catch {}
      this.stream = null;
    }
    send(this.ws, {type: 'stopped'});
  }
}

wss.on('connection', (ws, request) => {
  const origin = String(request.headers.origin || '');
  if (ALLOWED_ORIGINS.length && origin && !ALLOWED_ORIGINS.includes(origin)) {
    ws.close(1008, 'origin_not_allowed');
    return;
  }

  let session = null;
  ws.on('message', (data, isBinary) => {
    try {
      if (!session) {
        if (isBinary) throw new Error('start_required');
        const message = JSON.parse(data.toString('utf8'));
        if (message.type !== 'start') throw new Error('start_required');
        const auth = verifyToken(message.token);
        if (message.locale !== auth.locale) throw new Error('locale_mismatch');
        session = new Session(ws, auth);
        session.startStream();
        return;
      }

      if (!isBinary) {
        const message = JSON.parse(data.toString('utf8'));
        if (message.type === 'stop') session.stop();
        return;
      }

      session.pushAudio(Buffer.from(data));
    } catch (error) {
      send(ws, {type: 'error', code: error?.message || 'stream_error', message: 'voice_stream_error'});
      try { ws.close(1008, 'voice_stream_error'); } catch {}
    }
  });

  ws.on('close', () => session?.stop());
  ws.on('error', () => session?.stop());
});

console.log(`Farast voice stream listening on ws://${HOST}:${PORT}`);
console.log(`Google Speech V2 region=${REGION} model=chirp_3`);
