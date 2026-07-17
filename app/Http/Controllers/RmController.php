<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\SheetAvailability;
use App\Models\SheetCustPartNum;
use App\Models\SheetFinancial;
use App\Models\SheetGeneral;
use App\Models\SheetGtins;
use App\Models\SheetBomGeneral;
use App\Models\SheetInputProducts;
use App\Models\SheetLogistics;
use App\Models\SheetPlanning;
use App\Models\SheetQtyConvers;
use App\Models\SheetSalesData;
use App\Models\SheetSuppPartNum;
use App\Models\SheetUomChar;
use App\Models\Labels;
use App\Models\OracleTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use PDO;

// use Illuminate\Support\Facades\DB;
// เปิด Query Log
// DB::enableQueryLog();

class RmController extends Controller
{
    private function fetchMasterOptions(string $table, string $chooseColumn, string $useColumn): array
    {
      try {
        $model = new OracleTable();
        $model->setTable($table);

        return $model->newQuery()
          ->select([
            DB::raw("{$chooseColumn} as option_label"),
            DB::raw("{$useColumn} as option_value"),
          ])
          ->whereNotNull($useColumn)
          ->distinct()
          ->orderBy($chooseColumn)
          ->get()
          ->map(function ($row) {
            $label = $row->option_label ?? $row->option_value;
            $value = $row->option_value ?? $row->option_label;

            return [
              'label' => $label,
              'value' => $value,
              'code' => $value,
            ];
          })
          ->filter(fn ($row) => filled($row['value']))
          ->values()
          ->all();
      } catch (\Throwable $exception) {
        return [];
      }
    }

