<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Brand;
use App\Models\FgMaterialDml;
use App\Models\MasterMattypePack;
use App\Models\MasterUOM;
use App\Models\Proj12CompSemiLv1V;
use App\Models\Proj12CompSemiLv2V;
use App\Models\Proj12DmlFgComp;
use App\Models\Proj12DmlSemiL1CompM4;
use App\Models\Proj12DmlSemiL2CompM5;
use App\Models\Proj12SemiFgLv1Id;
use App\Models\Proj12SemiFgLv1Bom;
use App\Models\Proj12SemiFgLv2Id;
use App\Models\Proj12SemiFgLv2Bom;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use PDO;

class PackMaterialController extends Controller
{
  protected $uoms;
  protected $packSubMattypesList;

  public function __construct()
  {
    $this->uoms = MasterUOM::all()->map(function ($b) {
      return [
        "value" => $b->code_uom,
        "label" => $b->description_uom,
      ];
    })->toArray();
    $this->packSubMattypesList = $this->packSubMattypes();
  }

  protected function upsertComponent(array $components, array $componentItem): array
  {
    $collection = collect($components);
    $existingIndex = $collection->search(fn($item) => ($item['code'] ?? null) === $componentItem['code']);

    if ($existingIndex === false) {
      $collection->push($componentItem);
    } else {
      $collection->put($existingIndex, array_merge($collection->get($existingIndex), $componentItem));
    }

    return $collection->values()->all();
  }

  protected function resolveFgSiteByMaterialId(?string $materialId): string
  {
    $materialId = trim((string) $materialId);

    if ($materialId === '') {
      return '';
    }

    try {
      $row = FgMaterialDml::query()
        ->selectRaw('TRIM(SITE) as site')
        ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
        ->first();

      return trim((string) ($row->site ?? ''));
    } catch (\Throwable $e) {
      Log::debug('packmaterial.resolve-fg-site.failed', [
        'materialId' => $materialId,
        'error' => $e->getMessage(),
      ]);

      return '';
    }
  }

  protected function loadFgMaterialInputByMaterialId(string $materialId): ?array
  {
    $materialId = trim($materialId);

    if ($materialId === '') {
      return null;
    }

    $materialRow = FgMaterialDml::query()
      ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
      ->first();

    if (!$materialRow) {
      return null;
    }

    $material = $materialRow instanceof Model
      ? $materialRow->getAttributes()
      : (array) $materialRow;

    $materialSource = array_change_key_case($material, CASE_LOWER);

    $pick = function (array $source, array $keys, string $default = ''): string {
      foreach ($keys as $key) {
        if (array_key_exists($key, $source) && trim((string) $source[$key]) !== '') {
          return trim((string) $source[$key]);
        }
      }

      return $default;
    };

    return [
      'materialId' => $materialId,
      'bomId' => $pick($materialSource, ['fg_bom_id', 'bom_fg_id', 'bom_id']),
      'bomDesc' => $pick($materialSource, ['desc_fg_bom_id', 'bom_fg_desc', 'desc_bom_id']),
    ];
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

    $materialInput = $this->loadFgMaterialInputByMaterialId($materialId);

    return [
      'fgBomId' => trim((string) ($materialInput['bomId'] ?? '')),
      'fgBomDesc' => trim((string) ($materialInput['bomDesc'] ?? '')),
    ];
  }

  protected function generateFgBomId(?string $suggestId, ?string $site, ?string $userLogin = null): array
  {
    return $this->generateFgBomIdProcedure(
      (string) $suggestId,
      (string) $site,
      $userLogin
    );
  }

  protected function generateSemiFgLv1BomId(?string $fgMaterialId, ?string $fgBomId, ?string $mattype, ?string $subMattype): array
  {
    $fgMaterialId = trim((string) $fgMaterialId);
    $fgBomId = trim((string) $fgBomId);
    $mattype = trim((string) ($mattype ?: '1'));
    $subMattype = trim((string) ($subMattype ?: '1'));

    if ($fgMaterialId === '' || $fgBomId === '') {
      return [
        'bomId' => '',
        'levelMaterialId' => '',
        'error' => '',
      ];
    }

    $pdo = DB::connection('oracle')->getPdo();
    $semiFgLv1BomId = null;
    $semiFgLv1Id = null;
    $error = null;
    $stmt = $pdo->prepare('BEGIN proj1_2_gen_semifg_lv1(:p_fg_matid, :p_fg_bomid, :p_mattype, :p_sub_mattype, :p_semifg_lv1_bomid, :p_semifg_lv1_id, :P_ERROR); END;');
    $stmt->bindValue(':p_fg_matid', $fgMaterialId, PDO::PARAM_STR);
    $stmt->bindValue(':p_fg_bomid', $fgBomId, PDO::PARAM_STR);
    $stmt->bindValue(':p_mattype', $mattype, PDO::PARAM_STR);
    $stmt->bindValue(':p_sub_mattype', $subMattype, PDO::PARAM_STR);
    $stmt->bindParam(':p_semifg_lv1_bomid', $semiFgLv1BomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_semifg_lv1_id', $semiFgLv1Id, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    return [
      'bomId' => trim((string) $semiFgLv1BomId),
      'levelMaterialId' => trim((string) $semiFgLv1Id),
      'error' => trim((string) $this->resolveProcedureErrorMessage($error)),
    ];
  }

  protected function generateSemiFgLv2BomId(?string $fgMaterialId, ?string $fgBomId, ?string $mattype, ?string $subMattype, ?string $userLogin = null): array
  {
    $fgMaterialId = trim((string) $fgMaterialId);
    $fgBomId = trim((string) $fgBomId);
    $mattype = trim((string) ($mattype ?: '2'));
    $subMattype = trim((string) ($subMattype ?: '0'));
    $userLogin = trim((string) ($userLogin ?? ''));

    if ($fgMaterialId === '' || $fgBomId === '') {
      return [
        'bomId' => '',
        'levelMaterialId' => '',
        'error' => '',
      ];
    }

    $pdo = DB::connection('oracle')->getPdo();
    $semiFgLv2BomId = null;
    $semiFgLv2Id = null;
    $error = null;
    $stmt = $pdo->prepare('BEGIN proj1_2_gen_semifg_lv2(:p_fg_matid, :p_fg_bomid, :p_mattype, :p_sub_mattype, :p_user_login, :p_semifg_lv2_bomid, :p_semifg_lv2_id, :P_ERROR); END;');
    $stmt->bindValue(':p_fg_matid', $fgMaterialId, PDO::PARAM_STR);
    $stmt->bindValue(':p_fg_bomid', $fgBomId, PDO::PARAM_STR);
    $stmt->bindValue(':p_mattype', $mattype, PDO::PARAM_STR);
    $stmt->bindValue(':p_sub_mattype', $subMattype, PDO::PARAM_STR);
    $stmt->bindValue(':p_user_login', $userLogin, PDO::PARAM_STR);
    $stmt->bindParam(':p_semifg_lv2_bomid', $semiFgLv2BomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_semifg_lv2_id', $semiFgLv2Id, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    return [
      'bomId' => trim((string) $semiFgLv2BomId),
      'levelMaterialId' => trim((string) $semiFgLv2Id),
      'error' => trim((string) $this->resolveProcedureErrorMessage($error)),
    ];
  }

