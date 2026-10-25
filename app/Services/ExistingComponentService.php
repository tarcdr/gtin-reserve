<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDO;

class ExistingComponentService
{
    private const CONFIG = [
        'fg' => [
            'table' => 'PROJ1_2_DML_FG_COMP_MAT_ID', 'id' => 'COMPONENT_MAT_ID', 'description' => 'COMP_MAT_ID_DESCRIPTION',
            'view' => 'PROJ1_2_FG_COMP_MATID_LS', 'viewId' => 'M5_ID', 'viewDescription' => 'M5_DESCRIPTION',
            'procedure' => 'PROJ1_2_SAVE_COMP_BOMFG_ID',
        ],
        'semi2' => [
            'table' => 'PROJ1_2_DML_SEMI_L2_COMP_MATID', 'id' => 'MATERIAL_ID_M5_EXISTING', 'description' => 'MATERIAL_ID_M5_EXISTING_DESC',
            'view' => 'PROJ1_2_SEMI_L2_COM_EXS_ID_LS', 'viewId' => 'M5_ID', 'viewDescription' => 'M5_DESCRIPTION',
            'procedure' => 'PROJ1_2_SAVE_COMP_BOM_SEML2_ID',
        ],
        'semi1' => [
            'table' => 'PROJ1_2_DML_SEMI_L1_COMP_ID', 'id' => 'MATERIAL_ID_M4_EXIST', 'description' => 'MATERIAL_ID_M4_EXIST_DESC',
            'view' => 'PROJ1_2_SEMI_L1_COM_EXS_ID_LS', 'viewId' => 'M4_ID', 'viewDescription' => 'M4_DESCRIPTION',
            'procedure' => 'PROJ1_2_SAVE_COMP_BOM_SEML1_ID',
        ],
        'raw4' => [
            'table' => 'PROJ1_2_DML_SEMI_L1_M4_COMP_ID', 'id' => 'STORE_MATERIAL_ID_M4', 'description' => 'STORE_MATERIAL_ID_M4_DESC',
            'view' => 'PROJ1_2_COMP_M4_MAT_ID_M4_LS', 'viewId' => 'M4_COMP_M4_ID', 'viewDescription' => 'M4_COMP_M4_DESC',
            'procedure' => 'PROJ1_2_SAVE_COMP_BOM_SL1_M4ID',
        ],
    ];

    protected function config(string $scope): array
    {
        abort_unless(isset(self::CONFIG[$scope]), 404);
        return self::CONFIG[$scope];
    }

    public function availability(string $scope): array
    {
        $config = $this->config($scope);
        $names = [$config['table'], $config['view'], $config['procedure'], 'PROJ1_2_TRN_LOG'];
        if (in_array($scope, ['fg', 'semi1', 'semi2'], true)) {
            $prefix = $scope === 'fg' ? 'PROJ1_2_FG_COMP_MATID' : ($scope === 'semi1' ? 'PROJ1_2_SEMI_L1_COM_EXS' : 'PROJ1_2_SEMI_L2_COM_EXS');
            $names = array_merge($names, [$prefix.($scope === 'fg' ? '_MATYP_LS' : '_MAT_LS'), $prefix.($scope === 'fg' ? '_SUBMT_LS' : '_SUB_LS'), 'PROJ1_2_MASTER_MATERIAL']);
            if ($scope === 'semi2') $names[] = $prefix.'_CAT_LS';
        } else {
            $names[] = 'PROJ1_2_SAVE_INSUPD_ETS';
            $names[] = 'PROJ1_2_DML_SEMI_L1_M4_COMP_M4';
        }
        try {
            $objects = DB::connection('oracle')->table('USER_OBJECTS')->whereIn('OBJECT_NAME', $names)->get();
            $valid = $objects->filter(fn ($row) => strtoupper($row->status ?? $row->STATUS ?? '') === 'VALID')
                ->map(fn ($row) => strtoupper($row->object_name ?? $row->OBJECT_NAME ?? ''))->all();
            $missing = array_values(array_diff($names, $valid));
            if ($scope === 'semi1' && !DB::connection('oracle')->table('USER_ARGUMENTS')->where('OBJECT_NAME', $config['procedure'])->where('ARGUMENT_NAME', 'P_SEMI_FG_LV1_BOM_ID')->exists()) $missing[] = 'P_SEMI_FG_LV1_BOM_ID';
            return ['ready' => !$missing, 'reason' => $missing ? 'ยังไม่พร้อมใช้งาน กรุณาติดต่อผู้ดูแลระบบ' : '', 'objects' => $missing];
        } catch (\Throwable $exception) {
            report($exception);
            return ['ready' => false, 'reason' => 'ยังตรวจสอบความพร้อมไม่ได้ กรุณาลองใหม่', 'objects' => $names];
        }
    }

