import crypto from 'node:crypto';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {WebSocketServer} from 'ws';
import dotenv from 'dotenv';
import speech from '@google-cloud/speech';

const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
dotenv.config({path:path.join(root,'.env')});
const PORT=Number(process.env.VOICE_STREAM_PORT||6002),HOST=process.env.VOICE_STREAM_HOST||'127.0.0.1';
const APP_KEY=String(process.env.APP_KEY||''),PROJECT_ID=String(process.env.GOOGLE_CLOUD_PROJECT||process.env.GOOGLE_PROJECT_ID||''),REGION=String(process.env.GOOGLE_SPEECH_REGION||'us');
const MAX_FRAME=24000,RESTART_MS=Number(process.env.VOICE_STREAM_RESTART_MS||270000);
const ORIGINS=String(process.env.VOICE_STREAM_ORIGINS||'http://127.0.0.1:8001,http://localhost:8001').split(',').map(x=>x.trim()).filter(Boolean);
if(!APP_KEY)throw new Error('APP_KEY is required.');if(!PROJECT_ID)throw new Error('GOOGLE_CLOUD_PROJECT is required.');
const options={};if(process.env.GOOGLE_APPLICATION_CREDENTIALS)options.keyFilename=process.env.GOOGLE_APPLICATION_CREDENTIALS;if(process.env.GOOGLE_CLOUD_CREDENTIALS_JSON)options.credentials=JSON.parse(process.env.GOOGLE_CLOUD_CREDENTIALS_JSON);
const SpeechClient=speech.v2?.SpeechClient;if(!SpeechClient)throw new Error('Google Speech V2 client is unavailable.');
const google=new SpeechClient(options),wss=new WebSocketServer({host:HOST,port:PORT,maxPayload:MAX_FRAME+2048});
const decode=v=>Buffer.from(v.replace(/-/g,'+').replace(/_/g,'/')+'='.repeat((4-v.length%4)%4),'base64').toString('utf8');
function tokenVerify(token){const [p,s]=String(token||'').split('.',2);if(!p||!s)throw new Error('invalid_token');const e=crypto.createHmac('sha256',APP_KEY).update(p).digest('hex');if(s.length!==e.length||!crypto.timingSafeEqual(Buffer.from(s),Buffer.from(e)))throw new Error('invalid_signature');const d=JSON.parse(decode(p));if(!d.uid||!d.locale||Number(d.exp)<Math.floor(Date.now()/1000))throw new Error('expired_token');return d}
function send(ws,x){if(ws.readyState===1)ws.send(JSON.stringify(x))}
function request(locale){return{recognizer:`projects/${PROJECT_ID}/locations/${REGION}/recognizers/_`,streamingConfig:{config:{explicitDecodingConfig:{encoding:'LINEAR16',sampleRateHertz:16000,audioChannelCount:1},languageCodes:[locale],model:'chirp_3',features:{enableAutomaticPunctuation:true}},streamingFeatures:{interimResults:true,enableVoiceActivityEvents:true}}}}
class Session{
 constructor(ws,auth){this.ws=ws;this.auth=auth;this.stream=null;this.closed=false;this.timer=null;this.lastFinal=''}
 start(){if(this.closed)return;if(this.stream)try{this.stream.end()}catch{};const factory=typeof google._streamingRecognize==='function'?google._streamingRecognize.bind(google):google.streamingRecognize.bind(google);this.stream=factory().on('error',e=>{if(!this.closed)send(this.ws,{type:'error',code:e?.code??'provider_error'})}).on('data',r=>{for(const x of r?.results||[]){const a=x?.alternatives?.[0];const t=String(a?.transcript||'').trim();if(!t)continue;if(x.isFinal){this.lastFinal=t;send(this.ws,{type:'final',text:t,confidence:Number(a?.confidence||0),provider:'google',model:'chirp_3'})}else send(this.ws,{type:'interim',text:t,stability:Number(x.stability||0),provider:'google',model:'chirp_3'})}}});this.stream.write(request(this.auth.locale));clearTimeout(this.timer);this.timer=setTimeout(()=>{if(!this.closed){send(this.ws,{type:'restarting'});this.start()}},RESTART_MS);send(this.ws,{type:'ready',provider:'google',model:'chirp_3'})}
 audio(buf){if(this.closed||!this.stream)return;for(let i=0;i<buf.length;i+=MAX_FRAME)this.stream.write({audio:buf.subarray(i,Math.min(i+MAX_FRAME,buf.length))})}
 stop(){this.closed=true;clearTimeout(this.timer);if(this.stream)try{this.stream.end()}catch{}this.stream=null}
}
wss.on('connection',(ws,req)=>{const origin=String(req.headers.origin||'');if(origin&&ORIGINS.length&&!ORIGINS.includes(origin)){ws.close(1008,'origin_not_allowed');return}let s=null;ws.on('message',(data,binary)=>{try{if(!s){if(binary)throw new Error('start_required');const m=JSON.parse(data.toString());if(m.type!=='start')throw new Error('start_required');const a=tokenVerify(m.token);if(m.locale!==a.locale)throw new Error('locale_mismatch');s=new Session(ws,a);s.start();return}if(binary)s.audio(Buffer.from(data));else{const m=JSON.parse(data.toString());if(m.type==='stop')s.stop()}}catch(e){send(ws,{type:'error',code:e?.message||'stream_error'});try{ws.close(1008,'voice_stream_error')}catch{}}});ws.on('close',()=>s?.stop());ws.on('error',()=>s?.stop())});
console.log(`Farast voice stream: ws://${HOST}:${PORT} | Google STT V2 ${REGION}/chirp_3`);
