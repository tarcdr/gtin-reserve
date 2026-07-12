<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj12DmlBizsupCompM6 extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'PROJ1_2_DML_BIZSUP_COMP_M6';

    public $incrementing = false;
    public $timestamps = false;
}
