<?php

namespace App\Http\Controllers;

use App\Services\EditorAiAssistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class EditorAiAssistController extends Controller
{
    public function __invoke(Request $request, EditorAiAssistService $ai)
    {
        $data = $request->validate([
            'operation'=>['required','string','max:80'],'text'=>['required','string','max:100000'],'sentence'=>['nullable','string','max:4000'],
            'before_cursor'=>['nullable','string','max:12000'],'after_cursor'=>['nullable','string','max:12000'],'target_language'=>['nullable','string','max:120'],
            'tone'=>['nullable','string','max:120'],'instruction'=>['nullable','string','max:1000'],'processing_mode'=>['nullable','string','in:automatic,local,server,external'],
            'provider'=>['nullable','string','max:40','regex:/^[A-Za-z0-9._-]+$/'],
        ]);
        try {
            $result = $ai->assist($data['operation'],$data['text'],array_merge($data,['user'=>$request->user()]));
            return response()->json(array_merge(['ok'=>true],$result));
        } catch (ValidationException|HttpExceptionInterface $e) { throw $e; }
        catch (\InvalidArgumentException $e) { return response()->json(['ok'=>false,'message'=>$e->getMessage()],422); }
        catch (\Throwable $e) {
            Log::warning('farast.editor.ai_assist_failed',['user_id'=>$request->user()->id,'operation'=>$data['operation'],'error_code'=>preg_match('/^ai_[a-z0-9_]+$/',$e->getMessage())?$e->getMessage():'ai_provider_failure']);
            return response()->json(['ok'=>false,'message'=>'پیشنهاد هوش مصنوعی در این لحظه در دسترس نیست.'],502);
        }
    }
}
