<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OverviewSheet extends StringValueBinder implements FromArray, WithCustomValueBinder, WithEvents, WithStrictNullComparison, WithTitle
{
    public function __construct(private array $record, private int $number) {}

    public function title(): string
    {
        return 'Overview '.$this->number;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->record['header'] as $field) {
            $rows[] = [$field['label'], $field['value'] ?? '—'];
        }
        foreach ($this->record['sections'] as $section) {
            $rows[] = [null];
            $rows[] = [$section['label']];
            $rows[] = array_merge($section['parentLabels'], [$section['label'], 'COMPONENTS', 'STATUS', 'ACTION', 'BUILT', 'COMPLETE']);
            if (! $section['rows']) {
                $rows[] = ['ไม่มีข้อมูลในส่วนนี้'];
            }
            foreach ($section['rows'] as $row) {
                $rows[] = array_map(fn ($value) => $value === null || $value === '' ? '—' : (string) $value,
                    array_merge($row['parents'], [$row['id'], $row['component'], $row['status'], $row['action'], $row['built'], $row['complete']]));
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $range = $sheet->calculateWorksheetDimension();
            $sheet->getStyle($range)->getAlignment()->setVertical('top')->setWrapText(true);
            $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
            $sheet->freezePane('A'.(count($this->record['header']) + 1));
            foreach (range('A', 'H') as $column) {
                $sheet->getColumnDimension($column)->setWidth(24);
            }
            $row = count($this->record['header']);
            $sheet->getStyle('A1:A'.$row)->getFont()->setBold(true);
            foreach ($this->record['sections'] as $section) {
                $row += 2;
                $sheet->getStyle('A'.$row.':H'.($row + 1))->getFont()->setBold(true);
                $sheet->getStyle('A'.$row.':H'.($row + 1))->getFill()->setFillType('solid')->getStartColor()->setARGB('FFEAF2F8');
                $start = $row + 2;
                if (($this->record['header'][0]['label'] ?? '') === 'BOM FG') {
                    foreach ($section['rows'] as $index => $detail) {
                        $values = array_merge($detail['parents'], [$detail['id']]);
                        foreach ($values as $column => $value) {
                            $prefix = array_slice($values, 0, $column + 1);
                            if (in_array(null, $prefix, true) || in_array('', $prefix, true)) {
                                continue;
                            }
                            $matches = fn ($item) => array_slice(array_merge($item['parents'], [$item['id']]), 0, $column + 1) === $prefix;
                            if ($index > 0 && $matches($section['rows'][$index - 1])) {
                                continue;
                            }
                            $count = 1;
                            while (isset($section['rows'][$index + $count]) && $matches($section['rows'][$index + $count])) {
                                $count++;
                            }
                            if ($count > 1) {
                                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column + 1);
                                $sheet->mergeCells($letter.($start + $index).':'.$letter.($start + $index + $count - 1));
                            }
                        }
                    }
                }
                $row += 1 + max(1, count($section['rows']));
            }
        }];
    }
}
