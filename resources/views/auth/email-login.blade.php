@extends('layouts.app')
@section('content')
<div class="auth-shell"><div class="auth-card">
 <div class="auth-brand"><div class="mark">F</div><h1>ورود با ایمیل</h1><p>ابتدا ایمیل ثبت‌شده را تأیید می‌کنیم؛ سپس شماره موبایل همان حساب نیز تأیید می‌شود.</p></div>
 <form id="emailForm"><div class="auth-field"><label>ایمیل</label><input name="email" type="email" autocomplete="email" required placeholder="you@example.com"></div><button class="auth-btn primary">ارسال کد تأیید</button></form>
 <div id="emailMessage" class="auth-message" hidden></div><div class="auth-links"><a href="/login">ورود با شماره موبایل</a></div>
</div></div>
@endsection
@push('scripts')<script>
(()=>{const f=document.getElementById('emailForm'),m=document.getElementById('emailMessage'),csrf=document.querySelector('meta[name=csrf-token]').content;f.addEventListener('submit',async e=>{e.preventDefault();const b=f.querySelector('button');b.disabled=true;try{const r=await fetch('/login/email',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify(Object.fromEntries(new FormData(f)))});const j=await r.json();if(!r.ok)throw new Error(j.message||'ارسال کد ناموفق بود');location.href='/login/email?step=otp'}catch(err){m.hidden=false;m.className='auth-message auth-error';m.textContent=err.message;b.disabled=false}})})();
</script>
@if(session('email_login.email'))<script>/* OTP form is rendered below after request. */</script>@endif
@endsection
