<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj12ListCompBsMatidV extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'PROJ1_2_BS_LIST_COMP_MATID_V';

    public $incrementing = false;
    public $timestamps = false;

    /**
     * Expected view columns for filtering and display:
     * list_brand, list_mat_type, list_sub_type, list_mat_id,
     * material_id, search_desc, material_desc_en, material_desc_th
     */
}
