<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OracleTable extends Model
{
    use HasFactory;

    protected $connection = 'oracle';

    public $incrementing = false;
    public $timestamps = false;
}