    public function requireReady(string $scope): void
    {
        $availability = $this->availability($scope);
        if (!$availability['ready']) {
            throw ValidationException::withMessages(['componentId' => $availability['reason']]);
        }
    }

    private function trimmed(Builder $query, string $column, string $value): Builder
    {
        return $query->whereRaw('TRIM('.$column.') = ?', [trim($value)]);
    }

    private function row(Builder $query, bool $lock): array
    {
        $record = ($lock ? $query->lockForUpdate() : $query)->first();
        abort_unless($record, 404);
        return array_map(fn ($value) => is_string($value) ? trim($value) : $value, array_change_key_case((array) $record, CASE_UPPER));
    }

    public function context(string $scope, array $input, bool $lock = false): array
    {
        $this->config($scope);
        $db = DB::connection('oracle');
        $fg = $this->row($this->trimmed($db->table('PROJ1_2_DML_FG_MATTYPE_1'), 'MATERIAL_ID_FG_1', $input['fgMaterialId']), $lock);
        // The current FG save flow stores its BOM on the material row; older records may use the separate BOM table.
        if (($fg['FG_BOM_ID'] ?? '') !== '') {
            abort_unless($fg['FG_BOM_ID'] === $input['fgBomId'], 404);
            $bom = $fg;
        } else {
            $bom = $this->row($this->trimmed($this->trimmed($db->table('PROJ1_2_DML_FG_BOM'), 'MATERIAL_ID_FG_1', $input['fgMaterialId']), 'FG_BOM_ID', $input['fgBomId']), $lock);
        }
        $context = [
            'fgMaterialId' => $input['fgMaterialId'], 'fgBomId' => $bom['FG_BOM_ID'],
            'fgBomDesc' => $bom['DESC_FG_BOM_ID'] ?? '', 'canWrite' => !in_array($fg['STATUS_ROW'] ?? 'INS', ['COM', 'ETS'], true) && !in_array($bom['STATUS_ROW'] ?? 'INS', ['COM', 'ETS'], true),
        ];
        if (in_array($scope, ['semi1', 'semi2', 'raw4'], true)) {
            $parents = app(BomContextService::class);
            $l1 = $scope !== 'semi2' ? $parents->level(1, $context['fgBomId'], $input['semiFgLv1BomId'], $lock) : null;
            if ($scope !== 'semi2') abort_unless($l1, 404);
            $l2 = $parents->level(2, $context['fgBomId'], $l1 ? $l1['semiFgLv2BomId'] : $input['semiFgLv2BomId'], $lock);
            abort_unless($l2, 404);
            $context += ['semiFgLv2BomId' => $l2['bomId'], 'semiFgLv2BomDesc' => $l2['bomDesc'], 'semiFgLv2Id' => $l2['id'], 'levelMaterialId' => $l2['id']];
            if ($l1) {
                $context['levelMaterialId'] = $l1['id'];
                $context += ['semiFgLv1BomId' => $l1['bomId'], 'semiFgLv1BomDesc' => $l1['bomDesc']];
            }
            foreach (array_filter([$l1, $l2]) as $parent) {
                $context['canWrite'] = $context['canWrite'] && !in_array($parent['statusRow'], ['COM', 'ETS'], true) && !in_array($parent['idStatus'], ['COM', 'ETS'], true);
            }
            if ($scope === 'raw4') {
                $master = $this->row($this->trimmed($this->trimmed($db->table('PROJ1_2_DML_SEMI_L1_COMP_M4'), 'SEMI_FG_LV1_BOM_ID', $l1['bomId']), 'MATERIAL_ID_M4', $input['masterMaterialId']), $lock);
                $context += ['masterMaterialId' => $master['MATERIAL_ID_M4'], 'masterDescription' => $master['SEARCH_DESCRIPTION'] ?? ''];
                $context['canWrite'] = $context['canWrite'] && $l1['sourceType'] !== 'existing' && !in_array($master['STATUS_ROW'] ?? 'INS', ['COM', 'ETS'], true);
            }
        }
        return $context;
    }

