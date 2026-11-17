<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['consulted_on' => 'date'];

    public function records(): HasMany
    {
        return $this->hasMany(DocumentaryRecord::class);
    }
}
