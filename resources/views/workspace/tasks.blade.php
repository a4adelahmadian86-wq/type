@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-list-check"></i> میز کار</span>
            <h1>وظایف من</h1>
            <p class="dashboard-sub">پیش‌نویس‌ها و تیکت‌های باز مرتبط با حساب شما.</p>
        </div>
    </header>

    <div class="ws-grid two">
        <section class="panel">
            <div class="panel-heading"><div><h2>پیش‌نویس اسناد</h2></div></div>
            @forelse($draftDocs as $d)
                <article class="doc">
                    <span><i class="fa-solid fa-file-pen"></i><span>{{ $d->title }}</span></span>
                    <span>{{ $d->status }}</span>
                    <span>{{ number_format((int) $d->page_count) }} صفحه</span>
                    <a href="{{ route('editor', ['document' => $d->id]) }}">ادامه</a>
                </article>
            @empty
                <div class="empty-state"><p>پیش‌نویس بازی ندارید.</p></div>
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-heading"><div><h2>تیکت‌های باز</h2></div></div>
            @forelse($openTickets as $t)
                <article class="doc">
                    <span><i class="fa-solid fa-headset"></i><span>{{ $t->subject }}</span></span>
                    <span>{{ $t->status }}</span>
                    <a href="{{ route('support') }}">مشاهده</a>
                </article>
            @empty
                <div class="empty-state"><p>تیکت بازی وجود ندارد.</p></div>
            @endforelse
        </section>
    </div>
</div>
@endsection
