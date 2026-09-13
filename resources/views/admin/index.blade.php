@extends('layouts.app')
@section('content')
<div class="master-admin" dir="rtl">
  <aside class="admin-sidebar">
    <div class="admin-brand"><span class="admin-logo"><i class="fa-solid fa-layer-group"></i></span><div><b>فراست</b><small>مدیریت مرکزی</small></div></div>
    <nav>
      <a class="active" href="#overview"><i class="fa-solid fa-gauge-high"></i><span>نمای کلی</span></a>
      <a href="#users"><i class="fa-solid fa-users"></i><span>کاربران و دسترسی‌ها</span></a>
      <a href="#ai"><i class="fa-solid fa-wand-magic-sparkles"></i><span>هوش مصنوعی</span></a>
      <a href="#pricing"><i class="fa-solid fa-tags"></i><span>قیمت‌گذاری</span></a>
      <a href="#security"><i class="fa-solid fa-shield-halved"></i><span>امنیت و سرویس‌ها</span></a>
    </nav>
    <div class="admin-side-footer"><span class="live-dot"></span> سامانه عملیاتی</div>
  </aside>
  <main class="admin-main">
    <header class="admin-header"><div><span class="admin-kicker">MASTER CONTROL</span><h1>داشبورد مدیر اصلی</h1><p>تمام محدودیت‌ها، قابلیت‌ها، هوش مصنوعی و کاربران از اینجا کنترل می‌شوند.</p></div><div class="admin-profile"><div class="profile-avatar">م</div><div><b>مدیر اصلی</b><small>{{ auth()->user()->mobile }}</small></div><form method="post" action="{{ route('logout') }}">@csrf<button title="خروج"><i class="fa-solid fa-arrow-right-from-bracket"></i></button></form></div></header>
    @if(session('status'))<div class="admin-toast"><i class="fa-solid fa-circle-check"></i>{{ session('status') }}</div>@endif
    <section id="overview" class="admin-stats">
      <article><span class="stat-icon blue"><i class="fa-solid fa-users"></i></span><div><small>کاربران</small><strong>{{ number_format($stats['users']) }}</strong><em>ثبت‌نام شده</em></div></article>
      <article><span class="stat-icon purple"><i class="fa-solid fa-file-lines"></i></span><div><small>اسناد</small><strong>{{ number_format($stats['documents']) }}</strong><em>تولید شده</em></div></article>
      <article><span class="stat-icon green"><i class="fa-solid fa-wand-magic-sparkles"></i></span><div><small>تعامل AI</small><strong>{{ number_format($stats['ai']) }}</strong><em>{{ number_format($stats['ai_today']) }} امروز</em></div></article>
      <article><span class="stat-icon orange"><i class="fa-solid fa-infinity"></i></span><div><small>مدیر اصلی</small><strong>نامحدود</strong><em>بدون سقف مصرف</em></div></article>
    </section>
    <section id="ai" class="admin-section admin-hero-panel">
      <div class="section-heading"><div><span class="section-icon"><i class="fa-solid fa-robot"></i></span><div><h2>مرکز هوش مصنوعی</h2><p>کلیدها رمزنگاری‌شده ذخیره می‌شوند و مقدار واقعی آن‌ها در صفحه نمایش داده نمی‌شود.</p></div></div><span class="configured-pill"><i class="fa-solid fa-circle"></i> Gemini {{ $secretStatus['gemini'] ? 'متصل' : 'نیازمند کلید' }}</span></div>
      <form method="post" action="{{ route('admin.settings.update') }}" class="settings-grid">@csrf
        <div class="field secret-field"><label><i class="fa-solid fa-key"></i> کلید Gemini</label><input type="password" name="gemini_api_key" placeholder="{{ $secretStatus['gemini'] ? 'کلید تنظیم شده است؛ برای تغییر وارد کنید' : 'کلید Gemini API' }}" autocomplete="new-password"><small>خالی بگذارید تا مقدار فعلی حفظ شود.</small></div>
        <div class="field secret-field"><label><i class="fa-solid fa-cloud"></i> کلید Files</label><input type="password" name="files_api_key" placeholder="{{ $secretStatus['files'] ? 'کلید تنظیم شده است؛ برای تغییر وارد کنید' : 'کلید سرویس Files' }}" autocomplete="new-password"><small>برای سرویس فایل و پردازش ورودی‌های بزرگ.</small></div>
        <div class="field"><label><i class="fa-solid fa-microchip"></i> مدل Gemini</label><input name="gemini_model" value="{{ $settings['gemini_model'] }}"></div>
        <div class="field"><label><i class="fa-solid fa-file-arrow-up"></i> حداکثر حجم فایل</label><input type="number" name="max_file_mb" value="{{ $settings['max_file_mb'] }}" min="1"><small>مگابایت</small></div>
        <div class="field"><label><i class="fa-solid fa-bolt"></i> درخواست AI روزانه</label><input type="number" name="daily_ai_requests" value="{{ $settings['daily_ai_requests'] }}" min="0"><small>برای کاربران عادی</small></div>
        <div class="field"><label><i class="fa-solid fa-gift"></i> صفحه رایگان هفتگی</label><input type="number" name="weekly_free_pages" value="{{ $settings['weekly_free_pages'] }}" min="0"><small>برای هر کاربر عادی</small></div>
        <div class="settings-actions"><button class="admin-primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره تنظیمات هوش مصنوعی</button></div>
      </form>
    </section>
    <section class="admin-section">
      <div class="section-heading"><div><span class="section-icon green-bg"><i class="fa-solid fa-sliders"></i></span><div><h2>قابلیت‌های پیش‌فرض کاربران</h2><p>این تنظیمات روی همه کاربران عادی اعمال می‌شود و برای هر کاربر قابل override است.</p></div></div></div>
      <form method="post" action="{{ route('admin.settings.update') }}" class="capability-grid">@csrf
        @foreach(['can_type'=>'ویرایش و تایپ','can_ai'=>'پردازش Gemini','can_voice'=>'تایپ صوتی','can_export_docx'=>'خروجی Word','can_export_pdf'=>'خروجی PDF','can_feedback'=>'ثبت بازخورد','can_support'=>'پشتیبانی'] as $key=>$label)
          <label class="toggle-card"><span><i class="fa-solid {{ $key==='can_ai'?'fa-wand-magic-sparkles':($key==='can_voice'?'fa-microphone':($key==='can_support'?'fa-headset':'fa-check')) }}"></i>{{ $label }}</span><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" {{ filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}><b></b></label>
        @endforeach
        <div class="settings-actions"><button class="admin-secondary"><i class="fa-solid fa-check-double"></i> ذخیره دسترسی‌های پیش‌فرض</button></div>
      </form>
    </section>
    <section id="users" class="admin-section">
      <div class="section-heading"><div><span class="section-icon"><i class="fa-solid fa-users-gear"></i></span><div><h2>کاربران و دسترسی اختصاصی</h2><p>امکانات و سقف مصرف هر کاربر را جداگانه مدیریت کنید.</p></div></div><span class="count-pill">{{ $users->count() }} کاربر اخیر</span></div>
      <div class="user-table">
        @forelse($users as $u)
          @php $cap=app(\App\Services\CapabilityService::class)->forUser($u); @endphp
          <article class="user-row">
            <div class="user-main"><span class="user-avatar">{{ mb_substr($u->name ?: 'ک',0,1) }}</span><div><b>{{ $u->name ?: 'کاربر بدون نام' }}</b><small>{{ $u->mobile }} @if($u->email) · {{ $u->email }} @endif</small></div></div>
            <div class="user-badges"><span class="badge {{ $u->is_blocked ? 'danger' : 'ok' }}">{{ $u->is_blocked ? 'مسدود' : 'فعال' }}</span><span class="badge">{{ $u->role }}</span></div>
            @if(!$u->isAdmin())
            <form method="post" action="{{ route('admin.user.capabilities', $u) }}" class="user-cap-form">@csrf
              <div class="mini-toggles">@foreach(['can_type'=>'تایپ','can_ai'=>'AI','can_voice'=>'صوت','can_export_docx'=>'Word','can_export_pdf'=>'PDF','can_support'=>'پشتیبانی'] as $key=>$label)<label title="{{ $label }}"><input type="checkbox" name="{{ $key }}" value="1" {{ $cap[$key] ? 'checked' : '' }}><span>{{ $label }}</span></label>@endforeach</div>
              <div class="mini-limits"><label>رایگان/هفته<input type="number" name="weekly_free_pages" value="{{ $cap['weekly_free_pages'] }}" min="0"></label><label>MB<input type="number" name="max_file_mb" value="{{ $cap['max_file_mb'] }}" min="1"></label><label>AI/روز<input type="number" name="daily_ai_requests" value="{{ $cap['daily_ai_requests'] }}" min="0"></label></div>
              <div class="row-actions"><button class="save-mini"><i class="fa-solid fa-floppy-disk"></i> ذخیره</button><button type="submit" formaction="{{ route('admin.user.toggle', $u) }}" formmethod="post" class="block-mini">{{ $u->is_blocked ? 'رفع مسدودی' : 'مسدود کردن' }}</button></div>
            </form>
            @else <div class="master-unlimited"><i class="fa-solid fa-infinity"></i> مدیر اصلی · نامحدود</div>@endif
          </article>
        @empty<div class="empty-admin">هنوز کاربری ثبت نشده است.</div>@endforelse
      </div>
    </section>
    <section id="pricing" class="admin-section">
      <div class="section-heading"><div><span class="section-icon orange-bg"><i class="fa-solid fa-tags"></i></span><div><h2>قیمت‌گذاری</h2><p>قواعد فعلی قیمت‌گذاری از همین پنل قابل تغییر است.</p></div></div></div>
      <div class="pricing-admin-grid">@foreach($rules as $rule)<form method="post" action="{{ route('admin.pricing.update') }}" class="price-rule">@csrf<input type="hidden" name="key" value="{{ $rule->key }}"><label>{{ $rule->label }}<input name="label" value="{{ $rule->label }}"></label><label>مقدار<input type="number" name="value" value="{{ $rule->value }}" min="0"></label><button><i class="fa-solid fa-floppy-disk"></i></button></form>@endforeach</div>
    </section>
    <section id="security" class="admin-section security-panel"><div><span class="section-icon red-bg"><i class="fa-solid fa-shield-halved"></i></span><div><h2>وضعیت سرویس‌های حساس</h2><p>کلیدهای واقعی هرگز در این صفحه یا لاگ برنامه نمایش داده نمی‌شوند.</p></div></div><div class="service-status"><span><i class="fa-solid fa-brain"></i> Gemini</span><b class="{{ $secretStatus['gemini']?'online':'offline' }}">{{ $secretStatus['gemini']?'متصل':'تنظیم نشده' }}</b><span><i class="fa-solid fa-hard-drive"></i> Files</span><b class="{{ $secretStatus['files']?'online':'offline' }}">{{ $secretStatus['files']?'تنظیم شده':'تنظیم نشده' }}</b></div></section>
  </main>
</div>
@endsection
