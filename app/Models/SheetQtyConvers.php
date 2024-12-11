<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class SheetQtyConvers extends Model
{
    use HasCompositeKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proj1_2_xc_sheet_qty_convers';
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    // ระบุประเภทของคีย์ (เช่น string หรือ int)
    protected $keyType = 'string';

    // ระบุชื่อของ Composite Keys
    protected $primaryKey = ['material_id', 'quantity_uom'];

    // ฟิลด์ที่อนุญาตให้ทำ Mass Assignment
    protected $fillable = [
        'material_id',
        'quantity',
        'quantity_uom',
        'corres_qty',
        'corres_qty_uom',
    ];

    public $timestamps = false;
}
