<?php

namespace App\Listeners;

use App\Events\TransactionCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SendTransactionSmsNotification implements ShouldQueue
{
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
    public function handle(TransactionCreatedEvent $event): void
    {
        $transaction = $event->transaction;
        $compte = $transaction->compte;
        $client = $compte->client;

        if (!$client || !$client->telephone) {
            Log::warning('Client telephone not found for transaction SMS', ['transaction_id' => $transaction->id]);
            return;
        }

        $message = "Transaction effectuée: " . ucfirst($transaction->type) . " de " . $transaction->montant . " " . $transaction->devise . ". Nouveau solde: " . $compte->solde . " " . $compte->devise;

        try {
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $twilio->messages->create(
                $client->telephone,
                [
                    'from' => env('TWILIO_PHONE_NUMBER'),
                    'body' => $message
                ]
            );
            Log::info('SMS sent for transaction', ['transaction_id' => $transaction->id, 'to' => $client->telephone]);
        } catch (\Exception $e) {
            Log::error('Failed to send SMS for transaction', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
        }
    }
}
