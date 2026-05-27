<?php

declare(strict_types=1);

namespace App\Domain\Quotes\Repositories;

use App\Domain\Quotes\Aggregates\Quote;

interface QuoteRepositoryInterface
{
    public function save(Quote $quote): Quote;
    public function update(int $id, Quote $quote): Quote;
    public function findById(int $id): ?Quote;
    public function nextNumero(): string;
}
