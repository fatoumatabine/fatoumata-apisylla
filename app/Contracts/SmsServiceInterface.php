<?php

namespace App\Contracts;

interface SmsServiceInterface
{
    /**
     * Envoyer un SMS
     *
     * @param string $to Numéro de téléphone destinataire
     * @param string $message Contenu du message
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendSms(string $to, string $message): bool;

    /**
     * Vérifier si le service est disponible
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
