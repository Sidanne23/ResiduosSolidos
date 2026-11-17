<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['detected_at' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'location_verified_at' => 'datetime', 'verified_at' => 'datetime', 'attended_at' => 'datetime', 'closed_at' => 'datetime'];

    public function type()
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id');
    }

    public function state()
    {
        return $this->belongsTo(IncidentStatus::class, 'status_code', 'code');
    }

    public function zone()
    {
        return $this->belongsTo(OperationalZone::class, 'zone_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function evidence()
    {
        return $this->hasMany(IncidentEvidence::class)->orderBy('id');
    }

    public function history()
    {
        return $this->hasMany(IncidentHistory::class)->orderByDesc('id');
    }

    public function site()
    {
        return $this->belongsTo(CriticalSite::class, 'critical_site_id');
    }

    public function duplicateOf()
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === 'registrador') {
            $query->where(fn ($q) => $q->where('reported_by', $user->id)->orWhere('assigned_to', $user->id));
        } elseif ($user->role === 'consultor') {
            $query->whereNotNull('verified_at')->where('status_code', '!=', 'rechazada');
        }
    }

    public function getDetectedLocalAttribute(): string
    {
        return $this->detected_at->timezone('America/Lima')->format('d/m/Y H:i');
    }
}
