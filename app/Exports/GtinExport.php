<?php

namespace App\Exports;

use App\Models\Gtin;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GtinExport implements FromCollection, WithHeadings
{
    public function headings(): array
    {
        return [
            'Material ID',
            'Tranding unit',
            'GTIN',
            'Last user',
            'Last update',
            'Status',
        ];
    }

    public function collection()
    {
        return Gtin::addSelect('material_id')
                ->addSelect('trading_unit')
                ->addSelect(DB::raw('"\'" || global_trade_item_number as global_trade_item_number'))
                ->addSelect('user_last_update')
                ->addSelect('last_update')
                ->addSelect('status_gtin')
                ->orderByRaw('(case when status_gtin = \'RESERVE\' then 0 else 1 end) asc')
                ->orderBy('material_id')->get();
    }
}