<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BomContextService
{
    private function exists(string $table): bool
    {
        return Schema::connection('oracle')->hasTable($table);
    }

    public function existing(int $level, string $fgBomId): array
    {
        $result = [];
        $table = "PROJ1_2_DML_SEMI_L{$level}_BOM_EXIST";
        if (!$this->exists($table)) return [];
        foreach (DB::connection('oracle')->table($table)->whereRaw('TRIM(FG_BOM_ID) = ?', [$fgBomId])->orderBy('NO')->get() as $record) {
            $row = array_change_key_case((array) $record, CASE_UPPER);
            $found = $this->level($level, $fgBomId, trim($row["SEMI_FG_LV{$level}_BOM_ID_EXIST"]), false, true);
            if ($found) $result[] = $found;
        }
        return $result;
    }

    public function level(int $level, string $fgBomId, ?string $bomId = null, bool $lock = false, bool $existingOnly = false): ?array
    {
        $db = DB::connection('oracle');
        foreach ($existingOnly ? [true] : [false, true] as $existing) {
            $suffix = $existing ? '_EXIST' : '';
            $table = "PROJ1_2_DML_SEMI_L{$level}_BOM{$suffix}";
            if (!$this->exists($table)) continue;
            $bomColumn = "SEMI_FG_LV{$level}_BOM_ID{$suffix}";
            $query = $db->table($table)->whereRaw('TRIM(FG_BOM_ID) = ?', [$fgBomId]);
            if ($bomId !== null && $bomId !== '') $query->whereRaw("TRIM({$bomColumn}) = ?", [$bomId]);
            $record = ($lock ? $query->lockForUpdate() : $query)->first();
            if (!$record) continue;
            $row = array_map(fn ($v) => is_string($v) ? trim($v) : $v, array_change_key_case((array) $record, CASE_UPPER));
            $idTable = "PROJ1_2_DML_SEMI_L{$level}_ID{$suffix}";
            $idQuery = $db->table($idTable)->whereRaw('TRIM(FG_BOM_ID) = ?', [$fgBomId])->whereRaw("TRIM({$bomColumn}) = ?", [$row[$bomColumn]]);
            $idRecord = ($lock ? $idQuery->lockForUpdate() : $idQuery)->first();
            if (!$idRecord) continue;
            $id = array_map(fn ($v) => is_string($v) ? trim($v) : $v, array_change_key_case((array) $idRecord, CASE_UPPER));
            return [
                'no' => $row['NO'] ?? '', 'sourceType' => $existing ? 'existing' : 'new',
                'id' => $id["SEMI_FG_LV{$level}_ID{$suffix}"], 'desc' => $id["DESC_SEMI_FG_LV{$level}_ID{$suffix}"] ?? '',
                'bomId' => $row[$bomColumn], 'bomDesc' => $row["DESC_{$bomColumn}"] ?? '',
                'fgBomId' => $fgBomId, 'fgMaterialId' => $row['MATERIAL_ID_FG_1'] ?? '', 'materialIdFg1' => $row['MATERIAL_ID_FG_1'] ?? '',
                'statusRow' => $row['STATUS_ROW'] ?? 'INS', 'idStatus' => $id['STATUS_ROW'] ?? 'INS',
                'semiFgLv2BomId' => $row['SEMI_FG_LV2_BOM_ID'] ?? '', 'semiFgLv2Id' => $row['SEMI_FG_LV2_ID'] ?? '',
                'userCreate' => $row['USER_CREATE'] ?? '', 'site' => $row['SITE'] ?? '',
            ];
        }
        return null;
    }
}