    public function filters(string $scope = 'fg'): array
    {
        $db = DB::connection('oracle');
        $prefix = $scope === 'fg' ? 'PROJ1_2_FG_COMP_MATID' : ($scope === 'semi1' ? 'PROJ1_2_SEMI_L1_COM_EXS' : 'PROJ1_2_SEMI_L2_COM_EXS');
        $typeView = $prefix.($scope === 'fg' ? '_MATYP_LS' : '_MAT_LS');
        $subView = $prefix.($scope === 'fg' ? '_SUBMT_LS' : '_SUB_LS');
        $result = [
            'mattypes' => $db->table($typeView)->selectRaw('TRIM(MATTYPE) as code')->distinct()->orderBy('code')->get()->map(fn ($row) => ['value' => $row->code, 'label' => $row->code])->all(),
            'subMattypes' => $db->table($subView)->selectRaw('TRIM(SUB_MATTYPE) as code'.(in_array($scope, ['fg', 'semi2'], true) ? ', TRIM(DESC_PROD_CAT) as description' : ''))->distinct()->orderBy('code')->get()->map(fn ($row) => ['value' => $row->code, 'label' => $row->code.(isset($row->description) ? ' - '.$row->description : '')])->all(),
        ];
        if ($scope === 'semi2') $result['productSubCats'] = $db->table($prefix.'_CAT_LS')->selectRaw('TRIM(CODE_SUB_CAT) as code, TRIM(DESC_PROD_SUB_CAT) as description')->distinct()->orderBy('code')->get()->map(fn ($row) => ['value' => $row->code, 'label' => $row->code.' - '.$row->description])->all();
        return $result;
    }

    public function options(string $scope, array $filters): array
    {
        $config = $this->config($scope);
        $query = DB::connection('oracle')->table($config['view'].' as choices');
        if (in_array($scope, ['fg', 'semi1', 'semi2'], true)) {
            if (empty($filters['mattype']) || !isset($filters['subMattype']) || $filters['subMattype'] === '') {
                return [];
            }
            $allowed = $this->filters($scope);
            if (!in_array($filters['mattype'], array_column($allowed['mattypes'], 'value'), true) || !in_array($filters['subMattype'], array_column($allowed['subMattypes'], 'value'), true)) {
                return [];
            }
            if ($scope === 'semi2' && !in_array($filters['productSubCat'] ?? '', array_column($allowed['productSubCats'], 'value'), true)) return [];
            // MAT_TYPE in this master is the combined category (e.g. 50 for Mattype 5 / Sub Mattype 0). SUB_TYPE is the product subcategory.
            // The list view only exposes ID and description, so join the master category rather than guessing from the ID.
            $query->whereExists(function (Builder $master) use ($config, $filters, $scope) {
                $master->selectRaw('1')->from('PROJ1_2_MASTER_MATERIAL as master')
                    ->whereRaw('TRIM(master.MATERIAL_ID) = TRIM(choices.'.$config['viewId'].')')
                    ->whereRaw('TRIM(master.MAT_TYPE) = ?', [$filters['mattype'].$filters['subMattype']]);
                if ($scope === 'semi2') $master->whereRaw('TRIM(master.MAT_TYPE) || TRIM(master.SUB_TYPE) = ?', [$filters['productSubCat'] ?? '']);
            });
        }
        return $query->selectRaw('TRIM('.$config['viewId'].') as code, TRIM('.$config['viewDescription'].') as description')
            ->distinct()->orderBy('code')->get()->map(fn ($row) => ['value' => $row->code, 'label' => $row->code, 'description' => $row->description ?? ''])->all();
    }

