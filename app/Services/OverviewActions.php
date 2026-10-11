<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OverviewActions
{
    public const ROUTES = [
        1 => ['product.new', 'product.create'],
        2 => ['packmaterial.fg-bom.new', 'packmaterial.fg-bom.save'],
        3 => ['material-levels.semi-fg-lv2.new', 'material-levels.semi-fg-lv2.save'],
        4 => ['packmaterial.semi-fg-lv2-bom.new', 'packmaterial.semi-fg-lv2-bom.save'],
        5 => ['material-levels.semi-fg-lv1.new', 'material-levels.semi-fg-lv1.save'],
        6 => ['packmaterial.semi-fg-lv1-bom.new', 'packmaterial.semi-fg-lv1-bom.save'],
        7 => ['material-levels.raw.new', 'material-levels.raw.save'],
        8 => ['material-levels.raw.new', 'material-levels.raw.save'],
    ];

    private function rows(string $table, array $where): array
    {
        $query = DB::connection('oracle')->table($table);
        foreach ($where as $key => $value) {
            $query->whereRaw('TRIM('.$key.') = ?', [trim((string) $value)]);
        }

        return $query->get()->map(fn ($row) => array_map(fn ($value) => is_string($value) ? trim($value) : $value, array_change_key_case((array) $row, CASE_UPPER)))->all();
    }

    private function editable(array $row): bool
    {
        return strtoupper(trim((string) ($row['STATUS_ROW'] ?? ''))) !== 'COM'
            && strtoupper(trim((string) ($row['STATUS'] ?? ''))) !== 'COM';
    }

    private function candidate(array $path, array $fields): array
    {
        return ['path' => $path, 'label' => implode(' → ', $path), 'fields' => $fields];
    }

    /** Only authoritative DML parent paths can open a create form. INDEX alone is insufficient. */
    public function options(array $record): array
    {
        $h = $record['identity'];
        $result = array_fill(1, 8, []);
        $result[1] = [$this->candidate([], [])];
        if (empty($h['FG_BOM']) || empty($h['FG_ID'])) {
            return $result;
        }
        $fgRows = $this->rows('PROJ1_2_DML_FG_MATTYPE_1', ['FG_BOM_ID' => $h['FG_BOM'], 'MATERIAL_ID_FG_1' => $h['FG_ID']]);
        if (count($fgRows) !== 1 || ! $this->editable($fgRows[0])) {
            return $result;
        }
        $fg = $fgRows[0];
        $base = [
            'fgMaterialId' => $h['FG_ID'], 'referentMaterialId' => $h['FG_ID'],
            'fgBomId' => $h['FG_BOM'], 'fgBomDesc' => $fg['DESC_FG_BOM_ID'] ?? '',
            'site' => $fg['SITE'] ?? '', 'actionMode' => 'create', 'mode' => 'create',
        ];
        $result[2] = [$this->candidate([$h['FG_ID']], array_merge($base, [
            'bomId' => $h['FG_BOM'], 'bomDesc' => $base['fgBomDesc'], 'ownerLevel' => 'fg',
        ]))];
        $result[3] = [$this->candidate([$h['FG_ID']], $base)];
        $scope = ['FG_BOM_ID' => $h['FG_BOM'], 'MATERIAL_ID_FG_1' => $h['FG_ID']];
        $lv2Rows = $this->rows('PROJ1_2_DML_SEMI_L2_ID', $scope);
        $lv1Rows = $this->rows('PROJ1_2_DML_SEMI_L1_ID', $scope);
        foreach ($lv2Rows as $lv2) {
            $id2 = $lv2['SEMI_FG_LV2_ID'] ?? '';
            $bom2 = $lv2['SEMI_FG_LV2_BOM_ID'] ?? '';
            // Verify membership under this displayed header, not just membership in DML.
            $linked = collect($record['sections'][0]['rows'])->contains(fn ($row) => $row['component'] === $id2)
                || collect($record['sections'][1]['rows'])->contains(fn ($row) => $row['id'] === $id2);
            if (! $id2 || ! $bom2 || ! $linked || ! $this->editable($lv2)) {
                continue;
            }
            $fields2 = array_merge($base, [
                'bomId' => $bom2, 'levelMaterialId' => $id2, 'levelBomId' => $bom2, 'semiFgLvBomId' => $bom2,
                'semiFgLvBomDesc' => $lv2['DESC_SEMI_FG_LV2_BOM_ID'] ?? '',
                'ownerLevel' => 'semiFgLv2',
            ]);
            $result[4][] = $this->candidate([$h['FG_ID'], $id2], $fields2);
            $result[5][] = $this->candidate([$h['FG_ID'], $id2], array_merge($base, [
                'parentLevelBomId' => $bom2, 'semiFgLv2BomId' => $bom2,
                'semi_fg_lv2_id' => $id2, 'semi_fg_lv2_bom_id' => $bom2,
                'fgDetail' => ['semiFgLv2' => ['id' => $id2, 'bomId' => $bom2]],
            ]));
            foreach ($lv1Rows as $lv1) {
                $id1 = $lv1['SEMI_FG_LV1_ID'] ?? '';
                $bom1 = $lv1['SEMI_FG_LV1_BOM_ID'] ?? '';
                $linked1 = collect($record['sections'][1]['rows'])->contains(fn ($row) => $row['id'] === $id2 && $row['component'] === $id1)
                    || collect($record['sections'][2]['rows'])->contains(fn ($row) => $row['parents'] === [$id2] && $row['id'] === $id1);
                if (! $id1 || ! $bom1 || ! $linked1 || ! $this->editable($lv1)
                    || ($lv1['SEMI_FG_LV2_ID'] ?? '') !== $id2 || ($lv1['SEMI_FG_LV2_BOM_ID'] ?? '') !== $bom2) {
                    continue;
                }
                $fields1 = array_merge($base, [
                    'bomId' => $bom1, 'levelMaterialId' => $id1, 'levelBomId' => $bom1, 'semiFgLvBomId' => $bom1,
                    'semiFgLvBomDesc' => $lv1['DESC_SEMI_FG_LV1_BOM_ID'] ?? '',
                    'parentLevelBomId' => $bom2, 'ownerLevel' => 'semiFgLv1',
                    'fgDetail' => ['semiFgLv2' => ['id' => $id2, 'bomId' => $bom2]],
                ]);
                $result[6][] = $this->candidate([$h['FG_ID'], $id2, $id1], $fields1);
                $result[7][] = $this->candidate([$h['FG_ID'], $id2, $id1], $fields1);
                // Source table identifies these as Material Type4; never infer type from an ID prefix.
                $type4Rows = $this->rows('PROJ1_2_DML_SEMI_L1_COMP_M4', ['SEMI_FG_LV1_BOM_ID' => $bom1]);
                foreach ($type4Rows as $raw) {
                    $rawId = $raw['MATERIAL_ID_M4'] ?? '';
                    $linkedRaw = collect($record['sections'][2]['rows'])->contains(fn ($row) => $row['parents'] === [$id2] && $row['id'] === $id1 && $row['component'] === $rawId);
                    if (! $rawId || ! $linkedRaw || ! $this->editable($raw)) {
                        continue;
                    }
                    $candidate = $this->candidate([$h['FG_ID'], $id2, $id1, $rawId], array_merge($fields1, [
                        'rawMaterialId' => $rawId, 'rawMaterialType' => '4',
                    ]));
                    $result[8][] = $candidate;
                }
            }
        }
        foreach ($result as &$candidates) {
            // Ambiguous duplicate paths must not pick an arbitrary DML owner.
            $counts = array_count_values(array_map(fn ($c) => json_encode($c['path']), $candidates));
            $candidates = array_values(array_filter($candidates, fn ($c) => $counts[json_encode($c['path'])] === 1));
        }

        return $result;
    }

    public function resolve(array $header, int $action, array $path): array
    {
        $data = app(OverviewService::class)->load('products', 1, $header);
        if ($data['notice'] || ! $data['records']) {
            throw ValidationException::withMessages(['overview' => 'รายการ Overview นี้ไม่พร้อมใช้งานแล้ว']);
        }
        $options = $this->options($data['records'][0]);
        foreach ($options[$action] ?? [] as $candidate) {
            if ($candidate['path'] === $path) {
                return $candidate['fields'];
            }
        }
        throw ValidationException::withMessages(['overview' => 'ไม่พบ parent ที่ตรงกัน หรือเจ้าของข้อมูลเป็น COM หรือข้อมูลไม่ครบ']);
    }
}
