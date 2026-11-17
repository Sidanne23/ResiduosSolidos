<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentHistory extends Model
{
    protected $table = 'incident_history';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['created_at' => 'datetime', 'changes' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
