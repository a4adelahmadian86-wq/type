<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class GeminiService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    public function transcribe(string $mime, string $base64, array $context = []): array
    {
        $key = SiteSetting::read('gemini_api_key') ?: config('services.gemini.key');
        $model = SiteSetting::read('gemini_model') ?: config('services.gemini.model', 'gemini-3.8-flash');
        abort_unless($key, 503, 'سرویس هوش مصنوعی تنظیم نشده است');
        $requestId = (string) Str::uuid();
        $prompt = <<<'PROMPT'
تو موتور OCR و رونویسی دقیق یک سامانه تایپ حرفه‌ای هستی.
وظیفه فقط رونویسی دیداری و دقیق محتوای ورودی است؛ نه ویرایش، نه بازنویسی، نه خلاصه‌سازی و نه تکمیل متن.
قوانین غیرقابل مذاکره:
۱. هیچ کلمه، جمله، توضیح، عنوان، نتیجه یا نشانه‌ای که در منبع دیده نمی‌شود اضافه نکن.
۲. ترتیب کلمات، ترتیب خطوط، پاراگراف‌بندی، شکست خطوط و فاصله‌های معنادار را حفظ کن.
۳. علائم نگارشی را فقط در صورتی خروجی بده که در منبع وجود داشته باشند یا شکل آن‌ها در تصویر به‌طور روشن قابل تشخیص باشد. برای زیباتر شدن متن علامت نگارشی اضافه نکن.
۴. نیم‌فاصله U+200C را هرجا در منبع وجود دارد دقیقاً حفظ کن. اگر وجود آن از تصویر روشن نیست، حدس نزن و آن را اضافه نکن.
۵. حروف فارسی، عربی و انگلیسی را همان‌طور که در منبع هستند نگه دار و زبان را ترجمه یا فارسی‌سازی نکن.
۶. اگر واژه‌ای واقعاً ناخواناست، آن را حدس نزن. همان واژه را با نزدیک‌ترین خوانش دیداری برگردان و در uncertain همان مورد را ثبت کن.
۷. جدول، نمودار، چارت، شکل، WordArt، TextBox، فرمول و محتوای گرافیکی را متن عادی محسوب نکن. اگر چنین محتوایی بخش اصلی ورودی است، rejected=true کن.
۸. پاراگراف خالی را حفظ کن. فاصله خطی و شکست پاراگراف را از روی منبع بازسازی کن.
۹. هیچ HTML یا Markdown داخل متن ننویس.
۱۰. پاسخ فقط JSON مطابق schema باشد.
برای هر بخش متن، یک block بساز. نوع block فقط paragraph، heading، list_item، quote یا blank باشد و فقط وقتی از ظاهر منبع قابل تشخیص است از heading یا list_item استفاده کن. سطح heading را در level بده.
برای موارد مشکوک، suggestions فقط پیشنهادهای احتمالی باشند و هرگز در text جایگزین نشوند.
PROMPT;
        $interaction = AiInteraction::create(['user_id'=>$context['user_id']??null,'document_id'=>$context['document_id']??null,'provider'=>'gemini','model'=>$model,'operation'=>'ocr_transcription','request_id'=>$requestId,'source_hash'=>$context['source_hash']??null,'prompt_hash'=>hash('sha256',$prompt),'input_bytes'=>(int)($context['input_bytes']??0),'status'=>'started','input_meta'=>['mime'=>$mime,'source_name'=>$context['source_name']??null]]);
        $started=hrtime(true);
        try {
            $inputs=[['type'=>'text','text'=>$prompt]];$imageCount=0;
            if($mime==='application/zip'){
                $raw=base64_decode($base64,true);$tmp=tempnam(sys_get_temp_dir(),'farast_zip_');file_put_contents($tmp,$raw);$zip=new ZipArchive();abort_unless($zip->open($tmp)===true,422,'فایل ZIP قابل خواندن نیست');$allowed=['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
                for($i=0;$i<$zip->numFiles&&$imageCount<40;$i++){ $name=$zip->getNameIndex($i);$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));if(!isset($allowed[$ext]))continue;$data=$zip->getFromIndex($i);if($data===false)continue;$inputs[]=['type'=>'image','data'=>base64_encode($data),'mime_type'=>$allowed[$ext]];$imageCount++; }
                $zip->close();@unlink($tmp);abort_if($imageCount===0,422,'فایل ZIP شامل تصویر قابل پردازش نیست');
            }elseif(str_starts_with($mime,'image/')){$inputs[]=['type'=>'image','data'=>$base64,'mime_type'=>$mime];$imageCount=1;}elseif($mime==='application/pdf'){$inputs[]=['type'=>'document','data'=>$base64,'mime_type'=>'application/pdf'];}else{abort(422,'نوع فایل برای OCR پشتیبانی نمی‌شود');}
            $response=Http::timeout(180)->retry(2,700)->withHeaders(['x-goog-api-key'=>$key,'Content-Type'=>'application/json','X-Farast-Request-Id'=>$requestId])->post(self::ENDPOINT,['model'=>$model,'input'=>$inputs,'store'=>false,'system_instruction'=>'دقت رونویسی از هر نوع زیباتر کردن متن مهم‌تر است. متن را هرگز از خودت تولید نکن.','response_format'=>['type'=>'text','mime_type'=>'application/json','schema'=>['type'=>'object','properties'=>['rejected'=>['type'=>'boolean'],'reason'=>['type'=>'string'],'page_count'=>['type'=>'integer'],'blocks'=>['type'=>'array','items'=>['type'=>'object','properties'=>['type'=>['type'=>'string','enum'=>['paragraph','heading','list_item','quote','blank']],'level'=>['type'=>'integer'],'text'=>['type'=>'string'],'uncertain'=>['type'=>'array','items'=>['type'=>'object','properties'=>['text'=>['type'=>'string'],'suggestions'=>['type'=>'array','items'=>['type'=>'string']]],'required'=>['text','suggestions']]]],'required'=>['type','level','text','uncertain']]]],'required'=>['rejected','reason','page_count','blocks']]]]);
            $latency=(int)((hrtime(true)-$started)/1000000);if(!$response->successful())throw new \RuntimeException('Gemini HTTP '.$response->status().' '.$response->body());
            $providerId=$response->json('id');$rawOutput=collect($response->json('outputs',[]))->filter(fn($o)=>($o['type']??null)==='text')->pluck('text')->implode('');if($rawOutput==='')$rawOutput=collect($response->json('steps',[]))->flatMap(fn($s)=>$s['content']??[])->filter(fn($c)=>($c['type']??null)==='text')->pluck('text')->implode('');$json=json_decode($rawOutput,true);if(!is_array($json))throw new \RuntimeException('Gemini returned invalid JSON');
            $interaction->update(['provider_interaction_id'=>$providerId,'latency_ms'=>$latency,'output_bytes'=>strlen($rawOutput),'status'=>'completed','output_meta'=>['http_status'=>$response->status(),'image_count'=>$imageCount,'block_count'=>count($json['blocks']??[]),'rejected'=>(bool)($json['rejected']??false)]]);
            Log::info('farast.ai.completed',['request_id'=>$requestId,'interaction_id'=>$interaction->id,'provider_interaction_id'=>$providerId,'model'=>$model,'latency_ms'=>$latency,'status'=>'completed']);$json['_ai_interaction_id']=$interaction->id;$json['_request_id']=$requestId;return $json;
        }catch(\Throwable $e){$latency=(int)((hrtime(true)-$started)/1000000);$interaction->update(['latency_ms'=>$latency,'status'=>'failed','error_message'=>mb_substr($e->getMessage(),0,4000)]);Log::error('farast.ai.failed',['request_id'=>$requestId,'interaction_id'=>$interaction->id,'model'=>$model,'latency_ms'=>$latency,'error'=>$e->getMessage()]);throw $e;}
    }
}
