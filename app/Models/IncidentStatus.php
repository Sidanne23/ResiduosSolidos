<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentStatus extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $guarded = ['code'];
}
