<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use Illuminate\Mail\Mailable;

class PlantillaMail extends Mailable
{
    /**
     * @param string $emailSubject  Rendered email subject
     * @param string $htmlBody      Rendered HTML body (full HTML, not just a fragment)
     */
    public function __construct(
        public readonly string $emailSubject,
        public readonly string $htmlBody,
    ) {}

    public function build(): static
    {
        return $this
            ->subject($this->emailSubject)
            ->view('emails.plantilla');
        // The view receives $htmlBody automatically as a public property
    }
}
