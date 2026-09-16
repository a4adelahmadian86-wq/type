<?php

namespace App\Services;

use App\Models\SiteSetting;

/**
 * تنظیم سرویس ایمیل از پنل ادمین / .env
 *
 * اولویت فراست: ارسال از خود سرور (sendmail / SMTP محلی) بدون وابستگی پولی به سرویس بیرونی.
 */
class MailConfigService
{
    public const PROVIDERS = [
        'sendmail' => 'سرور خود سایت (Sendmail/Postfix) — رایگان و بدون سرویس بیرونی',
        'local' => 'SMTP محلی 127.0.0.1 — رایگان روی همین سرور',
        'log' => 'فقط لاگ (توسعه — ایمیل واقعی ارسال نمی‌شود)',
        'smtp' => 'SMTP دلخواه (سرور خودتان یا هر میزبان)',
        'mailtrap' => 'Mailtrap (تست رایگان — بیرونی)',
        'brevo' => 'Brevo (سقف رایگان — بیرونی، اختیاری)',
        'resend' => 'Resend (سقف رایگان — بیرونی، اختیاری)',
    ];

    public function apply(): void
    {
        $provider = (string) SiteSetting::read('mail_provider', env('MAIL_MAILER', 'sendmail'));

        $fromAddress = SiteSetting::read('mail_from_address', env('MAIL_FROM_ADDRESS', 'noreply@localhost'));
        $fromName = SiteSetting::read('mail_from_name', env('MAIL_FROM_NAME', 'FARAST'));

        config([
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $fromName,
        ]);

        match ($provider) {
            'sendmail' => config(['mail.default' => 'sendmail']),
            'local' => $this->applySmtpPreset(
                host: SiteSetting::read('mail_host', '127.0.0.1'),
                port: (int) SiteSetting::read('mail_port', 25),
                username: null,
                password: null,
                encryption: null,
            ),
            'mailtrap' => $this->applySmtpPreset(
                host: SiteSetting::read('mail_host', 'sandbox.smtp.mailtrap.io'),
                port: (int) SiteSetting::read('mail_port', 2525),
                username: SiteSetting::read('mail_username', env('MAIL_USERNAME')),
                password: SiteSetting::read('mail_password', env('MAIL_PASSWORD')) ?: null,
                encryption: SiteSetting::read('mail_encryption', 'tls'),
            ),
            'brevo' => $this->applySmtpPreset(
                host: SiteSetting::read('mail_host', 'smtp-relay.brevo.com'),
                port: (int) SiteSetting::read('mail_port', 587),
                username: SiteSetting::read('mail_username', env('MAIL_USERNAME')),
                password: SiteSetting::read('mail_password', env('MAIL_PASSWORD'))
                    ?: SiteSetting::read('brevo_api_key', env('BREVO_API_KEY'))
                    ?: null,
                encryption: SiteSetting::read('mail_encryption', 'tls'),
            ),
            'resend' => $this->applyResend(
                SiteSetting::read('resend_api_key', env('RESEND_API_KEY'))
            ),
            'smtp' => $this->applySmtpPreset(
                host: SiteSetting::read('mail_host', env('MAIL_HOST', '127.0.0.1')),
                port: (int) SiteSetting::read('mail_port', env('MAIL_PORT', 25)),
                username: SiteSetting::read('mail_username', env('MAIL_USERNAME')),
                password: SiteSetting::read('mail_password', env('MAIL_PASSWORD')) ?: null,
                encryption: SiteSetting::read('mail_encryption', env('MAIL_ENCRYPTION')) ?: null,
            ),
            default => config(['mail.default' => 'log']),
        };
    }

    public function currentProvider(): string
    {
        return (string) SiteSetting::read('mail_provider', env('MAIL_MAILER', 'sendmail'));
    }

    public function providerConfigured(string $provider): bool
    {
        return match ($provider) {
            'log' => true,
            'sendmail', 'local' => true,
            'resend' => filled(SiteSetting::read('resend_api_key', env('RESEND_API_KEY'))),
            'mailtrap', 'brevo', 'smtp' => filled(SiteSetting::read('mail_username', env('MAIL_USERNAME')))
                || filled(SiteSetting::read('mail_password', env('MAIL_PASSWORD')))
                || filled(SiteSetting::read('brevo_api_key', env('BREVO_API_KEY')))
                || filled(SiteSetting::read('mail_host', env('MAIL_HOST'))),
            default => false,
        };
    }

    public function isSelfHosted(string $provider): bool
    {
        return in_array($provider, ['sendmail', 'local', 'smtp'], true);
    }

    protected function applySmtpPreset(
        string $host,
        int $port,
        ?string $username,
        ?string $password,
        ?string $encryption,
    ): void {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.encryption' => $encryption ?: null,
        ]);
    }

    protected function applyResend(?string $apiKey): void
    {
        if (filled($apiKey)) {
            config([
                'services.resend.key' => $apiKey,
                'mail.default' => 'resend',
            ]);
        } else {
            config(['mail.default' => 'log']);
        }
    }
}
