<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS notification.
     * Currently running with a dummy log driver as requested.
     */
    public function send(string $phone, string $message): bool
    {
        // ponytail: dummy SMS adapter logs to storage/logs/sms.log; plug in SSL Wireless/Twilio gateway when credentials provided
        $logEntry = sprintf(
            "[%s] DUMMY SMS to %s: %s\n",
            now()->format('Y-m-d H:i:s'),
            $phone,
            $message
        );

        $smsLogPath = storage_path('logs/sms.log');
        file_put_contents($smsLogPath, $logEntry, FILE_APPEND | LOCK_EX);

        Log::info("Dummy SMS logged to {$phone}: {$message}");

        return true;
    }
}
