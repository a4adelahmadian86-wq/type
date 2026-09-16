@extends('layouts.app')

@section('content')
<div class="farast-admin" dir="rtl">
    <aside class="farast-admin-nav">
        <div class="farast-admin-brand">
            <span class="farast-admin-mark" aria-hidden="true"><i></i><i></i><i></i><i></i><b></b></span>
            <div><strong>فراست</strong><small>مرکز عملیات</small></div>
        </div>
        <div class="farast-admin-nav-label">کنسول مدیریت</div>
        <nav>
            <a href="{{ route('admin.index') }}"><i class="fa-solid fa-gauge-high"></i><span>نمای کلی</span></a>
            <a href="{{ route('admin.finance') }}"><i class="fa-solid fa-wallet"></i><span>مالی</span></a>
            <a class="is-active" href="{{ route('admin.emails') }}"><i class="fa-solid fa-envelope"></i><span>سیستم ایمیل</span></a>
            <a href="{{ route('admin.social') }}"><i class="fa-solid fa-share-nodes"></i><span>شبکه‌های اجتماعی</span></a>
        </nav>
        <div class="farast-admin-nav-bottom">
            <a href="{{ route('dashboard') }}"><i class="fa-solid fa-arrow-right"></i><span>بازگشت به داشبورد</span></a>
        </div>
        <div class="farast-admin-live"><span></span><div><b>سامانه عملیاتی</b><small>کنترل ایمیل فعال است</small></div></div>
    </aside>

    <main class="farast-admin-main">
        <header class="farast-admin-topbar">
            <div class="farast-admin-heading">
                <div class="farast-admin-kicker"><span></span> EMAIL SYSTEM</div>
                <h1>سیستم ایمیل حرفه‌ای</h1>
                <p>سرویس‌دهنده، انواع اعلان، تست ارسال و لاگ از یک پنل.</p>
            </div>
            <div class="farast-admin-top-actions">
                <span class="provider-state {{ $providerConfigured ? 'ok' : 'warn' }}">
                    {{ $providers[$mailer] ?? $mailer }} · {{ $providerConfigured ? 'آماده' : 'نیاز به تنظیم' }}
                </span>
            </div>
        </header>

        @if(session('status'))
            <div class="farast-admin-toast"><i class="fa-solid fa-circle-check"></i>{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="farast-admin-toast" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c">
                <i class="fa-solid fa-circle-exclamation"></i>{{ $errors->first() }}
            </div>
        @endif

        <section class="admin-panel-wide email-panel">
            <div class="admin-panel-head">
                <div>
                    <span class="panel-eyebrow">PROVIDER</span>
                    <h2>سرویس‌دهنده ایمیل</h2>
                    <p>Resend / Brevo / Mailtrap / SMTP — برای ارسال واقعی یکی را پیکربندی کنید. حالت Log فقط در لاگ سرور می‌نویسد.</p>
                </div>
            </div>

            <form method="post" action="{{ route('admin.emails.settings') }}" class="settings-modern email-settings-form">
                @csrf

                <label class="modern-field">
                    <span><i class="fa-solid fa-cloud"></i> سرویس‌دهنده</span>
                    <select name="mail_provider">
                        @foreach($providers as $key => $label)
                            <option value="{{ $key }}" @selected($mailer === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small>Resend برای پروداکشن رایگان · Brevo SMTP روزانه · Mailtrap فقط تست · Log فقط توسعه</small>
                </label>

                <label class="modern-field">
                    <span><i class="fa-solid fa-at"></i> آدرس فرستنده (From)</span>
                    <input type="email" name="mail_from_address" value="{{ $mailFrom }}" placeholder="noreply@yourdomain.com" required>
                    <small>باید دامنهٔ تأییدشده در سرویس‌دهنده باشد — example.com کار نمی‌کند.</small>
                </label>

                <label class="modern-field">
                    <span><i class="fa-solid fa-signature"></i> نام فرستنده</span>
                    <input name="mail_from_name" value="{{ $mailFromName }}" placeholder="FARAST">
                </label>

                <label class="modern-field">
                    <span><i class="fa-solid fa-key"></i> کلید Resend API</span>
                    <input type="password" name="resend_api_key" placeholder="{{ $hasResendKey ? 'تنظیم‌شده — برای تغییر وارد کنید' : 're_...' }}" autocomplete="new-password">
                    <small>از <a href="https://resend.com" target="_blank" rel="noopener">resend.com</a></small>
                </label>

                <label class="modern-field">
                    <span><i class="fa-solid fa-key"></i> کلید / رمز Brevo</span>
                    <input type="password" name="brevo_api_key" placeholder="{{ $hasBrevoKey ? 'تنظیم‌شده — برای تغییر وارد کنید' : 'کلید SMTP Brevo' }}" autocomplete="new-password">
                </label>

                <label class="modern-field">
                    <span><i class="fa-solid fa-server"></i> SMTP Host</span>
                    <input name="mail_host" value="{{ $mailHost }}" placeholder="smtp-relay.brevo.com">
                </label>

                <label class="modern-field">
                    <span><i class="fa-solid fa-network-wired"></i> SMTP Port</span>
                    <input type="number" name="mail_port" value="{{ $mailPort }}" min="1" max="65535">
                </label>

                <label class="modern-field">
                    <span><i class="fa-solid fa-user"></i> SMTP Username</span>
                    <input name="mail_username" value="{{ $mailUsername }}" autocomplete="off">
                </label>

                <label class="modern-field">
                    <span><i class="fa-solid fa-lock"></i> SMTP Password</span>
                    <input type="password" name="mail_password" placeholder="{{ $hasSmtpPassword ? 'تنظیم‌شده — برای تغییر وارد کنید' : 'رمز SMTP' }}" autocomplete="new-password">
                </label>

                <label class="modern-field">
                    <span><i class="fa-solid fa-shield"></i> رمزنگاری</span>
                    <select name="mail_encryption">
                        <option value="tls" @selected(($mailEncryption ?? 'tls') === 'tls')>TLS</option>
                        <option value="ssl" @selected(($mailEncryption ?? '') === 'ssl')>SSL</option>
                        <option value="null" @selected(($mailEncryption ?? '') === '' || ($mailEncryption ?? '') === 'null')>بدون</option>
                    </select>
                </label>

                <label class="modern-field email-toggle-field">
                    <span><i class="fa-solid fa-power-off"></i> سیستم ایمیل فعال</span>
                    <div class="check-line">
                        <input type="hidden" name="email_enabled" value="0">
                        <input type="checkbox" name="email_enabled" value="1" @checked($flags['email_enabled'] ?? true)>
                        <span>ارسال همهٔ انواع ایمیل</span>
                    </div>
                </label>

                <label class="modern-field email-toggle-field">
                    <span><i class="fa-solid fa-bolt"></i> ارسال هم‌زمان (Sync)</span>
                    <div class="check-line">
                        <input type="hidden" name="email_sync" value="0">
                        <input type="checkbox" name="email_sync" value="1" @checked(filter_var(\App\Models\SiteSetting::read('email_sync', true), FILTER_VALIDATE_BOOLEAN))>
                        <span>بدون صف — توصیه می‌شود مگر worker صف فعال باشد</span>
                    </div>
                </label>

                <div class="settings-submit">
                    <button type="submit" class="admin-primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره تنظیمات ایمیل</button>
                </div>
            </form>
        </section>

        <section class="admin-panel-wide email-panel">
            <div class="admin-panel-head">
                <div>
                    <span class="panel-eyebrow">TYPES</span>
                    <h2>انواع ایمیل</h2>
                    <p>هر نوع را جداگانه روشن/خاموش کنید.</p>
                </div>
            </div>
            <form method="post" action="{{ route('admin.emails.settings') }}" class="capability-modern email-type-flags">
                @csrf
                <input type="hidden" name="mail_provider" value="{{ $mailer }}">
                <input type="hidden" name="mail_from_address" value="{{ $mailFrom }}">
                <input type="hidden" name="mail_from_name" value="{{ $mailFromName }}">
                @foreach($types as $key => $label)
                    <label class="capability-item">
                        <span><i class="fa-solid fa-envelope"></i>{{ $label }}</span>
                        <input type="hidden" name="email_{{ $key }}_enabled" value="0">
                        <input type="checkbox" name="email_{{ $key }}_enabled" value="1" @checked($flags['email_'.$key.'_enabled'] ?? true)>
                        <b></b>
                    </label>
                @endforeach
                <div class="settings-submit" style="grid-column:1/-1">
                    <button type="submit" class="admin-primary"><i class="fa-solid fa-check"></i> ذخیره انواع ایمیل</button>
                </div>
            </form>
        </section>

        <section class="admin-panel-wide email-panel">
            <div class="admin-panel-head">
                <div>
                    <span class="panel-eyebrow">TEST</span>
                    <h2>ارسال آزمایشی</h2>
                    <p>پس از ذخیرهٔ سرویس‌دهنده و From معتبر، اینجا تست کنید.</p>
                </div>
            </div>
            <form method="post" action="{{ route('admin.emails.test') }}" class="settings-modern" style="grid-template-columns:1fr auto;align-items:end">
                @csrf
                <label class="modern-field">
                    <span><i class="fa-solid fa-paper-plane"></i> ایمیل مقصد</span>
                    <input type="email" name="test_email" required value="{{ old('test_email') }}" placeholder="you@yourdomain.com">
                </label>
                <div class="settings-submit" style="margin:0">
                    <button type="submit" class="admin-primary"><i class="fa-solid fa-flask"></i> ارسال تست</button>
                </div>
            </form>
        </section>

        <section class="admin-panel-wide email-panel">
            <div class="admin-panel-head">
                <div>
                    <span class="panel-eyebrow">SUPPORT</span>
                    <h2>پاسخ سریع تیکت</h2>
                    <p>پاسخ برای کاربر ایمیل می‌شود.</p>
                </div>
                <span class="live-pill">{{ $tickets->count() }} تیکت</span>
            </div>
            <div class="email-ticket-list">
                @forelse($tickets as $ticket)
                    <article class="email-ticket-card">
                        <div class="email-ticket-card__head">
                            <div>
                                <b>#{{ $ticket->id }} — {{ $ticket->subject }}</b>
                                <small>{{ $ticket->user?->name }} · {{ $ticket->user?->email ?: 'بدون ایمیل' }} · {{ $ticket->status }}</small>
                            </div>
                            <span>{{ $ticket->created_at?->format('Y/m/d H:i') }}</span>
                        </div>
                        <form method="post" action="{{ route('admin.tickets.reply', $ticket) }}" class="email-ticket-reply">
                            @csrf
                            <textarea name="body" rows="3" required maxlength="5000" placeholder="متن پاسخ مدیر..."></textarea>
                            <div class="email-ticket-reply__actions">
                                <select name="status">
                                    <option value="answered">پاسخ‌داده‌شده</option>
                                    <option value="open">باز</option>
                                    <option value="closed">بسته</option>
                                </select>
                                <button type="submit" class="admin-primary"><i class="fa-solid fa-reply"></i> ارسال پاسخ + ایمیل</button>
                            </div>
                        </form>
                    </article>
                @empty
                    <div class="email-empty">تیکتی ثبت نشده است.</div>
                @endforelse
            </div>
        </section>

        <section class="admin-panel-wide email-panel">
            <div class="admin-panel-head">
                <div>
                    <span class="panel-eyebrow">LOGS</span>
                    <h2>لاگ ایمیل‌ها</h2>
                    <p>آخرین ارسال‌ها، وضعیت و خطاها.</p>
                </div>
                <span class="live-pill">{{ $logs->count() }} مورد</span>
            </div>
            <div class="email-log-list">
                @forelse($logs as $log)
                    <article class="email-log-row">
                        <div class="email-log-row__main">
                            <span class="email-log-icon"><i class="fa-solid fa-envelope"></i></span>
                            <div>
                                <b>{{ $log->subject }}</b>
                                <small>{{ $log->to_email }} · {{ $types[$log->type] ?? $log->type }} · {{ $log->meta['provider'] ?? '-' }}</small>
                            </div>
                        </div>
                        <div class="email-log-row__meta">
                            <span class="email-log-status status-{{ $log->status }}">{{ $log->status }}</span>
                            <span>{{ $log->created_at?->format('Y/m/d H:i') }}</span>
                        </div>
                        @if($log->error)
                            <div class="email-log-error">{{ \Illuminate\Support\Str::limit($log->error, 220) }}</div>
                        @endif
                    </article>
                @empty
                    <div class="email-empty">هنوز لاگی ثبت نشده است.</div>
                @endforelse
            </div>
        </section>
    </main>
</div>
@endsection
