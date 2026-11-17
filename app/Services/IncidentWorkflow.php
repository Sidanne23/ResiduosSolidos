<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentEvidence;
use App\Models\IncidentHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class IncidentWorkflow
{
    public function create(array $data, array $photos, User $user): Incident
    {
        $files = [];
        try {
            return DB::transaction(function () use ($data, $photos, $user, &$files) {
                $data = collect($data)->except(['photos', 'lock_version'])->all();
                if (! $user->can('manage', Incident::class)) {
                    unset($data['priority'],$data['priority_reason']);
                }
                $data['detected_at'] = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['detected_at'], 'America/Lima')->utc();
                $incident = Incident::create($data + ['code' => 'INC-'.now()->timezone('America/Lima')->format('Ymd').'-'.Str::upper(Str::random(8)), 'reported_by' => $user->id, 'status_code' => 'reportada']);
                $this->photos($incident, $photos, 'antes', 'Evidencia del reporte inicial', $user, $files);
                $this->history($incident, 'created', null, 'Reporte registrado; pendiente de verificación.', $user);

                return $incident;
            });
        } catch (Throwable $e) {
            $this->cleanup($files);
            throw $e;
        }
    }

    public function update(Incident $incident, array $data, array $photos, User $user): Incident
    {
        $files = [];
        try {
            return DB::transaction(function () use ($incident, $data, $photos, $user, &$files) {
                $row = Incident::lockForUpdate()->findOrFail($incident->id);
                Gate::forUser($user)->authorize('update', $row);
                if (! $user->can('manage', Incident::class)) {
                    unset($data['priority'],$data['priority_reason']);
                }
                $this->version($row, $data['lock_version']);
                $before = $row->getAttributes();
                $from = $row->status_code;
                $row->fill(collect($data)->except(['photos', 'lock_version'])->all());
                $row->detected_at = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['detected_at'], 'America/Lima')->utc();
                $changed = $row->isDirty(['title', 'description', 'incident_type_id', 'detected_at', 'address', 'zone_id', 'latitude', 'longitude', 'location_source']);
                if ($changed && $row->verified_at !== null) {
                    $row->status_code = 'pendiente_verificacion';
                    $row->verified_at = null;
                    $row->verified_by = null;
                    $row->location_verified_at = null;
                    $row->location_verified_by = null;
                    $row->attended_at = null;
                    $row->closed_at = null;
                    $row->attention_summary = null;
                }
                $row->lock_version++;
                $row->save();
                $this->photos($row, $photos, 'antes', 'Evidencia incorporada en edición', $user, $files);
                $this->history($row, 'edited', $from, $changed ? 'Datos del reporte actualizados. Las verificaciones previas dejan de estar vigentes si correspondía.' : 'Datos complementarios actualizados.', $user, ['before' => $before, 'after' => $row->getAttributes()]);

                return $row;
            });
        } catch (Throwable $e) {
            $this->cleanup($files);
            throw $e;
        }
    }

    public function transition(Incident $incident, array $data, User $user): Incident
    {
        return DB::transaction(function () use ($incident, $data, $user) {
            $row = Incident::lockForUpdate()->findOrFail($incident->id);
            Gate::forUser($user)->authorize('transition', $row);
            $this->version($row, $data['lock_version']);
            $from = $row->status_code;
            $to = $data['status_code'];
            $manager = $user->can('manage', Incident::class);
            $allowed = ($from === $to && $manager) || DB::table('incident_status_transitions')->where(['from_status' => $from, 'to_status' => $to, 'enabled' => true])->exists();
            $this->ensure($allowed, 'status_code', 'El cambio de estado no está habilitado.');
            $this->ensure($manager || $to === 'pendiente_verificacion', 'status_code', 'Su perfil solo puede solicitar verificación.');
            if ($manager) {
                foreach (['assigned_to', 'critical_site_id', 'duplicate_of_id'] as $key) {
                    if (array_key_exists($key, $data)) {
                        $row->$key = $data[$key];
                    }
                }
                if (! empty($data['priority'])) {
                    $row->priority = $data['priority'];
                    $row->priority_reason = $data['priority_reason'];
                }
                if (! empty($data['attention_summary'])) {
                    $row->attention_summary = $data['attention_summary'];
                }
                if (! empty($data['verify_location'])) {
                    $this->ensure($row->latitude !== null && $row->longitude !== null && app(Geography::class)->contains((float) $row->latitude, (float) $row->longitude), 'verify_location', 'Se requieren coordenadas válidas dentro del ámbito disponible.');
                    $row->location_verified_at = now();
                    $row->location_verified_by = $user->id;
                }
            }
            if ($to === 'verificada' && $from !== $to) {
                $row->verified_at = now();
                $row->verified_by = $user->id;
            }
            if ($to === 'asignada') {
                $this->ensure($row->assigned_to !== null, 'assigned_to', 'Seleccione un responsable activo.');
            }
            if (in_array($to, ['asignada', 'en_atencion', 'atendida', 'cerrada'], true)) {
                $this->ensure($row->verified_at !== null, 'status_code', 'Primero debe verificarse la incidencia.');
                $this->ensure($row->assigned_to !== null && User::whereKey($row->assigned_to)->where('active', true)->exists(), 'assigned_to', 'La atención requiere un responsable activo.');
            }
            if (in_array($to, ['atendida', 'cerrada'], true)) {
                $this->ensure(mb_strlen(trim((string) $row->attention_summary)) >= 20, 'attention_summary', 'Describa la intervención y su justificación (mínimo 20 caracteres).');
                if ($to === 'atendida' && $from !== $to) {
                    $row->attended_at = now();
                }
                if ($to === 'cerrada') {
                    $this->ensure($row->attended_at !== null, 'status_code', 'Antes del cierre debe existir atención registrada.');
                    if ($from !== $to) {
                        $row->closed_at = now();
                    }
                }
            }
            if ($to === 'pendiente_verificacion' && $from !== $to) {
                $row->verified_at = null;
                $row->verified_by = null;
                $row->location_verified_at = null;
                $row->location_verified_by = null;
                $row->attended_at = null;
                $row->closed_at = null;
                $row->attention_summary = null;
            }
            if ($to === 'rechazada') {
                $row->verified_at = null;
                $row->verified_by = null;
                $row->location_verified_at = null;
                $row->location_verified_by = null;
            }
            if ($to === 'en_atencion' && $from !== $to) {
                $row->attended_at = null;
                $row->closed_at = null;
            }
            if ($row->duplicate_of_id !== null) {
                $seen = [$row->id];
                $cursor = $row->duplicate_of_id;
                while ($cursor !== null) {
                    $this->ensure(! in_array($cursor, $seen, true), 'duplicate_of_id', 'La asociación de duplicados no puede formar un ciclo.');
                    $seen[] = $cursor;
                    $target = Incident::visibleTo($user)->find($cursor);
                    $this->ensure($target !== null, 'duplicate_of_id', 'Reporte de referencia no accesible.');
                    $cursor = $target->duplicate_of_id;
                }
            }
            $row->status_code = $to;
            $row->lock_version++;
            $row->save();
            $this->history($row, 'status_changed', $from, $data['note'], $user, ['assigned_to' => $row->assigned_to, 'priority' => $row->priority, 'priority_reason' => $row->priority_reason, 'attention_summary' => $row->attention_summary, 'location_verified_at' => $row->location_verified_at, 'critical_site_id' => $row->critical_site_id, 'duplicate_of_id' => $row->duplicate_of_id]);

            return $row;
        });
    }

    public function attach(Incident $incident, array $photos, string $phase, string $caption, int $version, User $user): void
    {
        $files = [];
        try {
            DB::transaction(function () use ($incident, $photos, $phase, $caption, $version, $user, &$files) {
                $row = Incident::lockForUpdate()->findOrFail($incident->id);
                Gate::forUser($user)->authorize('attach', $row);
                $this->version($row, $version);
                if ($phase === 'despues') {
                    $this->ensure($user->can('manage', Incident::class) || $row->assigned_to === $user->id, 'phase', 'La evidencia de atención corresponde al responsable o revisor.');
                }
                $this->photos($row, $photos, $phase, $caption, $user, $files);
                $row->lock_version++;
                $row->save();
                $this->history($row, 'evidence_added', $row->status_code, $caption, $user, ['phase' => $phase, 'files' => count($photos)]);
            });
        } catch (Throwable $e) {
            $this->cleanup($files);
            throw $e;
        }
    }

    private function photos(Incident $incident, array $photos, string $phase, string $caption, User $user, array &$files): void
    {
        foreach ($photos as $photo) {
            $path = $photo->store('incidents/'.$incident->id, 'local');
            $files[] = $path;
            IncidentEvidence::create(['incident_id' => $incident->id, 'phase' => $phase, 'private_path' => $path, 'original_name' => mb_substr(basename($photo->getClientOriginalName()), 0, 255), 'mime' => $photo->getMimeType(), 'bytes' => $photo->getSize(), 'sha256' => hash_file('sha256', $photo->getRealPath()), 'caption' => $caption, 'uploaded_by' => $user->id, 'created_at' => now()]);
        }
    }

    public function history(Incident $incident, string $action, ?string $from, string $note, User $user, array $changes = []): void
    {
        IncidentHistory::create(['incident_id' => $incident->id, 'action' => $action, 'from_status' => $from, 'to_status' => $incident->status_code, 'note' => $note, 'changes' => $changes, 'user_id' => $user->id, 'created_at' => now()]);
        Audit::record('incident.'.$action, 'incident:'.$incident->id, ['code' => $incident->code, 'from' => $from, 'to' => $incident->status_code]);
    }

    private function version(Incident $row, int $version): void
    {
        $this->ensure($row->lock_version === $version, 'lock_version', 'Otro usuario modificó este reporte. Recargue el expediente antes de continuar.');
    }

    private function ensure(bool $ok, string $field, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function cleanup(array $files): void
    {
        foreach ($files as $path) {
            Storage::disk('local')->delete($path);
        }
    }
}
