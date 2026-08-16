<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj1BrandGtingV extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'PROJ1_BRAND_GTIN_V';

    public $incrementing = false;
    public $timestamps = false;
}
