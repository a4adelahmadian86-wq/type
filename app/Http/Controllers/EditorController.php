<?php

namespace App\Http\Controllers;

use App\Models\AiFeedback;
use App\Models\EditorSession;
use App\Models\TypingDocument;
use App\Models\WeeklyFreePage;
use App\Services\GeminiService;
use App\Services\PricingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditorController extends Controller
{
    public function pricing() { return view('pricing'); }

    public function dashboard() { return view('dashboard', ['documents' => auth()->user()->documents()->latest()->get()]); }

    public function create() { return view('editor'); }

    public function upload(Request $r)
    {
        $r->validate(['source' => 'required|file|max:51200|mimes:jpg,jpeg,png,webp,pdf,zip']);
        $f = $r->file('source');
        $path = $f->store('typing/'.auth()->id(), 'private');
        return response()->json(['path' => $path, 'mime' => $f->getMimeType(), 'name' => $f->getClientOriginalName()]);
    }

    public function analyze(Request $r, GeminiService $ai, PricingService $pricing)
    {
        $data = $r->validate(['path'=>'required|string','mime'=>'required|string','source_name'=>'nullable|string|max:255']);
        abort_unless(Str::startsWith($data['path'], 'typing/'.auth()->id().'/'), 403);
        $bytes = Storage::disk('private')->get($data['path']);
        $hash = hash('sha256', $bytes);

        try {
            $result = $ai->transcribe($data['mime'], base64_encode($bytes), [
                'user_id' => auth()->id(),
                'source_hash' => $hash,
                'input_bytes' => strlen($bytes),
                'source_name' => $data['source_name'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('farast.editor.ocr_exception', ['user_id'=>auth()->id(),'error'=>$e->getMessage()]);
            return response()->json(['ok'=>false,'message'=>'ارتباط با هوش مصنوعی برقرار نشد. جزئیات خطا در لاگ ثبت شده است.'], 502);
        }

        if (($result['rejected'] ?? false)) {
            return response()->json([
                'ok'=>false,
                'message'=>'این ورودی شامل جدول، نمودار، شکل، فرمول یا محتوای گرافیکی است و برای تایپ دقیق متن عادی پذیرفته نمی‌شود.',
                'reason'=>$result['reason'] ?? null,
                'interaction_id'=>$result['_ai_interaction_id'] ?? null,
            ], 422);
        }

        $blocks = $result['blocks'] ?? [];
        $html = $this->blocksToHtml($blocks);
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace(['</p>','</li>','<br>'], ['\n','\n','\n'],$html))));
        $pages = max(1, (int)($result['page_count'] ?? 1));
        $quote = $pricing->quote($text, $pages);
        $price = $quote['price_rials'];

        $week = now()->startOfWeek()->toDateString();
        try {
            WeeklyFreePage::create(['user_id'=>auth()->id(),'week_start'=>$week,'pages'=>1]);
            $price = max(0, $price - (int)$quote['breakdown']['base_page']);
        } catch (UniqueConstraintViolationException $e) {}

        $doc = TypingDocument::create([
            'user_id'=>auth()->id(), 'title'=>'سند جدید', 'content'=>$html,
            'source_path'=>$data['path'], 'source_hash'=>$hash, 'page_count'=>$pages,
            'word_count'=>$quote['word_count'],
            'language_mix'=>['fa'=>preg_match('/[\x{0600}-\x{06FF}]/u',$text)>0,'en'=>preg_match('/[A-Za-z]/',$text)>0],
            'status'=>'analyzed', 'price_rials'=>$price,
            'expires_at'=>now()->addDays((int)env('FILES_TTL_DAYS',14)),
        ]);

        // Link the persisted AI interaction to the document after the document exists.
        if (!empty($result['_ai_interaction_id'])) {
            \App\Models\AiInteraction::whereKey($result['_ai_interaction_id'])->update(['document_id'=>$doc->id]);
        }

        return response()->json([
            'ok'=>true,
            'document_id'=>$doc->id,
            'html'=>$html,
            'text'=>$text,
            'pages'=>$pages,
            'price'=>$price,
            'issues'=>$this->flattenIssues($blocks),
            'breakdown'=>$quote['breakdown'],
            'interaction_id'=>$result['_ai_interaction_id'] ?? null,
            'request_id'=>$result['_request_id'] ?? null,
        ]);
    }

    public function save(Request $r)
    {
        $d = $r->validate(['document_id'=>'required|integer','content'=>'required|string|max:4000000']);
        $doc = TypingDocument::where('id',$d['document_id'])->where('user_id',auth()->id())->firstOrFail();
        $html = $this->sanitizeEditorHtml($d['content']);
        $plain = trim(preg_replace('/\s+/u',' ',strip_tags($html)));
        $doc->update(['content'=>$html,'word_count'=>count(preg_split('/\s+/u',$plain,-1,PREG_SPLIT_NO_EMPTY))]);
        return response()->json(['ok'=>true,'saved_at'=>now()->toIso8601String()]);
    }

    public function feedback(Request $r)
    {
        $d = $r->validate([
            'document_id'=>'required|integer',
            'ai_interaction_id'=>'nullable|integer',
            'type'=>'required|string|in:word_correction,wrong_output,rating,formatting,other',
            'rating'=>'nullable|integer|min:1|max:5',
            'category'=>'nullable|string|max:80',
            'original_text'=>'nullable|string|max:1000',
            'corrected_text'=>'nullable|string|max:1000',
            'note'=>'nullable|string|max:3000',
            'context'=>'nullable|array',
        ]);
        $doc = TypingDocument::where('id',$d['document_id'])->where('user_id',auth()->id())->firstOrFail();
        if (!empty($d['ai_interaction_id'])) {
            $d['ai_interaction_id'] = \App\Models\AiInteraction::whereKey($d['ai_interaction_id'])->where('user_id',auth()->id())->value('id');
        }
        $feedback = AiFeedback::create([
            'user_id'=>auth()->id(),'document_id'=>$doc->id,'ai_interaction_id'=>$d['ai_interaction_id'] ?? null,
            'type'=>$d['type'],'rating'=>$d['rating'] ?? null,'category'=>$d['category'] ?? null,
            'original_text'=>$d['original_text'] ?? null,'corrected_text'=>$d['corrected_text'] ?? null,
            'note'=>$d['note'] ?? null,'context'=>$d['context'] ?? null,
        ]);
        Log::info('farast.ai.feedback', ['feedback_id'=>$feedback->id,'user_id'=>auth()->id(),'document_id'=>$doc->id,'type'=>$feedback->type]);
        return response()->json(['ok'=>true,'feedback_id'=>$feedback->id]);
    }

    public function heartbeat(Request $r)
    {
        $token=$r->session()->get('editor_session_token');
        $s=EditorSession::where('token',$token)->where('user_id',auth()->id())->first();
        if($s)$s->update(['last_seen_at'=>now(),'expires_at'=>now()->addMinutes(20)]);
        return response()->json(['ok'=>true]);
    }

    private function blocksToHtml(array $blocks): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $type = $block['type'] ?? 'paragraph';
            $text = (string)($block['text'] ?? '');
            $uncertain = $block['uncertain'] ?? [];
            $safe = e($text);
            foreach ($uncertain as $u) {
                $word = (string)($u['text'] ?? '');
                if ($word === '') continue;
                $suggestions = json_encode(array_values($u['suggestions'] ?? []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                $replacement = '<span class="ai-uncertain" data-original="'.e($word).'" data-suggestions="'.e($suggestions).'">'.e($word).'</span>';
                $safe = preg_replace('/'.preg_quote(e($word),'/').'/u', $replacement, $safe, 1) ?? $safe;
            }
            $safe = nl2br($safe, false);
            if ($type === 'heading') $html .= '<h'.min(3,max(1,(int)($block['level'] ?? 2))).'>'.$safe.'</h'.min(3,max(1,(int)($block['level'] ?? 2))).'>';
            elseif ($type === 'list_item') $html .= '<p class="ai-list-item">• '.$safe.'</p>';
            elseif ($type === 'quote') $html .= '<blockquote>'.$safe.'</blockquote>';
            elseif ($type === 'blank') $html .= '<p><br></p>';
            else $html .= '<p>'.$safe.'</p>';
        }
        return $html !== '' ? $html : '<p><br></p>';
    }

    private function flattenIssues(array $blocks): array
    {
        $issues=[];
        foreach($blocks as $block){foreach(($block['uncertain'] ?? []) as $u){$issues[]=['word'=>$u['text'] ?? '','suggestions'=>$u['suggestions'] ?? []];}}
        return $issues;
    }

    private function sanitizeEditorHtml(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><s><ol><ul><li><blockquote><h1><h2><h3><span><div>'; 
        $html = strip_tags($html, $allowed);
        if (!class_exists(\DOMDocument::class)) return $html;
        $dom = new \DOMDocument('1.0','UTF-8');
        @$dom->loadHTML('<?xml encoding="UTF-8"><div id="farast-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
        $root=$dom->getElementById('farast-root');
        if(!$root) return $html;
        $allowedAttrs=['class','dir','data-original','data-suggestions'];
        $walker=function($node) use (&$walker,$allowedAttrs){
            if($node instanceof \DOMElement){
                foreach(iterator_to_array($node->attributes) as $attr){if(!in_array($attr->name,$allowedAttrs,true))$node->removeAttribute($attr->name);}
            }
            foreach(iterator_to_array($node->childNodes) as $child)$walker($child);
        };
        $walker($root);
        $out=''; foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);
        return $out ?: '<p><br></p>';
    }
}
