<?php
namespace App\Services;
use App\Models\PricingRule;
class PricingService {
 public function quote(string $text, int $pages): array {
   $rules=PricingRule::where('active',true)->pluck('value','key');
   $base=(int)($rules['page_base'] ?? 35000);
   $english=(int)($rules['english_multiplier'] ?? 120);
   $factor=1.0;
   $englishChars=preg_match_all('/[A-Za-z]/',$text) ?: 0;
   $allChars=max(1,mb_strlen($text));
   if ($englishChars/$allChars > 0.2) $factor=max(1,$english/100);
   return ['pages'=>max(1,$pages),'word_count'=>$this->words($text),'price_rials'=>(int)round(max(1,$pages)*$base*$factor),'breakdown'=>['base_page'=>$base,'language_factor'=>round($factor,2)]];
 }
 private function words(string $text): int { return count(preg_split('/\s+/u',trim($text),-1,PREG_SPLIT_NO_EMPTY)); }
}
