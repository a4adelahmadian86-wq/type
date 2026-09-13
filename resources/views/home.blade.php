@extends('layouts.app')
@section('content')
@if($announcements->count())
<section class="home-announcements" dir="rtl">
    <div class="home-announcement-label"><i class="fa-solid fa-bullhorn"></i><b>آخرین اطلاعیه</b></div>
    <div class="home-announcement-main"><strong>{{ $announcements->first()->title }}</strong><span>{{ \Illuminate\Support\Str::limit($announcements->first()->body,150) }}</span></div>
    <a href="/announcements">همه اعلانات <i class="fa-solid fa-arrow-left"></i></a>
</section>
@endif

<section class="hero farast-home-hero" id="start">
    <div class="hero-copy">
        <span class="eyebrow"><i class="fa-solid fa-cubes-stacked"></i> یک فضای واحد برای خرید و خدمات</span>
        <h1>فایل بخر، خدمت سفارش بده، کار را در یک فضای هوشمند ادامه بده.</h1>
        <p>فراست فروش فایل‌های دیجیتال، تایپ و تبدیل، ویرایش، پردازش هوشمند، جستجو، سبد خرید و فضای کاربر را در یک تجربه یکپارچه کنار هم قرار می‌دهد.</p>
        <div class="actions"><a class="btn primary" href="#farastStore"><i class="fa-solid fa-store"></i> ورود به فروشگاه</a><a class="btn ghost" href="/editor"><i class="fa-solid fa-pen-ruler"></i> شروع خدمات</a></div>
        <div class="hero-trust"><span><i class="fa-solid fa-bag-shopping"></i> سبد خرید یکپارچه</span><span><i class="fa-solid fa-microphone"></i> ورودی صوتی</span><span><i class="fa-solid fa-wand-magic-sparkles"></i> پردازش هوشمند</span><span><i class="fa-solid fa-shield-halved"></i> فضای کار خصوصی</span></div>
    </div>

    <div class="hero-card premium-upload-card">
        <div class="upload-home" id="homeDrop">
            <div class="upload-orbit"><span><i class="fa-solid fa-cloud-arrow-up"></i></span><i class="fa-solid fa-sparkles orbit-spark"></i></div>
            <strong>فایل را برای برآورد سریع رها کنید</strong>
            <span>تصویر، PDF یا ZIP تصاویر</span>
            <button type="button" id="homeChoose"><i class="fa-solid fa-folder-open"></i> انتخاب فایل</button>
            <input id="homeFile" type="file" accept="image/*,.pdf,.zip" hidden>
        </div>
        <div id="homeMsg" class="home-upload-msg" hidden></div>
        <small><i class="fa-solid fa-gift"></i> اگر اعتبار هفتگی شما فعال باشد، یک صفحه در برآورد اعمال می‌شود.</small>
    </div>
</section>

<section class="farast-app-section farast-dual-engine" aria-label="هسته‌های اصلی فراست">
    <div class="farast-section-head"><div><h2>دو موتور اصلی فراست</h2><p>فروشگاه و خدمات، دو بخش مستقل اما متصل در یک محصول واحد.</p></div></div>
    <div class="farast-service-grid">
        <article class="farast-service-card" data-searchable><div class="farast-service-icon"><i class="fa-solid fa-store"></i></div><div><h3>فروش فایل دیجیتال</h3><p>کاتالوگ دسته‌بندی‌شده، جستجو و فیلتر، پیش‌نمایش محصول، سبد خرید، پرداخت، سفارش‌ها، دانلود امن و کتابخانه فایل‌های خریداری‌شده.</p><div class="farast-service-actions"><a class="farast-mini-btn primary" href="#farastStore"><i class="fa-solid fa-table-cells-large"></i> مشاهده ویترین</a></div></div></article>
        <article class="farast-service-card" data-searchable><div class="farast-service-icon"><i class="fa-solid fa-pen-ruler"></i></div><div><h3>تایپ و خدمات هوشمند</h3><p>برآورد قبل از پردازش، OCR، ویرایشگر حرفه‌ای، تایپ صوتی، قیمت‌گذاری، پرداخت و خروجی Word و PDF.</p><div class="farast-service-actions"><a class="farast-mini-btn primary" href="#start"><i class="fa-solid fa-arrow-up"></i> بارگذاری فایل</a><a class="farast-mini-btn" href="/pricing"><i class="fa-solid fa-calculator"></i> نرخ خدمات</a></div></div></article>
    </div>
