<?php

declare(strict_types=1);

namespace App\Infrastructure\SIFEN\Validators;

use Illuminate\Http\Client\Response;
use RuntimeException;

class SifenResponseValidator
{
    public function validate(Response $response): void
    {
        if ($response->failed()) {
            throw new RuntimeException(
                "SIFEN devolvió error HTTP {$response->status()}: {$response->body()}"
            );
        }
    }
}
