<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\FgBomDml;
use App\Models\FgMaterialDml;
use App\Models\MasterUOM;
use App\Models\Proj12MasterBomSemiLv1V;
use App\Models\Proj12MasterBomSemiLv2V;
use App\Models\Proj12DmlSemiL2CompM5;
use App\Models\Proj12SemiFgLv2Bom;
use App\Models\Proj12SemiFgLv2Id;
use PDO;

class MaterialLevelController extends Controller
{
  protected $mattypes;
  protected $subMattypes;
  protected $uoms;

  public function __construct()
  {
    $this->mattypes = [
      ["code" => "1", "label" => "1"],
      ["code" => "5", "label" => "5"],
      ["code" => "7", "label" => "7"],
      ["code" => "8", "label" => "8"],
      ["code" => "9", "label" => "9"],
    ];
    $this->subMattypes = [
      ["code" => "0", "label" => "0"],
      ["code" => "1", "label" => "1"],
      ["code" => "2", "label" => "2"],
      ["code" => "3", "label" => "3"],
    ];
    $this->uoms = MasterUOM::all()->map(function ($b) {
      return [
        "value" => $b->code_uom,
        "label" => $b->description_uom,
      ];
    })->toArray();
  }

  protected function persistFgDraft(Request $request, array $fgDetail): void
  {
    $key = $fgDetail['materialId'] ?? $request->get('fgMaterialId') ?? $fgDetail['bomId'] ?? $request->get('fgBomId');

    if ($key) {
      $request->session()->put("product_drafts.{$key}", $fgDetail);
    }
  }

  protected function resolveFgBomByMaterialId(?string $materialId): array
  {
    $materialId = trim((string) $materialId);

    if ($materialId === '') {
      return [
        'fgBomId' => '',
        'fgBomDesc' => '',
      ];
    }

    $materialRow = FgMaterialDml::query()
      ->selectRaw('TRIM(fg_bom_id) as fg_bom_id, TRIM(desc_fg_bom_id) as desc_fg_bom_id')
      ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
      ->first();

    if ($materialRow) {
      return [
        'fgBomId' => trim((string) ($materialRow->fg_bom_id ?? '')),
        'fgBomDesc' => trim((string) ($materialRow->desc_fg_bom_id ?? '')),
      ];
    }

    $bomRow = FgBomDml::query()
      ->selectRaw('TRIM(FG_BOM_ID) as fg_bom_id, TRIM(DESC_FG_BOM_ID) as desc_fg_bom_id')
      ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
      ->first();

    return [
      'fgBomId' => trim((string) ($bomRow->fg_bom_id ?? '')),
      'fgBomDesc' => trim((string) ($bomRow->desc_fg_bom_id ?? '')),
    ];
  }

  protected function baseInput(Request $request, string $table, string $mattype, string $subMattype): array
  {
    $fgMaterialId = $request->get('referentMaterialId') ?? $request->get('materialId') ?? $request->get('fgMaterialId');
    $resolvedFgBom = $this->resolveFgBomByMaterialId($fgMaterialId);

    return [
      'mode'         => $request->get('mode', $request->get('levelMaterialId') ? 'view' : 'create'),
      'fgDetail'     => $request->get('fgDetail', []),
      'fgMaterialId' => $fgMaterialId,
      'fgBomId'      => $request->get('bomId') ?? $request->get('fgBomId') ?? $resolvedFgBom['fgBomId'],
      'fgBomDesc'    => $request->get('bomDesc') ?? $request->get('fgBomDesc') ?? $resolvedFgBom['fgBomDesc'],
      'parentMattype'=> $request->get('parentMattype') ?? $request->get('mattype'),
      'parentSubMattype' => $request->get('parentSubMattype') ?? $request->get('subMattype'),
      'mattype'      => $mattype,
      'subMattype'   => $subMattype,
      'materialId'   => $request->get('levelMaterialId') ?? '',
      'referentMaterialId' => $request->get('referentMaterialId') ?? $request->get('materialId') ?? $request->get('fgMaterialId'),
      'searchDesc'   => $request->get('searchDesc') ?? '',
      'fullDescEn'   => $request->get('fullDescEn') ?? '',
      'fullDescTh'   => $request->get('fullDescTh') ?? '',
      'uom'          => $request->get('uom') ?? '',
      'storageTable' => $table,
    ];
  }

