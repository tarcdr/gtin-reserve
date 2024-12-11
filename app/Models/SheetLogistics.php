<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class SheetLogistics extends Model
{
    use HasCompositeKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proj1_2_xc_sheet_logistics';
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    // ระบุประเภทของคีย์ (เช่น string หรือ int)
    protected $keyType = 'string';

    // ระบุชื่อของ Composite Keys
    protected $primaryKey = ['material_id', 'site_id'];

    // ฟิลด์ที่อนุญาตให้ทำ Mass Assignment
    protected $fillable = [
        'material_id',
        'site_id',
        'status',
        'storage_group_id',
    ];

    public $timestamps = false;
}
