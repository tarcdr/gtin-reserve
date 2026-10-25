<?php

namespace App\Http\Controllers;

use App\Services\ExistingComponentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RawChildComponentController extends PackMaterialController
{
    private function context(Request $request): array
    {
        $input = $request->validate([
            'fgMaterialId' => ['required', 'string'], 'fgBomId' => ['required', 'string'],
            'semiFgLv1BomId' => ['required', 'string'], 'masterMaterialId' => ['required', 'string'],
        ]);
        return app(ExistingComponentService::class)->context('raw4', $input);
    }

    private function rows(array $context)
    {
        return DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_M4_COMP_M4')->whereRaw('TRIM(SEMI_FG_LV1_BOM_ID) = ?', [$context['semiFgLv1BomId']])->whereRaw('TRIM(MASTER_MATERIAL_ID_M4) = ?', [$context['masterMaterialId']]);
    }

    private function back(array $context): string
    {
        return route('packmaterial.semi-fg-lv1-bom.new', ['actionMode' => 'view', 'levelMaterialId' => $context['levelMaterialId'], 'referentMaterialId' => $context['fgMaterialId'], 'componentId' => $context['masterMaterialId']]);
    }

    public function form(Request $request)
    {
        $context = $this->context($request);
        $mode = $request->validate(['actionMode' => ['nullable', 'in:create,view,edit,delete']])['actionMode'] ?? 'create';
        $row = null;
        if ($mode !== 'create') {
            $request->validate(['childId' => ['required', 'string']]);
            $record = $this->rows($context)->whereRaw('TRIM(COMP_M4_MATERIAL_ID_M4) = ?', [$request->childId])->first();
            abort_unless($record, 404);
            $row = array_change_key_case((array) $record, CASE_UPPER);
            if (empty($row['PROD_SUB_CAT'])) {
                // This is a generated New component: the existing generator prefixes IDs with the four-character product subcategory.
                $category = DB::connection('oracle')->table('PROJ1_2_COMP_SEMI_L1_V')->whereRaw('TRIM(CODE_SUB_CAT) = ?', [substr(trim($row['COMP_M4_MATERIAL_ID_M4']), 0, 4)])->first();
                if ($category) {
                    $c = array_change_key_case((array) $category, CASE_UPPER);
                    $row += ['SUB_MATTYPE' => $c['SUB_MATTYPE'], 'PRODUCT_CAT' => $c['CODE_PRODUCT_CAT'], 'PROD_SUB_CAT' => $c['CODE_SUB_CAT']];
                    foreach (['SUB_MATTYPE' => 'SUB_MATTYPE', 'PRODUCT_CAT' => 'CODE_PRODUCT_CAT', 'PROD_SUB_CAT' => 'CODE_SUB_CAT'] as $field => $source) $row[$field] = $row[$field] ?: $c[$source];
                }
            }
        }
        $InputData = $context + [
            'actionMode' => $mode, 'ownerLevel' => 'semiFgLv1', 'mattype' => '4', 'bomId' => $context['semiFgLv1BomId'], 'bomDesc' => $context['semiFgLv1BomDesc'],
            'semiFgLvBomId' => $context['semiFgLv1BomId'], 'semiFgLvBomDesc' => $context['semiFgLv1BomDesc'],
            'subMattype' => $row['SUB_MATTYPE'] ?? '', 'productCat' => $row['PRODUCT_CAT'] ?? '', 'productSubCat' => $row['PROD_SUB_CAT'] ?? '',
            'componentId' => $row['COMP_M4_MATERIAL_ID_M4'] ?? '', 'searchDesc' => $row['SEARCH_DESCRIPTION'] ?? '',
            'fullDescEn' => $row['FULL_DESCRIPTION_EN'] ?? '', 'fullDescTh' => $row['FULL_DESCRIPTION_TH'] ?? '', 'uom' => $row['UOM'] ?? '',
            'canEdit' => $context['canWrite'] && ($row === null || (($row['STATUS_ROW'] ?? '') === 'INS' && ($row['USER_CREATE'] ?? '') === $request->user()->user_login)),
        ];
        return Inertia::render('ExistingComponents/RawNew', ['InputData' => $InputData, 'subMattypes' => $this->packSubMattypes('4'), 'uoms' => $this->uoms, 'backUrl' => $this->back($context)]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'mattype' => ['required', 'in:4'], 'subMattype' => ['required', 'string'], 'productCat' => ['required', 'string'], 'productSubCat' => ['required', 'string'],
            'componentId' => ['required', 'string'], 'searchDesc' => ['required', 'string', 'max:40'], 'fullDescEn' => ['required', 'string'], 'fullDescTh' => ['required', 'string'], 'uom' => ['required', 'string'],
        ]);
        DB::connection('oracle')->transaction(function () use ($request, $data) {
            $context = $this->context($request);
            $service = app(ExistingComponentService::class);
            $service->requireWritable($service->context('raw4', $context, true));
            $category = DB::connection('oracle')->table('PROJ1_2_COMP_SEMI_L1_V')->whereRaw('TRIM(SUB_MATTYPE) = ?', [$data['subMattype']])->whereRaw('TRIM(CODE_PRODUCT_CAT) = ?', [$data['productCat']])->whereRaw('TRIM(CODE_SUB_CAT) = ?', [$data['productSubCat']])->exists();
            if (!str_starts_with($data['componentId'], $data['productSubCat'])) throw ValidationException::withMessages(['componentId' => 'Component ID ไม่ตรงกับ category ที่ Generate']);
            if (!$category) throw ValidationException::withMessages(['productSubCat' => 'Product category ไม่ตรงกับตัวเลือก']);
            $savedRecord = $this->rows($context)->whereRaw('TRIM(COMP_M4_MATERIAL_ID_M4) = ?', [$data['componentId']])->first();
            $saved = (bool) $savedRecord;
            if ($savedRecord) {
                $stored = array_change_key_case((array) $savedRecord, CASE_UPPER);
                if (($stored['PRODUCT_CAT'] ?? $data['productCat']) !== $data['productCat'] || ($stored['PROD_SUB_CAT'] ?? $data['productSubCat']) !== $data['productSubCat']) throw ValidationException::withMessages(['productSubCat' => 'เปลี่ยน category ของ component เดิมไม่ได้']);
            }
            if (!$saved && !DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_COMP_M4_TP')->whereRaw('TRIM(MATERIAL_ID_M4_TMP) = ?', [$data['componentId']])->whereRaw('TRIM(USER_CREATE_TMP) = ?', [$request->user()->user_login])->where(fn ($q) => $q->whereNull('STATUS_USED')->orWhere('STATUS_USED', '<>', 'SAVED'))->exists()) throw ValidationException::withMessages(['componentId' => 'Generate component ID ใหม่ก่อนบันทึก']);
            $service->callProcedure('PROJ1_2_SAVE_COMP_BOM_SL1_M4', [
                'P_BOM_SEMI_LV1_ID' => $context['semiFgLv1BomId'], 'P_MASTER_MATERIAL_ID_M4' => $context['masterMaterialId'],
                'P_MATTYPE' => '4', 'P_SUBMATTYPE' => $data['subMattype'], 'P_PRODUCT_CAT' => $data['productCat'], 'P_PROD_SUB_CAT' => $data['productSubCat'], 'P_SITE' => '',
                'P_COMPONENT_ID' => $data['componentId'], 'P_SEARCH_DESC' => $data['searchDesc'], 'P_COMP_DESC_EN' => $data['fullDescEn'], 'P_COMP_DESC_TH' => $data['fullDescTh'], 'P_UOM' => $data['uom'],
                'P_USER_ROLE' => $request->user()->role, 'P_USER' => $request->user()->user_login,
            ]);
        });
        return redirect($this->back($this->context($request)));
    }

    public function remove(Request $request)
    {
        $request->validate(['childId' => ['required', 'string']]);
        DB::connection('oracle')->transaction(function () use ($request) {
            $context = $this->context($request); $service = app(ExistingComponentService::class);
            $service->requireWritable($service->context('raw4', $context, true));
            $deleted = $this->rows($context)->whereRaw('TRIM(COMP_M4_MATERIAL_ID_M4) = ?', [$request->childId])->where('STATUS_ROW', 'INS')->where('USER_CREATE', $request->user()->user_login)->delete();
            if ($deleted !== 1) throw ValidationException::withMessages(['componentId' => 'รายการนี้ลบไม่ได้']);
            DB::connection('oracle')->table('PROJ1_2_TRN_LOG')->insert(['TABLE_NAME_TRN' => 'PROJ1_2_DML_SEMI_L1_M4_COMP_M4', 'TYPE_DML_TRN' => 'DELETE', 'USER_DML_TRN' => $request->user()->user_login, 'USER_ROLE_DML_TRN' => $request->user()->role, 'TIME_DML_TRN' => DB::raw('CURRENT_TIMESTAMP')]);
        });
        return redirect($this->back($this->context($request)));
    }
}
