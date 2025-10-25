<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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

        // Envoyer SMS avec le code (ici, on log car pas de service SMS)
        Log::info("SMS envoyé à {$client->telephone} : Votre code d'authentification est : $code");
    }
}
