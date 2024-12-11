<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class SheetGeneral extends Model
{
    use HasCompositeKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proj1_2_xc_sheet_general';
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    // ระบุประเภทของคีย์ (เช่น string หรือ int)
    protected $keyType = 'string';

    // ระบุชื่อของ Composite Keys
    protected $primaryKey = ['material_id'];

    // ฟิลด์ที่อนุญาตให้ทำ Mass Assignment
    protected $fillable = [
        'material_id',
        'material_desc',
        'full_material_desc',
        'meterial_desc_th',
        'product_category_id',
        'mat_type',
        'sub_type',
        'brand',
        'base_uom',
        'inv_valuation_uom',
        'pillar',
        'division',
        'department',
        'sub_department',
        'class',
        'sub_class',
        'section',
        'series',
        'attribute_1',
        'register_off',
        'shelf_life',
        'hs_code',
        'country',
        'old_product_id',
        'identified_stock_type',
        'serial_number_profile',
        'retail_sales_price',
        'product_core',
        'attribute_2',
        'detail_name',
    ];

    public $timestamps = false;
}
