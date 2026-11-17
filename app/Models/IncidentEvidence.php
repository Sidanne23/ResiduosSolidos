<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentEvidence extends Model
{
    protected $table = 'incident_evidence';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['created_at' => 'datetime'];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
