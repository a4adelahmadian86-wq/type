@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-headset"></i> پشتیبانی</span>
            <h1>مرکز پاسخگویی</h1>
            <p class="dashboard-sub">تیکت‌های پشتیبانی مرتبط با حساب شما. پاسخ‌ها پس از ثبت توسط تیم پشتیبانی نمایش داده می‌شوند.</p>
        </div>
    </header>

    @if(session('status'))
        <div class="finance-alert"><i class="fa-solid fa-circle-check"></i>{{ session('status') }}</div>
    @endif

    <div class="ws-grid two support-layout">
        <section class="panel">
            <div class="panel-heading"><div><h2>تیکت جدید</h2><p>موضوع و پیام را واضح بنویسید.</p></div></div>
            <form method="post" action="{{ route('support.create') }}" class="account-form support-form">
                @csrf
                <label>موضوع
                    <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="160" placeholder="مثلاً مشکل در خروجی PDF">
                </label>
                <label>پیام
                    <textarea name="body" required maxlength="5000" rows="6" placeholder="توضیح کامل مشکل یا درخواست...">{{ old('body') }}</textarea>
                </label>
                @error('subject')<div class="form-error">{{ $message }}</div>@enderror
                @error('body')<div class="form-error">{{ $message }}</div>@enderror
                <button class="btn primary" type="submit"><i class="fa-solid fa-paper-plane"></i> ارسال تیکت</button>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading"><div><h2>گفتگوهای شما</h2><p>{{ number_format($tickets->count()) }} تیکت</p></div></div>
            @forelse($tickets as $t)
                <article class="ticket-card">
                    <header class="ticket-head">
                        <div>
                            <strong>{{ $t->subject }}</strong>
                            <small>#{{ $t->id }} · {{ $t->updated_at?->diffForHumans() }}</small>
                        </div>
                        <span class="ticket-status status-{{ $t->status }}">{{ $t->status }}</span>
                    </header>
                    <div class="ticket-thread">
                        @foreach($t->messages as $m)
                            <div class="bubble {{ $m->user_id === auth()->id() ? 'mine' : 'staff' }}">
                                <p>{{ $m->body }}</p>
                                <time>{{ $m->created_at?->format('Y/m/d H:i') }}</time>
                            </div>
                        @endforeach
                    </div>
                    @if(!in_array($t->status, ['closed', 'resolved'], true))
                        <form method="post" action="{{ route('support.message', $t) }}" class="ticket-reply">
                            @csrf
                            <input type="text" name="body" placeholder="پاسخ شما..." required maxlength="5000">
                            <button type="submit" class="btn">ارسال</button>
                        </form>
                    @endif
                </article>
            @empty
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>هنوز تیکتی ثبت نکرده‌اید.</p>
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection
