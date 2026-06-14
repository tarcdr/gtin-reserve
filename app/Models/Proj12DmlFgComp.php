<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj12DmlFgComp extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'proj1_2_dml_fg_comp';

    public $incrementing = false;
    public $timestamps = false;
}
