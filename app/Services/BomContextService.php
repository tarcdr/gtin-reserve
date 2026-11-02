<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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
            $found = $this->level($level, $fgBomId, trim($row["SEMI_FG_LV{$level}_BOM_ID_EXIST"]), false, true, $level === 1 ? trim($row['SEMI_FG_LV2_BOM_ID'] ?? '') : null);
            if ($found) $result[] = $found;
        }
        return $result;
    }

    public function level(int $level, string $fgBomId, ?string $bomId = null, bool $lock = false, bool $existingOnly = false, ?string $parentLv2BomId = null): ?array
    {
        $db = DB::connection('oracle');
        foreach ($existingOnly ? [true] : [false, true] as $existing) {
            $suffix = $existing ? '_EXIST' : '';
            $table = "PROJ1_2_DML_SEMI_L{$level}_BOM{$suffix}";
            if (!$this->exists($table)) continue;
            $bomColumn = "SEMI_FG_LV{$level}_BOM_ID{$suffix}";
            $query = $db->table($table)->whereRaw('TRIM(FG_BOM_ID) = ?', [$fgBomId]);
            if ($bomId !== null && $bomId !== '') $query->whereRaw("TRIM({$bomColumn}) = ?", [$bomId]);
            if ($level === 1 && $parentLv2BomId) $query->whereRaw('TRIM(SEMI_FG_LV2_BOM_ID) = ?', [$parentLv2BomId]);
            $matches = ($lock ? $query->lockForUpdate() : $query)->limit(2)->get();
            if ($matches->count() > 1) throw ValidationException::withMessages(['parent' => 'พบ BOM มากกว่าหนึ่งรายการ กรุณาระบุ parent BOM ให้ครบ']);
            $record = $matches->first();
            if (!$record) continue;
            $row = array_map(fn ($v) => is_string($v) ? trim($v) : $v, array_change_key_case((array) $record, CASE_UPPER));
            $idTable = "PROJ1_2_DML_SEMI_L{$level}_ID{$suffix}";
            $idQuery = $db->table($idTable)->whereRaw('TRIM(FG_BOM_ID) = ?', [$fgBomId])->whereRaw("TRIM({$bomColumn}) = ?", [$row[$bomColumn]]);
            if ($existing && $level === 1) $idQuery->whereRaw('TRIM(SEMI_FG_LV2_BOM_ID) = ?', [$row['SEMI_FG_LV2_BOM_ID']]);
            $idMatches = ($lock ? $idQuery->lockForUpdate() : $idQuery)->limit(2)->get();
            if ($idMatches->count() > 1) throw ValidationException::withMessages(['parent' => 'BOM เชื่อมกับหลาย Material กรุณาตรวจข้อมูลต้นทาง']);
            $idRecord = $idMatches->first();
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
    public function resolveLevel1(?string $fgBomId, ?string $materialId, ?string $bomId = null, ?string $parentLv2BomId = null): array
    {
        $matches = [];
        foreach ([false, true] as $existing) {
            $suffix = $existing ? '_EXIST' : '';
            $table = 'PROJ1_2_DML_SEMI_L1_ID'.$suffix;
            if (!$this->exists($table)) continue;
            $query = DB::connection('oracle')->table($table);
            if ($fgBomId) $query->whereRaw('TRIM(FG_BOM_ID) = ?', [$fgBomId]);
            if ($materialId) $query->whereRaw('TRIM(SEMI_FG_LV1_ID'.$suffix.') = ?', [$materialId]);
            if ($bomId) $query->whereRaw('TRIM(SEMI_FG_LV1_BOM_ID'.$suffix.') = ?', [$bomId]);
            if (!$fgBomId && !$materialId && !$bomId) break;
            foreach ($query->get() as $record) {
                $row = array_map(fn ($v) => is_string($v) ? trim($v) : $v, array_change_key_case((array) $record, CASE_UPPER));
                $linkedBom = $row['SEMI_FG_LV1_BOM_ID'.$suffix] ?? '';
                if ($linkedBom === '') continue;
                $parent = $parentLv2BomId ?: ($row['SEMI_FG_LV2_BOM_ID'] ?? null);
                $found = $this->level(1, $row['FG_BOM_ID'], $linkedBom, false, $existing, $parent);
                if ($found) $matches[$found['fgBomId'].'|'.$found['bomId'].'|'.$found['semiFgLv2BomId']] = $found;
            }
        }
        if (count($matches) !== 1) throw ValidationException::withMessages(['parent' => count($matches) ? 'ข้อมูล parent BOM กำกวม กรุณาระบุ FG/LV2/LV1 BOM ให้ครบ' : 'ไม่พบความสัมพันธ์ของ LV1 Material กับ parent BOM กรุณาตรวจข้อมูลต้นทาง']);
        return array_values($matches)[0];
    }

}