  protected function fetchSubMattypesFromView(string $viewName, array $fallback = []): array
  {
    $viewName = trim($viewName);

    if ($viewName === '') {
      return $fallback;
    }

    try {
      $modelClass = match ($viewName) {
        'PROJ1_2_MASTER_BOM_SEMI_LV1_V' => Proj12MasterBomSemiLv1V::class,
        'PROJ1_2_MASTER_BOM_SEMI_LV2_V' => Proj12MasterBomSemiLv2V::class,
        default => null,
      };

      if ($modelClass === null) {
        return $fallback;
      }

      $sampleRow = $modelClass::query()->selectRaw('*')->first();

      if (!$sampleRow) {
        return $fallback;
      }

      $availableColumns = array_change_key_case((array) $sampleRow, CASE_UPPER);
      $candidateColumns = [
        ['code' => 'SUB_TYPE', 'label' => null],
        ['code' => 'SUBTYPE', 'label' => null],
        ['code' => 'SUB_MATTYPE', 'label' => 'DESCRIPTION'],
        ['code' => 'SUB_MATTYPE', 'label' => 'DESC'],
        ['code' => 'SUB_MATTYPE', 'label' => 'SUB_MATTYPE_DESC'],
        ['code' => 'SUB_MATTYPE', 'label' => 'SUBMATTYPE_DESC'],
        ['code' => 'SUB_MATTYPE', 'label' => 'SUB_MATTYPE_LABEL'],
        ['code' => 'SUB_MATTYPE', 'label' => 'SUB_MATTYPE_NAME'],
        ['code' => 'SUB_MATTYPE', 'label' => 'DESCRIPTION_SUB_MATTYPE'],
        ['code' => 'SUB_MATTYPE', 'label' => 'SHORT_DESCRIPTION'],
        ['code' => 'SUB_MATTYPE', 'label' => 'LONG_DESCRIPTION'],
        ['code' => 'SUB_MATTYPE', 'label' => null],
        ['code' => 'SUBMATTYPE', 'label' => 'DESCRIPTION'],
        ['code' => 'SUBMATTYPE', 'label' => 'DESC'],
        ['code' => 'SUBMATTYPE', 'label' => 'SUB_MATTYPE_DESC'],
        ['code' => 'SUBMATTYPE', 'label' => 'SUBMATTYPE_DESC'],
        ['code' => 'SUBMATTYPE', 'label' => 'SUB_MATTYPE_LABEL'],
        ['code' => 'SUBMATTYPE', 'label' => 'SUB_MATTYPE_NAME'],
        ['code' => 'SUBMATTYPE', 'label' => 'DESCRIPTION_SUB_MATTYPE'],
        ['code' => 'SUBMATTYPE', 'label' => 'SHORT_DESCRIPTION'],
        ['code' => 'SUBMATTYPE', 'label' => 'LONG_DESCRIPTION'],
        ['code' => 'SUBMATTYPE', 'label' => null],
        ['code' => 'SUB_MATTYPE_CODE', 'label' => 'DESCRIPTION'],
        ['code' => 'SUB_MATTYPE_CODE', 'label' => 'DESC'],
        ['code' => 'SUB_MATTYPE_CODE', 'label' => 'SUB_MATTYPE_DESC'],
        ['code' => 'SUB_MATTYPE_CODE', 'label' => 'SUBMATTYPE_DESC'],
        ['code' => 'SUB_MATTYPE_CODE', 'label' => null],
        ['code' => 'SUB_MATTYPE_ID', 'label' => null],
        ['code' => 'SUB_MATTYPE_NO', 'label' => null],
        ['code' => 'SUB_MATTYPE_VALUE', 'label' => null],
      ];

      foreach ($candidateColumns as $candidate) {
        if (!array_key_exists($candidate['code'], $availableColumns)) {
          continue;
        }

        $labelColumn = $candidate['label'] && array_key_exists($candidate['label'], $availableColumns)
          ? $candidate['label']
          : $candidate['code'];

        $rows = $modelClass::query()
          ->selectRaw("TRIM({$candidate['code']}) as code, TRIM({$labelColumn}) as label")
          ->whereRaw("TRIM({$candidate['code']}) IS NOT NULL")
          ->distinct()
          ->orderByRaw("TRIM({$candidate['code']})")
          ->get()
          ->map(function ($row) {
            $code = trim((string) ($row->code ?? ''));
            $label = trim((string) ($row->label ?? $code));

            return [
              'code' => $code,
              'label' => $label !== '' ? $label : $code,
            ];
          })
          ->filter(fn ($row) => $row['code'] !== '')
          ->values()
          ->all();

        if (!empty($rows)) {
          return $rows;
        }
      }
    } catch (\Throwable $exception) {
      Log::warning('material-levels.sub-mattypes.lookup.failed', [
        'viewName' => $viewName,
        'error' => $exception->getMessage(),
      ]);
    }

    return $fallback;
  }

  protected function resolveComponents(Request $request, array $fallback = []): array
  {
    return collect($request->get('components', $fallback))
      ->filter(fn($item) => is_array($item) && !empty($item['code']))
      ->keyBy('code')
      ->values()
      ->all();
  }

