<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array', 'summary' => 'array', 'confirmed_at' => 'datetime'];
}
