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


 public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function depots()
    {
        return $this->hasMany(Depot::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function transporters()
    {
        return $this->hasMany(Transporter::class);
    }
    
}


         
