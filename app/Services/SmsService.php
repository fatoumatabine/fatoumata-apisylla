<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private SmsServiceInterface $smsProvider;

    public function __construct(SmsServiceInterface $smsProvider)
    {
        $this->smsProvider = $smsProvider;
    }

    /**
     * Envoyer un SMS de notification de transaction
     */
    public function sendTransactionNotification(string $phoneNumber, string $message): bool
    {
        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Envoyer un SMS générique
     */
    public function sendSms(string $to, string $message): bool
    {
        if (!$this->smsProvider->isAvailable()) {
            Log::warning('Aucun service SMS disponible');
            return false;
        }

        return $this->smsProvider->sendSms($to, $message);
    }

    /**
     * Vérifier si le service est disponible
     */
    public function isAvailable(): bool
    {
        return $this->smsProvider->isAvailable();
    }
}