    private function scopedRows(string $scope, array $context): Builder
    {
        $config = $this->config($scope);
        $query = $this->trimmed(DB::connection('oracle')->table($config['table']), 'FG_BOM_ID', $context['fgBomId']);
        if (in_array($scope, ['semi1', 'semi2', 'raw4'], true)) {
            $fields = $scope === 'semi2' ? ['SEMI_FG_LV2_BOM_ID' => 'semiFgLv2BomId'] : ['SEMI_FG_LV2_BOM_ID' => 'semiFgLv2BomId', 'SEMI_FG_LV1_BOM_ID' => 'semiFgLv1BomId'];
            if ($scope === 'raw4') $fields['MASTER_MATERIAL_ID_M4'] = 'masterMaterialId';
            foreach ($fields as $column => $field) {
                $this->trimmed($query, $column, $context[$field]);
            }
        }
        return $query;
    }

    public function rows(string $scope, array $context): array
    {
        $config = $this->config($scope);
        $descriptions = $scope === 'raw4' ? array_column($this->options('raw4', []), 'description', 'value') : [];
        return $this->scopedRows($scope, $context)->orderBy('NO')->get()->map(function ($record) use ($config, $descriptions) {
            $row = array_change_key_case((array) $record, CASE_UPPER);
            return [
                'no' => trim((string) $row['NO']), 'code' => trim((string) $row[$config['id']]),
                'label' => trim((string) ($row[$config['description']] ?? $descriptions[trim((string) $row[$config['id']])] ?? '')), 'status' => trim((string) ($row['STATUS_ROW'] ?? '')),
                'userCreate' => trim((string) ($row['USER_CREATE'] ?? '')), 'sourceType' => 'matid',
            ];
        })->all();
    }

