<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj12MasterBomSemiLv1V extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'PROJ1_2_MASTER_BOM_SEMI_LV1_V';

    public $incrementing = false;
    public $timestamps = false;
}
