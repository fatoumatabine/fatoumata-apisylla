<?php

namespace App\Events;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

public $compte;
public $client;
    public $password;
public $code;

/**
 * Create a new event instance.
 */
public function __construct(Compte $compte, Client $client, ?string $password = null, ?string $code = null)
{
    $this->compte = $compte;
    $this->client = $client;
    $this->password = $password;
    $this->code = $code;
}
}
