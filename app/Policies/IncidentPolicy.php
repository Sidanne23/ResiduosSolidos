<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->active;
    }

    public function view(User $user, Incident $incident): bool
    {
        return $user->active && Incident::visibleTo($user)->whereKey($incident->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->active && in_array($user->role, ['administrador', 'analista', 'registrador', 'responsable_limpieza'], true);
    }

    public function manage(User $user): bool
    {
        return $user->active && in_array($user->role, ['administrador', 'analista', 'responsable_limpieza'], true);
    }

    public function update(User $user, Incident $incident): bool
    {
        return $this->view($user, $incident) && ! in_array($incident->status_code, ['cerrada', 'rechazada'], true)
            && ($this->manage($user) || ($incident->reported_by === $user->id && in_array($incident->status_code, ['reportada', 'pendiente_verificacion'], true)));
    }

    public function transition(User $user, Incident $incident): bool
    {
        return $this->view($user, $incident) && ($this->manage($user) || ($incident->reported_by === $user->id && $incident->status_code === 'reportada'));
    }

    public function attach(User $user, Incident $incident): bool
    {
        return $this->view($user, $incident) && ! in_array($incident->status_code, ['cerrada', 'rechazada'], true)
            && ($this->manage($user) || $incident->reported_by === $user->id || $incident->assigned_to === $user->id);
    }
}
