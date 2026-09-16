<?php

namespace App\Http\Controllers;

use App\Models\TypingDocument;
use App\Services\CapabilityService;
use Illuminate\Http\Request;

class DocumentsWorkspaceController extends Controller
{
    public function mine(Request $request, CapabilityService $capabilities)
    {
        return $this->list($request, $capabilities, ['status' => null], 'اسناد من', 'همه اسناد متعلق به حساب شما (به‌جز حذف‌شده).');
    }

    public function recent(Request $request, CapabilityService $capabilities)
    {
        $docs = TypingDocument::query()
            ->where('user_id', $request->user()->id)
            ->where('status', '!=', 'deleted')
            ->latest('updated_at')
            ->paginate(20);

        return view('documents.mine', [
            'documents' => $docs,
            'title' => 'اسناد اخیر',
            'subtitle' => 'به‌ترتیب آخرین ویرایش',
            'capabilities' => $capabilities->forUser($request->user()),
        ]);
    }

    public function drafts(Request $request, CapabilityService $capabilities)
    {
        return $this->list($request, $capabilities, ['status' => 'draft'], 'پیش‌نویس‌ها', 'اسنادی که هنوز نهایی نشده‌اند.');
    }

    public function deleted(Request $request, CapabilityService $capabilities)
    {
        return $this->list($request, $capabilities, ['status' => 'deleted'], 'حذف‌شده‌ها', 'اسنادی که به وضعیت حذف‌شده منتقل شده‌اند.');
    }

    public function all(Request $request, CapabilityService $capabilities)
    {
        $docs = TypingDocument::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(30);

        return view('documents.all', [
            'documents' => $docs,
            'capabilities' => $capabilities->forUser($request->user()),
        ]);
    }

    protected function list(Request $request, CapabilityService $capabilities, array $filters, string $title, string $subtitle)
    {
        $q = TypingDocument::query()->where('user_id', $request->user()->id);

        if (($filters['status'] ?? null) === null) {
            $q->where('status', '!=', 'deleted');
        } elseif (isset($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        $docs = $q->latest()->paginate(20);

        return view('documents.mine', [
            'documents' => $docs,
            'title' => $title,
            'subtitle' => $subtitle,
            'capabilities' => $capabilities->forUser($request->user()),
        ]);
    }
}
