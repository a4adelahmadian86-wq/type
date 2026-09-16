<?php

namespace App\Http\Controllers;

use App\Models\TypingDocument;
use Illuminate\Http\Request;

class DocumentsWorkspaceController extends Controller
{
    public function mine(Request $request)
    {
        $docs = TypingDocument::query()
            ->where('user_id', $request->user()->id)
            ->where('status', '!=', 'deleted')
            ->latest()
            ->paginate(20);

        return view('documents.mine', [
            'documents' => $docs,
            'heading' => 'اسناد من',
            'subtitle' => 'فقط اسنادی که متعلق به حساب شما هستند نمایش داده می‌شوند.',
        ]);
    }

    public function recent(Request $request)
    {
        $docs = TypingDocument::query()
            ->where('user_id', $request->user()->id)
            ->where('status', '!=', 'deleted')
            ->latest('updated_at')
            ->limit(30)
            ->get();

        return view('documents.list', [
            'documents' => $docs,
            'heading' => 'اسناد اخیر',
            'subtitle' => 'آخرین اسناد به‌روزشده حساب شما.',
            'empty' => 'هنوز سند به‌روزشده‌ای ندارید.',
        ]);
    }

    public function drafts(Request $request)
    {
        $docs = TypingDocument::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['draft', 'pending'])
            ->latest()
            ->get();

        return view('documents.list', [
            'documents' => $docs,
            'heading' => 'پیش‌نویس‌ها',
            'subtitle' => 'اسنادی که هنوز نهایی یا تسویه نشده‌اند.',
            'empty' => 'پیش‌نویسی وجود ندارد.',
        ]);
    }

    public function deleted(Request $request)
    {
        $docs = TypingDocument::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'deleted')
            ->latest()
            ->get();

        return view('documents.list', [
            'documents' => $docs,
            'heading' => 'موارد حذف‌شده',
            'subtitle' => 'اسنادی که وضعیت حذف دارند. بازیابی خودکار در نسخه فعلی وجود ندارد.',
            'empty' => 'مورد حذف‌شده‌ای ثبت نشده است.',
        ]);
    }

    public function all(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $docs = TypingDocument::query()
            ->with('user:id,name,mobile')
            ->latest()
            ->paginate(30);

        return view('documents.all', compact('docs'));
    }
}
