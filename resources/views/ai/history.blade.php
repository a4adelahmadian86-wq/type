@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-clock-rotate-left"></i> هوش مصنوعی</span>
            <h1>تاریخچه عملیات AI</h1>
            <p class="dashboard-sub">فقط عملیات ثبت‌شده برای حساب شما.</p>
        </div>
        <a class="btn" href="{{ route('ai.quota') }}"><i class="fa-solid fa-gauge-high"></i> سهمیه</a>
    </header>

    <section class="panel recent-documents">
        @forelse($items as $row)
            <article class="doc">
                <span><i class="fa-solid fa-wand-magic-sparkles"></i><span>{{ $row->operation ?: 'عملیات' }}</span></span>
                <span>{{ $row->provider ?: '—' }}</span>
                <span>{{ $row->status }}</span>
                <span>{{ $row->latency_ms ? $row->latency_ms.' ms' : '—' }}</span>
                <span>{{ $row->created_at?->format('Y/m/d H:i') }}</span>
            </article>
        @empty
            <div class="empty-state"><i class="fa-solid fa-robot"></i><p>هنوز عملیاتی ثبت نشده است.</p></div>
        @endforelse
        <div class="ws-pagination">{{ $items->links() }}</div>
    </section>
</div>
@endsection
