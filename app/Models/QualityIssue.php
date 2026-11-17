<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityIssue extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['record_codes' => 'array'];
}
