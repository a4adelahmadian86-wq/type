@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid {{ $page['icon'] }}"></i> {{ $page['eyebrow'] }}</span>
            <h1>{{ $page['title'] }}</h1>
        </div>
        <a class="btn" href="{{ route('dashboard') }}"><i class="fa-solid fa-arrow-right"></i> بازگشت به داشبورد</a>
    </header>

    <section class="panel module-empty">
        <div class="empty-state">
            <i class="fa-solid {{ $page['icon'] }}"></i>
            <p>{{ $page['body'] }}</p>
            <small>این یک صفحه واقعی با مسیر و کنترل دسترسی است؛ داده جعلی نمایش داده نمی‌شود.</small>
            <a href="{{ route('dashboard') }}">بازگشت به نمای کلی</a>
        </div>
    </section>
</div>
@endsection
