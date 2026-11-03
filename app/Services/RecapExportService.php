<?php

namespace App\Services;

use App\Exports\SingleRecapDetailExport;
use App\Mail\RecapExportMail;
use App\Models\Recap;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class RecapExportService
{
    /**
     * Build recap export spreadsheet and email it to the given user.
     *
     * @throws RuntimeException When export generation or delivery fails.
     */
    public function emailExportToUser(User $user, Recap $recap): void
    {
        if (empty($user->email)) {
            throw new RuntimeException('Your account does not have an email address on file.');
        }

        $mailer = (string) config('mail.default');
        if ($mailer === 'log') {
            throw new RuntimeException(
                'Email delivery is not configured on this server (MAIL_MAILER is "log"). An administrator must set MAIL_MAILER=smtp and SMTP credentials in .env, then run: php artisan config:clear'
            );
        }

        if ($mailer === 'array') {
            throw new RuntimeException('Email delivery is not configured for production use (MAIL_MAILER is "array").');
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "recap_export_{$recap->id}_{$recap->event_id}_{$timestamp}.xlsx";

        try {
            $excelContent = Excel::raw(
                new SingleRecapDetailExport($recap),
                ExcelFormat::XLSX
            );

            if (! is_string($excelContent) || $excelContent === '') {
                throw new RuntimeException('Recap export file could not be generated.');
            }

            Mail::to($user->email)->send(new RecapExportMail($recap, $excelContent, $filename));

            Log::info('Recap export email sent', [
                'recap_id' => $recap->id,
                'event_id' => $recap->event_id,
                'user_id' => $user->id,
                'email' => $user->email,
                'mailer' => $mailer,
                'attachment_bytes' => strlen($excelContent),
            ]);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Recap export email failed', [
                'recap_id' => $recap->id,
                'event_id' => $recap->event_id,
                'user_id' => $user->id,
                'email' => $user->email,
                'mailer' => $mailer,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            throw new RuntimeException(
                'We could not send the recap export email. '.$e->getMessage(),
                0,
                $e
            );
        }
    }
}
