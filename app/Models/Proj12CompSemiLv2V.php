<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj12CompSemiLv2V extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'PROJ1_2_COMP_SEMI_L2_V';

    public $incrementing = false;
    public $timestamps = false;
}
