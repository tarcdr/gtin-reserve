<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\MasterUOM;
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

  protected function baseInput(Request $request, string $table, string $mattype, string $subMattype): array
  {
    return [
      'mode'         => $request->get('mode', $request->get('levelMaterialId') ? 'view' : 'create'),
      'fgDetail'     => $request->get('fgDetail', []),
      'fgMaterialId' => $request->get('materialId') ?? $request->get('fgMaterialId'),
      'fgBomId'      => $request->get('bomId') ?? $request->get('fgBomId'),
      'fgBomDesc'    => $request->get('bomDesc') ?? $request->get('fgBomDesc'),
      'parentMattype'=> $request->get('parentMattype') ?? $request->get('mattype'),
      'parentSubMattype' => $request->get('parentSubMattype') ?? $request->get('subMattype'),
      'mattype'      => $mattype,
      'subMattype'   => $subMattype,
      'materialId'   => $request->get('levelMaterialId') ?? '',
      'searchDesc'   => $request->get('searchDesc') ?? '',
      'fullDescEn'   => $request->get('fullDescEn') ?? '',
      'fullDescTh'   => $request->get('fullDescTh') ?? '',
      'uom'          => $request->get('uom') ?? '',
      'storageTable' => $table,
    ];
  }

  protected function resolveComponents(Request $request, array $fallback = []): array
  {
    return collect($request->get('components', $fallback))
      ->filter(fn($item) => is_array($item) && !empty($item['code']))
      ->keyBy('code')
      ->values()
      ->all();
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
    if (($InputData['mode'] ?? 'create') === 'create') {
      $InputData['subMattype'] = '';
    }
    $semiFgLv2 = data_get($InputData, 'fgDetail.semiFgLv2', []);
    $InputData['levelBomId'] = $request->get('levelBomId') ?? ($semiFgLv2['bomId'] ?? '');
    $InputData['levelBomDesc'] = $request->get('levelBomDesc') ?? ($semiFgLv2['bomDesc'] ?? '');
    $components = $this->resolveComponents($request);
    return Inertia::render('MaterialLevels/SemiFgLevel2', [
      'InputData' => $InputData,
      'mattypes' => $this->mattypes,
      'subMattypes' => $this->subMattypes,
      'uoms' => $this->uoms,
      'components' => $components,
    ]);
  }

  public function semiFgLevel1(Request $request): Response
  {
    $InputData = $this->baseInput($request, 'proj1_semi_fg_lv1', '1', '1');
    $components = $this->resolveComponents($request);
    return Inertia::render('MaterialLevels/SemiFgLevel1', [
      'InputData' => $InputData,
      'mattypes' => $this->mattypes,
      'subMattypes' => $this->subMattypes,
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

    $fgDetail = $request->get('fgDetail', []);
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

    return Redirect::route('material-levels.semi-fg-lv2.new', [
      'mode' => 'view',
      'fgDetail' => $fgDetail,
      'materialId' => $fgDetail['materialId'] ?? null,
      'bomId' => $fgDetail['bomId'] ?? null,
      'bomDesc' => $fgDetail['bomDesc'] ?? null,
      'levelBomId' => $detail['bomId'],
      'levelBomDesc' => $detail['bomDesc'],
      'levelMaterialId' => $detail['id'],
      'searchDesc' => $detail['searchDesc'],
      'fullDescEn' => $detail['fullDescEn'],
      'fullDescTh' => $detail['fullDescTh'],
      'uom' => $detail['uom'],
      'components' => $detail['components'],
    ]);
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
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN proj1_2_gen_semifg_lv2(:p_fg_matid, :p_fg_bomid, :p_mattype, :p_sub_mattype, :p_semifg_lv2_bomid, :p_semifg_lv2_id); END;');
    $stmt->bindParam(':p_fg_matid', $fgMaterialId, PDO::PARAM_STR);
    $stmt->bindParam(':p_fg_bomid', $fgBomId, PDO::PARAM_STR);
    $stmt->bindParam(':p_mattype', $mattype, PDO::PARAM_STR);
    $stmt->bindParam(':p_sub_mattype', $subMattype, PDO::PARAM_STR);
    $stmt->bindParam(':p_semifg_lv2_bomid', $levelBomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_semifg_lv2_id', $levelMaterialId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->execute();

    return response()->json([
      'program' => 'proj1_2_gen_semifg_lv2',
      'levelBomId' => $levelBomId,
      'materialId' => $levelMaterialId,
    ]);
  }

  public function saveSemiFgLevel1(Request $request): RedirectResponse
  {
    $fgDetail = $request->get('fgDetail', []);
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
      'fgDetail' => $fgDetail,
      'materialId' => $fgDetail['materialId'] ?? null,
      'bomId' => $fgDetail['bomId'] ?? null,
      'bomDesc' => $fgDetail['bomDesc'] ?? null,
      'levelMaterialId' => $detail['id'],
      'searchDesc' => $detail['searchDesc'],
      'fullDescEn' => $detail['fullDescEn'],
      'fullDescTh' => $detail['fullDescTh'],
      'uom' => $detail['uom'],
      'components' => $detail['components'],
    ]);
  }

  public function createComponentRaw(Request $request): RedirectResponse
  {
    return Redirect::route('material-levels.raw.new', $request->all());
  }

  public function createComponentSemiFgLevel2(Request $request): RedirectResponse
  {
    $payload = $request->all();
    $payload['ownerLevel'] = 'semiFgLv2';
    $payload['subMattype'] = '0';
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
