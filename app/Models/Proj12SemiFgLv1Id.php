<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj12SemiFgLv1Id extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'PROJ1_2_DML_SEMI_L1_ID';

    public $incrementing = false;
    public $timestamps = false;
}