  protected function saveFgComponentBom(array $data, ?string $userLogin = null, ?string $userRole = null): void
  {
    $userLogin = $userLogin ?: 'system';
    $userRole = $userRole ?: 'GTIN';
    $bomFgId = trim((string) ($data['bomId'] ?? ''));
    $mattype = trim((string) ($data['mattype'] ?? '5'));
    $subMattype = trim((string) ($data['subMattype'] ?? ''));
    $productCat = trim((string) ($data['productCat'] ?? ''));
    $productSubCat = trim((string) ($data['productSubCat'] ?? ''));
    $componentId = trim((string) ($data['componentId'] ?? ''));
    $searchDesc = trim((string) ($data['searchDesc'] ?? ''));
    $fullDescEn = trim((string) ($data['fullDescEn'] ?? ''));
    $fullDescTh = trim((string) ($data['fullDescTh'] ?? ''));
    $uom = trim((string) ($data['uom'] ?? ''));

    if ($bomFgId === '' || $componentId === '') {
      throw ValidationException::withMessages([
        'componentId' => 'BOM ID or Component ID is missing.',
      ]);
    }

    $pdo = DB::connection('oracle')->getPdo();
    $error = null;
    $stmt = $pdo->prepare('BEGIN PROJ1_2_SAVE_COMP_BOMFG(:P_BOM_FG_ID, :P_MATTYPE, :P_SUBMATTYPE, :P_PRODUCT_CAT, :P_PROD_SUB_CAT, :P_COMPONENT_ID, :P_SEARCH_DESC, :P_COMP_DESC_EN, :P_COMP_DESC_TH, :P_UOM, :P_USER_ROLE, :P_USER, :P_ERROR); END;');
    $stmt->bindValue(':P_BOM_FG_ID', $bomFgId, PDO::PARAM_STR);
    $stmt->bindValue(':P_MATTYPE', $mattype, PDO::PARAM_STR);
    $stmt->bindValue(':P_SUBMATTYPE', $subMattype, PDO::PARAM_STR);
    $stmt->bindValue(':P_PRODUCT_CAT', $productCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_PROD_SUB_CAT', $productSubCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMPONENT_ID', $componentId, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEARCH_DESC', $searchDesc, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_EN', $fullDescEn, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_TH', $fullDescTh, PDO::PARAM_STR);
    $stmt->bindValue(':P_UOM', $uom, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER_ROLE', (string) $userRole, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER', (string) $userLogin, PDO::PARAM_STR);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    $resolvedError = trim((string) $this->resolveProcedureErrorMessage($error));
    if ($resolvedError !== '') {
      Log::warning('packmaterial.save-fg-component-bom.failed', [
        'bomFgId' => $bomFgId,
        'componentId' => $componentId,
        'error' => $error,
        'resolvedError' => $resolvedError,
      ]);

      throw ValidationException::withMessages([
        'componentId' => $resolvedError,
      ]);
    }
  }

  protected function saveSemiFgLv1ComponentBom(array $data, ?string $userLogin = null, ?string $userRole = null): void
  {
    $userLogin = $userLogin ?: 'system';
    $userRole = $userRole ?: 'GTIN';
    // The parent BOM lives in backMaterialId when creating/editing a component.
    // levelMaterialId may be overwritten with the generated child component id.
    $bomSemiLv1Id = trim((string) (
      $data['semiFgLvBomId']
      ?? $data['bomId']
      ?? $data['backMaterialId']
      ?? $data['levelMaterialId']
      ?? $data['materialId']
      ?? ''
    ));
    $mattype = trim((string) ($data['mattype'] ?? '2'));
    $subMattype = trim((string) ($data['subMattype'] ?? '0'));
    $productCat = trim((string) ($data['productCat'] ?? ''));
    $productSubCat = trim((string) ($data['productSubCat'] ?? ''));
    $site = trim((string) (
      $data['site']
      ?? data_get($data, 'fgDetail.semiFgLv2.site')
      ?? data_get($data, 'ownerDetail.site')
      ?? ''
    ));
    $componentId = trim((string) ($data['componentId'] ?? ''));
    $searchDesc = trim((string) ($data['searchDesc'] ?? ''));
    $fullDescEn = trim((string) ($data['fullDescEn'] ?? ''));
    $fullDescTh = trim((string) ($data['fullDescTh'] ?? ''));
    $uom = trim((string) ($data['uom'] ?? ''));

    if ($bomSemiLv1Id === '' || $componentId === '') {
      throw ValidationException::withMessages([
        'componentId' => 'BOM ID or Component ID is missing.',
      ]);
    }

    $pdo = DB::connection('oracle')->getPdo();
    $error = null;
    $stmt = $pdo->prepare('BEGIN proj1_2_save_comp_bom_semi_l1(:P_BOM_SEMI_LV1_ID, :P_MATTYPE, :P_SUBMATTYPE, :P_PRODUCT_CAT, :P_PROD_SUB_CAT, :P_SITE, :P_COMPONENT_ID, :P_SEARCH_DESC, :P_COMP_DESC_EN, :P_COMP_DESC_TH, :P_UOM, :P_USER_ROLE, :P_USER, :P_ERROR); END;');
    $stmt->bindValue(':P_BOM_SEMI_LV1_ID', $bomSemiLv1Id, PDO::PARAM_STR);
    $stmt->bindValue(':P_MATTYPE', $mattype, PDO::PARAM_STR);
    $stmt->bindValue(':P_SUBMATTYPE', $subMattype, PDO::PARAM_STR);
    $stmt->bindValue(':P_PRODUCT_CAT', $productCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_PROD_SUB_CAT', $productSubCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_SITE', $site, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMPONENT_ID', $componentId, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEARCH_DESC', $searchDesc, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_EN', $fullDescEn, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_TH', $fullDescTh, PDO::PARAM_STR);
    $stmt->bindValue(':P_UOM', $uom, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER_ROLE', (string) $userRole, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER', (string) $userLogin, PDO::PARAM_STR);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    $resolvedError = trim((string) $this->resolveProcedureErrorMessage($error));
    if ($resolvedError !== '') {
      Log::warning('packmaterial.save-semi-fg-lv1-component-bom.failed', [
        'bomSemiLv1Id' => $bomSemiLv1Id,
        'componentId' => $componentId,
        'error' => $error,
        'resolvedError' => $resolvedError,
      ]);

      throw ValidationException::withMessages([
        'componentId' => $resolvedError,
      ]);
    }
  }

  protected function saveSemiFgLv2ComponentBom(array $data, ?string $userLogin = null, ?string $userRole = null): void
  {
    $userLogin = $userLogin ?: 'system';
    $userRole = $userRole ?: 'GTIN';
    // The parent BOM lives in backMaterialId when creating/editing a component.
    // levelMaterialId may be overwritten with the generated child component id.
    $bomSemiLv2Id = trim((string) (
      $data['semiFgLvBomId']
      ?? $data['bomId']
      ?? $data['backMaterialId']
      ?? $data['levelMaterialId']
      ?? $data['materialId']
      ?? ''
    ));
    $mattype = trim((string) ($data['mattype'] ?? '2'));
    $subMattype = trim((string) ($data['subMattype'] ?? '0'));
    $productCat = trim((string) ($data['productCat'] ?? ''));
    $productSubCat = trim((string) ($data['productSubCat'] ?? ''));
    $site = trim((string) (
      $data['site']
      ?? data_get($data, 'fgDetail.semiFgLv2.site')
      ?? data_get($data, 'ownerDetail.site')
      ?? ''
    ));
    $componentId = trim((string) ($data['componentId'] ?? ''));
    $searchDesc = trim((string) ($data['searchDesc'] ?? ''));
    $fullDescEn = trim((string) ($data['fullDescEn'] ?? ''));
    $fullDescTh = trim((string) ($data['fullDescTh'] ?? ''));
    $uom = trim((string) ($data['uom'] ?? ''));

    if ($bomSemiLv2Id === '' || $componentId === '') {
      throw ValidationException::withMessages([
        'componentId' => 'BOM ID or Component ID is missing.',
      ]);
    }

    $pdo = DB::connection('oracle')->getPdo();
    $error = null;
    $stmt = $pdo->prepare('BEGIN proj1_2_save_comp_bom_semi_l2(:P_BOM_SEMI_LV2_ID, :P_MATTYPE, :P_SUBMATTYPE, :P_PRODUCT_CAT, :P_PROD_SUB_CAT, :P_SITE, :P_COMPONENT_ID, :P_SEARCH_DESC, :P_COMP_DESC_EN, :P_COMP_DESC_TH, :P_UOM, :P_USER_ROLE, :P_USER, :P_ERROR); END;');
    $stmt->bindValue(':P_BOM_SEMI_LV2_ID', $bomSemiLv2Id, PDO::PARAM_STR);
    $stmt->bindValue(':P_MATTYPE', $mattype, PDO::PARAM_STR);
    $stmt->bindValue(':P_SUBMATTYPE', $subMattype, PDO::PARAM_STR);
    $stmt->bindValue(':P_PRODUCT_CAT', $productCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_PROD_SUB_CAT', $productSubCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_SITE', $site, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMPONENT_ID', $componentId, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEARCH_DESC', $searchDesc, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_EN', $fullDescEn, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_TH', $fullDescTh, PDO::PARAM_STR);
    $stmt->bindValue(':P_UOM', $uom, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER_ROLE', (string) $userRole, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER', (string) $userLogin, PDO::PARAM_STR);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    $resolvedError = trim((string) $this->resolveProcedureErrorMessage($error));
    if ($resolvedError !== '') {
      Log::warning('packmaterial.save-semi-fg-lv2-component-bom.failed', [
        'bomSemiLv2Id' => $bomSemiLv2Id,
        'componentId' => $componentId,
        'error' => $error,
        'resolvedError' => $resolvedError,
      ]);

      throw ValidationException::withMessages([
        'componentId' => $resolvedError,
      ]);
    }
  }

  protected function loadSemiFgLv1ComponentByBomAndComponentId(?string $levelMaterialId, ?string $componentId): ?array
  {
    $levelMaterialId = trim((string) $levelMaterialId);
    $componentId = trim((string) $componentId);

    if ($levelMaterialId === '' || $componentId === '') {
      return null;
    }

    try {
      $resolvedBom = $this->loadSemiFgLv1BomByMaterialId($levelMaterialId);
      $bomId = trim((string) ($resolvedBom['semiFgLvBomId'] ?? $levelMaterialId));
      $bomDesc = trim((string) ($resolvedBom['semiFgLvBomDesc'] ?? ''));

      $row = Proj12DmlSemiL1CompM4::query()
        ->selectRaw('
          TRIM(NO) as no,
          TRIM(MATERIAL_ID_M4) as component_id,
          TRIM(SEARCH_DESCRIPTION) as search_description,
          TRIM(FULL_DESCRIPTION_EN) as full_description_en,
          TRIM(FULL_DESCRIPTION_TH) as full_description_th,
          TRIM(SEMI_FG_LV1_BOM_NO) as semi_fg_lv1_bom_no,
          TRIM(SITE) as site,
          TRIM(UOM) as uom,
          TRIM(STATUS_ROW) as status_row
        ')
        ->whereRaw('TRIM(SEMI_FG_LV1_BOM_NO) = ?', [$bomId])
        ->whereRaw('TRIM(MATERIAL_ID_M4) = ?', [$componentId])
        ->first();

      if (!$row) {
        return null;
      }

      $record = $row instanceof Model
        ? $row->getAttributes()
        : (array) $row;

      $source = array_change_key_case($record, CASE_LOWER);
      $componentIdValue = trim((string) ($source['component_id'] ?? ''));

      return [
        'bomId' => trim((string) ($source['semi_fg_lv1_bom_no'] ?? $bomId)),
        'bomDesc' => $bomDesc,
        'componentId' => $componentIdValue,
        'productSubCat' => $componentIdValue !== '' ? substr($componentIdValue, 0, 4) : '',
        'productCat' => $componentIdValue !== '' ? substr($componentIdValue, 0, 2) : '',
        'searchDesc' => trim((string) ($source['search_description'] ?? '')),
        'fullDescEn' => trim((string) ($source['full_description_en'] ?? '')),
        'fullDescTh' => trim((string) ($source['full_description_th'] ?? '')),
        'uom' => trim((string) ($source['uom'] ?? '')),
        'status' => trim((string) ($source['status_row'] ?? 'INS')),
      ];
    } catch (\Throwable $exception) {
      Log::warning('packmaterial.semi-fg-lv1-component.lookup.failed', [
        'levelMaterialId' => $levelMaterialId,
        'bomId' => $bomId ?? '',
        'componentId' => $componentId,
        'error' => $exception->getMessage(),
      ]);

      throw $exception;
    }
  }

  protected function loadSemiFgLv2ComponentByBomAndComponentId(?string $levelMaterialId, ?string $componentId): ?array
  {
    $levelMaterialId = trim((string) $levelMaterialId);
    $componentId = trim((string) $componentId);

    if ($levelMaterialId === '' || $componentId === '') {
      return null;
    }

    try {
      $resolvedBom = $this->loadSemiFgLv2BomByMaterialId($levelMaterialId);
      $bomId = trim((string) ($resolvedBom['semiFgLvBomId'] ?? $levelMaterialId));
      $bomDesc = trim((string) ($resolvedBom['semiFgLvBomDesc'] ?? ''));

      $row = Proj12DmlSemiL2CompM5::query()
        ->selectRaw('
          TRIM(NO) as no,
          TRIM(MATERIAL_ID_M5) as component_id,
          TRIM(SEARCH_DESCRIPTION) as search_description,
          TRIM(FULL_DESCRIPTION_EN) as full_description_en,
          TRIM(FULL_DESCRIPTION_TH) as full_description_th,
          TRIM(SEMI_FG_LV2_BOM_NO) as semi_fg_lv2_bom_no,
          TRIM(SITE) as site,
          TRIM(UOM) as uom,
          TRIM(STATUS_ROW) as status_row
        ')
        ->whereRaw('TRIM(SEMI_FG_LV2_BOM_NO) = ?', [$bomId])
        ->whereRaw('TRIM(MATERIAL_ID_M5) = ?', [$componentId])
        ->first();

      if (!$row) {
        return null;
      }

      $record = $row instanceof Model
        ? $row->getAttributes()
        : (array) $row;

      $source = array_change_key_case($record, CASE_LOWER);
      $componentIdValue = trim((string) ($source['component_id'] ?? ''));

      return [
        'bomId' => trim((string) ($source['semi_fg_lv2_bom_no'] ?? $bomId)),
        'bomDesc' => $bomDesc,
        'componentId' => $componentIdValue,
        'productSubCat' => $componentIdValue !== '' ? substr($componentIdValue, 0, 4) : '',
        'productCat' => $componentIdValue !== '' ? substr($componentIdValue, 0, 2) : '',
        'searchDesc' => trim((string) ($source['search_description'] ?? '')),
        'fullDescEn' => trim((string) ($source['full_description_en'] ?? '')),
        'fullDescTh' => trim((string) ($source['full_description_th'] ?? '')),
        'uom' => trim((string) ($source['uom'] ?? '')),
        'status' => trim((string) ($source['status_row'] ?? 'INS')),
      ];
    } catch (\Throwable $exception) {
      Log::warning('packmaterial.semi-fg-lv2-component.lookup.failed', [
        'levelMaterialId' => $levelMaterialId,
        'bomId' => $bomId ?? '',
        'componentId' => $componentId,
        'error' => $exception->getMessage(),
      ]);

      throw $exception;
    }
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
      'fgMaterialId' => trim((string) ($row->material_id_fg_1 ?? '')),
      'site' => trim((string) ($row->site ?? '')),
      'components' => [],
      'statusRow' => trim((string) ($row->status_row ?? '')),
    ];
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
      'fgBomId' => trim((string) ($row->fg_bom_id ?? '')),
      'id' => trim((string) ($row->semi_fg_lv2_id ?? '')),
      'desc' => trim((string) ($row->desc_semi_fg_lv2_id ?? '')),
      'searchDesc' => trim((string) ($row->desc_semi_fg_lv2_id ?? '')),
      'fullDescEn' => trim((string) ($row->full_desc_semi_fg_lv2_en ?? '')),
      'fullDescTh' => trim((string) ($row->full_desc_semi_fg_lv2_th ?? '')),
      'uom' => trim((string) ($row->uom_semi_fg_l2id ?? '')),
      'mattype' => trim((string) ($row->mattype_semi_fg_l2id ?? '')),
      'subMattype' => trim((string) ($row->sub_mattype_semi_fg_l2id ?? '')),
      'materialIdFg1' => trim((string) ($row->material_id_fg_1 ?? '')),
      'fgMaterialId' => trim((string) ($row->material_id_fg_1 ?? '')),
      'site' => trim((string) ($row->site ?? '')),
      'components' => [],
      'statusRow' => trim((string) ($row->status_row ?? '')),
    ];
  }

  protected function loadSemiFgLv1BomByMaterialId(?string $levelMaterialId): ?array
  {
    $levelMaterialId = trim((string) $levelMaterialId);

    if ($levelMaterialId === '') {
      return null;
    }

    try {
      $idRow = Proj12SemiFgLv1Id::query()
        ->selectRaw('
          TRIM(FG_BOM_ID) as fg_bom_id,
          TRIM(MATERIAL_ID_FG_1) as material_id_fg_1
        ')
        ->whereRaw('TRIM(SEMI_FG_LV1_ID) = ?', [$levelMaterialId])
        ->first();

      if (!$idRow) {
        return null;
      }

      $row = Proj12SemiFgLv1Bom::query()
        ->selectRaw('
          TRIM(SEMI_FG_LV1_BOM_ID) as semi_fg_lv_bom_id,
          TRIM(DESC_SEMI_FG_LV1_BOM_ID) as semi_fg_lv_bom_desc,
          TRIM(FG_BOM_ID) as fg_bom_id,
          TRIM(MATERIAL_ID_FG_1) as material_id_fg_1
        ')
        ->whereRaw('TRIM(FG_BOM_ID) = ?', [trim((string) ($idRow->fg_bom_id ?? ''))])
        ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [trim((string) ($idRow->material_id_fg_1 ?? ''))])
        ->first();
    } catch (\Throwable $e) {
      Log::debug('packmaterial.load-semi-fg-lv1-bom.failed', [
        'levelMaterialId' => $levelMaterialId,
        'error' => $e->getMessage(),
      ]);
      return null;
    }

    if (!$row) {
      return null;
    }

    return [
      'semiFgLvBomId' => trim((string) ($row->semi_fg_lv_bom_id ?? '')),
      'semiFgLvBomDesc' => trim((string) ($row->semi_fg_lv_bom_desc ?? '')),
    ];
  }

  protected function loadSemiFgLv2BomByMaterialId(?string $levelMaterialId): ?array
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
          TRIM(SEMI_FG_LV2_BOM_ID) as semi_fg_lv_bom_id,
          TRIM(DESC_SEMI_FG_LV2_BOM_ID) as semi_fg_lv_bom_desc,
          TRIM(FG_BOM_ID) as fg_bom_id,
          TRIM(MATERIAL_ID_FG_1) as material_id_fg_1
        ')
        ->whereRaw('TRIM(FG_BOM_ID) = ?', [trim((string) ($idRow->fg_bom_id ?? ''))])
        ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [trim((string) ($idRow->material_id_fg_1 ?? ''))])
        ->first();
    } catch (\Throwable $e) {
      Log::debug('packmaterial.load-semi-fg-lv2-bom.failed', [
        'levelMaterialId' => $levelMaterialId,
        'error' => $e->getMessage(),
      ]);
      return null;
    }

