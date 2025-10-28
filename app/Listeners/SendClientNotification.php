<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client; // Ajout de l'import pour Twilio

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

        // Envoi d'email
        if ($password) {
            Log::info("Email envoyé à {$client->email} : Votre compte a été créé. Mot de passe : $password. Utilisez ce mot de passe pour vous connecter.");
            Mail::raw("Votre compte a été créé. Mot de passe : $password. Utilisez ce mot de passe pour vous connecter.", function ($message) use ($client) {
                $message->to($client->email)->subject('Authentification Compte');
            });
        }

        // Envoi de SMS
        if ($code) {
            Log::info("SMS envoyé à {$client->telephone} : Votre code d'accès est : $code");
            try {
                $twilioSid = env('TWILIO_SID');
                $twilioToken = env('TWILIO_TOKEN');
                $twilioFrom = env('TWILIO_FROM');

                if ($twilioSid && $twilioToken && $twilioFrom) {
                    $twilio = new Client($twilioSid, $twilioToken);
                    $twilio->messages->create(
                        $client->telephone,
                        [
                            'from' => $twilioFrom,
                            'body' => "Votre code d'accès est : $code",
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
}
