<?php

declare(strict_types=1);

namespace App\Application\Auth\TwoFactor;

use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use App\Models\User;

final class EnableTwoFactorUseCase
{
    public function __construct(
        private readonly TwoFactorService $service,
    ) {}

    /**
     * @return array{secret: string, recovery_codes: array<string>, qr_uri: string}
     */
    public function execute(User $user): array
    {
        return $this->service->startSetup($user);
    }
}
