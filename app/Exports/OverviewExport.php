<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OverviewExport implements WithMultipleSheets
{
    public function __construct(private array $records) {}

    public function sheets(): array
    {
        return array_map(fn ($record, $index) => new OverviewSheet($record, $index + 1), $this->records, array_keys($this->records));
    }
}
