<?php

namespace App\Exports;

use App\Models\MaterialTemp;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MaterialTempExport implements FromCollection, WithHeadings
{
    public function headings(): array
    {
        return [
            'Material ID',
            'Material Desc',
            'Brand',
            'Mattype',
            'Last user',
            'Last update',
            'Status',
        ];
    }

    public function collection()
    {
        return MaterialTemp::addSelect('material_id')
                ->addSelect('material_desc')
                ->addSelect('brand')
                ->addSelect('mattype')
                ->addSelect('last_user')
                ->addSelect('last_update')
                ->addSelect('status')
                ->orderByRaw('(case when status = \'RESERVE\' then 0 else 1 end) asc')
                ->orderBy('last_update', 'desc')->get();
    }
}