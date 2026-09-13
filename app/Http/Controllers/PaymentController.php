<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\TypingDocument;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\FreeQuotaService;
use App\Services\PricingService;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function createForDocument(Request $request, TypingDocument $document, PricingService $pricing, FreeQuotaService $free)
    {
        abort_unless($document->user_id === auth()->id() && $document->status !== 'deleted', 404);
        $request->validate(['accept_terms' => ['accepted']]);
        $quote = $pricing->quote((string)$document->content, max(1, (int)$document->page_count));
        $freePages = $free->availablePages(auth()->user(), $this->freePagesSetting());
        $freeApplied = min(1, $freePages, $quote['chargeable_pages']);
        $discount = $freeApplied * $quote['breakdown']['base_page'];
        $subtotal = max(0, $quote['price_rials']);
        $tax = $this->tax($subtotal - $discount);
        $total = max(0, $subtotal - $discount + $tax);
        $order = Order::create([
            'user_id' => auth()->id(), 'document_id' => $document->id,
            'subtotal_rials' => $subtotal, 'discount_rials' => $discount, 'tax_rials' => $tax,
            'total_rials' => $total, 'status' => 'pending',
            'pricing_snapshot' => ['content_hash'=>hash('sha256',(string)$document->content),'quote'=>$quote,'tax_rate'=>$this->taxRate(),'free_pages_available'=>$freePages],
            'free_pages_applied' => $freeApplied, 'terms_accepted_at' => now(),
        ]);
        return redirect()->route('checkout', $order);
    }

    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 404);
        $wallet = auth()->user()->wallet()->firstOrCreate([], ['balance_rials'=>0]);
        return view('checkout', compact('order','wallet'));
    }

    public function payWithWallet(Request $request, Order $order, PricingService $pricing, FreeQuotaService $free)
    {
        abort_unless($order->user_id === auth()->id(), 404);
        $request->validate(['accept_terms'=>['accepted']]);
        $result = DB::transaction(function () use ($order, $pricing, $free) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->isPaid()) return $locked;
            $document = TypingDocument::whereKey($locked->document_id)->where('user_id', auth()->id())->lockForUpdate()->firstOrFail();
            $quote = $pricing->quote((string)$document->content, max(1, (int)$document->page_count));
            $freeAvailable = $free->availablePages(auth()->user(), $this->freePagesSetting());
            $freeApplied = min(1, $freeAvailable, $quote['chargeable_pages']);
            $discount = $freeApplied * $quote['breakdown']['base_page'];
            $subtotal = max(0, $quote['price_rials']);
            $tax = $this->tax($subtotal - $discount);
            $total = max(0, $subtotal - $discount + $tax);
            $hash = hash('sha256',(string)$document->content);
            $locked->update(['subtotal_rials'=>$subtotal,'discount_rials'=>$discount,'tax_rials'=>$tax,'total_rials'=>$total,'free_pages_applied'=>$freeApplied,'pricing_snapshot'=>['content_hash'=>$hash,'quote'=>$quote,'tax_rate'=>$this->taxRate(),'free_pages_available'=>$freeAvailable],'terms_accepted_at'=>now()]);
            $wallet = Wallet::firstOrCreate(['user_id'=>auth()->id()], ['balance_rials'=>0]);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            if ($total > $wallet->balance_rials) throw new \RuntimeException('موجودی کیف پول برای این سفارش کافی نیست.');
            $before = $wallet->balance_rials; $after = $before - $total;
            if ($total > 0) {
                $wallet->update(['balance_rials'=>$after]);
                WalletTransaction::create(['wallet_id'=>$wallet->id,'type'=>'debit','amount_rials'=>$total,'balance_before'=>$before,'balance_after'=>$after,'reference_type'=>'order','reference_id'=>$locked->id,'description'=>'پرداخت هزینه خروجی سند','idempotency_key'=>'order-'.$locked->id]);
            }
            if ($freeApplied > 0) $free->consume(auth()->user(), $freeApplied, $this->freePagesSetting());
            Payment::create(['order_id'=>$locked->id,'gateway'=>'wallet','amount_rials'=>$total,'status'=>'paid','transaction_id'=>'WALLET-'.$locked->id.'-'.now()->timestamp,'paid_at'=>now()]);
            $locked->update(['status'=>'paid','paid_at'=>now()]);
            $document->update(['status'=>'paid','price_rials'=>$total]);
            return $locked->fresh();
        });
        if ($result->isPaid()) return redirect()->route('editor')->with('status','پرداخت با موفقیت ثبت شد؛ اکنون خروجی قابل دریافت است.');
        return back()->withErrors(['payment'=>'پرداخت انجام نشد.']);
    }

    private function freePagesSetting(): int { return max(0,(int)SiteSetting::read('weekly_free_pages',1)); }
    private function taxRate(): float { return max(0,(float)SiteSetting::read('tax_rate_percent',10)); }
    private function tax(int $taxable): int { return (bool)SiteSetting::read('tax_enabled',true) ? (int)round(max(0,$taxable) * $this->taxRate() / 100) : 0; }
}
