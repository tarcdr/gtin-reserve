<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class SheetInputProducts extends Model
{
    use HasCompositeKey;

    protected $table = 'PROJ1_2_XC_SHEET_INPUT_PROD';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = ['bom_id', 'variant_id', 'line_item_grp_id', 'line_item_bom', 'input_prod_id'];

    protected $fillable = [
        'bom_id',
        'variant_id',
        'line_item_grp_id',
        'line_item_bom',
        'input_prod_id',
        'quantity',
        'quantity_uom',
        'engr_chg_order_id',
        'fixed_qty_indi',
        'deleted',
        'status_row',
        'user_role',
        'user_create',
        'create_date',
        'user_update',
        'update_date',
    ];

    public $timestamps = false;
}