</section>

<section class="farast-app-section" id="farastStore" style="margin-top:52px">
    <div class="farast-section-head"><div><h2>ویترین فایل‌های دیجیتال</h2><p>ساختار یکپارچه فروشگاه برای اتصال کاتالوگ، پیش‌نمایش، قیمت و خرید آماده شده است.</p></div><span class="farast-section-link"><i class="fa-solid fa-signal"></i> فروشگاه فراست</span></div>
    @if(isset($products) && $products->count())
        <div class="farast-product-grid">
            @foreach($products as $product)
            <article class="farast-product-card" data-searchable>
                <div class="farast-product-preview"><i class="fa-regular fa-file-lines"></i></div>
                <div class="farast-product-body"><h3>{{ $product->title }}</h3><p>{{ \Illuminate\Support\Str::limit($product->short_description ?? '',75) }}</p><div class="farast-product-meta"><span class="farast-product-price">{{ number_format($product->price) }} تومان</span><div class="farast-product-actions"><button type="button" class="add-cart" data-cart-add="{{ $product->id }}" data-title="{{ $product->title }}" data-price="{{ $product->price }}" aria-label="افزودن به سبد"><i class="fa-solid fa-bag-shopping"></i></button></div></div></div>
            </article>
            @endforeach
        </div>
    @else
        <div class="farast-store-empty"><div><i class="fa-solid fa-box-open"></i></div><strong>ویترین برای اتصال کاتالوگ واقعی آماده است.</strong><span>محصول، دسته‌بندی، پیش‌نمایش، قیمت، سفارش و دانلود امن در Store Engine به همین فضای یکپارچه متصل می‌شوند.</span></div>
    @endif
</section>

<section class="features farast-feature-strip">
    <article><span class="feature-icon"><i class="fa-solid fa-file-pen"></i></span><b>ویرایشگر حرفه‌ای</b><span>محیط A4، ویرایش، بررسی واژه‌های مشکوک و خروجی Word و PDF.</span></article>
    <article><span class="feature-icon"><i class="fa-solid fa-microphone-lines"></i></span><b>ورودی صوتی</b><span>مسیر مستقیم مرورگر و مسیر پشتیبان سروری برای مرورگرهای فاقد Speech Recognition.</span></article>
    <article><span class="feature-icon"><i class="fa-solid fa-bag-shopping"></i></span><b>خرید یکپارچه</b><span>سبد خرید از هر بخش قابل دسترسی است و انتخاب‌ها در فضای کار حفظ می‌شوند.</span></article>
    <article><span class="feature-icon"><i class="fa-solid fa-wifi"></i></span><b>تجربه اپلیکیشنی</b><span>قطع اینترنت پوسته برنامه را از بین نمی‌برد و وضعیت سرویس به‌صورت واقعی پایش می‌شود.</span></article>
</section>

<section class="home-steps"><div><b>۱</b><span>فایل یا محصول را انتخاب کنید</span></div><div><b>۲</b><span>برآورد و شرایط شروع را ببینید</span></div><div><b>۳</b><span>در فضای کار ادامه دهید</span></div><div><b>۴</b><span>خروجی یا فایل خریداری‌شده را دریافت کنید</span></div></section>

<div class="typing-quote-modal" id="typingQuoteModal" hidden>
    <div class="typing-quote-card" role="dialog" aria-modal="true" aria-labelledby="typingQuoteTitle">
        <button class="typing-quote-close" type="button" id="typingQuoteClose" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
        <div class="typing-quote-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <h2 id="typingQuoteTitle">برآورد اولیه فایل</h2>
        <p id="typingQuoteLead">فایل بررسی شد.</p>
        <div class="typing-quote-lines" id="typingQuoteLines"></div>
        <p class="typing-quote-note">این مبلغ برآورد سریع پیش از تایپ است و مبلغ نهایی پس از پردازش دقیق متن محاسبه می‌شود.</p>
        <div class="typing-quote-actions" id="typingQuoteActions"><button type="button" class="quote-continue" id="typingQuoteAccept">ادامه</button><button type="button" class="quote-decline" id="typingQuoteDecline">فعلاً ادامه نمی‌دهم</button></div>
        <div class="typing-decline-message" id="typingDeclineMessage" hidden></div>
    </div>
</div>
@endsection

