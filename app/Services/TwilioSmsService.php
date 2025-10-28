<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioSmsService implements SmsServiceInterface
{
    private ?Client $twilio = null;
    private ?string $fromNumber = null;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.from');

        if ($sid && $token && $this->fromNumber) {
            try {
                $this->twilio = new Client($sid, $token);
            } catch (\Exception $e) {
                Log::error('Erreur d\'initialisation du client Twilio: ' . $e->getMessage());
            }
        }
    }

    /**
     * Envoyer un SMS via Twilio
     */
    public function sendSms(string $to, string $message): bool
    {
        if (!$this->isAvailable()) {
            Log::warning('Service Twilio non disponible pour l\'envoi de SMS', [
                'to' => $to,
                'message_length' => strlen($message),
            ]);
            return false;
        }

        try {
            $this->twilio->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $message,
            ]);

            Log::info('SMS envoyé avec succès via Twilio', [
                'to' => $to,
                'from' => $this->fromNumber,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS via Twilio', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Vérifier si le service Twilio est disponible
     */
    public function isAvailable(): bool
    {
        return $this->twilio !== null && $this->fromNumber !== null;
    }
}
