<?php

namespace App\Exports;

use App\Models\Gtin;
use Maatwebsite\Excel\Concerns\FromCollection;

class GtinExport implements FromCollection, WithHeadingRow
{
    public function collection()
    {
        return Gtin::addSelect('material_id')
                ->addSelect('trading_unit')
                ->addSelect('global_trade_item_number')
                ->addSelect('user_last_update')
                ->addSelect('last_update')
                ->addSelect('status_gtin')
                ->orderByRaw('(case when status_gtin = \'RESERVE\' then 0 else 1 end) asc')
                ->orderBy('material_id')->get();
    }
}