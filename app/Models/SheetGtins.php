<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class SheetGtins extends Model
{
    use HasCompositeKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proj1_2_xc_sheet_gtins';
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    // ระบุประเภทของคีย์ (เช่น string หรือ int)
    protected $keyType = 'string';

    // ระบุชื่อของ Composite Keys
    protected $primaryKey = ['material_id', 'trading_unit'];

    // ฟิลด์ที่อนุญาตให้ทำ Mass Assignment
    protected $fillable = [
        'material_id',
        'trading_unit',
        'gtin_number',
    ];

    public $timestamps = false;
}
