@extends('layouts.app')
@section('content')
<div class="auth-shell">
  <div class="auth-card">
    <div class="auth-brand"><div class="mark">F</div><h1>ورود به فراست</h1><p>شماره موبایل یا ایمیل؛ ساده، مرحله‌به‌مرحله و امن.</p></div>
    @if(session('warning'))<div class="auth-message auth-error">{{ session('warning') }}</div>@endif
    @if($continueUrl)<div class="upload-continue">پس از ورود، به همان مرحله‌ای که بودید برمی‌گردید.</div>@endif
    <form id="phoneForm">
      @csrf
      <div class="auth-field"><label>شماره موبایل</label><input name="mobile" inputmode="tel" autocomplete="tel" placeholder="۰۹۱۲۱۲۳۴۵۶۷" required></div>
      <button class="auth-btn primary" type="submit">ادامه</button>
    </form>
    <div id="loginMessage" class="auth-message" hidden></div>
    <div class="auth-links"><a href="/forgot-password">فراموشی رمز عبور</a><a href="/login/email">ورود با ایمیل</a></div>
    <div class="auth-divider">ایمیل فقط برای باشگاه مشتریان، اطلاع‌رسانی و ورود جایگزین استفاده می‌شود.</div>
    <div class="auth-links"><a href="/">بازگشت به صفحه اصلی</a></div>
  </div>
</div>
@endsection
@push('scripts')
<script>
(()=>{
 const form=document.getElementById('phoneForm'), msg=document.getElementById('loginMessage');
 const csrf=document.querySelector('meta[name=csrf-token]').content;
 form.addEventListener('submit',async e=>{
   e.preventDefault(); const btn=form.querySelector('button'); btn.disabled=true; msg.hidden=false; msg.className='auth-message'; msg.textContent='در حال بررسی شماره…';
   try{
     const r=await fetch('/login/phone',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify(Object.fromEntries(new FormData(form)))});
     const j=await r.json(); if(!r.ok) throw new Error(j.message||'اطلاعات واردشده صحیح نیست.');
     location.href=j.next;
   }catch(err){msg.className='auth-message auth-error';msg.textContent=err.message;btn.disabled=false;}
 });
})();
</script>
@endpush
