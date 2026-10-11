<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OverviewService
{
    public const DEFINITIONS = [
        'products' => [
            'title' => 'Overview Products',
            'header' => 'PROJ1_2_INDEX_SECTION_HEADER_V',
            'keys' => ['FG_BOM', 'GTIN', 'FG_ID'],
            'labels' => ['BOM FG', 'GTIN', 'FG ID'],
            'headerFields' => ['FG_BOM', 'GTIN', 'FG_ID'],
            'sections' => [
                ['FG', 'FG_ID', 'COMPONENTS_FG', 'STATUS_FG', 'FG', []],
                ['SEMI FG LV.2', 'SEMI_FG_LV_2_ID', 'COMPONENTS_SEMI_FG_LV_2', 'STATUS_SEMI_FG_LV_2', 'SFGL2', []],
                ['SEMI FG LV.1', 'SEMI_FG_LV_1_ID', 'COMPONENTS_SEMI_FG_LV_1', 'STATUS_SEMI_FG_LV_1', 'SFGL1', ['SEMI_FG_LV_2_ID']],
                ['RAW MATERIAL ID', 'RAW_MAT_TYPE4_ID', 'COMPONENTS_RAW_MAT4', 'STATUS_RAW_MAT4', 'MAT4', ['SEMI_FG_LV_2_ID', 'SEMI_FG_LV_1_ID']],
            ],
        ],
        'bs' => [
            'title' => 'Overview Business Supply',
            'header' => 'PROJ1_2_INDEX_SECTION_BS_HT_V',
            'keys' => ['BOM_BUSINESS_SUPPLY', 'BS_GTIN', 'BS_ID'],
            'labels' => ['BOM BUSINESS SUPPLY', 'GTIN', 'BS ID', 'BS TYPE'],
            'headerFields' => ['BOM_BUSINESS_SUPPLY', 'BS_GTIN', 'BS_ID', 'BS_TYPE'],
            'sections' => [['BS ID', 'BS_ID', 'COMPONENTS_BS', 'STATUS_BS', 'BS_DT', []]],
        ],
    ];

    public static function flag(mixed $value): string
    {
        return match (strtoupper(trim((string) $value))) {
            'Y' => '1',
            'N' => '0',
            '' => '—',
            default => (string) $value,
        };
    }

    private function row(object $row): array
    {
        return array_change_key_case((array) $row, CASE_UPPER);
    }

    private function identity(array $row, array $keys): string
    {
        return json_encode(array_map(fn ($key) => $row[$key] ?? null, $keys), JSON_THROW_ON_ERROR);
    }

    /** A read-only Oracle transaction keeps headers and sections in one consistent snapshot.
     * Null page loads every header for export; an integer loads only the displayed record.
     */
    public function load(string $kind, ?int $page = 1, ?array $identity = null): array
    {
        $definition = self::DEFINITIONS[$kind];
        $connection = DB::connection('oracle');

        return $connection->transaction(function () use ($connection, $definition, $page, $identity) {
            if ($connection->getDriverName() === 'oracle') {
                $connection->statement('SET TRANSACTION READ ONLY');
            }
            $query = $connection->table($definition['header'])->select($definition['headerFields']);
            foreach ($definition['keys'] as $key) {
                $query->orderBy($key);
            }
            // Preserve duplicate source rows rather than silently deduplicating them.
            $headers = $query->get()->map(fn ($row) => $this->row($row))->all();
            $total = count($headers);
            $currentPage = $total ? min(max($page ?? 1, 1), $total) : 0;
            $notice = null;
            if ($identity !== null) {
                $found = false;
                foreach ($headers as $index => $header) {
                    if ($this->identity($header, $definition['keys']) === $this->identity($identity, $definition['keys'])) {
                        $currentPage = $index + 1;
                        $found = true;
                        break;
                    }
                }
                if (! $found) {
                    $currentPage = $total ? 1 : 0;
                    $notice = 'รายการเดิมไม่อยู่ใน Overview แล้ว กลับไปยังรายการแรก';
                }
            }
            $selected = $page === null ? $headers : ($total ? [$headers[$currentPage - 1]] : []);
            $records = array_map(fn ($header) => [
                'identity' => array_intersect_key($header, array_flip($definition['keys'])),
                'header' => array_map(fn ($key, $label) => [
                    'label' => $label, 'value' => $header[$key] ?? null,
                ], $definition['headerFields'], $definition['labels']),
                'sections' => [],
            ], $selected);

            foreach ($definition['sections'] as [$label, $id, $component, $status, $suffix, $parents]) {
                $actionSuffix = match ($suffix) {
                    'SFGL2' => 'SEMI_FG_LV_2', 'SFGL1' => 'SEMI_FG_LV_1',
                    'MAT4' => 'RAW_MAT4', 'BS_DT' => 'BS', default => 'FG',
                };
                $built = 'BUILT_ACTION_'.$actionSuffix;
                $complete = 'COMPLETE_ACTION_'.$actionSuffix;
                $columns = array_values(array_unique(array_merge($definition['keys'], $parents, [$id, $component, $status, 'ACTION_DETAILS', $built, $complete])));
                $details = [];
                if ($selected) {
                    $query = $connection->table('PROJ1_2_INDEX_SECTION_'.$suffix.'_V')->select($columns);
                    if ($page !== null) {
                        foreach ($definition['keys'] as $key) {
                            $query->where($key, $selected[0][$key]);
                        }
                    }
                    foreach (array_unique(array_merge($definition['keys'], $parents, [$id, $component, $status, 'ACTION_DETAILS', $built, $complete])) as $key) {
                        $query->orderBy($key);
                    }
                    foreach ($query->get() as $row) {
                        $row = $this->row($row);
                        $details[$this->identity($row, $definition['keys'])][] = [
                            'parents' => array_map(fn ($key) => $row[$key] ?? null, $parents),
                            'id' => $row[$id] ?? null,
                            'component' => $row[$component] ?? null,
                            'status' => $row[$status] ?? null,
                            'action' => $row['ACTION_DETAILS'] ?? null,
                            'builtRaw' => $row[$built] ?? null,
                            'completeRaw' => $row[$complete] ?? null,
                            'built' => self::flag($row[$built] ?? null),
                            'complete' => self::flag($row[$complete] ?? null),
                        ];
                    }
                }
                foreach ($selected as $index => $header) {
                    $records[$index]['sections'][] = [
                        'label' => $label,
                        'parentLabels' => array_map(fn ($key) => str_replace('_', ' ', preg_replace('/_ID$/', '', $key)), $parents),
                        'rows' => $details[$this->identity($header, $definition['keys'])] ?? [],
                    ];
                }
            }

            return ['title' => $definition['title'], 'records' => $records, 'page' => $currentPage, 'total' => $total, 'notice' => $notice];
        });
    }
}