    public function rawNewRows(array $context): array
    {
        return $this->trimmed($this->trimmed(DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_M4_COMP_M4'), 'SEMI_FG_LV1_BOM_ID', $context['semiFgLv1BomId']), 'MASTER_MATERIAL_ID_M4', $context['masterMaterialId'])
            ->selectRaw('TRIM(COMP_M4_MATERIAL_ID_M4) as code, TRIM(SEARCH_DESCRIPTION) as description, TRIM(STATUS_ROW) as status, TRIM(USER_CREATE) as creator')
            ->orderBy('code')->get()->map(fn ($row) => ['code' => $row->code, 'label' => $row->description ?? '', 'status' => $row->status ?? '', 'userCreate' => $row->creator ?? ''])->all();
    }

    public function save(string $scope, array $input, string $user, string $role): void
    {
        $this->requireReady($scope);
        DB::connection('oracle')->transaction(function () use ($scope, $input, $user, $role) {
            $context = $this->context($scope, $input, true);
            $this->requireWritable($context);
            $selected = collect($this->options($scope, $input))->firstWhere('value', $input['componentId']);
            if (!$selected) {
                throw ValidationException::withMessages(['componentId' => 'กรุณาเลือก Material ID ที่ตรงกับเงื่อนไข']);
            }
            $config = $this->config($scope);
            if ($this->trimmed($this->scopedRows($scope, $context), $config['id'], $selected['value'])->exists()) {
                throw ValidationException::withMessages(['componentId' => 'Material ID นี้ถูกเพิ่มแล้ว']);
            }
            $bindings = $scope === 'fg' ? [
                'P_BOM_FG_ID' => $context['fgBomId'], 'P_COMPONENT_MAT_ID' => $selected['value'],
                'P_COMP_MAT_ID_DESCRIPTION' => $selected['description'], 'P_USER_ROLE' => $role, 'P_USER' => $user,
            ] : ($scope === 'raw4' ? [
                'P_STORE_MATERIAL_ID_M4' => $selected['value'], 'P_FG_BOM_ID' => $context['fgBomId'],
                'P_SEMI_FG_LV2_BOM_ID' => $context['semiFgLv2BomId'], 'P_SEMI_FG_LV1_BOM_ID' => $context['semiFgLv1BomId'],
                'P_MASTER_MATERIAL_ID_M4' => $context['masterMaterialId'], 'P_STATUS_ROW' => 'INS',
                'P_USER_ROLE' => $role, 'P_USER_CREATE' => $user, 'P_CREATE_DATE' => null, 'P_USER_UPDATE' => null, 'P_UPDATE_DATE' => null,
            ] : []);
            if ($scope === 'semi2' || $scope === 'semi1') {
                $bindings = [
                    'P_FG_BOM_ID' => $context['fgBomId'], 'P_MATERIAL_ID_FG_1' => $context['fgMaterialId'],
                    ($scope === 'semi1' ? 'P_SEMI_FG_LV1_BOM_ID' : 'P_SEMI_FG_LV2_ID') => $scope === 'semi1' ? $context['semiFgLv1BomId'] : $context['semiFgLv2Id'],
                    'P_SEMI_FG_LV2_BOM_ID' => $context['semiFgLv2BomId'], 'P_COMPONENT_ID_EXIST' => $selected['value'],
                    'P_COMPONENT_ID_EXIST_DESC' => $selected['description'], 'P_USER_ROLE' => $role, 'P_USER' => $user,
                ];
            }
            $this->execute($config['procedure'], $bindings);
        });
    }

    public function callProcedure(string $procedure, array $bindings): void
    {
        $this->execute($procedure, $bindings);
    }

    protected function execute(string $procedure, array $bindings): void
    {
        $statement = DB::connection('oracle')->getPdo()->prepare('BEGIN '.$procedure.'('.implode(', ', array_map(fn ($name) => $name.' => :'.$name, array_keys($bindings))).', P_ERROR => :P_ERROR); END;');
        foreach ($bindings as $name => $value) {
            $statement->bindValue(':'.$name, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
        $error = '';
        $statement->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
        $statement->execute();
        if (trim((string) $error) !== '') {
            throw ValidationException::withMessages(['componentId' => 'บันทึกไม่สำเร็จ: '.trim($error)]);
        }
    }

    public function requireWritable(array $context): void
    {
        if (!$context['canWrite']) {
            throw ValidationException::withMessages(['componentId' => 'ไม่สามารถแก้ไขข้อมูลที่ Complete หรือส่งออกแล้ว']);
        }
    }

    public function delete(string $scope, array $input, string $user, string $role): void
    {
        $this->requireReady($scope);
        DB::connection('oracle')->transaction(function () use ($scope, $input, $user, $role) {
            $context = $this->context($scope, $input, true);
            $this->requireWritable($context);
            $config = $this->config($scope);
            // Existing FG delete procedures target NEW components only. Delete the selected existing link, not the master material.
            $query = $this->trimmed($this->scopedRows($scope, $context), 'NO', $input['no']);
            $query = $this->trimmed($query, $config['id'], $input['componentId']);
            $query = $this->trimmed($query, 'USER_CREATE', $user);
            $query = $this->trimmed($query, 'STATUS_ROW', 'INS');
            if ($query->delete() !== 1) {
                throw ValidationException::withMessages(['componentId' => 'ไม่พบรายการที่ลบได้ หรือรายการนี้ไม่ได้สร้างโดยคุณ']);
            }
            DB::connection('oracle')->table('PROJ1_2_TRN_LOG')->insert([
                'TABLE_NAME_TRN' => $config['table'], 'TYPE_DML_TRN' => 'DELETE', 'USER_DML_TRN' => $user,
                'USER_ROLE_DML_TRN' => $role, 'TIME_DML_TRN' => DB::raw('CURRENT_TIMESTAMP'),
            ]);
        });
    }
}
