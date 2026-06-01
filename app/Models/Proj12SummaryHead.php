<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj12SummaryHead extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'proj1_2_summary_head';

    public $incrementing = false;
    public $timestamps = false;
}
