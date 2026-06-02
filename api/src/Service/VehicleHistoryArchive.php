<?php

namespace App\Service;

final readonly class VehicleHistoryArchive
{
    public function __construct(
        public string $path,
        public string $filename,
    ) {}
}
