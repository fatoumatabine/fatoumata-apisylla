<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendTransactionNotification implements ShouldQueue
{
    private SmsService $smsService;

    /**
     * Create the event listener.
     */
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Handle the event.
     */
    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->transaction;
        $compte = $transaction->compte;
        $client = $compte->client;

        if (!$client || !$client->telephone) {
            Log::warning('Client ou numéro de téléphone manquant pour la notification', [
                'transaction_id' => $transaction->id,
                'client_id' => $client?->id,
            ]);
            return;
        }

        // Préparer le message selon le type de transaction
        $message = $this->prepareMessage($transaction, $compte);

        // Envoyer la notification SMS
        $success = $this->smsService->sendTransactionNotification($client->telephone, $message);

        if ($success) {
            Log::info('Notification SMS de transaction envoyée avec succès', [
                'transaction_id' => $transaction->id,
                'client_telephone' => $client->telephone,
            ]);
        } else {
            Log::warning('Échec de l\'envoi de la notification SMS de transaction', [
                'transaction_id' => $transaction->id,
                'client_telephone' => $client->telephone,
            ]);
        }
    }

    /**
     * Préparer le message de notification selon le type de transaction
     */
    private function prepareMessage($transaction, $compte): string
    {
        $montant = number_format($transaction->montant, 0, ',', ' ');
        $reference = $transaction->reference;
        $solde = number_format($compte->solde, 0, ',', ' ');

        if ($transaction->isCredit()) {
            return "Dépôt effectué avec succès. Montant: {$montant} FCFA. Référence: {$reference}. Nouveau solde: {$solde} FCFA.";
        } elseif ($transaction->isDebit()) {
            return "Retrait effectué avec succès. Montant: {$montant} FCFA. Référence: {$reference}. Solde restant: {$solde} FCFA.";
        }

        return "Transaction effectuée. Montant: {$montant} FCFA. Référence: {$reference}. Solde: {$solde} FCFA.";
    }
}