    private function reportFieldOptions(): array
    {
      return [
        'AVAILABILITY' => [
          'planning_area_id' => $this->fetchMasterOptions('PROJ1_2_MASTER_PLANNING_AREA', 'PLANNING_AREA_LIST', 'PLANNING_AREA_ID'),
          'status' => $this->fetchMasterOptions('PROJ1_2_MASTER_STATUS_ITEM', 'STATUS', 'STATUS'),
        ],
        'CUST_PART_NUM' => [
          'customer_id' => $this->fetchMasterOptions('PROJ1_2_MASTER_CUSTOMER', 'CUSTOMER_ID_LIST', 'CUSTOMER_ID'),
          'customer_part_number' => $this->fetchMasterOptions('PROJ1_2_MASTER_CUST_PART_NUM', 'CUSTOMER_PART_NUMBER', 'CUSTOMER_PART_NUMBER'),
        ],
        'FINANCIAL' => [
          'company_id' => $this->fetchMasterOptions('PROJ1_2_MASTER_COMPANY', 'COMPANY_LIST', 'COMPANY_ID'),
          'business_residence_id' => $this->fetchMasterOptions('PROJ1_2_MASTER_BUSINESS_RES', 'BUSINESS_RES_LIST', 'BUSINESS_RESIDENCE_ID'),
        ],
        'GENERAL' => [
          'product_category_id' => $this->fetchMasterOptions('PROJ1_2_MASTER_PRODUCT_CAT', 'PRODUCT_CAT_LIST', 'PRODUCT_CATEGORY_ID'),
          'pillar' => $this->fetchMasterOptions('PROJ1_2_MASTER_PILLAR', 'PILLAR', 'PILLAR'),
          'division' => $this->fetchMasterOptions('PROJ1_2_MASTER_DIVISION', 'DIVISION', 'DIVISION'),
          'department' => $this->fetchMasterOptions('PROJ1_2_MASTER_DEPARTMENT', 'DEPARTMENT_LIST', 'DESCRIPTION_DEPARTMENT'),
          'sub_department' => $this->fetchMasterOptions('PROJ1_2_MASTER_SUBDEPARTMENT', 'SUBDEPARTMENT_LIST', 'DESCRIPTION_SUBDEPARTMENT'),
          'class' => $this->fetchMasterOptions('PROJ1_2_MASTER_CLASS', 'CLASS', 'CLASS'),
          'sub_class' => $this->fetchMasterOptions('PROJ1_2_MASTER_SUB_CLASS', 'SUB_CLASS', 'SUB_CLASS'),
          'section' => $this->fetchMasterOptions('PROJ1_2_MASTER_SECTION', 'SECTION', 'SECTION'),
          'series' => $this->fetchMasterOptions('PROJ1_2_MASTER_SERIES', 'SERIES', 'SERIES'),
          'attribute_1' => $this->fetchMasterOptions('PROJ1_2_MASTER_ATTRIBUTE1', 'ATTRIBUTE_1', 'ATTRIBUTE_1'),
          'hs_code' => $this->fetchMasterOptions('PROJ1_2_MASTER_HS_CODE', 'HS_CODE', 'HS_CODE'),
          'country' => $this->fetchMasterOptions('PROJ1_2_MASTER_COUNTRY', 'COUNTRY', 'COUNTRY'),
          'attribute_2' => $this->fetchMasterOptions('PROJ1_2_MASTER_ATTRIBUTE_2', 'ATTRIBUTE_2', 'ATTRIBUTE_2'),
        ],
        'GTINS' => [
          'trading_unit' => $this->fetchMasterOptions('PROJ1_2_MASTER_TRADING_UNIT', 'TRADING_UNIT', 'TRADING_UNIT'),
        ],
        'LOGISTICS' => [
          'status' => $this->fetchMasterOptions('PROJ1_2_MASTER_STATUS_ITEM', 'STATUS', 'STATUS'),
          'storage_group_id' => $this->fetchMasterOptions('PROJ1_2_MASTER_STORAGE_GROUP', 'STORAGE_GROUP_LIST', 'STORAGE_GROUP_ID'),
        ],
        'PLANNING' => [
          'planning_area_id' => $this->fetchMasterOptions('PROJ1_2_MASTER_PLANNING_AREA', 'PLANNING_AREA_LIST', 'PLANNING_AREA_ID'),
          'status' => $this->fetchMasterOptions('PROJ1_2_MASTER_STATUS_ITEM', 'STATUS', 'STATUS'),
          'planning_uom' => $this->fetchMasterOptions('PROJ1_2_MASTER_PLANNING_UOM', 'PLANNING_UOM', 'PLANNING_UOM'),
          'demand_manage_procedure' => $this->fetchMasterOptions('PROJ1_2_MASTER_DEMAND_PROC', 'DEMAND_MANAGE_PROCEDURE', 'DEMAND_MANAGE_PROCEDURE'),
          'procurement_type' => $this->fetchMasterOptions('PROJ1_2_MASTER_PROCURE_TYPE', 'PROCUREMENT_TYPE', 'PROCUREMENT_TYPE'),
          'planning_procedure' => $this->fetchMasterOptions('PROJ1_2_MASTER_PLANNING_PROC', 'PLANNING_PROCEDURE', 'PLANNING_PROCEDURE'),
          'lot_sizing_method' => $this->fetchMasterOptions('PROJ1_2_MASTER_LOT_SIZING_METH', 'LOT_SIZING_METHOD', 'LOT_SIZING_METHOD'),
        ],
        'QTY_CONVERS' => [
          'quantity_uom' => $this->fetchMasterOptions('PROJ1_2_MASTER_UOM', 'QUANTITY_UOM_LIST', 'QUANTITY_UOM_LIST'),
          'corres_qty_uom' => $this->fetchMasterOptions('PROJ1_2_MASTER_CORRES_UOM', 'CORRES_QTY_UOM', 'CORRES_QTY_UOM'),
        ],
        'INPUT_PRODUCTS' => [
          'quantity_uom' => $this->fetchMasterOptions('PROJ1_2_MASTER_UOM', 'QUANTITY_UOM_LIST', 'QUANTITY_UOM_LIST'),
        ],
        'SALES_DATA' => [
          'sales_org_id' => $this->fetchMasterOptions('PROJ1_2_MASTER_SALES_ORG', 'SALES_ORG_ID', 'SALES_ORG_ID'),
          'distribution_channel' => $this->fetchMasterOptions('PROJ1_2_MASTER_DIST_CHANNEL', 'DIST_CHANNEL_LIST', 'DIST_CHANNEL_LIST'),
          'status' => $this->fetchMasterOptions('PROJ1_2_MASTER_STATUS_ITEM', 'STATUS', 'STATUS'),
          'sales_uom' => $this->fetchMasterOptions('PROJ1_2_MASTER_SALES_UOM', 'SALES_UOM', 'SALES_UOM'),
          'item_group' => $this->fetchMasterOptions('PROJ1_2_MASTER_ITEM_GROUP', 'ITEM_GROUP', 'ITEM_GROUP'),
        ],
        'SUPP_PART_NUM' => [
          'supplier_id' => $this->fetchMasterOptions('PROJ1_2_MASTER_SUPPLIER', 'SUPPLIER_LIST', 'SUPPLIER_ID'),
          'supplier_part_number' => $this->fetchMasterOptions('PROJ1_2_MASTER_SUPPL_PART_NUM', 'SUPPLIER_PART_NUMBER', 'SUPPLIER_PART_NUMBER'),
        ],
        'UOM_CHAR' => [
          'quantity_uom' => $this->fetchMasterOptions('PROJ1_2_MASTER_UOM', 'QUANTITY_UOM_LIST', 'DESCRIPTION_UOM'),
        ],
      ];
    }

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