  protected function loadSemiFgLv2Components(?string $levelMaterialId = null, ?string $fgBomId = null, ?string $levelBomId = null): array
  {
    $levelMaterialId = trim((string) $levelMaterialId);
    $fgBomId = trim((string) $fgBomId);
    $levelBomId = trim((string) $levelBomId);

    try {
      $rows = Proj12DmlSemiL2CompM5::query()
        ->selectRaw('
          TRIM(NO) as no,
          TRIM(MATERIAL_ID_M5) as code,
          TRIM(SEARCH_DESCRIPTION) as label,
          TRIM(FULL_DESCRIPTION_EN) as full_desc_en,
          TRIM(FULL_DESCRIPTION_TH) as full_desc_th,
          TRIM(SEMI_FG_LV2_BOM_NO) as semi_fg_lv2_bom_no,
          TRIM(SITE) as site,
          TRIM(UOM) as uom,
          TRIM(STATUS) as status,
          TRIM(STATUS_ROW) as status_row,
          TRIM(USER_ROLE) as user_role,
          TRIM(USER_CREATE) as user_create,
          TRIM(CREATE_DATE) as create_date,
          TRIM(USER_UPDATE) as user_update,
          TRIM(UPDATE_DATE) as update_date
        ')
        ->when($levelMaterialId !== '', fn ($query) => $query->whereRaw('TRIM(SEMI_FG_LV2_BOM_NO) = ?', [$levelMaterialId]))
        ->when($levelMaterialId === '' && $fgBomId !== '', fn ($query) => $query->whereRaw('TRIM(SEMI_FG_LV2_BOM_NO) = ?', [$fgBomId]))
        ->when($levelMaterialId === '' && $fgBomId === '' && $levelBomId !== '', fn ($query) => $query->whereRaw('TRIM(SEMI_FG_LV2_BOM_NO) = ?', [$levelBomId]))
        ->orderByRaw('TRIM(MATERIAL_ID_M5)')
        ->get();

      return $rows
        ->map(function ($row) {
          $record = $row instanceof \Illuminate\Database\Eloquent\Model
            ? $row->getAttributes()
            : (array) $row;

          $source = array_change_key_case($record, CASE_LOWER);
          $code = trim((string) ($source['code'] ?? ''));
          $label = trim((string) ($source['label'] ?? $code));

          return [
            'code' => $code,
            'label' => $label !== '' ? $label : $code,
            'status' => trim((string) ($source['status'] ?? $source['status_row'] ?? 'INS')),
            'searchDesc' => trim((string) ($source['label'] ?? '')),
            'fullDescEn' => trim((string) ($source['full_desc_en'] ?? '')),
            'fullDescTh' => trim((string) ($source['full_desc_th'] ?? '')),
            'uom' => trim((string) ($source['uom'] ?? '')),
            'productSubCat' => $code !== '' ? substr($code, 0, 4) : '',
            'productCat' => $code !== '' ? substr($code, 0, 2) : '',
          ];
        })
        ->filter(fn ($item) => trim((string) ($item['code'] ?? '')) !== '')
        ->values()
        ->all();
    } catch (\Throwable $exception) {
      Log::warning('material-levels.semi-fg-lv2.components.lookup.failed', [
        'levelMaterialId' => $levelMaterialId,
        'fgBomId' => $fgBomId,
        'error' => $exception->getMessage(),
      ]);

      return [];
    }
  }

  protected function loadSemiFgLv2ByFgBomId(?string $fgBomId): ?array
  {
    $fgBomId = trim((string) $fgBomId);

    if ($fgBomId === '') {
      return null;
    }

    $row = Proj12SemiFgLv2Id::query()
      ->selectRaw('
        TRIM(FG_BOM_ID) as fg_bom_id,
        TRIM(SEMI_FG_LV2_ID) as semi_fg_lv2_id,
        TRIM(DESC_SEMI_FG_LV2_ID) as desc_semi_fg_lv2_id,
        TRIM(FULL_DESC_SEMI_FG_LV2_EN) as full_desc_semi_fg_lv2_en,
        TRIM(FULL_DESC_SEMI_FG_LV2_TH) as full_desc_semi_fg_lv2_th,
        TRIM(MATTYPE_SEMI_FG_L2ID) as mattype_semi_fg_l2id,
        TRIM(SUB_MATTYPE_SEMI_FG_L2ID) as sub_mattype_semi_fg_l2id,
        TRIM(UOM_SEMI_FG_L2ID) as uom_semi_fg_l2id,
        TRIM(MATERIAL_ID_FG_1) as material_id_fg_1,
        TRIM(SITE) as site,
        TRIM(STATUS_ROW) as status_row
      ')
      ->whereRaw('TRIM(FG_BOM_ID) = ?', [$fgBomId])
      ->first();

    if (!$row) {
      return null;
    }

    return [
      'bomId' => trim((string) ($row->semi_fg_lv2_id ?? '')),
      'bomDesc' => trim((string) ($row->desc_semi_fg_lv2_id ?? '')),
      'id' => trim((string) ($row->semi_fg_lv2_id ?? '')),
      'desc' => trim((string) ($row->desc_semi_fg_lv2_id ?? '')),
      'searchDesc' => trim((string) ($row->desc_semi_fg_lv2_id ?? '')),
      'fullDescEn' => trim((string) ($row->full_desc_semi_fg_lv2_en ?? '')),
      'fullDescTh' => trim((string) ($row->full_desc_semi_fg_lv2_th ?? '')),
      'uom' => trim((string) ($row->uom_semi_fg_l2id ?? '')),
      'mattype' => trim((string) ($row->mattype_semi_fg_l2id ?? '')),
      'subMattype' => trim((string) ($row->sub_mattype_semi_fg_l2id ?? '')),
      'materialIdFg1' => trim((string) ($row->material_id_fg_1 ?? '')),
      'site' => trim((string) ($row->site ?? '')),
      'components' => [],
      'statusRow' => trim((string) ($row->status_row ?? '')),
    ];
  }

  protected function loadSemiFgLv2ByMaterialId(?string $levelMaterialId): ?array
  {
    $levelMaterialId = trim((string) $levelMaterialId);

    if ($levelMaterialId === '') {
      return null;
    }

    $row = Proj12SemiFgLv2Id::query()
      ->selectRaw('
        TRIM(FG_BOM_ID) as fg_bom_id,
        TRIM(SEMI_FG_LV2_ID) as semi_fg_lv2_id,
        TRIM(DESC_SEMI_FG_LV2_ID) as desc_semi_fg_lv2_id,
        TRIM(FULL_DESC_SEMI_FG_LV2_EN) as full_desc_semi_fg_lv2_en,
        TRIM(FULL_DESC_SEMI_FG_LV2_TH) as full_desc_semi_fg_lv2_th,
        TRIM(MATTYPE_SEMI_FG_L2ID) as mattype_semi_fg_l2id,
        TRIM(SUB_MATTYPE_SEMI_FG_L2ID) as sub_mattype_semi_fg_l2id,
        TRIM(UOM_SEMI_FG_L2ID) as uom_semi_fg_l2id,
        TRIM(MATERIAL_ID_FG_1) as material_id_fg_1,
        TRIM(SITE) as site,
        TRIM(STATUS_ROW) as status_row
      ')
      ->whereRaw('TRIM(SEMI_FG_LV2_ID) = ?', [$levelMaterialId])
      ->first();

    if (!$row) {
      return null;
    }

    return [
      'fgBomId' => trim((string) ($row->fg_bom_id ?? '')),
      'bomId' => '',
      'bomDesc' => '',
      'id' => trim((string) ($row->semi_fg_lv2_id ?? '')),
      'desc' => trim((string) ($row->desc_semi_fg_lv2_id ?? '')),
      'searchDesc' => trim((string) ($row->desc_semi_fg_lv2_id ?? '')),
      'fullDescEn' => trim((string) ($row->full_desc_semi_fg_lv2_en ?? '')),
      'fullDescTh' => trim((string) ($row->full_desc_semi_fg_lv2_th ?? '')),
      'uom' => trim((string) ($row->uom_semi_fg_l2id ?? '')),
      'mattype' => trim((string) ($row->mattype_semi_fg_l2id ?? '')),
      'subMattype' => trim((string) ($row->sub_mattype_semi_fg_l2id ?? '')),
      'materialIdFg1' => trim((string) ($row->material_id_fg_1 ?? '')),
      'site' => trim((string) ($row->site ?? '')),
      'components' => [],
      'statusRow' => trim((string) ($row->status_row ?? '')),
    ];
  }

  protected function loadSemiFgLv2DraftByFgMaterialId(?string $fgMaterialId): ?array
  {
    $fgMaterialId = trim((string) $fgMaterialId);

    if ($fgMaterialId === '') {
      return null;
    }

    $draft = session()->get("product_drafts.{$fgMaterialId}.semiFgLv2");

    if (!is_array($draft)) {
      return null;
    }

    return [
      'bomId' => trim((string) ($draft['bomId'] ?? '')),
      'bomDesc' => trim((string) ($draft['bomDesc'] ?? '')),
      'id' => trim((string) ($draft['id'] ?? '')),
      'desc' => trim((string) ($draft['desc'] ?? '')),
      'searchDesc' => trim((string) ($draft['searchDesc'] ?? $draft['desc'] ?? '')),
      'fullDescEn' => trim((string) ($draft['fullDescEn'] ?? '')),
      'fullDescTh' => trim((string) ($draft['fullDescTh'] ?? '')),
      'uom' => trim((string) ($draft['uom'] ?? '')),
      'components' => is_array($draft['components'] ?? null) ? $draft['components'] : [],
    ];
  }

  protected function loadSemiFgLv2BomById(?string $levelMaterialId): ?array
  {
    $levelMaterialId = trim((string) $levelMaterialId);

    if ($levelMaterialId === '') {
      return null;
    }

    try {
      $idRow = Proj12SemiFgLv2Id::query()
        ->selectRaw('
          TRIM(FG_BOM_ID) as fg_bom_id,
          TRIM(MATERIAL_ID_FG_1) as material_id_fg_1
        ')
        ->whereRaw('TRIM(SEMI_FG_LV2_ID) = ?', [$levelMaterialId])
        ->first();

      if (!$idRow) {
        return null;
      }

      $row = Proj12SemiFgLv2Bom::query()
        ->selectRaw('
          TRIM(SEMI_FG_LV2_BOM_ID) as semi_fg_lv2_bom_id,
          TRIM(DESC_SEMI_FG_LV2_BOM_ID) as semi_fg_lv2_bom_desc,
          TRIM(FG_BOM_ID) as fg_bom_id,
          TRIM(MATERIAL_ID_FG_1) as material_id_fg_1
        ')
        ->whereRaw('TRIM(FG_BOM_ID) = ?', [trim((string) ($idRow->fg_bom_id ?? ''))])
        ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [trim((string) ($idRow->material_id_fg_1 ?? ''))])
        ->first();
    } catch (\Throwable $e) {
      Log::debug('material-levels.load-semi-fg-lv2-bom.failed', [
        'levelMaterialId' => $levelMaterialId,
        'error' => $e->getMessage(),
      ]);
      return null;
    }

    if (!$row) {
      return null;
    }

    return [
      'bomId' => trim((string) ($row->semi_fg_lv2_bom_id ?? '')),
      'bomDesc' => trim((string) ($row->semi_fg_lv2_bom_desc ?? '')),
    ];
  }

  protected function loadSemiFgLv1DraftByFgMaterialId(?string $fgMaterialId): ?array
  {
    $fgMaterialId = trim((string) $fgMaterialId);

    if ($fgMaterialId === '') {
      return null;
    }

    $draft = session()->get("product_drafts.{$fgMaterialId}.semiFgLv1");

    if (!is_array($draft)) {
      return null;
    }

    return [
      'id' => trim((string) ($draft['id'] ?? '')),
      'desc' => trim((string) ($draft['desc'] ?? '')),
      'searchDesc' => trim((string) ($draft['searchDesc'] ?? $draft['desc'] ?? '')),
      'fullDescEn' => trim((string) ($draft['fullDescEn'] ?? '')),
      'fullDescTh' => trim((string) ($draft['fullDescTh'] ?? '')),
      'uom' => trim((string) ($draft['uom'] ?? '')),
      'components' => is_array($draft['components'] ?? null) ? $draft['components'] : [],
    ];
  }

  protected function callSaveSemiFgLevel2Procedure(Request $request, array $inputData, ?string $userLogin = null, ?string $userRole = null): void
  {
    $pdo = DB::connection('oracle')->getPdo();
    $error = null;
    $userLogin = $userLogin ?: 'system';
    $userRole = $userRole ?: 'GTIN';
    $mode = trim((string) ($inputData['mode'] ?? $request->get('mode', 'create')));
    $statusRow = trim((string) ($request->get('statusRow') ?? ($mode === 'create' ? 'INS' : 'UPD')));
    $fgDetail = is_array($inputData['fgDetail'] ?? null) ? $inputData['fgDetail'] : [];

    $semiFgL2BomId = trim((string) ($inputData['levelBomId'] ?? $request->get('levelBomId') ?? $request->get('bomId') ?? $fgDetail['semiFgLv2']['bomId'] ?? ''));
    $semiFgL2BomDesc = trim((string) ($inputData['levelBomDesc'] ?? $request->get('levelBomDesc') ?? $request->get('bomDesc') ?? $fgDetail['semiFgLv2']['bomDesc'] ?? ''));
    $semiFgL2Id = trim((string) ($inputData['levelMaterialId'] ?? $request->get('levelMaterialId') ?? $request->get('materialId') ?? $fgDetail['semiFgLv2']['id'] ?? ''));
    $semiFgL2IdDesc = trim((string) ($inputData['searchDesc'] ?? $request->get('searchDesc') ?? $fgDetail['semiFgLv2']['searchDesc'] ?? ''));
    $semiFgL2IdFullDescEn = trim((string) ($inputData['fullDescEn'] ?? $request->get('fullDescEn') ?? $fgDetail['semiFgLv2']['fullDescEn'] ?? ''));
    $semiFgL2IdFullDescTh = trim((string) ($inputData['fullDescTh'] ?? $request->get('fullDescTh') ?? $fgDetail['semiFgLv2']['fullDescTh'] ?? ''));
    $fgBomId = trim((string) ($inputData['fgBomId'] ?? $request->get('fgBomId') ?? $request->get('bomId') ?? $fgDetail['bomId'] ?? ''));
    $materialIdFg1 = trim((string) ($inputData['fgMaterialId'] ?? $request->get('fgMaterialId') ?? $request->get('materialId') ?? $request->get('referentMaterialId') ?? $fgDetail['materialId'] ?? ''));
    $site = trim((string) ($inputData['site'] ?? $request->get('site') ?? $fgDetail['site'] ?? ''));
    $uom = trim((string) ($inputData['uom'] ?? $request->get('uom') ?? $fgDetail['semiFgLv2']['uom'] ?? ''));

    Log::debug('material-levels.semi-fg-lv2.save.start', [
      'mode' => $mode,
      'statusRow' => $statusRow,
      'semiFgL2BomId' => $semiFgL2BomId,
      'semiFgL2Id' => $semiFgL2Id,
      'fgBomId' => $fgBomId,
      'materialIdFg1' => $materialIdFg1,
    ]);

    $stmt = $pdo->prepare('BEGIN proj1_2_save_bom_semi_l2(:P_MATTYPE, :P_SUB_MATTYPE, :P_SEMI_FG_L2_BOM_ID, :P_SEMI_FG_L2_BOM_DESC, :P_SEMI_FG_L2_ID, :P_SEMI_FG_L2_ID_DESC, :P_SEMI_FG_L2_ID_FULL_DESC_EN, :P_SEMI_FG_L2_ID_FULL_DESC_TH, :P_FG_BOM_ID, :P_MATERIAL_ID_FG_1, :P_SITE, :P_UOM_SEMI_FG_L2ID, :P_STATUS_ROW, :P_USER_ROLE, :P_USER_LOGIN, :P_ERROR); END;');
    $stmt->bindValue(':P_MATTYPE', (string) ($inputData['mattype'] ?? $request->get('mattype', '2')), PDO::PARAM_STR);
    $stmt->bindValue(':P_SUB_MATTYPE', (string) ($inputData['subMattype'] ?? $request->get('subMattype', '')), PDO::PARAM_STR);
    $stmt->bindValue(':P_SEMI_FG_L2_BOM_ID', $semiFgL2BomId, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEMI_FG_L2_BOM_DESC', $semiFgL2BomDesc, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEMI_FG_L2_ID', $semiFgL2Id, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEMI_FG_L2_ID_DESC', $semiFgL2IdDesc, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEMI_FG_L2_ID_FULL_DESC_EN', $semiFgL2IdFullDescEn, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEMI_FG_L2_ID_FULL_DESC_TH', $semiFgL2IdFullDescTh, PDO::PARAM_STR);
    $stmt->bindValue(':P_FG_BOM_ID', $fgBomId, PDO::PARAM_STR);
    $stmt->bindValue(':P_MATERIAL_ID_FG_1', $materialIdFg1, PDO::PARAM_STR);
    $stmt->bindValue(':P_SITE', $site, PDO::PARAM_STR);
    $stmt->bindValue(':P_UOM_SEMI_FG_L2ID', $uom, PDO::PARAM_STR);
    $stmt->bindValue(':P_STATUS_ROW', $statusRow, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER_ROLE', (string) $userRole, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER_LOGIN', (string) $userLogin, PDO::PARAM_STR);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    $resolvedError = $this->resolveProcedureErrorMessage($error);
    if (trim($resolvedError) !== '') {
      Log::warning('material-levels.semi-fg-lv2.save.failed', [
        'error' => $error,
        'resolvedError' => $resolvedError,
      ]);

      throw ValidationException::withMessages([
        'levelBomId' => $resolvedError,
      ]);
    }
  }

  protected function mockComponents(string $level): array
  {
    $map = [
      'raw' => [
        ['code' => 'RM0001', 'label' => 'Raw Material A', 'status' => 'ACTIVE'],
        ['code' => 'RM0002', 'label' => 'Raw Material B', 'status' => 'HOLD'],
      ],
      'lv2' => [
        ['code' => 'S2-0001', 'label' => 'Semi FG Lv2 A', 'status' => 'INS'],
      ],
      'lv1' => [
        ['code' => 'S1-0001', 'label' => 'Semi FG Lv1 A', 'status' => 'DRAFT'],
      ],
      'bns' => [
        ['code' => 'BS-0001', 'label' => 'Business Supply A', 'status' => 'ACTIVE'],
      ],
    ];

    return $map[$level] ?? [];
  }

  public function rawMaterial(Request $request): Response
  {
    $InputData = [
      'bomId'      => $request->get('bomId'),
      'bomDesc'    => $request->get('bomDesc'),
      'mattype'    => '5',
      'subMattype' => '',
      'storageTable' => 'proj1_raw_material',
    ];
    $components = $this->mockComponents('raw');
    return Inertia::render('MaterialLevels/RawMaterial', [
      'InputData' => $InputData,
      'mattypes' => $this->mattypes,
      'subMattypes' => $this->subMattypes,
      'uoms' => $this->uoms,
      'components' => $components,
    ]);
  }

  public function semiFgLevel2(Request $request): Response
  {
    $InputData = $this->baseInput($request, 'proj1_semi_fg_lv2', '2', $request->get('subMattype', ''));
    $subMattypes = $this->fetchSubMattypesFromView('PROJ1_2_MASTER_BOM_SEMI_LV2_V', $this->subMattypes);
    $semiFgLv2Bom = $this->loadSemiFgLv2BomById($request->get('levelMaterialId'));
    $semiFgLv2 = $this->loadSemiFgLv2ByMaterialId($request->get('levelMaterialId'))
      ?? $this->loadSemiFgLv2DraftByFgMaterialId($InputData['fgMaterialId'] ?? null)
      ?? $this->loadSemiFgLv2ByFgBomId($InputData['fgBomId'] ?? null);

    if ($semiFgLv2) {
      if ($semiFgLv2Bom) {
        $semiFgLv2['bomId'] = $semiFgLv2Bom['bomId'];
        $semiFgLv2['bomDesc'] = $semiFgLv2Bom['bomDesc'];
      }
      $InputData['fgDetail'] = array_replace($InputData['fgDetail'] ?? [], [
        'semiFgLv2' => $semiFgLv2,
      ]);
      $InputData['levelBomId'] = $request->get('levelBomId') ?? $semiFgLv2['bomId'] ?? '';
      $InputData['levelBomDesc'] = $request->get('levelBomDesc') ?? $semiFgLv2['bomDesc'] ?? '';
      $InputData['levelMaterialId'] = $request->get('levelMaterialId') ?? $semiFgLv2['id'] ?? '';
      $InputData['fgBomId'] = $request->get('fgBomId') ?? $semiFgLv2['fgBomId'] ?? $InputData['fgBomId'] ?? '';
      $InputData['subMattype'] = $request->get('subMattype') ?? $semiFgLv2['subMattype'] ?? '';
      $InputData['mattype'] = $semiFgLv2['mattype'] ?? $InputData['mattype'] ?? '2';
      $InputData['searchDesc'] = $request->get('searchDesc') ?? $semiFgLv2['searchDesc'] ?? '';
      $InputData['fullDescEn'] = $request->get('fullDescEn') ?? $semiFgLv2['fullDescEn'] ?? '';
      $InputData['fullDescTh'] = $request->get('fullDescTh') ?? $semiFgLv2['fullDescTh'] ?? '';
      $InputData['uom'] = $request->get('uom') ?? $semiFgLv2['uom'] ?? '';
      $InputData['materialId'] = $request->get('levelMaterialId') ?? $semiFgLv2['id'] ?? '';
      $InputData['mode'] = $request->get('mode', 'view');
    }

    if (is_array($semiFgLv2Bom) && trim((string) ($semiFgLv2Bom['bomId'] ?? '')) !== '') {
      $InputData['levelBomId'] = $request->get('levelBomId') ?? $semiFgLv2Bom['bomId'];
      $InputData['levelBomDesc'] = $request->get('levelBomDesc') ?? $semiFgLv2Bom['bomDesc'];
    }

    $semiFgLv1 = data_get($InputData, 'fgDetail.semiFgLv1') ?? $this->loadSemiFgLv1DraftByFgMaterialId($InputData['fgMaterialId'] ?? null);
    if (is_array($semiFgLv1)) {
      $InputData['fgDetail'] = array_replace($InputData['fgDetail'] ?? [], [
        'semiFgLv1' => $semiFgLv1,
      ]);
    }

    if (($InputData['mode'] ?? 'create') === 'create') {
      $InputData['subMattype'] = '';
    }
    $semiFgLv2 = data_get($InputData, 'fgDetail.semiFgLv2', []);
    $InputData['levelBomId'] = $request->get('levelBomId') ?? ($semiFgLv2['bomId'] ?? $InputData['levelBomId'] ?? '');
    $InputData['levelBomDesc'] = $request->get('levelBomDesc') ?? ($semiFgLv2['bomDesc'] ?? $InputData['levelBomDesc'] ?? '');
    if (($InputData['levelBomId'] ?? '') === '' && is_array($semiFgLv2Bom)) {
      $InputData['levelBomId'] = trim((string) ($semiFgLv2Bom['bomId'] ?? ''));
      $InputData['levelBomDesc'] = trim((string) ($semiFgLv2Bom['bomDesc'] ?? ''));
    }
    $components = $this->loadSemiFgLv2Components(
      $InputData['levelMaterialId'] ?? $request->get('levelMaterialId') ?? $semiFgLv2['id'] ?? null,
      $InputData['fgBomId'] ?? $request->get('fgBomId') ?? null,
      $InputData['levelBomId'] ?? $request->get('levelBomId') ?? $semiFgLv2['bomId'] ?? null
    );

    if (empty($components)) {
      $components = $this->resolveComponents($request);
    }

    return Inertia::render('MaterialLevels/SemiFgLevel2', [
      'InputData' => $InputData,
      'mattypes' => $this->mattypes,
      'subMattypes' => $subMattypes,
      'uoms' => $this->uoms,
      'components' => $components,
    ]);
  }

  public function semiFgLevel1(Request $request): Response
  {
    $InputData = $this->baseInput($request, 'proj1_semi_fg_lv1', '1', '1');
    $subMattypes = $this->fetchSubMattypesFromView('PROJ1_2_MASTER_BOM_SEMI_LV1_V', $this->subMattypes);
    $semiFgLv1 = $this->loadSemiFgLv1DraftByFgMaterialId($InputData['fgMaterialId'] ?? null);

    if ($semiFgLv1) {
      $InputData['fgDetail'] = array_replace($InputData['fgDetail'] ?? [], [
        'semiFgLv1' => $semiFgLv1,
      ]);
      $InputData['levelMaterialId'] = $request->get('levelMaterialId') ?? $semiFgLv1['id'] ?? '';
      $InputData['searchDesc'] = $request->get('searchDesc') ?? $semiFgLv1['searchDesc'] ?? '';
      $InputData['fullDescEn'] = $request->get('fullDescEn') ?? $semiFgLv1['fullDescEn'] ?? '';
      $InputData['fullDescTh'] = $request->get('fullDescTh') ?? $semiFgLv1['fullDescTh'] ?? '';
      $InputData['uom'] = $request->get('uom') ?? $semiFgLv1['uom'] ?? '';
      $InputData['materialId'] = $request->get('levelMaterialId') ?? $semiFgLv1['id'] ?? '';
      $InputData['mode'] = $request->get('mode', 'view');
    }

    $components = $this->resolveComponents($request);
    return Inertia::render('MaterialLevels/SemiFgLevel1', [
      'InputData' => $InputData,
      'mattypes' => $this->mattypes,
      'subMattypes' => $subMattypes,
      'uoms' => $this->uoms,
      'components' => $components,
    ]);
  }

  public function saveRaw(Request $request): RedirectResponse
  {
    return back()->with('success', 'Raw material saved.');
  }

  public function saveSemiFgLevel2(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'subMattype' => ['required', 'in:0,1,2'],
      'levelBomId' => ['required'],
      'levelBomDesc' => ['required'],
      'materialId' => ['required'],
      'searchDesc' => ['required'],
      'fullDescEn' => ['required'],
      'fullDescTh' => ['required'],
      'uom' => ['required'],
    ]);

    $inputData = $this->baseInput($request, 'proj1_semi_fg_lv2', '2', $validated['subMattype']);
    $this->callSaveSemiFgLevel2Procedure($request, array_merge($inputData, [
      'levelBomId' => $validated['levelBomId'],
      'levelBomDesc' => $validated['levelBomDesc'],
      'levelMaterialId' => $validated['materialId'],
      'searchDesc' => $validated['searchDesc'],
      'fullDescEn' => $validated['fullDescEn'],
      'fullDescTh' => $validated['fullDescTh'],
      'uom' => $validated['uom'],
      'subMattype' => $validated['subMattype'],
    ]), $request->user()?->user_login, $request->user()?->role);

    $fgDetail = $request->get('fgDetail', []);
    $referentMaterialId = trim((string) (
      $inputData['fgMaterialId']
      ?? $request->get('referentMaterialId')
      ?? $request->get('fgMaterialId')
      ?? $request->get('materialId')
      ?? $fgDetail['materialId']
      ?? ''
    ));
    $detail = [
      'bomId' => $validated['levelBomId'],
      'bomDesc' => $validated['levelBomDesc'],
      'id' => $validated['materialId'],
      'desc' => $validated['searchDesc'],
      'searchDesc' => $validated['searchDesc'],
      'fullDescEn' => $validated['fullDescEn'],
      'fullDescTh' => $validated['fullDescTh'],
      'uom' => $validated['uom'],
      'components' => $request->get('components', []),
      'subMattype' => $validated['subMattype'],
    ];
    $fgDetail['semiFgLv2'] = $detail;
    $this->persistFgDraft($request, $fgDetail);

    $components = $this->loadSemiFgLv2Components(
      $detail['id'],
      $inputData['fgBomId'] ?? $request->get('fgBomId'),
      $detail['bomId'] ?? $request->get('levelBomId')
    );
    if (empty($components)) {
      $components = $request->get('components', []);
    }

    return Redirect::route('material-levels.semi-fg-lv2.new', [
      'mode' => 'view',
      'referentMaterialId' => $referentMaterialId !== '' ? $referentMaterialId : null,
      'levelMaterialId' => $detail['id'],
      'levelBomId' => $detail['bomId'],
      'fgBomId' => $inputData['fgBomId'] ?? $request->get('fgBomId'),
      'fgDetail' => $fgDetail,
      'components' => $components,
    ]);
  }

  public function updateSemiFgLevel2(Request $request): RedirectResponse
  {
    return $this->saveSemiFgLevel2($request);
  }

  public function generateSemiFgLevel2(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'fgMaterialId' => ['required'],
      'fgBomId' => ['required'],
      'mattype' => ['required'],
      'subMattype' => ['required', 'in:0,1,2'],
    ]);

    $fgMaterialId = $validated['fgMaterialId'];
    $fgBomId = $validated['fgBomId'];
    $mattype = $validated['mattype'];
    $subMattype = $validated['subMattype'];
    $levelBomId = null;
    $levelMaterialId = null;
    $error = null;
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN proj1_2_gen_semifg_lv2(:p_fg_matid, :p_fg_bomid, :p_mattype, :p_sub_mattype, :p_semifg_lv2_bomid, :p_semifg_lv2_id, :P_ERROR); END;');
    $stmt->bindParam(':p_fg_matid', $fgMaterialId, PDO::PARAM_STR);
    $stmt->bindParam(':p_fg_bomid', $fgBomId, PDO::PARAM_STR);
    $stmt->bindParam(':p_mattype', $mattype, PDO::PARAM_STR);
    $stmt->bindParam(':p_sub_mattype', $subMattype, PDO::PARAM_STR);
    $stmt->bindParam(':p_semifg_lv2_bomid', $levelBomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_semifg_lv2_id', $levelMaterialId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    $resolvedError = $this->resolveProcedureErrorMessage($error);
    if (trim($resolvedError) !== '') {
      throw ValidationException::withMessages([
        'subMattype' => $resolvedError,
      ]);
    }

    return response()->json([
      'program' => 'proj1_2_gen_semifg_lv2',
      'levelBomId' => $levelBomId,
      'materialId' => $levelMaterialId,
    ]);
  }

  public function saveSemiFgLevel1(Request $request): RedirectResponse
  {
    $fgDetail = $request->get('fgDetail', []);
    $referentMaterialId = trim((string) (
      $request->get('referentMaterialId')
      ?? $request->get('fgMaterialId')
      ?? $request->get('materialId')
      ?? $fgDetail['materialId']
      ?? ''
    ));
    $detail = [
      'id' => $request->get('materialId'),
      'desc' => $request->get('searchDesc'),
      'searchDesc' => $request->get('searchDesc'),
      'fullDescEn' => $request->get('fullDescEn'),
      'fullDescTh' => $request->get('fullDescTh'),
      'uom' => $request->get('uom'),
      'components' => $request->get('components', []),
    ];
    $fgDetail['semiFgLv1'] = $detail;
    $this->persistFgDraft($request, $fgDetail);

    return Redirect::route('material-levels.semi-fg-lv1.new', [
      'mode' => 'view',
      'referentMaterialId' => $referentMaterialId !== '' ? $referentMaterialId : null,
      'levelMaterialId' => $detail['id'],
    ]);
  }

  public function createComponentRaw(Request $request): RedirectResponse
  {
    return Redirect::route('material-levels.raw.new', $request->all());
  }

  public function createComponentSemiFgLevel2(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'fgMaterialId' => ['required'],
      'fgBomId' => ['required'],
      'mattype' => ['nullable'],
      'subMattype' => ['nullable'],
    ]);

    $fgMaterialId = trim((string) $validated['fgMaterialId']);
    $fgBomId = trim((string) $validated['fgBomId']);
    $mattype = trim((string) ($validated['mattype'] ?? '2'));
    $subMattype = trim((string) ($validated['subMattype'] ?? '0'));
    $semiFgLv2BomId = null;
    $semiFgLv2Id = null;
    $error = null;
    $pdo = DB::connection('oracle')->getPdo();
    $stmt = $pdo->prepare('BEGIN proj1_2_gen_semifg_lv2(:p_fg_matid, :p_fg_bomid, :p_mattype, :p_sub_mattype, :p_semifg_lv2_bomid, :p_semifg_lv2_id, :P_ERROR); END;');
    $stmt->bindValue(':p_fg_matid', $fgMaterialId, PDO::PARAM_STR);
    $stmt->bindValue(':p_fg_bomid', $fgBomId, PDO::PARAM_STR);
    $stmt->bindValue(':p_mattype', $mattype, PDO::PARAM_STR);
    $stmt->bindValue(':p_sub_mattype', $subMattype, PDO::PARAM_STR);
    $stmt->bindParam(':p_semifg_lv2_bomid', $semiFgLv2BomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_semifg_lv2_id', $semiFgLv2Id, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    $resolvedError = $this->resolveProcedureErrorMessage($error);
    if (trim($resolvedError) !== '') {
      throw ValidationException::withMessages([
        'fgMaterialId' => $resolvedError,
      ]);
    }

    $payload = $request->all();
    $payload['ownerLevel'] = 'semiFgLv2';
    $payload['actionMode'] = 'create';
    $payload['mattype'] = $mattype;
    $payload['subMattype'] = $subMattype;
    $payload['bomId'] = trim((string) $semiFgLv2BomId);
    $payload['bomDesc'] = trim((string) ($request->get('levelBomDesc') ?? $request->get('bomDesc') ?? ''));
    $payload['levelBomId'] = trim((string) $semiFgLv2BomId);
    $payload['levelMaterialId'] = trim((string) $semiFgLv2Id);
    $payload['materialId'] = trim((string) $semiFgLv2Id);
    $payload['searchDesc'] = trim((string) ($request->get('searchDesc') ?? ''));
    $payload['fullDescEn'] = trim((string) ($request->get('fullDescEn') ?? ''));
    $payload['fullDescTh'] = trim((string) ($request->get('fullDescTh') ?? ''));
    $payload['uom'] = trim((string) ($request->get('uom') ?? ''));
    $payload['ownerDetail'] = array_merge($payload['ownerDetail'] ?? [], [
      'bomId' => trim((string) $semiFgLv2BomId),
      'bomDesc' => trim((string) ($request->get('levelBomDesc') ?? $request->get('bomDesc') ?? '')),
      'id' => trim((string) $semiFgLv2Id),
      'desc' => trim((string) ($request->get('searchDesc') ?? '')),
      'searchDesc' => trim((string) ($request->get('searchDesc') ?? '')),
      'fullDescEn' => trim((string) ($request->get('fullDescEn') ?? '')),
      'fullDescTh' => trim((string) ($request->get('fullDescTh') ?? '')),
      'uom' => trim((string) ($request->get('uom') ?? '')),
    ]);

    return Redirect::route('packmaterial.new', $payload);
  }

  public function createComponentSemiFgLevel1(Request $request): RedirectResponse
  {
    $payload = $request->all();
    $payload['ownerLevel'] = 'semiFgLv1';
    $payload['subMattype'] = '0';
    return Redirect::route('packmaterial.new', $payload);
  }

}
