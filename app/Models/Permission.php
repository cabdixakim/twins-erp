<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
    ];

   public function roles()
{
    // explicitly specify pivot table name: role_permission
    return $this->belongsToMany(Role::class, 'role_permission')->withTimestamps();
}
}