    if (!$row) {
      return null;
    }

    return [
      'semiFgLvBomId' => trim((string) ($row->semi_fg_lv_bom_id ?? '')),
      'semiFgLvBomDesc' => trim((string) ($row->semi_fg_lv_bom_desc ?? '')),
    ];
  }

  protected function loadSemiFgLv1ByMaterialId(?string $levelMaterialId): ?array
  {
    $levelMaterialId = trim((string) $levelMaterialId);

    if ($levelMaterialId === '') {
      return null;
    }

    $row = Proj12SemiFgLv1Id::query()
      ->selectRaw('
        TRIM(FG_BOM_ID) as fg_bom_id,
        TRIM(SEMI_FG_LV1_ID) as semi_fg_lv1_id,
        TRIM(DESC_SEMI_FG_LV1_ID) as desc_semi_fg_lv1_id,
        TRIM(FULL_DESC_SEMI_FG_LV1_EN) as full_desc_semi_fg_lv1_en,
        TRIM(FULL_DESC_SEMI_FG_LV1_TH) as full_desc_semi_fg_lv1_th,
        TRIM(MATTYPE_SEMI_FG_L1ID) as mattype_semi_fg_l1id,
        TRIM(SUB_MATTYPE_SEMI_FG_L1ID) as sub_mattype_semi_fg_l1id,
        TRIM(UOM_SEMI_FG_L1ID) as uom_semi_fg_l1id,
        TRIM(MATERIAL_ID_FG_1) as material_id_fg_1,
        TRIM(SITE) as site,
        TRIM(STATUS_ROW) as status_row
      ')
      ->whereRaw('TRIM(SEMI_FG_LV1_ID) = ?', [$levelMaterialId])
      ->first();

    if (!$row) {
      return null;
    }

    return [
      'fgBomId' => trim((string) ($row->fg_bom_id ?? '')),
      'bomId' => '',
      'bomDesc' => '',
      'id' => trim((string) ($row->semi_fg_lv1_id ?? '')),
      'desc' => trim((string) ($row->desc_semi_fg_lv1_id ?? '')),
      'searchDesc' => trim((string) ($row->desc_semi_fg_lv1_id ?? '')),
      'fullDescEn' => trim((string) ($row->full_desc_semi_fg_lv1_en ?? '')),
      'fullDescTh' => trim((string) ($row->full_desc_semi_fg_lv1_th ?? '')),
      'uom' => trim((string) ($row->uom_semi_fg_l1id ?? '')),
      'mattype' => trim((string) ($row->mattype_semi_fg_l1id ?? '')),
      'subMattype' => trim((string) ($row->sub_mattype_semi_fg_l1id ?? '')),
      'materialIdFg1' => trim((string) ($row->material_id_fg_1 ?? '')),
      'fgMaterialId' => trim((string) ($row->material_id_fg_1 ?? '')),
      'site' => trim((string) ($row->site ?? '')),
      'components' => [],
      'statusRow' => trim((string) ($row->status_row ?? '')),
    ];
  }

  protected function loadFgComponentByMaterialAndComponentId(?string $materialId, ?string $componentId): ?array
  {
    $materialId = trim((string) $materialId);
    $componentId = trim((string) $componentId);

    if ($materialId === '' || $componentId === '') {
      return null;
    }

    $fgInput = $this->loadFgMaterialInputByMaterialId($materialId);
    $bomId = trim((string) ($fgInput['bomId'] ?? ''));

    if ($bomId === '') {
      return null;
    }

    $row = Proj12DmlFgComp::query()
      ->whereRaw('TRIM(BOM_FG_ID) = ?', [$bomId])
      ->whereRaw('TRIM(COMPONENT_ID) = ?', [$componentId])
      ->first();

    if (!$row) {
      return null;
    }

    $record = $row instanceof Model
      ? $row->getAttributes()
      : (array) $row;

    $source = array_change_key_case($record, CASE_LOWER);
    $pick = function (array $source, array $keys, string $default = ''): string {
      foreach ($keys as $key) {
        if (array_key_exists($key, $source) && trim((string) $source[$key]) !== '') {
          return trim((string) $source[$key]);
        }
      }

      return $default;
    };

    return [
      'bomId' => $pick($source, ['bom_fg_id', 'fg_bom_id'], $bomId),
      'bomDesc' => $pick($source, ['desc_fg_bom_id', 'bom_fg_desc', 'fg_bom_desc'], trim((string) ($fgInput['bomDesc'] ?? ''))),
      'mattype' => $pick($source, ['mattype'], '5'),
      'subMattype' => $pick($source, ['submattype', 'sub_mattype']),
      'productCat' => $pick($source, ['product_cat', 'product_category']),
      'productSubCat' => $pick($source, ['prod_sub_cat', 'product_sub_cat']),
      'componentId' => $pick($source, ['component_id', 'comp_id'], $componentId),
      'searchDesc' => $pick($source, ['search_desc', 'search_description']),
      'fullDescEn' => $pick($source, ['comp_desc_en', 'full_desc_en', 'full_description_en']),
      'fullDescTh' => $pick($source, ['comp_desc_th', 'full_desc_th', 'full_description_th']),
      'uom' => $pick($source, ['uom', 'uom_code', 'code_uom']),
      'materialId' => $materialId,
    ];
  }

  protected function packSubMattypes(string $mattype = '5'): array
  {
    $mattype = (string) $mattype;

    return MasterMattypePack::query()
      ->selectRaw('TRIM(submattype) as code, TRIM(description) as description')
      ->whereRaw('TRIM(mattype) = ?', [$mattype])
      ->whereRaw('TRIM(submattype) IS NOT NULL')
      ->whereRaw('TRIM(product_sub_cat) IS NULL')
      ->groupByRaw('TRIM(submattype), TRIM(description)')
      ->orderByRaw('TRIM(submattype)')
      ->get()
      ->map(function ($row) {
        return [
          'code' => (string) $row->code,
          'description' => (string) $row->description,
        ];
      })
      ->values()
      ->toArray();
  }

  protected function packProductCategories(string $mattype = '5', ?string $subMattype = null): array
  {
    $mattype = (string) $mattype;

    $query = MasterMattypePack::query()
      ->selectRaw('TRIM(SUBMATTYPE) as subMattypeCode, TRIM(PRODUCT_CATEGORY) as code, MIN(TRIM(DESCRIPTION)) as description')
      ->whereRaw('TRIM(mattype) = ?', [$mattype])
      ->whereRaw('TRIM(PRODUCT_CATEGORY) IS NOT NULL')
      ->whereRaw('TRIM(PRODUCT_SUB_CAT) IS NOT NULL')
      ->groupByRaw('TRIM(SUBMATTYPE), TRIM(PRODUCT_CATEGORY)')
      ->orderByRaw('TRIM(SUBMATTYPE), TRIM(PRODUCT_CATEGORY)');

    if ($subMattype !== null && $subMattype !== '') {
      $query->whereRaw('TRIM(SUBMATTYPE) = ?', [(string) $subMattype]);
    } else {
      return [];
    }

    return $query
      ->get()
      ->map(function ($row) {
        return [
          'subMattypeCode' => (string) $row->subMattypeCode,
          'code' => (string) $row->code,
          'description' => (string) $row->description,
        ];
      })
      ->values()
      ->toArray();
  }

  protected function packProductSubCategories(string $mattype = '5', ?string $subMattype = null, ?string $productCat = null): array
  {
    $mattype = (string) $mattype;

    $query = MasterMattypePack::query()
      ->selectRaw('TRIM(SUBMATTYPE) as subMattypeCode, TRIM(product_category) as productCatCode, TRIM(product_sub_cat) as code, TRIM(description) as description')
      ->whereRaw('TRIM(mattype) = ?', [$mattype])
      ->whereRaw('TRIM(product_category) IS NOT NULL')
      ->whereRaw('TRIM(product_sub_cat) IS NOT NULL')
      ->groupByRaw('TRIM(SUBMATTYPE), TRIM(product_category), TRIM(product_sub_cat), TRIM(description)')
      ->orderByRaw('TRIM(SUBMATTYPE), TRIM(product_category), TRIM(product_sub_cat)');

    if ($subMattype !== null && $subMattype !== '') {
      $query->whereRaw('TRIM(SUBMATTYPE) = ?', [(string) $subMattype]);
    }

    if ($productCat !== null && $productCat !== '') {
      $query->whereRaw('TRIM(product_category) = ?', [(string) $productCat]);
    }

    return $query
      ->get()
      ->map(function ($row) {
        return [
          'subMattypeCode' => (string) $row->subMattypeCode,
          'productCatCode' => (string) $row->productCatCode,
          'code' => (string) $row->code,
          'description' => (string) $row->description,
        ];
      })
      ->values()
      ->toArray();
  }

  protected function packComponentProductCategories(string $viewName, string $mattype = '5', ?string $subMattype = null): array
  {
    $mattype = trim($mattype) !== '' ? trim($mattype) : '5';
    $subMattype = trim((string) $subMattype) !== '' ? trim((string) $subMattype) : '0';

    try {
      $modelClass = $this->resolveComponentProductModel($viewName);

      if ($modelClass === null) {
        return [];
      }

      return $modelClass::query()
        ->selectRaw('TRIM(code_product_cat) as code, TRIM(desc_prod_cat) as description')
        ->whereRaw('TRIM(mattype) = ?', [$mattype])
        ->whereRaw('TRIM(sub_mattype) = ?', [$subMattype])
        ->whereRaw('TRIM(code_product_cat) IS NOT NULL')
        ->groupByRaw('TRIM(code_product_cat), TRIM(desc_prod_cat)')
        ->orderByRaw('TRIM(code_product_cat), TRIM(desc_prod_cat)')
        ->get()
        ->map(function ($row) {
          return [
            'code' => (string) $row->code,
            'description' => (string) $row->description,
          ];
        })
        ->values()
        ->toArray();
    } catch (\Throwable $exception) {
      Log::warning('packmaterial.component-product-categories.failed', [
        'viewName' => $viewName,
        'error' => $exception->getMessage(),
      ]);

      throw $exception;
    }
  }

  protected function packComponentProductSubCategories(string $viewName, string $mattype = '5', ?string $subMattype = null, ?string $productCat = null): array
  {
    $mattype = trim($mattype) !== '' ? trim($mattype) : '5';
    $subMattype = trim((string) $subMattype) !== '' ? trim((string) $subMattype) : '0';

    try {
      $modelClass = $this->resolveComponentProductModel($viewName);

      if ($modelClass === null) {
        return [];
      }

      $query = $modelClass::query()
        ->selectRaw('TRIM(code_sub_cat) as code, TRIM(desc_prod_sub_cat) as description')
        ->whereRaw('TRIM(mattype) = ?', [$mattype])
        ->whereRaw('TRIM(sub_mattype) = ?', [$subMattype])
        ->whereRaw('TRIM(code_sub_cat) IS NOT NULL')
        ->groupByRaw('TRIM(code_sub_cat), TRIM(desc_prod_sub_cat)')
        ->orderByRaw('TRIM(code_sub_cat), TRIM(desc_prod_sub_cat)');

      if ($productCat !== null && $productCat !== '') {
        $query->whereRaw('TRIM(code_product_cat) = ?', [(string) $productCat]);
      }

      return $query
        ->get()
        ->map(function ($row) {
          return [
            'code' => (string) $row->code,
            'description' => (string) $row->description,
          ];
        })
        ->values()
        ->toArray();
    } catch (\Throwable $exception) {
      Log::warning('packmaterial.component-product-sub-categories.failed', [
        'viewName' => $viewName,
        'error' => $exception->getMessage(),
      ]);

      throw $exception;
    }
  }

  protected function resolveComponentProductModel(string $viewName): ?string
  {
    return match ($viewName) {
      'PROJ1_2_COMP_SEMI_L1_V' => Proj12CompSemiLv1V::class,
      'PROJ1_2_COMP_SEMI_L2_V' => Proj12CompSemiLv2V::class,
      default => null,
    };
  }

  protected function redirectToOwner(Request $request, array $components): RedirectResponse
  {
    $ownerLevel = $request->get('ownerLevel', 'fg');
    $fgDetail = $request->get('fgDetail', []);
    $ownerDetail = $request->get('ownerDetail', []);

    if ($ownerLevel === 'fg') {
      $fgDetail['fgComponents'] = $components;
      return Redirect::route('product.view', $fgDetail);
    }

    if ($ownerLevel === 'semiFgLv1') {
      $fgDetail['semiFgLv1'] = array_merge($fgDetail['semiFgLv1'] ?? [], $ownerDetail, ['components' => $components]);
      return Redirect::route('material-levels.semi-fg-lv1.new', [
        'mode' => 'view',
        'levelMaterialId' => $ownerDetail['id'] ?? null,
      ]);
    }

    if ($ownerLevel === 'businessSupply') {
      $fgDetail['businessSupply'] = array_merge($fgDetail['businessSupply'] ?? [], $ownerDetail, ['components' => $components]);
      $backRoute = $request->get('backRoute', 'business-supply.new');
      return Redirect::route($backRoute, [
        'mode' => 'view',
        'fgDetail' => $fgDetail,
        'materialId' => $fgDetail['materialId'] ?? null,
        'bomId' => $fgDetail['bomId'] ?? null,
        'bomDesc' => $fgDetail['bomDesc'] ?? null,
        'levelMaterialId' => $ownerDetail['id'] ?? null,
        'searchDesc' => $ownerDetail['searchDesc'] ?? null,
        'fullDescEn' => $ownerDetail['fullDescEn'] ?? null,
        'fullDescTh' => $ownerDetail['fullDescTh'] ?? null,
        'uom' => $ownerDetail['uom'] ?? null,
        'components' => $components,
        'bizsupId' => $ownerDetail['id'] ?? null,
      ]);
    }

    $fgDetail['semiFgLv2'] = array_merge($fgDetail['semiFgLv2'] ?? [], $ownerDetail, ['components' => $components]);
    return Redirect::route('material-levels.semi-fg-lv2.new', [
      'mode' => 'view',
      'levelMaterialId' => $ownerDetail['id'] ?? null,
    ]);
  }

  protected function packMaterialViewName(string $ownerLevel): string
  {
    return match ($ownerLevel) {
      'semiFgLv1' => 'PackMaterial/SemiFgLv1Bom',
      'semiFgLv2' => 'PackMaterial/SemiFgLv2Bom',
      'businessSupply' => 'PackMaterial/BusinessSupplyBom',
      default => 'PackMaterial/FgBom',
    };
  }

  public function new(Request $request): Response
  {
    $subMattypes = $this->packSubMattypesList;
    $referentMaterialId = trim((string) ($request->referentMaterialId ?: $request->materialId ?: $request->fgMaterialId ?: ''));
    $actionMode = $request->actionMode ?: 'create';
    $ownerLevel = $request->ownerLevel ?: 'fg';
    $generatedBomId = trim((string) $request->bomId);
    $componentId = trim((string) $request->componentId);
    $loadedComponent = null;
    $resolvedSemiFgLv1 = null;
    $resolvedSemiFgLv2 = null;
    $resolvedSemiFgLv1MaterialId = '';
    $resolvedSemiFgLv1FgBomId = '';
    $resolvedSemiFgLv1FgBomDesc = '';
    $resolvedSemiFgLv1Bom = null;
    $resolvedSemiFgLv2MaterialId = '';
    $resolvedSemiFgLv2FgBomId = '';
    $resolvedSemiFgLv2FgBomDesc = '';
    $resolvedSemiFgLv2Bom = null;
    $defaultMattype = $ownerLevel === 'semiFgLv1' ? '4' : '5';
    $allowedMattypes = $ownerLevel === 'semiFgLv1'
      ? ['4']
      : ['5'];

    if ($ownerLevel === 'semiFgLv1') {
      $resolvedSemiFgLv1MaterialId = trim((string) ($request->levelMaterialId ?: $request->materialId ?: $request->fgMaterialId ?: ''));
      $resolvedSemiFgLv1 = $this->loadSemiFgLv1ByMaterialId($resolvedSemiFgLv1MaterialId);
      $resolvedSemiFgLv1Bom = $this->loadSemiFgLv1BomByMaterialId($resolvedSemiFgLv1MaterialId);

      if (is_array($resolvedSemiFgLv1)) {
        $resolvedFgMaterialId = trim((string) (
          $request->fgMaterialId
          ?: $request->referentMaterialId
          ?: $resolvedSemiFgLv1['fgMaterialId']
          ?: ''
        ));

        if ($resolvedFgMaterialId !== '') {
          $resolvedFgBom = $this->resolveFgBomByMaterialId($resolvedFgMaterialId);
          $resolvedSemiFgLv1FgBomId = trim((string) ($request->fgBomId ?: $resolvedSemiFgLv1['fgBomId'] ?: ($resolvedFgBom['fgBomId'] ?? '')));
          $resolvedSemiFgLv1FgBomDesc = trim((string) ($request->fgBomDesc ?: ($resolvedFgBom['fgBomDesc'] ?? '')));
          $request = $request->merge([
            'fgMaterialId' => $resolvedFgMaterialId,
            'referentMaterialId' => $resolvedFgMaterialId,
            'fgBomId' => $resolvedSemiFgLv1FgBomId,
            'fgBomDesc' => $resolvedSemiFgLv1FgBomDesc,
          ]);
        }

        if (($resolvedSemiFgLv1['site'] ?? '') !== '') {
          $request = $request->merge([
            'site' => $request->site ?: $resolvedSemiFgLv1['site'],
          ]);
        }

        if (($resolvedSemiFgLv1['id'] ?? '') !== '') {
          $request = $request->merge([
            'levelMaterialId' => $resolvedSemiFgLv1['id'],
          ]);
        }
      }
    }

    if ($ownerLevel === 'semiFgLv2') {
      $resolvedSemiFgLv2MaterialId = trim((string) ($request->levelMaterialId ?: $request->materialId ?: $request->fgMaterialId ?: ''));
      $resolvedSemiFgLv2 = $this->loadSemiFgLv2ByMaterialId($resolvedSemiFgLv2MaterialId);
      $resolvedSemiFgLv2Bom = $this->loadSemiFgLv2BomByMaterialId($resolvedSemiFgLv2MaterialId);
      if (is_array($resolvedSemiFgLv2)) {
        $resolvedFgMaterialId = trim((string) (
          $request->fgMaterialId
          ?: $request->referentMaterialId
          ?: $resolvedSemiFgLv2['fgMaterialId']
          ?: $resolvedSemiFgLv2['materialIdFg1']
          ?: ''
        ));
        if ($resolvedFgMaterialId !== '') {
          $resolvedFgBom = $this->resolveFgBomByMaterialId($resolvedFgMaterialId);
          $resolvedSemiFgLv2FgBomId = trim((string) ($request->fgBomId ?: $request->bomId ?: $resolvedSemiFgLv2['fgBomId'] ?: ($resolvedFgBom['fgBomId'] ?? '')));
          $resolvedSemiFgLv2FgBomDesc = trim((string) ($request->fgBomDesc ?: ($resolvedFgBom['fgBomDesc'] ?? '')));
          $request = $request->merge([
            'fgMaterialId' => $resolvedFgMaterialId,
            'referentMaterialId' => $resolvedFgMaterialId,
            'fgBomId' => $resolvedSemiFgLv2FgBomId,
            'fgBomDesc' => $resolvedSemiFgLv2FgBomDesc,
          ]);
        }
        if (($resolvedSemiFgLv2['site'] ?? '') !== '') {
          $request = $request->merge([
            'site' => $request->site ?: $resolvedSemiFgLv2['site'],
          ]);
        }
        if ($resolvedSemiFgLv2['id'] ?? false) {
          $request = $request->merge([
            'levelMaterialId' => $resolvedSemiFgLv2['id'],
          ]);
        }
      }
    }

    if ($ownerLevel === 'fg' && in_array($actionMode, ['edit', 'delete'], true) && $referentMaterialId !== '' && $componentId !== '') {
      $loadedComponent = $this->loadFgComponentByMaterialAndComponentId($referentMaterialId, $componentId);
    }

    if ($ownerLevel === 'semiFgLv2' && in_array($actionMode, ['edit', 'delete'], true) && $componentId !== '') {
      $loadedComponent = $this->loadSemiFgLv2ComponentByBomAndComponentId(
        $request->get('levelMaterialId') ?: $request->get('materialId'),
        $componentId
      );
    }

    if ($ownerLevel === 'semiFgLv1' && in_array($actionMode, ['edit', 'delete'], true) && $componentId !== '') {
      $loadedComponent = $this->loadSemiFgLv1ComponentByBomAndComponentId(
        $request->get('levelMaterialId') ?: $request->get('materialId'),
        $componentId
      );
    }

    if ($ownerLevel === 'semiFgLv1' && $generatedBomId === '' && is_array($resolvedSemiFgLv1Bom)) {
      $generatedBomId = trim((string) ($resolvedSemiFgLv1Bom['semiFgLvBomId'] ?? ''));
    }

    if ($ownerLevel === 'semiFgLv2' && $generatedBomId === '' && is_array($resolvedSemiFgLv2Bom)) {
      $generatedBomId = trim((string) ($resolvedSemiFgLv2Bom['semiFgLvBomId'] ?? ''));
    }

    if (in_array($ownerLevel, ['fg', 'semiFgLv1', 'semiFgLv2'], true) && $actionMode === 'create' && $generatedBomId === '') {
      if ($ownerLevel === 'semiFgLv2') {
        $fgMaterialId = trim((string) ($request->fgMaterialId ?: $referentMaterialId));
        $fgBomId = trim((string) ($request->fgBomId ?: $request->bomId ?: $resolvedSemiFgLv2FgBomId));
        if ($fgBomId === '' && $fgMaterialId !== '') {
          $fgInput = $this->loadFgMaterialInputByMaterialId($fgMaterialId);
          $fgBomId = trim((string) ($fgInput['bomId'] ?? ''));
        }

        $generated = $this->generateSemiFgLv2BomId(
          $fgMaterialId,
          $fgBomId,
          $request->mattype ?: '2',
          $request->subMattype ?: '0',
          (string) ($request->user()?->user_login ?? '')
        );

        if ($generated['error'] !== '') {
          Log::warning('packmaterial.generate-semi-fg-lv2-bom-id.failed', [
            'fgMaterialId' => $fgMaterialId,
            'fgBomId' => $fgBomId,
            'error' => $generated['error'],
          ]);
        }

        $generatedBomId = $generated['bomId'];
        if ($generated['levelMaterialId'] !== '') {
          $request = $request->merge([
            'levelMaterialId' => $generated['levelMaterialId'],
          ]);
        }
      } elseif ($ownerLevel === 'semiFgLv1') {
        $fgMaterialId = trim((string) ($request->fgMaterialId ?: $referentMaterialId));
        $fgBomId = trim((string) ($request->fgBomId ?: $resolvedSemiFgLv1FgBomId));
        if ($fgBomId === '' && $fgMaterialId !== '') {
          $fgInput = $this->loadFgMaterialInputByMaterialId($fgMaterialId);
          $fgBomId = trim((string) ($fgInput['bomId'] ?? ''));
        }

        $generated = $this->generateSemiFgLv1BomId(
          $fgMaterialId,
          $fgBomId,
          $request->mattype ?: '1',
          $request->subMattype ?: '0'
        );

        if ($generated['error'] !== '') {
          Log::warning('packmaterial.generate-semi-fg-lv1-bom-id.failed', [
            'fgMaterialId' => $fgMaterialId,
            'fgBomId' => $fgBomId,
            'error' => $generated['error'],
          ]);
        }

        $generatedBomId = $generated['bomId'];
        if ($generated['levelMaterialId'] !== '') {
          $request = $request->merge([
            'levelMaterialId' => $generated['levelMaterialId'],
          ]);
        }
      } else {
        $site = trim((string) ($request->site ?: $this->resolveFgSiteByMaterialId($referentMaterialId)));
        $generated = $this->generateFgBomId($referentMaterialId, $site, (string) ($request->user()?->user_login ?? ''));

        if ($generated['error'] !== '') {
          Log::warning('packmaterial.generate-fg-bom-id.failed', [
            'referentMaterialId' => $referentMaterialId,
            'site' => $site,
            'error' => $generated['error'],
          ]);
        }

        $generatedBomId = $generated['bomId'];
      }
    }

    $resolvedBackRoute = $ownerLevel === 'semiFgLv2'
      ? ($request->backRoute ?: 'material-levels.semi-fg-lv2.new')
      : ($ownerLevel === 'semiFgLv1'
        ? ($request->backRoute ?: 'material-levels.semi-fg-lv1.new')
        : ($request->backRoute ?: 'product.view'));
    $resolvedFgMaterialId = $ownerLevel === 'semiFgLv2'
      ? ($request->fgMaterialId ?: $request->referentMaterialId ?: $request->materialId ?: '')
      : ($ownerLevel === 'semiFgLv1'
        ? ($request->fgMaterialId ?: $request->referentMaterialId ?: $request->materialId ?: '')
        : $referentMaterialId);
    $resolvedReferentMaterialId = $ownerLevel === 'semiFgLv2'
      ? ($request->referentMaterialId ?: $request->fgMaterialId ?: $request->materialId ?: '')
      : ($ownerLevel === 'semiFgLv1'
        ? ($request->referentMaterialId ?: $request->fgMaterialId ?: $request->materialId ?: '')
        : $referentMaterialId);
    $resolvedLevelMaterialId = $ownerLevel === 'semiFgLv2'
      ? ($request->materialId ?: $request->levelMaterialId ?: '')
      : ($ownerLevel === 'semiFgLv1'
        ? ($request->materialId ?: $request->levelMaterialId ?: '')
        : $referentMaterialId);
    $resolvedBOMId = $ownerLevel === 'semiFgLv2'
      ? ($request->semiFgLvBomId ?: $request->bomId ?: ($resolvedSemiFgLv2Bom['semiFgLvBomId'] ?? '') ?: ($loadedComponent['bomId'] ?? '') ?: $generatedBomId)
      : ($ownerLevel === 'semiFgLv1'
        ? ($request->semiFgLvBomId ?: $request->bomId ?: ($resolvedSemiFgLv1Bom['semiFgLvBomId'] ?? '') ?: ($loadedComponent['bomId'] ?? '') ?: $generatedBomId)
        : ($loadedComponent['bomId'] ?? $generatedBomId));
    $resolvedBomDesc = $ownerLevel === 'semiFgLv2'
      ? ($request->semiFgLvBomDesc ?: $request->levelBomDesc ?: $request->bomDesc ?: ($resolvedSemiFgLv2Bom['semiFgLvBomDesc'] ?? '') ?: ($loadedComponent['bomDesc'] ?? '') ?: $resolvedSemiFgLv2FgBomDesc ?: '')
      : ($ownerLevel === 'semiFgLv1'
        ? ($request->semiFgLvBomDesc ?: $request->levelBomDesc ?: $request->bomDesc ?: ($resolvedSemiFgLv1Bom['semiFgLvBomDesc'] ?? '') ?: $resolvedSemiFgLv1FgBomDesc ?: '')
        : ($loadedComponent['bomDesc'] ?? $request->bomDesc));
    $resolvedFgBomId = $ownerLevel === 'semiFgLv2'
      ? ($request->fgBomId ?: $resolvedSemiFgLv2FgBomId)
      : ($ownerLevel === 'semiFgLv1'
        ? ($request->fgBomId ?: $resolvedSemiFgLv1FgBomId)
        : $request->fgBomId);
    $resolvedFgBomDesc = $ownerLevel === 'semiFgLv2'
      ? ($request->fgBomDesc ?: $resolvedSemiFgLv2FgBomDesc)
      : ($ownerLevel === 'semiFgLv1'
        ? ($request->fgBomDesc ?: $resolvedSemiFgLv1FgBomDesc)
        : ($request->fgBomDesc ?: ($loadedComponent['bomDesc'] ?? $request->bomDesc)));

    if ($ownerLevel === 'semiFgLv1' && $actionMode === 'create' && ($resolvedFgBomId === '' || !$this->loadSemiFgLv2ByFgBomId($resolvedFgBomId))) {
      throw ValidationException::withMessages([
        'componentId' => 'Create Semi FG Level 2 first.',
      ]);
    }

    $resolvedSite = $request->site
      ?: data_get($request->get('fgDetail', []), 'semiFgLv2.site')
      ?: data_get($request->get('ownerDetail', []), 'site')
      ?: ($resolvedSemiFgLv2['site'] ?? '')
      ?: ($resolvedSemiFgLv1['site'] ?? '')
      ?: '';

    $InputData = [
      'mattype' => $defaultMattype,
      'subMattype' => $loadedComponent['subMattype'] ?? ($request->subMattype ?? ''),
      'actionMode' => $actionMode,
      'ownerLevel' => $ownerLevel,
      'backRoute' => $resolvedBackRoute,
      'backMaterialId' => $request->backMaterialId ?: $request->referentMaterialId ?: $request->materialId ?: $request->fgMaterialId,
      'referentMaterialId' => $resolvedReferentMaterialId,
      'materialId' => $resolvedLevelMaterialId,
      'fgMaterialId' => $resolvedFgMaterialId,
      'bomId' => $resolvedBOMId,
      'bomDesc' => $resolvedBomDesc,
      'semiFgLvBomId' => $ownerLevel === 'fg' ? '' : $resolvedBOMId,
      'semiFgLvBomDesc' => $ownerLevel === 'fg' ? '' : $resolvedBomDesc,
      'fgDetail' => $request->get('fgDetail', []),
      'ownerDetail' => $request->get('ownerDetail', []),
      'components' => $request->get('components', []),
      'fgMaterialId' => $resolvedFgMaterialId,
      'fgBomId' => $resolvedFgBomId,
      'fgBomDesc' => $resolvedFgBomDesc,
      'levelMaterialId' => $request->levelMaterialId ?: $request->get('materialId'),
      'levelSearchDesc' => $request->levelSearchDesc,
      'levelFullDescEn' => $request->levelFullDescEn,
      'levelFullDescTh' => $request->levelFullDescTh,
      'levelUom' => $request->levelUom,
      'site' => $resolvedSite,
      'productCat' => $request->productCat ?: ($loadedComponent['productCat'] ?? ''),
      'productSubCat' => $request->productSubCat ?: ($loadedComponent['productSubCat'] ?? ''),
      'componentId' => $request->componentId ?: ($loadedComponent['componentId'] ?? $componentId),
      'searchDesc' => $request->searchDesc ?: ($loadedComponent['searchDesc'] ?? ''),
      'fullDescEn' => $request->fullDescEn ?: ($loadedComponent['fullDescEn'] ?? ''),
      'fullDescTh' => $request->fullDescTh ?: ($loadedComponent['fullDescTh'] ?? ''),
      'uom' => $request->uom ?: ($loadedComponent['uom'] ?? ''),
    ];

    if ($ownerLevel === 'semiFgLv1') {
      $InputData['subMattype'] = $request->subMattype ?: ($loadedComponent['subMattype'] ?? '0');
    }

    if ($ownerLevel === 'semiFgLv2') {
      $InputData['subMattype'] = $request->subMattype ?: ($resolvedSemiFgLv2['subMattype'] ?? ($loadedComponent['subMattype'] ?? ''));
    }

    $uoms = $this->uoms;
    $viewName = $this->packMaterialViewName($ownerLevel);

    return Inertia::render($viewName, compact('InputData', 'subMattypes', 'uoms'));
  }

  public function productCategories(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'subMattype' => ['nullable'],
      'ownerLevel' => ['nullable', 'in:fg,semiFgLv1,semiFgLv2,businessSupply'],
      'mattype' => ['nullable'],
    ]);

    $subMattype = $validated['subMattype'] ?? null;
    $ownerLevel = $validated['ownerLevel'] ?? 'fg';
    $mattype = trim((string) ($validated['mattype'] ?? '5'));

    if ($subMattype === null || $subMattype === '') {
      return response()->json([
        'subMattype' => '',
        'productCategories' => [],
        'productSubCategories' => [],
      ]);
    }

    if (in_array($ownerLevel, ['semiFgLv1', 'semiFgLv2'], true)) {
      $viewName = $ownerLevel === 'semiFgLv1'
        ? 'PROJ1_2_COMP_SEMI_L1_V'
        : 'PROJ1_2_COMP_SEMI_L2_V';
      $productCategories = $this->packComponentProductCategories($viewName, $mattype, (string) $subMattype);
      $productCat = $productCategories[0]['code'] ?? '';

      return response()->json([
        'subMattype' => (string) $subMattype,
        'productCategories' => $productCategories,
        'productSubCategories' => $this->packComponentProductSubCategories($viewName, $mattype, (string) $subMattype),
        'productCat' => $productCat,
      ]);
    }

    $productCategories = $this->packProductCategories('5', (string) $subMattype);
    $productCat = $productCategories[0]['code'] ?? '';

    return response()->json([
      'subMattype' => (string) $subMattype,
      'productCategories' => $productCategories,
      'productSubCategories' => $this->packProductSubCategories('5', (string) $subMattype, $productCat),
      'productCat' => $productCat,
    ]);
  }

  public function generateComponentId(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'productSubCat' => ['required'],
      'ownerLevel' => ['nullable', 'in:fg,semiFgLv1,semiFgLv2,businessSupply'],
    ]);

    $ownerLevel = $validated['ownerLevel'] ?? 'fg';
    $componentId = null;
    $error = null;
    $userLogin = (string) ($request->user()?->user_login ?? 'system');
    $pdo = DB::getPdo();

    if ($ownerLevel === 'semiFgLv2') {
      $stmt = $pdo->prepare('BEGIN proj1_2_gen_comp_bom_semi_l2(:p_semi_l2_sub_cat, :p_user_login, :p_componenid, :P_ERROR); END;');
      $stmt->bindParam(':p_semi_l2_sub_cat', $validated['productSubCat'], PDO::PARAM_STR);
      $stmt->bindParam(':p_user_login', $userLogin, PDO::PARAM_STR);
    } elseif ($ownerLevel === 'semiFgLv1') {
      $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_COMP_BOM_SEMI_L1(:p_prd_sub_cat, :p_user_login, :p_componenid, :p_error); END;');
      $stmt->bindParam(':p_prd_sub_cat', $validated['productSubCat'], PDO::PARAM_STR);
      $stmt->bindParam(':p_user_login', $userLogin, PDO::PARAM_STR);
    } else {
      $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_COMP_BOMFG(:p_prd_sub_cat, :p_user_login, :p_componenid, :p_error); END;');
      $stmt->bindParam(':p_prd_sub_cat', $validated['productSubCat'], PDO::PARAM_STR);
      $stmt->bindParam(':p_user_login', $userLogin, PDO::PARAM_STR);
    }

    $stmt->bindParam(':p_componenid', $componentId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_error', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    return response()->json([
      'program' => $ownerLevel === 'semiFgLv2'
        ? 'PROJ1_2_GEN_COMP_BOM_SEMI_L2'
        : ($ownerLevel === 'semiFgLv1' ? 'PROJ1_2_GEN_COMP_BOM_SEMI_L1' : 'PROJ1_2_GEN_COMP_BOMFG'),
      'componentId' => $componentId,
      'error' => $this->resolveProcedureErrorMessage($error),
    ]);
  }
}
