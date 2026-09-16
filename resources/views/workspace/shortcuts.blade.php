@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-bolt"></i> میز کار</span>
            <h1>میانبرها</h1>
            <p class="dashboard-sub">دسترسی سریع به بخش‌هایی که برای حساب شما مجاز است.</p>
        </div>
    </header>

    <div class="shortcut-grid">
        @foreach($items as $item)
            <a class="shortcut-card" href="{{ route($item['route']) }}">
                <i class="fa-solid {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
@endsection
