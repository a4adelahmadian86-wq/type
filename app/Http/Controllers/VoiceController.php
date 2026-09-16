<?php

namespace App\Http\Controllers;

use App\Services\AI\AiPrivacyPolicy;
use App\Services\AI\AiQuotaService;
use App\Services\GoogleSpeechToTextService;
use App\Services\VoiceCorrectionService;
use App\Services\VoiceTranscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoiceController extends Controller
{
    public function transcribe(
        Request $request,
        GoogleSpeechToTextService $googleSpeech,
        VoiceTranscriptionService $voice,
        VoiceCorrectionService $corrections,
        AiQuotaService $quota,
        AiPrivacyPolicy $privacy
    ) {
        $quota->assertAllowed($request->user(), 'can_voice');
        $data = $request->validate([
            'audio'=>['required','file','max:15360','mimetypes:audio/webm,video/webm,audio/ogg,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,video/mp4'],
            'locale'=>['required','string','in:fa-IR,en-US,ar-SA'],
            'processing_mode'=>['nullable','string','in:automatic,local,server,external'],
        ]);
        $processingMode = $privacy->resolve($data['processing_mode'] ?? null, ['external']);
        $file = $data['audio']; $bytes = file_get_contents($file->getRealPath());
        abort_if($bytes === false || $bytes === '', 422, 'فایل صوتی قابل خواندن نیست.');
        $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: 'audio/webm';
        $mime = match ($mime) {'video/webm'=>'audio/webm','video/mp4','audio/mp4'=>'audio/m4a','audio/x-wav'=>'audio/wav',default=>$mime};
        $context = ['user_id'=>$request->user()->id,'input_bytes'=>strlen($bytes),'processing_mode'=>$processingMode];
        try {
            $result = $googleSpeech->enabled() && in_array($mime,['audio/webm','audio/ogg','audio/wav'],true)
                ? $googleSpeech->transcribe($mime,$bytes,$data['locale'],$context)
                : $voice->transcribe($mime,$bytes,$data['locale'],$context);

            $rawText = (string)($result['text'] ?? '');
            $finalText = $corrections->apply(
                $rawText,
                $data['locale'],
                $result['engine'] ?? null,
                $request->user()->id
            );
            $result['raw_text'] = $rawText;
            $result['text'] = $finalText;
            $result['correction_applied'] = $finalText !== $rawText;
        } catch (\Throwable $e) {
            Log::warning('farast.voice.transcription_failed',['user_id'=>$request->user()->id,'google_speech_enabled'=>$googleSpeech->enabled(),'error_code'=>preg_match('/^[a-z0-9_.-]+$/i',$e->getMessage())?$e->getMessage():'voice_provider_failure']);
            return response()->json(['ok'=>false,'message'=>'رونویسی صوتی انجام نشد. دوباره تلاش کنید.'],502);
        }
        return response()->json(['ok'=>true,'text'=>$result['text'],'engine'=>$result['engine'] ?? 'gemini','interaction_id'=>$result['interaction_id'] ?? null,'request_id'=>$result['request_id'] ?? null,
            'ai'=>['request_id'=>$result['request_id'] ?? null,'operation'=>'voice.transcribe','provider'=>$result['engine'] ?? 'gemini','model'=>$result['model'] ?? null,'processing_mode'=>$processingMode,'status'=>'completed','result'=>['text'=>$result['text']],'suggestions'=>[],'warnings'=>[],'metadata'=>['interaction_id'=>$result['interaction_id'] ?? null,'locale'=>$data['locale'],'correction_applied'=>(bool)($result['correction_applied'] ?? false)],'usage'=>null,'error'=>null]]);
    }
}
