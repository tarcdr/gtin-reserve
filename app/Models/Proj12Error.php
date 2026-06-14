<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj12Error extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'PROJ1_2_T_ERROR';
    protected $primaryKey = 'CODE_ERR';
    protected $keyType = 'string';
    protected $guarded = [];

    public $incrementing = false;
    public $timestamps = false;
}
