<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FgBomDml extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'PROJ1_2_DML_FG_BOM';
    protected $primaryKey = 'NO';
    protected $keyType = 'string';
    protected $guarded = [];

    public $incrementing = false;
    public $timestamps = false;
}
