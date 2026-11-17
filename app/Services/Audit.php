<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Audit
{
    public static function record(string $action, string $subject, array $detail = []): void
    {
        DB::table('audit_events')->insert([
            'user_id' => Auth::id(), 'action' => $action, 'subject' => $subject,
            'detail' => json_encode($detail, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
