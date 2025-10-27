<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client; // Importer la classe Twilio Client

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ClientCreated $event): void
    {
        $client = $event->client;
        $password = $event->password;
        $code = $event->code;

        // Envoyer email d'authentification
        Mail::raw("Votre compte a été créé. Mot de passe : $password. Utilisez ce mot de passe pour vous connecter.", function ($message) use ($client) {
            $message->to($client->email)->subject('Authentification Compte');
        });

        // Envoyer SMS avec le code via Twilio
        try {
            $twilioSid = env('TWILIO_SID');
            $twilioToken = env('TWILIO_TOKEN');
            $twilioFrom = env('TWILIO_FROM');

            if ($twilioSid && $twilioToken && $twilioFrom) {
                $twilio = new Client($twilioSid, $twilioToken);
                $twilio->messages->create(
                    $client->telephone, // Numéro de téléphone du destinataire
                    [
                        'from' => $twilioFrom, // Votre numéro Twilio
                        'body' => "Votre code d'authentification est : $code",
                    ]
                );
                Log::info("SMS envoyé à {$client->telephone} via Twilio.");
            } else {
                Log::warning("Variables d'environnement Twilio non configurées. SMS non envoyé à {$client->telephone}.");
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du SMS à {$client->telephone} via Twilio: " . $e->getMessage());
        }
    }
}
