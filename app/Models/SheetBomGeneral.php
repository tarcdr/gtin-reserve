<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class SheetBomGeneral extends Model
{
    use HasCompositeKey;

    protected $table = 'PROJ1_2_XC_SHEET_BOM_GENERAL';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = ['bom_id', 'variant_id', 'language'];

    protected $fillable = [
        'bom_id',
        'variant_id',
        'language',
        'variant_desc',
        'long_text',
        'status_row',
        'user_role',
        'user_create',
        'create_date',
        'user_update',
        'update_date',
    ];

    public $timestamps = false;
}
