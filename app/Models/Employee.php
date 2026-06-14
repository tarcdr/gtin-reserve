<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'proj1_2_employees';

    public $incrementing = false;
    protected $primaryKey = 'code';
    protected $keyType = 'string';
}
