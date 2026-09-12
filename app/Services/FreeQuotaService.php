<?php
namespace App\Services;
use App\Models\TypingDocument;
use Illuminate\Support\Facades\DB;
class FreeQuotaService {
 public function consumeIfEligible(int $userId): bool {
   return DB::transaction(function() use ($userId) {
     $start=now()->startOfWeek();
     $used=TypingDocument::where('user_id',$userId)->where('created_at','>=',$start)->where('price_rials',0)->lockForUpdate()->exists();
     if ($used) return false;
     return true;
   });
 }
}
