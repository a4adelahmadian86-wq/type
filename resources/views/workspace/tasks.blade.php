@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-list-check"></i> میز کار</span>
            <h1>وظایف من</h1>
            <p class="dashboard-sub">تیکت‌های باز و پیش‌نویس‌های در جریان.</p>
        </div>
    </header>
    <div class="ws-grid two">
        <section class="panel">
            <div class="panel-heading"><h2>تیکت‌های باز</h2></div>
            @forelse($openTickets as $t)
                <article class="doc"><span>{{ $t->subject ?? ('تیکت #'.$t->id) }}</span><span>{{ $t->status }}</span></article>
            @empty
                <div class="empty-state"><p>تیکت بازی نیست.</p></div>
            @endforelse
        </section>
        <section class="panel">
            <div class="panel-heading"><h2>پیش‌نویس‌ها</h2></div>
            @forelse($draftDocs as $d)
                <article class="doc"><span>{{ $d->title ?: 'بدون عنوان' }}</span><a href="{{ route('editor') }}?doc={{ $d->id }}">ادامه</a></article>
            @empty
                <div class="empty-state"><p>پیش‌نویسی نیست.</p></div>
            @endforelse
        </section>
    </div>
</div>
@endsection
