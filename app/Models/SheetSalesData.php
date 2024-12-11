<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class SheetSalesData extends Model
{
    use HasCompositeKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proj1_2_xc_sheet_sales_data';
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    // ระบุประเภทของคีย์ (เช่น string หรือ int)
    protected $keyType = 'string';

    // ระบุชื่อของ Composite Keys
    protected $primaryKey = ['material_id', 'sales_org_id'];

    // ฟิลด์ที่อนุญาตให้ทำ Mass Assignment
    protected $fillable = [
        'material_id',
        'sales_org_id',
        'distribution_channel',
        'status',
        'sales_uom',
        'item_group',
    ];

    public $timestamps = false;
}
