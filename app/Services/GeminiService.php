<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
class GeminiService {
 public function transcribe(string $mime,string $base64): array {
  $key=config('services.gemini.key');$model=config('services.gemini.model','gemini-2.5-flash');abort_unless($key,503,'سرویس هوش مصنوعی تنظیم نشده است');
  $prompt='این سند را برای تایپ فارسی پردازش کن. فقط متن فارسی، انگلیسی و عربی را استخراج کن. جدول، نمودار، شکل و فرمول را نپذیر و اگر وجود داشت rejected=true کن. غلط های احتمالی OCR را در issues به صورت word و suggestions بده. خروجی فقط JSON با کلیدهای text,page_count,rejected,issues باشد.';
  $res=Http::timeout(90)->withHeaders(['x-goog-api-key'=>$key])->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent',['contents'=>[['parts'=>[['text'=>$prompt],['inline_data'=>['mime_type'=>$mime,'data'=>$base64]]]]],'generationConfig'=>['responseMimeType'=>'application/json']]);
  abort_unless($res->successful(),502,'خطا در پردازش هوش مصنوعی');$text=$res->json('candidates.0.content.parts.0.text','{}');$json=json_decode($text,true);abort_unless(is_array($json),502,'پاسخ نامعتبر هوش مصنوعی');return $json;
 }
}
