<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Redirect;
use App\Models\MasterUOM;

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
      'subMattype' => '1',
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
    $InputData = $this->baseInput($request, 'proj1_semi_fg_lv2', '1', '2');
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

  public function businessSupply(Request $request): Response
  {
    $InputData = $this->baseInput($request, 'proj1_business_supply', '9', '0');
    $components = $this->resolveComponents($request);
    return Inertia::render('MaterialLevels/BusinessSupply', [
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
    $fgDetail['semiFgLv2'] = $detail;
    $this->persistFgDraft($request, $fgDetail);

    return Redirect::route('material-levels.semi-fg-lv2.new', [
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

  public function saveBusinessSupply(Request $request): RedirectResponse
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
    $fgDetail['businessSupply'] = $detail;
    $this->persistFgDraft($request, $fgDetail);

    return Redirect::route('material-levels.business-supply.new', [
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

  public function createComponentBusinessSupply(Request $request): RedirectResponse
  {
    $payload = $request->all();
    $payload['ownerLevel'] = 'businessSupply';
    $payload['subMattype'] = '0';
    return Redirect::route('packmaterial.new', $payload);
  }
}
