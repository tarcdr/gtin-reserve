<?php

namespace App\Exports;

use App\Models\Gtin;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GtinExport implements FromCollection, WithHeadings, WithColumnFormatting
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

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function collection()
    {
        return Gtin::addSelect('material_id')
                ->addSelect('trading_unit')
                ->addSelect('global_trade_item_number')
                ->addSelect('user_last_update')
                ->addSelect('last_update')
                ->addSelect('status_gtin')
                ->orderByRaw('(case when status_gtin = \'RESERVE\' then 0 else 1 end) asc')
                ->orderByRaw('(case when last_update is null then to_date(\'1900-01-01\', \'YYYY-MM-DD\') else last_update end) desc')->get();
    }
}