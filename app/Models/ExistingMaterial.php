<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExistingMaterial extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'proj1_2_list_matid_existing_v';

    public $incrementing = false;
    public $timestamps = false;

    /**
     * Expected view columns for filtering and display:
     * brand, mat_type, sub_type, material_id, material_desc
     */
}
