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
        if ($level === 1) $names = array_merge($names, ['PROJ1_2_SEMI_FG_L1_ID_EXIST_LS', 'PROJ1_MATERIAL', 'PROJ1_2_MASTER_MATERIAL', 'PROJ1_BRAND']);
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
        $subtypes = $db->table("PROJ1_2_SEMI_FG_LV{$level}_ID_V")->whereNotNull('SUB_MATTYPE')->selectRaw('TRIM(SUB_MATTYPE) as code')->distinct()->get()->pluck('code');
        if ($level === 1) $subtypes = $subtypes->merge($db->table($prefix.'_SUBMT_LS')->selectRaw('TRIM(SUB_MATTYPE) as code')->distinct()->get()->pluck('code'));
        return [
            'brands' => $db->table($prefix.'_BRAND_LS')->selectRaw('TRIM(BRAND_ABB) as code, TRIM(BRAND) as description')->distinct()->orderBy('code')->get()->map(fn ($r) => ['value' => $r->code, 'label' => $r->code.' - '.$r->description])->all(),
            'mattypes' => $db->table($prefix.($level === 2 ? '_MATYP_LS' : '_MATTY_LS'))->selectRaw('TRIM(MATTYPE) as code')->distinct()->get()->map(fn ($r) => ['value' => $r->code, 'label' => $r->code])->all(),
            'subMattypes' => $subtypes->filter(fn ($code) => $code !== null && $code !== '')->unique()->sort()->values()->map(fn ($code) => ['value' => $code, 'label' => $code])->all(),
        ];
    }

    public function options(int $level, array $input): array
    {
        foreach (['brand', 'mattype', 'subMattype'] as $field) if (!isset($input[$field]) || $input[$field] === '') return [];
        $db = DB::connection('oracle');
        $query = $db->table("PROJ1_2_SEMI_FG_LV{$level}_ID_V")
            ->whereRaw('TRIM(BRAND_ABB) = ?', [$input['brand']])->whereRaw('TRIM(MATTYPE) = ?', [$input['mattype']])->whereRaw('TRIM(SUB_MATTYPE) = ?', [$input['subMattype']]);
        if ($level === 1) {
            // ERP LOV membership and master attributes take precedence over a source FG's brand.
            $query->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('PROJ1_2_SEMI_FG_L1_ID_EXIST_LS as lov')
                    ->whereRaw('TRIM(lov.MATERIAL_ID) = TRIM(PROJ1_2_SEMI_FG_LV1_ID_V.MATERIAL_ID)');
            });
        }
        $records = $query->orderBy('MATERIAL_ID')->orderBy('BOM_ID')->get();
        if ($level === 1) {
            $legacy = $db->table('PROJ1_2_SEMI_FG_L1_ID_EXIST_LS as lov')
                ->leftJoin('PROJ1_MATERIAL as erp', fn ($j) => $j->whereRaw('TRIM(erp.MATERIAL_ID) = TRIM(lov.MATERIAL_ID)'))
                ->leftJoin('PROJ1_2_MASTER_MATERIAL as m', fn ($j) => $j->whereRaw('TRIM(m.MATERIAL_ID) = TRIM(lov.MATERIAL_ID)'))
                ->leftJoin('PROJ1_BRAND as br', fn ($j) => $j->whereRaw('TRIM(br.BRAND) = COALESCE(TRIM(erp.BRAND), TRIM(m.BRAND)) OR TRIM(br.BRAND_ABB) = COALESCE(TRIM(erp.BRAND), TRIM(m.BRAND))'))
                ->leftJoin('PROJ1_2_SEMI_FG_LV1_ID_V as d', function ($j) use ($input) {
                    $j->whereRaw('TRIM(d.MATERIAL_ID) = TRIM(lov.MATERIAL_ID)')
                        ->whereRaw('TRIM(d.MATTYPE) = ?', [$input['mattype']])->whereRaw('TRIM(d.SUB_MATTYPE) = ?', [$input['subMattype']]);
                })
                ->where(function ($q) use ($input) {
                    $q->where(function ($master) use ($input) {
                        $master->whereRaw('TRIM(br.BRAND_ABB) = ?', [$input['brand']])
                            ->where(function ($type) use ($input) {
                                $type->whereRaw('COALESCE(TRIM(erp.MAT_TYPE), TRIM(m.MAT_TYPE)) = ?', [$input['mattype'].$input['subMattype']])
                                    ->orWhere(fn ($split) => $split->whereRaw('COALESCE(TRIM(erp.MAT_TYPE), TRIM(m.MAT_TYPE)) = ?', [$input['mattype']])->whereRaw('COALESCE(TRIM(erp.SUB_TYPE), TRIM(m.SUB_TYPE)) = ?', [$input['subMattype']]));
                            });
                    })->orWhere(fn ($fallback) => $fallback->whereNull('m.MATERIAL_ID')->whereNull('erp.MATERIAL_ID')->whereRaw('TRIM(d.BRAND_ABB) = ?', [$input['brand']]));
                })
                ->selectRaw('TRIM(lov.MATERIAL_ID) as MATERIAL_ID, COALESCE(TRIM(d.SEARCH_DESCRIPTION), TRIM(lov.MATERIAL_DESCRIPTION)) as SEARCH_DESCRIPTION, d.BOM_ID, d.BOM_DESCRIPTION, d.STATUS_ROW, d.FULL_DESCRIPTION_EN, d.FULL_DESCRIPTION_TH, d.UOM')
                ->distinct()->get();
            $records = $records->concat($legacy);
        }
        return $records->map(function ($record) {
            $r = array_map(fn ($v) => is_string($v) ? trim($v) : $v, array_change_key_case((array) $record, CASE_UPPER));
            $bom = $r['BOM_ID'] ?? '';
            return ['value' => $bom ?: 'unavailable:'.$r['MATERIAL_ID'], 'label' => $r['MATERIAL_ID'].($bom ? ' — '.$bom : ''),
                'selectable' => $bom !== '', 'unavailableReason' => $bom ? '' : 'ไม่มี BOM ต้นทาง',
                'description' => $r['SEARCH_DESCRIPTION'] ?? '', 'materialId' => $r['MATERIAL_ID'], 'bomId' => $bom,
                'bomDescription' => $r['BOM_DESCRIPTION'] ?? '', 'status' => $r['STATUS_ROW'] ?? '', 'fullDescEn' => $r['FULL_DESCRIPTION_EN'] ?? '', 'fullDescTh' => $r['FULL_DESCRIPTION_TH'] ?? '', 'uom' => $r['UOM'] ?? ''];
        })->unique('value')->sortBy('label')->values()->all();
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
            if (!$selected || !$selected['selectable']) throw ValidationException::withMessages(['componentId' => 'เลือก Existing BOM ที่ตรงกับ Brand/Mattype/Sub Mattype']);
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
