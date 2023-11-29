<?php

namespace App\Exports;

use App\Models\Gtin;
use Maatwebsite\Excel\Concerns\FromCollection;

class GtinExport implements FromCollection
{
    public function collection()
    {
        return Gtin::all();
    }
}