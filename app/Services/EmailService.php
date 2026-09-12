<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendOtp(string $email, string $code): void
    {
        $subject = 'کد تأیید ورود به فراست';
        $body = "کد تأیید شما: {$code}\n\nاین کد تا ۳ دقیقه معتبر است. اگر این درخواست از طرف شما نبوده است، این پیام را نادیده بگیرید.";

        Mail::raw($body, function ($message) use ($email, $subject) {
            $message->to($email)->subject($subject);
        });

        Log::info('farast.auth.email_otp_sent', ['email_hash' => hash('sha256', mb_strtolower($email))]);
    }
}
