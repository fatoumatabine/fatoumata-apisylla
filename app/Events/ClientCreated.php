<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientCreated
{
    use Dispatchable, SerializesModels;

    public $client;
    public $password;
public $code;

/**
* Create a new event instance.
*/
public function __construct(Client $client, ?string $password = null, ?string $code = null)
{
$this->client = $client;
    $this->password = $password;
        $this->code = $code;
}
}
