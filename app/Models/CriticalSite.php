<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriticalSite extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'verified_at' => 'datetime'];

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    public function zone()
    {
        return $this->belongsTo(OperationalZone::class, 'zone_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
