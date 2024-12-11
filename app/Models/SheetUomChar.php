<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class SheetUomChar extends Model
{
    use HasCompositeKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proj1_2_xc_sheet_uom_char';
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    // ระบุประเภทของคีย์ (เช่น string หรือ int)
    protected $keyType = 'string';

    // ระบุชื่อของ Composite Keys
    protected $primaryKey = ['material_id', 'unit_of_measure'];

    // ฟิลด์ที่อนุญาตให้ทำ Mass Assignment
    protected $fillable = [
        'material_id',
        'unit_of_measure',
        'net_weight',
        'uom_net_weight',
        'gross_weight',
        'uom_gross_weight',
        'net_volume',
        'uom_net_volume',
        'gross_volume',
        'uom_gross_volume',
        'lengths',
        'uom_length',
        'width',
        'uom_width',
        'height',
        'uom_height',
        'quantity',
        'quantity_uom',
        'quantity_type_char',
    ];

    public $timestamps = false;
}