@push('scripts')
<style>
.farast-home-hero{margin-top:36px}.farast-home-hero h1{letter-spacing:-.7px}.farast-home-hero .eyebrow{display:flex;align-items:center;gap:7px}.farast-dual-engine{margin-top:20px}.farast-store-empty{min-height:230px;padding:34px;border:1px dashed #cad7e8;border-radius:20px;background:linear-gradient(145deg,#fff,#f8fbff);display:grid;place-items:center;align-content:center;text-align:center;gap:9px;color:#7b8ba0}.farast-store-empty div{width:54px;height:54px;border-radius:16px;display:grid;place-items:center;background:#edf4ff;color:#1769ff;font-size:1.35rem}.farast-store-empty strong{color:#324866;font-size:.95rem}.farast-store-empty span{max-width:680px;font-size:.83rem;line-height:1.9}.farast-cart-row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px;border:1px solid #e5ebf3;border-radius:12px;margin-bottom:8px}.farast-cart-row strong,.farast-cart-row small{display:block}.farast-cart-row strong{font-size:.84rem;color:#2b405e}.farast-cart-row small{font-size:.74rem;color:#8391a4}.farast-cart-row button{width:30px;height:30px;border:0;border-radius:8px;background:#fff0f0;color:#c95555;cursor:pointer}.farast-feature-strip{margin-top:52px!important}
.typing-quote-modal{position:fixed;z-index:9000;inset:0;background:rgba(7,23,52,.58);backdrop-filter:blur(8px);display:grid;place-items:center;padding:20px}.typing-quote-modal[hidden]{display:none}.typing-quote-card{position:relative;width:min(520px,100%);background:#fff;border-radius:22px;padding:28px;box-shadow:0 28px 90px rgba(7,25,58,.25)}.typing-quote-close{position:absolute;left:16px;top:16px;width:34px;height:34px;border:1px solid #e2e8f1;border-radius:10px;background:#fff;color:#6c7c91;cursor:pointer}.typing-quote-icon{width:54px;height:54px;border-radius:16px;display:grid;place-items:center;background:linear-gradient(145deg,#edf4ff,#f5f1ff);color:#3e57d8;font-size:1.25rem}.typing-quote-card h2{margin:13px 0 4px;color:#182f52;font-size:1.25rem}.typing-quote-card>p{margin:0;color:#75859b;font-size:.84rem}.typing-quote-lines{margin:18px 0 12px;border:1px solid #e3e9f2;border-radius:15px;overflow:hidden}.typing-quote-line{min-height:45px;padding:10px 13px;display:flex;align-items:center;justify-content:space-between;gap:14px;border-bottom:1px solid #edf1f6;font-size:.84rem}.typing-quote-line:last-child{border-bottom:0}.typing-quote-line span{color:#708097}.typing-quote-line strong{color:#213958}.typing-quote-line.discount strong{color:#21845b}.typing-quote-line.total{background:#f7faff}.typing-quote-line.total strong{font-size:1rem;color:#1769ff}.typing-quote-line.deposit{background:#fff9ec}.typing-quote-line.deposit strong{color:#b87709}.quote-old{text-decoration:line-through;color:#9ca8b8!important;font-weight:500!important}.typing-quote-note{font-size:.75rem!important;line-height:1.8!important;color:#8996a7!important}.typing-quote-actions{display:flex;gap:9px;margin-top:18px}.typing-quote-actions button{flex:1;border-radius:11px;padding:10px 13px;font:inherit;font-size:.84rem;font-weight:700;cursor:pointer}.quote-continue{border:1px solid #1769ff;background:#1769ff;color:#fff}.quote-decline{border:1px solid #dce4ef;background:#fff;color:#60728a}.typing-decline-message{margin-top:18px;padding:16px;border:1px solid #e1e8f2;border-radius:14px;background:#f8faff;color:#52667f;font-size:.84rem;line-height:2}.home-upload-msg{font-size:.8rem;line-height:1.8}
</style>
<script>
(()=>{
const d=document.getElementById('homeDrop'),f=document.getElementById('homeFile'),c=document.getElementById('homeChoose'),m=document.getElementById('homeMsg'),token=document.querySelector('meta[name=csrf-token]')?.content||'',modal=document.getElementById('typingQuoteModal'),lines=document.getElementById('typingQuoteLines'),lead=document.getElementById('typingQuoteLead'),accept=document.getElementById('typingQuoteAccept'),decline=document.getElementById('typingQuoteDecline'),close=document.getElementById('typingQuoteClose'),declineMessage=document.getElementById('typingDeclineMessage');
if(!d)return;
const money=n=>new Intl.NumberFormat('fa-IR').format(Math.round(Number(n||0)/10))+' تومان';
const api=async(url,body={})=>{const r=await fetch(url,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify(body)});let j={};try{j=await r.json()}catch{}if(!r.ok){const e=new Error(j.message||'خطا در ارتباط با سرور');e.status=r.status;throw e}return j};
function openQuote(q){
  declineMessage.hidden=true;document.getElementById('typingQuoteActions').hidden=false;modal.hidden=false;
  lead.textContent=`حدود ${new Intl.NumberFormat('fa-IR').format(q.pages)} صفحه برای این فایل شناسایی شد.`;
  let html=`<div class="typing-quote-line"><span>برآورد اولیه</span><strong class="${q.discount_rials>0?'quote-old':''}">${money(q.estimate_rials)}</strong></div>`;
  if(q.discount_rials>0)html+=`<div class="typing-quote-line discount"><span>اعتبار هفتگی فراست</span><strong>− ${money(q.discount_rials)}</strong></div>`;
  html+=`<div class="typing-quote-line total"><span>برآورد پس از اعتبار</span><strong>${money(q.payable_estimate_rials)}</strong></div>`;
  if(q.deposit_rials>0)html+=`<div class="typing-quote-line deposit"><span>مبلغ لازم برای شروع این سفارش</span><strong>${money(q.deposit_rials)}</strong></div>`;
  lines.innerHTML=html;
  accept.textContent=q.deposit_rials>0?'پرداخت و ادامه':'تأیید و ادامه';
  accept.dataset.mode=q.mode;
}
async function estimate(){m.hidden=false;m.textContent='در حال برآورد سریع تعداد صفحات و هزینه…';try{const q=await api('/editor/preflight/estimate');m.textContent='برآورد اولیه آماده است.';if(q.mode==='free'){const go=await api('/editor/preflight/accept',{accept:true});location.href=go.editor_url||'/editor';return}openQuote(q)}catch(e){if(e.status===401){location.href='/login?continue='+encodeURIComponent('/?typing_preflight=1');return}m.textContent=e.message}}
async function send(file){m.hidden=false;m.textContent='در حال بارگذاری امن فایل…';const fd=new FormData();fd.append('source',file);try{const r=await fetch('/editor/upload',{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json'},body:fd});const j=await r.json();if(!r.ok)throw new Error(j.message||'آپلود ناموفق بود');if(j.requires_login){m.textContent='فایل موقتاً حفظ شد؛ برای برآورد و ادامه وارد حساب شوید.';location.href='/login?continue='+encodeURIComponent('/?typing_preflight=1');return}await estimate()}catch(e){m.textContent=navigator.onLine?e.message:'اتصال اینترنت در دسترس نیست؛ فایل ارسال نشده است.'}}
c.onclick=()=>f.click();f.onchange=()=>{if(f.files[0])send(f.files[0])};['dragenter','dragover'].forEach(x=>d.addEventListener(x,e=>{e.preventDefault();d.classList.add('dragover')}));['dragleave','drop'].forEach(x=>d.addEventListener(x,e=>{e.preventDefault();d.classList.remove('dragover')}));d.addEventListener('drop',e=>{if(e.dataTransfer.files[0])send(e.dataTransfer.files[0])});
accept.onclick=async()=>{accept.disabled=true;try{const j=await api('/editor/preflight/accept',{accept:true});location.href=j.action==='deposit'?j.checkout_url:(j.editor_url||'/editor')}catch(e){m.hidden=false;m.textContent=e.message;modal.hidden=true}finally{accept.disabled=false}};
decline.onclick=async()=>{decline.disabled=true;try{const j=await api('/editor/preflight/decline');document.getElementById('typingQuoteActions').hidden=true;lines.innerHTML='';lead.textContent='درخواست در همین مرحله متوقف شد.';declineMessage.textContent=j.message;declineMessage.hidden=false}catch(e){declineMessage.textContent='درخواست متوقف شد. هر زمان آماده بودید می‌توانید دوباره برگردید.';declineMessage.hidden=false}finally{decline.disabled=false}};
close.onclick=()=>{modal.hidden=true};modal.addEventListener('click',e=>{if(e.target===modal)modal.hidden=true});
if(new URLSearchParams(location.search).get('typing_preflight')==='1')estimate();
})();
</script>
@endpush
