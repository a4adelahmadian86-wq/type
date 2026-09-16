@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-bell-exclamation"></i> میز کار</span>
            <h1>موارد نیازمند اقدام</h1>
        </div>
    </header>
    <section class="panel">
        @forelse($needsAttention as $item)
            <article class="doc">
                <span><i class="fa-solid {{ $item['icon'] }}"></i> <strong>{{ $item['title'] }}</strong></span>
                <span>{{ $item['body'] }}</span>
            </article>
        @empty
            <div class="empty-state"><p>مورد فوری‌ای برای اقدام نیست.</p></div>
        @endforelse
        @if(($unpaid ?? collect())->isNotEmpty())
            <div class="panel-heading"><h2>اسناد نیازمند تسویه</h2></div>
            @foreach($unpaid as $d)
                <article class="doc"><span>{{ $d->title }}</span><span>{{ number_format($d->price_rials) }} ریال</span></article>
            @endforeach
        @endif
    </section>
</div>
@endsection
