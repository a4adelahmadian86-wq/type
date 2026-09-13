<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مرکز عملیات FARAST</title>
    <style>
        :root{font-family:Tahoma,"Segoe UI",sans-serif;color:#16243b;background:#f5f7fb;--navy:#091735;--blue:#1769ff;--line:#e3e8f1;--muted:#65758d;--card:#fff}
        *{box-sizing:border-box}body{margin:0}.shell{display:grid;grid-template-columns:260px 1fr;min-height:100vh}.side{background:var(--navy);color:#fff;padding:26px 18px}.brand{font-size:24px;font-weight:800;margin-bottom:30px}.brand small{display:block;color:#9eb1d3;font-size:11px;margin-top:5px}.nav a{display:block;color:#c9d5ea;text-decoration:none;padding:11px 13px;border-radius:10px;margin:4px 0}.nav a.active,.nav a:hover{background:#142853;color:#fff}.main{padding:30px;max-width:1500px;width:100%;margin:auto}.top{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:25px}.eyebrow{color:var(--blue);font-size:12px;font-weight:700}.title{font-size:30px;margin:5px 0}.sub{color:var(--muted);margin:0}.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:25px}.card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px;box-shadow:0 4px 18px #0917350a}.metric-label{color:var(--muted);font-size:13px}.metric{font-size:25px;font-weight:800;margin-top:8px}.metric-note{font-size:11px;color:var(--muted);margin-top:5px}.section{margin-top:24px}.section h2{font-size:18px;margin:0 0 12px}.health{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.health-item{background:#fff;border:1px solid var(--line);border-radius:14px;padding:15px}.dot{width:9px;height:9px;border-radius:50%;display:inline-block;margin-left:7px;background:#20a66a}.dot.warning{background:#e7a900}.dot.error{background:#d93c3c}.health-label{font-weight:700;font-size:13px}.health-detail{font-size:11px;color:var(--muted);margin-top:8px;line-height:1.7}.tables{display:grid;grid-template-columns:1fr 1fr;gap:16px}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse;font-size:12px}.table th,.table td{padding:11px 8px;border-bottom:1px solid var(--line);text-align:right;white-space:nowrap}.table th{color:var(--muted);font-weight:600}.status{padding:4px 8px;border-radius:999px;background:#eef3fb}.back{color:var(--blue);text-decoration:none;font-size:13px}@media(max-width:1000px){.shell{grid-template-columns:1fr}.side{display:none}.grid,.health{grid-template-columns:repeat(2,minmax(0,1fr))}.tables{grid-template-columns:1fr}}@media(max-width:600px){.main{padding:18px}.grid,.health{grid-template-columns:1fr}.title{font-size:24px}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="side">
        <div class="brand">FARAST<small>Operations Console</small></div>
        <nav class="nav">
            <a href="{{ route('admin.index') }}">نمای کلی مدیریت</a>
            <a class="active" href="{{ route('admin.operations') }}">مرکز عملیات</a>
            <a href="{{ route('admin.finance') }}">مالی و کیف پول</a>
            <a href="{{ route('admin.emails') }}">ایمیل و پشتیبانی</a>
        </nav>
    </aside>
    <main class="main">
        <div class="top">
            <div><div class="eyebrow">MASTER CONTROL</div><h1 class="title">مرکز عملیات FARAST</h1><p class="sub">داشبورد زنده برای فروش، کاربران، پردازش، AI، پشتیبانی و سلامت زیرساخت.</p></div>
            <a class="back" href="{{ route('home') }}">بازگشت به FARAST ←</a>
        </div>

        <div class="grid">
            <div class="card"><div class="metric-label">فروش امروز</div><div class="metric">{{ number_format($stats['sales_today']) }}</div><div class="metric-note">ریال</div></div>
            <div class="card"><div class="metric-label">فروش این هفته</div><div class="metric">{{ number_format($stats['sales_week']) }}</div><div class="metric-note">ریال</div></div>
            <div class="card"><div class="metric-label">فروش این ماه</div><div class="metric">{{ number_format($stats['sales_month']) }}</div><div class="metric-note">ریال</div></div>
            <div class="card"><div class="metric-label">سفارش‌های پرداخت‌شده</div><div class="metric">{{ number_format($stats['paid_orders']) }}</div><div class="metric-note">از {{ number_format($stats['orders']) }} سفارش</div></div>
            <div class="card"><div class="metric-label">کاربران فعال</div><div class="metric">{{ number_format($stats['active_users']) }}</div><div class="metric-note">کاربر جدید امروز: {{ number_format($stats['new_users']) }}</div></div>
            <div class="card"><div class="metric-label">پردازش‌های تایپ</div><div class="metric">{{ number_format($stats['typing_jobs']) }}</div><div class="metric-note">اسناد ثبت‌شده</div></div>
            <div class="card"><div class="metric-label">درخواست‌های AI</div><div class="metric">{{ number_format($stats['ai_requests']) }}</div><div class="metric-note">امروز: {{ number_format($stats['ai_today']) }}</div></div>
            <div class="card"><div class="metric-label">صف پشتیبانی</div><div class="metric">{{ number_format($stats['support_backlog']) }}</div><div class="metric-note">نیازمند رسیدگی</div></div>
            <div class="card"><div class="metric-label">پرداخت‌های ناموفق</div><div class="metric">{{ number_format($stats['failed_payments']) }}</div><div class="metric-note">failed / canceled</div></div>
            <div class="card"><div class="metric-label">تعهد کیف پول</div><div class="metric">{{ number_format($stats['wallet_liability']) }}</div><div class="metric-note">ریال</div></div>
        </div>

        <section class="section"><h2>سلامت سرویس‌ها</h2><div class="health">
            @foreach($health as $item)
                <div class="health-item"><div><span class="dot {{ $item['status'] !== 'ok' ? $item['status'] : '' }}"></span><span class="health-label">{{ $item['label'] }}</span></div><div class="health-detail">{{ $item['detail'] }}</div></div>
            @endforeach
        </div></section>

        <section class="section"><div class="tables">
            <div class="card"><h2>آخرین سفارش‌ها</h2><div class="table-wrap"><table class="table"><thead><tr><th>شناسه</th><th>کاربر</th><th>مبلغ</th><th>وضعیت</th></tr></thead><tbody>
            @forelse($recentOrders as $order)<tr><td>#{{ $order->id }}</td><td>{{ $order->user?->name ?: '—' }}</td><td>{{ number_format($order->total_rials) }}</td><td><span class="status">{{ $order->status }}</span></td></tr>@empty<tr><td colspan="4">هنوز سفارشی ثبت نشده است.</td></tr>@endforelse
            </tbody></table></div></div>
            <div class="card"><h2>آخرین تلاش‌های پرداخت</h2><div class="table-wrap"><table class="table"><thead><tr><th>شناسه</th><th>سفارش</th><th>درگاه</th><th>وضعیت</th></tr></thead><tbody>
            @forelse($recentPayments as $payment)<tr><td>#{{ $payment->id }}</td><td>#{{ $payment->order_id }}</td><td>{{ $payment->gateway }}</td><td><span class="status">{{ $payment->status }}</span></td></tr>@empty<tr><td colspan="4">هنوز پرداختی ثبت نشده است.</td></tr>@endforelse
            </tbody></table></div></div>
        </div></section>
    </main>
</div>
</body>
</html>
