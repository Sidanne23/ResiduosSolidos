<?php

namespace App\Actions;

use App\Services\Audit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    public function reset($user, array $input): void
    {
        Validator::make($input, ['password' => ['required', 'string', Password::min(12), 'confirmed']])->validate();
        $user->forceFill(['password' => $input['password'], 'remember_token' => null])->save();
        Audit::record('password.reset', "user:{$user->id}");
    }
}
