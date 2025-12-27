<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'active_company_id',
        'role_id',
        'status',
            
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // -------------------------
    // Relationships
    // -------------------------
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Multi-company membership.
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }

    /**
     * Currently active company.
     */
    public function activeCompany()
    {
        return $this->belongsTo(Company::class, 'active_company_id');
    }

    /**
     * Set active company (must belong to user).
     */
    public function setActiveCompany(int $companyId): void
    {
        if (!$this->companies()->whereKey($companyId)->exists()) {
            abort(403, 'You do not belong to that company.');
        }

        $this->active_company_id = $companyId;
        $this->save();
    }

    // -------------------------
    // Permission helpers (your logic preserved)
    // -------------------------
    public function hasRole(string $slug): bool
    {
        return $this->role && $this->role->slug === $slug;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if (!$this->role) {
            return false;
        }

        // owner = god-mode
        if ($this->role->slug === 'owner') {
            return true;
        }

        // assumes Role model has: permissions() relationship loaded/available
        return $this->role->permissions->contains(fn ($p) => $p->slug === $permissionSlug);
    }

    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }
        return false;
    }
}