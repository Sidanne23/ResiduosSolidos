<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentaryRecord extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['value' => 'decimal:10', 'restrictions' => 'array'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
