<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $client;
    public $password;
    public $code;

    /**
     * Create a new event instance.
     */
    public function __construct(Client $client, ?string $password, ?string $code)
    {
        $this->client = $client;
        $this->password = $password;
        $this->code = $code;
    }
}