    public function report(Request $request, $tab = 'AVAILABILITY'): Response
    {
      $columns = match ($tab) {
          'AVAILABILITY'  => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
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
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'CUST_PART_NUM' => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'customer_id',
              'label' => 'CUSTOMER_ID',
            ], [
              'name' => 'customer_part_number',
              'label' => 'CUSTOMER_PART_NUMBER',
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'FINANCIAL'     => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'company_id',
              'label' => 'COMPANY_ID',
            ], [
              'name' => 'business_residence_id',
              'label' => 'BUSINESS_RESIDENCE_ID',
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'GENERAL'       => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
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
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'BOM_GENERAL'   => [
            [
              'name' => 'bom_id',
              'label' => 'BOM_ID',
            ], [
              'name' => 'variant_id',
              'label' => 'VARIANT_ID',
            ], [
              'name' => 'language',
              'label' => 'LANGUAGE',
            ], [
              'name' => 'variant_desc',
              'label' => 'VARIANT_DESC',
            ], [
              'name' => 'long_text',
              'label' => 'LONG_TEXT',
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ], [
              'name' => 'user_role',
              'label' => 'USER_ROLE',
              'hidden' => true,
            ], [
              'name' => 'create_date',
              'label' => 'CREATE_DATE',
              'hidden' => true,
            ], [
              'name' => 'user_update',
              'label' => 'USER_UPDATE',
              'hidden' => true,
            ], [
              'name' => 'update_date',
              'label' => 'UPDATE_DATE',
              'hidden' => true,
            ],
          ],
          'INPUT_PRODUCTS' => [
            [
              'name' => 'bom_id',
              'label' => 'BOM_ID',
            ], [
              'name' => 'variant_id',
              'label' => 'VARIANT_ID',
            ], [
              'name' => 'line_item_grp_id',
              'label' => 'LINE_ITEM_GRP_ID',
            ], [
              'name' => 'line_item_bom',
              'label' => 'LINE_ITEM_BOM',
            ], [
              'name' => 'input_prod_id',
              'label' => 'INPUT_PROD_ID',
            ], [
              'name' => 'quantity',
              'label' => 'QUANTITY',
            ], [
              'name' => 'quantity_uom',
              'label' => 'QUANTITY_UOM',
            ], [
              'name' => 'engr_chg_order_id',
              'label' => 'ENGR_CHG_ORDER_ID',
            ], [
              'name' => 'fixed_qty_indi',
              'label' => 'FIXED_QTY_INDI',
            ], [
              'name' => 'deleted',
              'label' => 'DELETED',
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ], [
              'name' => 'user_role',
              'label' => 'USER_ROLE',
              'hidden' => true,
            ], [
              'name' => 'create_date',
              'label' => 'CREATE_DATE',
              'hidden' => true,
            ], [
              'name' => 'user_update',
              'label' => 'USER_UPDATE',
              'hidden' => true,
            ], [
              'name' => 'update_date',
              'label' => 'UPDATE_DATE',
              'hidden' => true,
            ],
          ],
          'GTINS'         => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
              'name' => 'material_id',
              'label' => 'MATERIAL_ID',
            ], [
              'name' => 'trading_unit',
              'label' => 'TRADING_UNIT',
            ], [
              'name' => 'gtin_number',
              'label' => 'GTIN_NUMBER',
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'LOGISTICS'     => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
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
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'PLANNING'      => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
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
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'QTY_CONVERS'   => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
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
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'SALES_DATA'    => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
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
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'SUPP_PART_NUM' => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
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
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          'UOM_CHAR'      => [
            [
              'name' => 'no',
              'label' => 'NO',
              'hidden' => true
            ], [
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
            ], [
              'name' => 'status_row',
              'label' => 'STATUS_ROW',
            ], [
              'name' => 'user_create',
              'label' => 'USER_CREATE',
            ],
          ],
          default => [],
      };
      $datas = match ($tab) {
        'AVAILABILITY'  => SheetAvailability::get(),
        'CUST_PART_NUM' => SheetCustPartNum::get(),
        'FINANCIAL'     => SheetFinancial::get(),
        'BOM_GENERAL'   => SheetBomGeneral::get(),
        'GENERAL'       => SheetGeneral::get(),
        'GTINS'         => SheetGtins::get(),
        'INPUT_PRODUCTS'=> SheetInputProducts::get(),
        'LOGISTICS'     => SheetLogistics::get(),
        'PLANNING'      => SheetPlanning::get(),
        'QTY_CONVERS'   => SheetQtyConvers::get(),
        'SALES_DATA'    => SheetSalesData::get(),
        'SUPP_PART_NUM' => SheetSuppPartNum::get(),
        'UOM_CHAR'      => SheetUomChar::get(),
        default => [],
      };
      $labels = match ($tab) {
        'AVAILABILITY'  => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'AVAILABILITY')->get(),
        'CUST_PART_NUM' => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'CUST_PART_NUM')->get(),
        'FINANCIAL'     => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'FINANCIAL')->get(),
        'BOM_GENERAL'   => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'BOM_GENERAL')->get(),
        'GENERAL'       => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'GENERAL')->get(),
        'GTINS'         => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'GTINS')->get(),
        'INPUT_PRODUCTS'=> Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'INPUT_PRODUCTS')->get(),
        'LOGISTICS'     => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'LOGISTICS')->get(),
        'PLANNING'      => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'PLANNING')->get(),
        'QTY_CONVERS'   => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'QTY_CONVERS')->get(),
        'SALES_DATA'    => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'SALES_DATA')->get(),
        'SUPP_PART_NUM' => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'SUPP_PART_NUM')->get(),
        'UOM_CHAR'      => Labels::where('label_page', 'TEMPLATE')->where('label_tab', 'UOM_CHAR')->get(),
        default => [],
      };
      $error = session('error');  // ข้อความ error
      $success = session('success');  // ข้อความ success
      $activeTab = $tab;
      $fieldOptions = $this->reportFieldOptions();

      return Inertia::render('RM/Report', compact('error', 'success', 'columns', 'datas', 'activeTab', 'labels', 'fieldOptions'));
    }

    public function update(Request $request): RedirectResponse
    {
        // Match $tab เพื่อกำหนดการทำงานที่แตกต่างกัน
        $modelClass = match ($request->tab) {
          'AVAILABILITY'  => SheetAvailability::class,
          'CUST_PART_NUM' => SheetCustPartNum::class,
          'FINANCIAL'     => SheetFinancial::class,
          'BOM_GENERAL'   => SheetBomGeneral::class,
          'GENERAL'       => SheetGeneral::class,
          'GTINS'         => SheetGtins::class,
          'INPUT_PRODUCTS'=> SheetInputProducts::class,
          'LOGISTICS'     => SheetLogistics::class,
          'PLANNING'      => SheetPlanning::class,
          'QTY_CONVERS'   => SheetQtyConvers::class,
          'SALES_DATA'    => SheetSalesData::class,
          'SUPP_PART_NUM' => SheetSuppPartNum::class,
          'UOM_CHAR'      => SheetUomChar::class,
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

        if (collect($keys)->every(fn ($value) => !filled($value))) {
            return redirect()
                ->route('rm.report', ['tab' => $request->tab])
                ->with('error', 'Primary keys are required for updating data.');
        }

        $conditions = $keys;
    
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

    public function delete(Request $request): RedirectResponse
    {
        // Match $tab เพื่อกำหนดการทำงานที่แตกต่างกัน
        $modelClass = match ($request->tab) {
          'AVAILABILITY'  => SheetAvailability::class,
          'CUST_PART_NUM' => SheetCustPartNum::class,
          'FINANCIAL'     => SheetFinancial::class,
          'BOM_GENERAL'   => SheetBomGeneral::class,
          'GENERAL'       => SheetGeneral::class,
          'GTINS'         => SheetGtins::class,
          'INPUT_PRODUCTS'=> SheetInputProducts::class,
          'LOGISTICS'     => SheetLogistics::class,
          'PLANNING'      => SheetPlanning::class,
          'QTY_CONVERS'   => SheetQtyConvers::class,
          'SALES_DATA'    => SheetSalesData::class,
          'SUPP_PART_NUM' => SheetSuppPartNum::class,
          'UOM_CHAR'      => SheetUomChar::class,
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

        if (collect($keys)->every(fn ($value) => !filled($value))) {
            return redirect()
                ->route('rm.report', ['tab' => $request->tab])
                ->with('error', 'Primary keys are required for updating data.');
        }

        $conditions = $keys;
    
        $record = $modelClass::query()->where($conditions)->first();

        if (!$record) {
            return redirect()
                ->route('rm.report', ['tab' => $request->tab])
                ->with('error', 'Record not found.');
        }

        // อัปเดตข้อมูลในโมเดล
        $record->fill($data);
        $record->delete();

        return redirect()
            ->route('rm.report', ['tab' => $request->tab])
            ->with('success', 'Data deleted successfully.');
    }

    private function buildExportResponse(Request $request, bool $comOnly = false)
    {
      $tabs = ['AVAILABILITY', 'BOM_GENERAL', 'CUST_PART_NUM', 'FINANCIAL', 'GENERAL', 'GTINS', 'INPUT_PRODUCTS', 'LOGISTICS', 'PLANNING', 'QTY_CONVERS', 'SALES_DATA', 'SUPP_PART_NUM', 'UOM_CHAR'];

      $columnsConfig = [
        'AVAILABILITY'  => ['material_id', 'planning_area_id', 'status', 'availability_check_scope', 'status_row', 'user_create'],
        'BOM_GENERAL'   => ['bom_id', 'variant_id', 'language', 'variant_desc', 'long_text', 'status_row', 'user_create'],
        'CUST_PART_NUM' => ['material_id', 'customer_id', 'customer_part_number', 'status_row', 'user_create'],
        'FINANCIAL'     => ['material_id', 'company_id', 'business_residence_id', 'status_row', 'user_create'],
        'GENERAL'       => ['material_id', 'material_desc', 'full_material_desc', 'meterial_desc_th', 'product_category_id', 'mat_type', 'sub_type', 'brand', 'base_uom', 'inv_valuation_uom', 'pillar', 'division', 'department', 'sub_department', 'class', 'sub_class', 'section', 'series', 'attribute_1', 'register_off', 'shelf_life', 'hs_code', 'country', 'old_product_id', 'identified_stock_type', 'serial_number_profile', 'retail_sales_price', 'product_core', 'attribute_2', 'detail_name', 'status_row', 'user_create'],
        'GTINS'         => ['material_id', 'trading_unit', 'gtin_number', 'status_row', 'user_create'],
        'INPUT_PRODUCTS'=> ['bom_id', 'variant_id', 'line_item_grp_id', 'line_item_bom', 'input_prod_id', 'quantity', 'quantity_uom', 'engr_chg_order_id', 'fixed_qty_indi', 'deleted', 'status_row', 'user_create'],
        'LOGISTICS'     => ['material_id', 'planning_area_id', 'status', 'planning_uom', 'demand_manage_procedure', 'procurement_type', 'planning_procedure', 'lot_sizing_method', 'status_row', 'user_create'],
        'PLANNING'      => ['material_id', 'planning_area_id', 'status', 'planning_uom', 'procurement_type', 'status_row', 'user_create'],
        'QTY_CONVERS'   => ['material_id', 'quantity', 'quantity_uom', 'corres_qty', 'corres_qty_uom', 'status_row', 'user_create'],
        'SALES_DATA'    => ['material_id', 'sales_org_id', 'distribution_channel', 'status', 'sales_uom', 'item_group', 'status_row', 'user_create'],
        'SUPP_PART_NUM' => ['material_id', 'supplier_id', 'supplier_part_number', 'supplier_lead_time', 'status_row', 'user_create'],
        'UOM_CHAR'      => ['material_id', 'unit_of_measure', 'net_weight', 'uom_net_weight', 'gross_weight', 'uom_gross_weight', 'net_volume', 'uom_net_volume', 'gross_volume', 'uom_gross_volume', 'lengths', 'uom_length', 'width', 'uom_width', 'height', 'uom_height', 'quantity', 'quantity_uom', 'quantity_type_char', 'status_row', 'user_create'],
      ];

      $sheets = [];
      foreach ($tabs as $tab) {
          $model = "\\App\\Models\\Sheet" . Str::studly(Str::lower($tab));
          if (class_exists($model)) {
              $query = $model::query();

              if ($comOnly) {
                $query->whereRaw('TRIM(status_row) = ?', ['COM']);
              }

              $sheets[$tab] = $query->get();
          }
      }

      ob_start(); // จับ output ทั้งหมดลง buffer เพื่อกัน BOM

      echo '<?xml version="1.0"?>' . "\n";
      echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
      echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

      foreach ($sheets as $sheetName => $records) {
          if ($records->isEmpty()) continue;

          echo "<Worksheet ss:Name=\"{$sheetName}\">\n";
          echo "<Table>\n";

          // Header row
          echo "<Row>\n";
          foreach ($columnsConfig[$sheetName] ?? [] as $col) {
              echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($col) . "</Data></Cell>\n";
          }
          echo "</Row>\n";

          // Data rows
          foreach ($records as $record) {
              echo "<Row>\n";
              foreach ($columnsConfig[$sheetName] ?? [] as $col) {
                  $value = $record->$col ?? '';
                  $type = is_numeric($value) ? 'Number' : 'String';
                  echo "<Cell><Data ss:Type=\"{$type}\">" . htmlspecialchars($value) . "</Data></Cell>\n";
              }
              echo "</Row>\n";
          }

          echo "</Table>\n";
          echo "</Worksheet>\n";
      }

      echo "</Workbook>\n";

      $output = ob_get_clean(); // เก็บ output จาก buffer

      $fileName = 'export_excel_' . now()->format('Ymd_His') . '.xml';
      $filePath = storage_path('app/public/' . $fileName);
      file_put_contents($filePath, trim($output)); // trim เพื่อกัน whitespace หน้า xml

      return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function export(Request $request)
    {
      return $this->buildExportResponse($request);
    }

    private function callExportToSapProcedure(Request $request, string $confirm): void
    {
      $userLogin = (string) ($request->user()?->user_login ?? '');
      $roleLogin = (string) ($request->user()?->role ?? '');
      $error = null;

      $pdo = DB::connection('oracle')->getPdo();
      $stmt = $pdo->prepare('BEGIN proj1_2_Export_to_SAP(:p_user_login, :p_role_login, :p_confirm, :P_ERROR); END;');
      $stmt->bindValue(':p_user_login', $userLogin, PDO::PARAM_STR);
      $stmt->bindValue(':p_role_login', $roleLogin, PDO::PARAM_STR);
      $stmt->bindValue(':p_confirm', $confirm, PDO::PARAM_STR);
      $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
      $stmt->execute();

      $resolvedError = $this->resolveProcedureErrorMessage($error);
      if (trim($resolvedError) !== '') {
        throw ValidationException::withMessages([
          'p_confirm' => $resolvedError,
        ]);
      }
    }

    public function exportToSap(Request $request)
    {
      $validated = $request->validate([
        'p_confirm' => ['required', 'string', 'max:4000'],
      ]);

      $response = $this->buildExportResponse($request, true);

      $this->callExportToSapProcedure($request, $validated['p_confirm']);

      return $response;
    }
}
