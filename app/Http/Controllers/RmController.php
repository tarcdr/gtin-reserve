<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Dash;
use App\Models\SheetAvailability;
use App\Models\SheetCustPartNum;
use App\Models\SheetFinancial;
use App\Models\SheetGeneral;
use App\Models\SheetGtins;
use App\Models\SheetLogistics;
use App\Models\SheetPlanning;
use App\Models\SheetQtyConvers;
use App\Models\SheetSalesData;
use App\Models\SheetSuppPartNum;
use App\Models\SheetUomChar;

use Illuminate\Support\Facades\DB;
// เปิด Query Log
DB::enableQueryLog();

class RmController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function viewComponentRequest(Request $request): Response
    {
      $boms = [
        ['code' => 'BOM001', 'label' => 'BOM 001 Description'],
        ['code' => 'BOM002', 'label' => 'BOM 002 Description'],
      ];
      return Inertia::render('RM/ComponentRequest', [ "boms" => $boms ]);
    }
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): Response
    {
      $input = [
        "bom" => $request->bom
      ];
      return Inertia::render('RM/MaterialCreate', [ "InputData" => $input ]);
    }

    public function report(Request $request, $tab = 'tab1'): Response
    {
      $columns = match ($tab) {
          'tab1' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'planning_area_id',
              'label' => 'PLANNING_AREA_ID',
            ], [
              'name' => 'status',
              'label' => 'STATUS',
            ], [
              'name' => 'availability_check_scope',
              'label' => 'AVAILABILITY_CHECK_SCOPE',
            ],
          ],
          'tab2' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'customer_id',
              'label' => 'CUSTOMER_ID',
            ], [
              'name' => 'customer_part_number',
              'label' => 'CUSTOMER_PART_NUMBER',
            ],
          ],
          'tab3' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'company_id',
              'label' => 'COMPANY_ID',
            ], [
              'name' => 'business_residence_id',
              'label' => 'BUSINESS_RESIDENCE_ID',
            ],
          ],
          'tab4' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'material_desc',
              'label' => 'MATERIAL_DESC',
            ], [
              'name' => 'full_material_desc',
              'label' => 'FULL_MATERIAL_DESC',
            ], [
              'name' => 'meterial_desc_th',
              'label' => 'METERIAL_DESC_TH',
            ], [
              'name' => 'product_category_id',
              'label' => 'PRODUCT_CATEGORY_ID',
            ], [
              'name' => 'mat_type',
              'label' => 'MAT_TYPE',
            ], [
              'name' => 'sub_type',
              'label' => 'SUB_TYPE',
            ], [
              'name' => 'brand',
              'label' => 'BRAND',
            ], [
              'name' => 'base_uom',
              'label' => 'BASE_UOM',
            ], [
              'name' => 'inv_valuation_uom',
              'label' => 'INV_VALUATION_UOM',
            ], [
              'name' => 'pillar',
              'label' => 'PILLAR',
            ], [
              'name' => 'division',
              'label' => 'DIVISION',
            ], [
              'name' => 'department',
              'label' => 'DEPARTMENT',
            ], [
              'name' => 'sub_department',
              'label' => 'SUB_DEPARTMENT',
            ], [
              'name' => 'class',
              'label' => 'CLASS',
            ], [
              'name' => 'sub_class',
              'label' => 'SUB_CLASS',
            ], [
              'name' => 'section',
              'label' => 'SECTION',
            ], [
              'name' => 'series',
              'label' => 'SERIES',
            ], [
              'name' => 'attribute_1',
              'label' => 'ATTRIBUTE_1',
            ], [
              'name' => 'register_off',
              'label' => 'REGISTER_OFF',
            ], [
              'name' => 'shelf_life',
              'label' => 'SHELF_LIFE',
            ], [
              'name' => 'hs_code',
              'label' => 'HS_CODE',
            ], [
              'name' => 'country',
              'label' => 'COUNTRY',
            ], [
              'name' => 'old_product_id',
              'label' => 'OLD_PRODUCT_ID',
            ], [
              'name' => 'identified_stock_type',
              'label' => 'IDENTIFIED_STOCK_TYPE',
            ], [
              'name' => 'serial_number_profile',
              'label' => 'SERIAL_NUMBER_PROFILE',
            ], [
              'name' => 'retail_sales_price',
              'label' => 'RETAIL_SALES_PRICE',
            ], [
              'name' => 'product_core',
              'label' => 'PRODUCT_CORE',
            ], [
              'name' => 'attribute_2',
              'label' => 'ATTRIBUTE_2',
            ], [
              'name' => 'detail_name',
              'label' => 'DETAIL_NAME',
            ],
          ],
          'tab5' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'trading_unit',
              'label' => 'TRADING_UNIT',
            ], [
              'name' => 'gtin_number',
              'label' => 'GTIN_NUMBER',
            ],
          ],
          'tab6' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'site_id',
              'label' => 'SITE_ID',
            ], [
              'name' => 'status',
              'label' => 'STATUS',
            ], [
              'name' => 'storage_group_id',
              'label' => 'STORAGE_GROUP_ID',
            ],
          ],
          'tab7' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'planning_area_id',
              'label' => 'PLANNING_AREA_ID',
            ], [
              'name' => 'status',
              'label' => 'STATUS',
            ], [
              'name' => 'planning_uom',
              'label' => 'PLANNING_UOM',
            ], [
              'name' => 'demand_manage_procedure',
              'label' => 'DEMAND_MANAGE_PROCEDURE',
            ], [
              'name' => 'procurement_type',
              'label' => 'PROCUREMENT_TYPE',
            ], [
              'name' => 'planning_procedure',
              'label' => 'PLANNING_PROCEDURE',
            ], [
              'name' => 'lot_sizing_method',
              'label' => 'LOT_SIZING_METHOD',
            ],
          ],
          'tab8' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'quantity',
              'label' => 'QUANTITY',
            ], [
              'name' => 'quantity_uom',
              'label' => 'QUANTITY_UOM',
            ], [
              'name' => 'corres_qty',
              'label' => 'CORRES_QTY',
            ], [
              'name' => 'corres_qty_uom',
              'label' => 'CORRES_QTY_UOM',
            ],
          ],
          'tab9' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'sales_org_id',
              'label' => 'SALES_ORG_ID',
            ], [
              'name' => 'distribution_channel',
              'label' => 'DISTRIBUTION_CHANNEL',
            ], [
              'name' => 'status',
              'label' => 'STATUS',
            ], [
              'name' => 'sales_uom',
              'label' => 'SALES_UOM',
            ], [
              'name' => 'item_group',
              'label' => 'ITEM_GROUP',
            ],
          ],
          'tab10' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'supplier_id',
              'label' => 'SUPPLIER_ID',
            ], [
              'name' => 'supplier_part_number',
              'label' => 'SUPPLIER_PART_NUMBER',
            ], [
              'name' => 'supplier_lead_time',
              'label' => 'SUPPLIER_LEAD_TIME',
            ],
          ],
          'tab11' => [
            [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'unit_of_measure',
              'label' => 'UNIT_OF_MEASURE',
            ], [
              'name' => 'net_weight',
              'label' => 'NET_WEIGHT',
            ], [
              'name' => 'uom_net_weight',
              'label' => 'UOM_NET_WEIGHT',
            ], [
              'name' => 'gross_weight',
              'label' => 'GROSS_WEIGHT',
            ], [
              'name' => 'uom_gross_weight',
              'label' => 'UOM_GROSS_WEIGHT',
            ], [
              'name' => 'net_volume',
              'label' => 'NET_VOLUME',
            ], [
              'name' => 'uom_net_volume',
              'label' => 'UOM_NET_VOLUME',
            ], [
              'name' => 'gross_volume',
              'label' => 'GROSS_VOLUME',
            ], [
              'name' => 'uom_gross_volume',
              'label' => 'UOM_GROSS_VOLUME',
            ], [
              'name' => 'lengths',
              'label' => 'LENGTHS',
            ], [
              'name' => 'uom_length',
              'label' => 'UOM_LENGTH',
            ], [
              'name' => 'width',
              'label' => 'WIDTH',
            ], [
              'name' => 'uom_width',
              'label' => 'UOM_WIDTH',
            ], [
              'name' => 'height',
              'label' => 'HEIGHT',
            ], [
              'name' => 'uom_height',
              'label' => 'UOM_HEIGHT',
            ], [
              'name' => 'quantity',
              'label' => 'QUANTITY',
            ], [
              'name' => 'quantity_uom',
              'label' => 'QUANTITY_UOM',
            ], [
              'name' => 'quantity_type_char',
              'label' => 'QUANTITY_TYPE_CHAR',
            ],
          ],
          default => [],
      };
      $datas = match ($tab) {
          'tab1'  => SheetAvailability::all(),
          'tab2'  => SheetCustPartNum::all(),
          'tab3'  => SheetFinancial::all(),
          'tab4'  => SheetGeneral::all(),
          'tab5'  => SheetGtins::all(),
          'tab6'  => SheetLogistics::all(),
          'tab7'  => SheetPlanning::all(),
          'tab8'  => SheetQtyConvers::all(),
          'tab9'  => SheetSalesData::all(),
          'tab10' => SheetSuppPartNum::all(),
          'tab11' => SheetUomChar::all(),
          default => [],
      };
      $error = session('error');  // ข้อความ error
      $success = session('success');  // ข้อความ success
      $activeTab = $tab;

      return Inertia::render('RM/Report', compact('error', 'success', 'columns', 'datas', 'activeTab'));
    }

    public function update(Request $request): RedirectResponse
    {
        // Match $tab เพื่อกำหนดการทำงานที่แตกต่างกัน
        $modelClass = match ($request->tab) {
            'tab1'  => SheetAvailability::class,
            'tab2'  => SheetCustPartNum::class,
            'tab3'  => SheetFinancial::class,
            'tab4'  => SheetGeneral::class,
            'tab5'  => SheetGtins::class,
            'tab6'  => SheetLogistics::class,
            'tab7'  => SheetPlanning::class,
            'tab8'  => SheetQtyConvers::class,
            'tab9'  => SheetSalesData::class,
            'tab10' => SheetSuppPartNum::class,
            'tab11' => SheetUomChar::class,
            default => throw new InvalidArgumentException('Invalid tab value'),
        };

        // ดึง Content จาก Request
        $content = $request->getContent();

        // แปลง Content (กรณีเป็น JSON)
        $data = json_decode($content, true);

        // ตรวจสอบว่า Content ถูกต้องและมีข้อมูลที่จำเป็น
        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()
                ->route('rm.report', ['tab' => $request->tab])
                ->with('error', 'Invalid JSON content.');
        }

        // ดึงชื่อ Primary Key จากโมเดล
        $primaryKeys = (new $modelClass)->getKeyName();

        // ตรวจสอบว่า Primary Keys มีอยู่ใน Content
        $keys = is_array($primaryKeys) ? array_intersect_key($data, array_flip($primaryKeys)) : [$primaryKeys => $data[$primaryKeys] ?? null];

        if (empty(array_filter($keys))) {
            return redirect()
                ->route('rm.report', ['tab' => $request->tab])
                ->with('error', 'Primary keys are required for updating data.');
        }

        $conditions = is_array($keys) ? array_intersect_key($data, array_flip($keys)) : [$keys => $data[$keys]];
    
        $record = $modelClass::query()->where($conditions)->first();

        if (!$record) {
            return redirect()
                ->route('rm.report', ['tab' => $request->tab])
                ->with('error', 'Record not found.');
        }

        // อัปเดตข้อมูลในโมเดล
        $record->fill($data);
        $record->save();

        return redirect()
            ->route('rm.report', ['tab' => $request->tab])
            ->with('success', 'Data updated successfully.');
    }
}
