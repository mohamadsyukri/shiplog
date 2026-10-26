<?php

declare(strict_types=1);

namespace App\Services\Imports;

/**
 * What one import did, for the command's table and the upload screen's message.
 */
final readonly class ImportResult
{
    public function __construct(
        public string $from,
        public string $to,
        public int $lanesUpserted,
        public int $entriesCreated,
        public int $entriesReplaced,
    ) {}
}
