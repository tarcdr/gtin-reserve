<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proj12SummaryDetail extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'proj1_2_summary_detail';

    public $incrementing = false;
    public $timestamps = false;
}
