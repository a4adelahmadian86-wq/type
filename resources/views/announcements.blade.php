@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-bullhorn"></i> اطلاع‌رسانی</span>
            <h1>اعلانات و اطلاع‌رسانی</h1>
            <p class="dashboard-sub">آخرین خبرها، تغییرات سرویس و اطلاعیه‌های منتشرشده توسط سامانه.</p>
        </div>
        @auth
            <a class="btn" href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high"></i> داشبورد</a>
        @endauth
    </header>

    <section class="panel">
        @forelse($announcements as $announcement)
            <article class="announcement-row type-{{ $announcement->type }}">
                <div class="announcement-row__icon">
                    <i class="fa-solid {{ $announcement->type === 'danger' ? 'fa-triangle-exclamation' : ($announcement->type === 'warning' ? 'fa-circle-exclamation' : ($announcement->type === 'success' ? 'fa-circle-check' : 'fa-circle-info')) }}"></i>
                </div>
                <div class="announcement-row__body">
                    <div class="announcement-row__meta">
                        <span>{{ $announcement->created_at?->format('Y/m/d') }}</span>
                        <span class="announcement-badge badge-{{ $announcement->type }}">
                            {{ $announcement->type === 'danger' ? 'مهم' : ($announcement->type === 'warning' ? 'هشدار' : ($announcement->type === 'success' ? 'موفقیت' : 'اطلاع‌رسانی')) }}
                        </span>
                    </div>
                    <h2>{{ $announcement->title }}</h2>
                    <p>{{ $announcement->body }}</p>
                </div>
            </article>
        @empty
            <div class="empty-state">
                <i class="fa-regular fa-bell-slash"></i>
                <p>اعلان فعالی وجود ندارد.</p>
                <small>در صورت انتشار اطلاعیه جدید، اینجا نمایش داده می‌شود.</small>
            </div>
        @endforelse

        @if(method_exists($announcements, 'links'))
            <div class="ws-pagination">{{ $announcements->links() }}</div>
        @endif
    </section>
</div>
@endsection
