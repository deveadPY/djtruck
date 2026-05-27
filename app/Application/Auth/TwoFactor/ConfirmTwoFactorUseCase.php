<?php

declare(strict_types=1);

namespace App\Application\Auth\TwoFactor;

use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use App\Models\User;

final class ConfirmTwoFactorUseCase
{
    public function __construct(
        private readonly TwoFactorService $service,
    ) {}

    public function execute(User $user, string $code): void
    {
        $this->service->confirm($user, $code);
    }
}
