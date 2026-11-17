<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdministrator extends Command
{
    protected $signature = 'app:admin';

    protected $description = 'Crear una cuenta de administrador mediante entrada interactiva segura';

    public function handle(): int
    {
        $name = $this->ask('Nombre del administrador');
        $email = strtolower((string) $this->ask('Correo electrónico'));
        $password = $this->secret('Contraseña (mínimo 12 caracteres)');
        $confirmation = $this->secret('Repetir contraseña');
        $validator = Validator::make(compact('name', 'email', 'password') + ['password_confirmation' => $confirmation], [
            'name' => 'required|string|max:150', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:12|confirmed',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = new User;
        $user->forceFill(compact('name', 'email', 'password') + ['role' => 'administrador', 'active' => true])->save();
        Audit::record('admin.created.cli', "user:{$user->id}");
        $this->info('Administrador creado. Acceda con su correo y contraseña.');

        return self::SUCCESS;
    }
}
