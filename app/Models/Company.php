<?php
// app/Models/Company.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'base_currency',
        'logo_path',
        'country',
        'timezone',
    ];
}


         
