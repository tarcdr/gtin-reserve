<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExistingLevelService
{
    public function __construct(private ExistingComponentService $components, private BomContextService $parents) {}

    public function availability(int $level): array
    {
        $names = ["PROJ1_2_SEMI_FG_LV{$level}_ID_V", "PROJ1_2_SAVE_BOM_SEMI_L{$level}_EXIST", "PROJ1_2_DML_SEMI_L{$level}_BOM_EXIST", "PROJ1_2_DML_SEMI_L{$level}_ID_EXIST", 'PROJ1_2_BOM_LINK_SEQ'];
        $valid = DB::connection('oracle')->table('USER_OBJECTS')->whereIn('OBJECT_NAME', $names)->where('STATUS', 'VALID')->get(['OBJECT_NAME'])->map(fn ($r) => $r->object_name ?? $r->OBJECT_NAME)->all();
        return ['ready' => !array_diff($names, $valid), 'reason' => array_diff($names, $valid) ? 'ยังไม่พร้อมใช้งาน กรุณาติดต่อผู้ดูแลระบบ' : ''];
    }

    public function context(int $level, array $input, bool $lock = false): array
    {
        if (!$this->availability($level)['ready']) throw ValidationException::withMessages(['componentId' => 'ยังไม่พร้อมใช้งาน กรุณาติดต่อผู้ดูแลระบบ']);
        $context = $this->components->context('fg', $input, $lock);
        if ($level === 1) {
            $parent = $this->parents->level(2, $context['fgBomId'], $input['semiFgLv2BomId'] ?? '', $lock);
            abort_unless($parent && !empty($input['semiFgLv2BomId']), 404);
            $context += ['semiFgLv2BomId' => $parent['bomId'], 'semiFgLv2BomDesc' => $parent['bomDesc'], 'semiFgLv2Id' => $parent['id']];
            $context['canWrite'] = $context['canWrite'] && $parent['sourceType'] === 'new' && !in_array($parent['statusRow'], ['COM', 'ETS'], true) && !in_array($parent['idStatus'], ['COM', 'ETS'], true);
        }
        return $context;
    }

    public function filters(int $level): array
    {
        $db = DB::connection('oracle');
        $prefix = "PROJ1_2_SEMI_L{$level}_EXIST";
        return [
            'brands' => $db->table($prefix.'_BRAND_LS')->selectRaw('TRIM(BRAND_ABB) as code, TRIM(BRAND) as description')->distinct()->orderBy('code')->get()->map(fn ($r) => ['value' => $r->code, 'label' => $r->code.' - '.$r->description])->all(),
            'mattypes' => $db->table($prefix.($level === 2 ? '_MATYP_LS' : '_MATTY_LS'))->selectRaw('TRIM(MATTYPE) as code')->distinct()->get()->map(fn ($r) => ['value' => $r->code, 'label' => $r->code])->all(),
            'subMattypes' => $db->table("PROJ1_2_SEMI_FG_LV{$level}_ID_V")->whereNotNull('SUB_MATTYPE')->selectRaw('TRIM(SUB_MATTYPE) as code')->distinct()->orderBy('code')->get()->map(fn ($r) => ['value' => $r->code, 'label' => $r->code])->all(),
        ];
    }

    public function options(int $level, array $input): array
    {
        foreach (['brand', 'mattype', 'subMattype'] as $field) if (!isset($input[$field]) || $input[$field] === '') return [];
        return DB::connection('oracle')->table("PROJ1_2_SEMI_FG_LV{$level}_ID_V")
            ->whereRaw('TRIM(BRAND_ABB) = ?', [$input['brand']])->whereRaw('TRIM(MATTYPE) = ?', [$input['mattype']])->whereRaw('TRIM(SUB_MATTYPE) = ?', [$input['subMattype']])
            ->orderBy('MATERIAL_ID')->orderBy('BOM_ID')->get()->map(function ($record) {
                $r = array_map(fn ($v) => is_string($v) ? trim($v) : $v, array_change_key_case((array) $record, CASE_UPPER));
                return ['value' => $r['BOM_ID'], 'label' => $r['MATERIAL_ID'].' — '.$r['BOM_ID'], 'description' => $r['SEARCH_DESCRIPTION'] ?? '', 'materialId' => $r['MATERIAL_ID'], 'bomId' => $r['BOM_ID'], 'bomDescription' => $r['BOM_DESCRIPTION'] ?? '', 'status' => $r['STATUS_ROW'] ?? '', 'fullDescEn' => $r['FULL_DESCRIPTION_EN'] ?? '', 'fullDescTh' => $r['FULL_DESCRIPTION_TH'] ?? '', 'uom' => $r['UOM'] ?? ''];
            })->unique('value')->values()->all();
    }

    public function rows(int $level, array $context): array
    {
        return array_values(array_filter($this->parents->existing($level, $context['fgBomId']), fn ($r) => $level === 2 || $r['semiFgLv2BomId'] === ($context['semiFgLv2BomId'] ?? '')));
    }

    public function save(int $level, array $input, string $user, string $role): void
    {
        DB::connection('oracle')->transaction(function () use ($level, $input, $user, $role) {
            $context = $this->context($level, $input, true);
            $this->components->requireWritable($context);
            $selected = collect($this->options($level, $input))->firstWhere('value', $input['componentId']);
            if (!$selected) throw ValidationException::withMessages(['componentId' => 'เลือก Existing BOM ที่ตรงกับ Brand/Mattype/Sub Mattype']);
            if ($this->parents->level($level, $context['fgBomId'], $selected['bomId'])) throw ValidationException::withMessages(['componentId' => 'BOM นี้ถูกเพิ่มแล้ว']);
            $fg = DB::connection('oracle')->table('PROJ1_2_DML_FG_MATTYPE_1')->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$context['fgMaterialId']])->first();
            $common = ['P_MATTYPE' => $input['mattype'], 'P_SUB_MATTYPE' => $input['subMattype'], 'P_FG_BOM_ID' => $context['fgBomId'], 'P_MATERIAL_ID_FG_1' => $context['fgMaterialId'], 'P_STATUS_ROW' => 'INS', 'P_USER_ROLE' => $role, 'P_USER_LOGIN' => $user];
            $bindings = $level === 2 ? [
                'P_SEMI_FG_L2_BOM_ID' => $selected['bomId'], 'P_SEMI_FG_L2_BOM_DESC' => $selected['bomDescription'], 'P_SEMI_FG_L2_ID' => $selected['materialId'], 'P_SEMI_FG_L2_ID_DESC' => $selected['description'],
                'P_SEMI_FG_L2_ID_FULL_DESC_EN' => $selected['fullDescEn'], 'P_SEMI_FG_L2_ID_FULL_DESC_TH' => $selected['fullDescTh'], 'P_UOM_SEMI_FG_L2ID' => $selected['uom'], 'P_SITE' => $fg->site ?? $fg->SITE ?? '',
            ] : [
                'P_SEMI_FG_L1_BOM_ID_EXIST' => $selected['bomId'], 'P_SEMI_FG_L1_BOM_DESC_EXIST' => $selected['bomDescription'], 'P_SEMI_FG_LV1_ID_EXIST' => $selected['materialId'], 'P_DESC_SEMI_FG_LV1_ID_EXIST' => $selected['description'],
                'P_SEMI_FG_L2_BOM_ID' => $context['semiFgLv2BomId'], 'P_SEMI_FG_L2_ID' => $context['semiFgLv2Id'], 'P_UOM_SEMI_FG_L1ID' => $selected['uom'],
            ];
            $this->components->callProcedure("PROJ1_2_SAVE_BOM_SEMI_L{$level}_EXIST", $common + $bindings);
        });
    }

    public function delete(int $level, array $input, string $user, string $role): void
    {
        DB::connection('oracle')->transaction(function () use ($level, $input, $user, $role) {
            $context = $this->context($level, $input, true);
            $this->components->requireWritable($context);
            $link = $this->parents->level($level, $context['fgBomId'], $input['componentId'], true, true);
            abort_unless($link && $link['no'] === $input['no'], 404);
            if ($link['userCreate'] !== $user || $link['statusRow'] !== 'INS' || ($level === 1 && $link['semiFgLv2BomId'] !== $context['semiFgLv2BomId'])) throw ValidationException::withMessages(['componentId' => 'รายการนี้ลบไม่ได้']);
            $db = DB::connection('oracle');
            $children = $level === 2 ? ['PROJ1_2_DML_SEMI_L1_BOM', 'PROJ1_2_DML_SEMI_L1_BOM_EXIST', 'PROJ1_2_DML_SEMI_L2_COMP_M5', 'PROJ1_2_DML_SEMI_L2_COMP_MATID'] : ['PROJ1_2_DML_SEMI_L1_COMP_ID'];
            foreach ($children as $table) {
                $q = $db->table($table)->whereRaw("TRIM(SEMI_FG_LV{$level}_BOM_ID) = ?", [$link['bomId']]);
                $q->whereRaw('TRIM(FG_BOM_ID) = ?', [$context['fgBomId']]);
                if ($q->exists()) throw ValidationException::withMessages(['componentId' => 'ลบรายการลูกก่อนลบ BOM นี้']);
            }
            foreach (['ID', 'BOM'] as $kind) {
                $table = "PROJ1_2_DML_SEMI_L{$level}_{$kind}_EXIST";
                $db->table($table)->whereRaw('TRIM(FG_BOM_ID) = ?', [$context['fgBomId']])->whereRaw("TRIM(SEMI_FG_LV{$level}_BOM_ID_EXIST) = ?", [$link['bomId']])->delete();
                $db->table('PROJ1_2_TRN_LOG')->insert(['TABLE_NAME_TRN' => $table, 'TYPE_DML_TRN' => 'DELETE', 'USER_DML_TRN' => $user, 'USER_ROLE_DML_TRN' => $role, 'TIME_DML_TRN' => DB::raw('CURRENT_TIMESTAMP')]);
            }
        });
    }
}
