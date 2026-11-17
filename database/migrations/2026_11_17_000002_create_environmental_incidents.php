<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_types', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150)->unique();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('operational_zones', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150)->unique();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('incident_statuses', function (Blueprint $t) {
            $t->string('code', 32)->primary();
            $t->string('name', 80);
            $t->unsignedTinyInteger('position');
        });
        Schema::create('incident_status_transitions', function (Blueprint $t) {
            $t->id();
            $t->string('from_status', 32);
            $t->string('to_status', 32);
            $t->boolean('enabled')->default(true);
            $t->foreign('from_status')->references('code')->on('incident_statuses')->restrictOnDelete();
            $t->foreign('to_status')->references('code')->on('incident_statuses')->restrictOnDelete();
            $t->unique(['from_status', 'to_status']);
        });
        Schema::create('operational_settings', function (Blueprint $t) {
            $t->string('key', 64)->primary();
            $t->json('value');
        });
        Schema::create('critical_sites', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name', 180);
            $t->text('address');
            $t->foreignId('zone_id')->nullable()->constrained('operational_zones')->restrictOnDelete();
            $t->decimal('latitude', 10, 7);
            $t->decimal('longitude', 10, 7);
            $t->text('verification_criterion');
            $t->string('status', 24)->default('activo')->index();
            $t->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('verified_at');
            $t->unsignedInteger('lock_version')->default(1);
            $t->timestamps();
        });
        Schema::create('incidents', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->foreignId('incident_type_id')->constrained()->restrictOnDelete();
            $t->string('title', 180);
            $t->text('description');
            $t->dateTime('detected_at')->index();
            $t->text('address');
            $t->foreignId('zone_id')->nullable()->constrained('operational_zones')->restrictOnDelete();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->string('location_source', 32)->nullable();
            $t->timestamp('location_verified_at')->nullable();
            $t->foreignId('location_verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('priority', 16)->nullable()->index();
            $t->text('priority_reason')->nullable();
            $t->string('reported_by_label', 180);
            $t->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $t->string('status_code', 32)->default('reportada')->index();
            $t->foreign('status_code')->references('code')->on('incident_statuses')->restrictOnDelete();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->timestamp('attended_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->text('attention_summary')->nullable();
            $t->text('observations')->nullable();
            $t->foreignId('duplicate_of_id')->nullable()->constrained('incidents')->restrictOnDelete();
            $t->foreignId('critical_site_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedInteger('lock_version')->default(1);
            $t->timestamps();
            $t->index(['status_code', 'detected_at']);
            $t->index(['zone_id', 'detected_at']);
            $t->index(['latitude', 'longitude']);
        });
        Schema::create('incident_evidence', function (Blueprint $t) {
            $t->id();
            $t->foreignId('incident_id')->constrained()->restrictOnDelete();
            $t->string('phase', 16);
            $t->string('private_path');
            $t->string('original_name');
            $t->string('mime', 80);
            $t->unsignedBigInteger('bytes');
            $t->char('sha256', 64);
            $t->text('caption')->nullable();
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('created_at');
        });
        Schema::create('incident_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('incident_id')->constrained()->restrictOnDelete();
            $t->string('action', 40);
            $t->string('from_status', 32)->nullable();
            $t->string('to_status', 32);
            $t->text('note');
            $t->json('changes')->nullable();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->timestamp('created_at');
        });
        Schema::create('critical_site_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('critical_site_id')->constrained()->restrictOnDelete();
            $t->string('status', 24);
            $t->text('note');
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->timestamp('created_at');
        });
        foreach (['Acumulación de residuos en vía pública', 'Punto de acumulación recurrente', 'Contenedor desbordado', 'Residuos abandonados', 'Incumplimiento de recolección reportado', 'Problemas asociados al recorrido del vehículo', 'Otros problemas de limpieza pública'] as $name) {
            DB::table('incident_types')->insert(['name' => $name, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $states = ['reportada' => 'Reportada', 'pendiente_verificacion' => 'Pendiente de verificación', 'verificada' => 'Verificada', 'asignada' => 'Asignada', 'en_atencion' => 'En atención', 'atendida' => 'Atendida', 'cerrada' => 'Cerrada', 'rechazada' => 'Rechazada'];
        $position = 0;
        foreach ($states as $code => $name) {
            DB::table('incident_statuses')->insert(compact('code', 'name') + ['position' => ++$position]);
        }
        $flow = ['reportada' => ['pendiente_verificacion', 'verificada', 'rechazada'], 'pendiente_verificacion' => ['verificada', 'rechazada'], 'verificada' => ['asignada', 'rechazada'], 'asignada' => ['en_atencion', 'verificada'], 'en_atencion' => ['atendida', 'asignada'], 'atendida' => ['cerrada', 'en_atencion'], 'cerrada' => ['pendiente_verificacion'], 'rechazada' => ['pendiente_verificacion']];
        foreach ($flow as $from => $destinations) {
            foreach ($destinations as $to) {
                DB::table('incident_status_transitions')->insert(['from_status' => $from, 'to_status' => $to, 'enabled' => true]);
            }
        }
        DB::table('operational_settings')->insert(['key' => 'duplicate_detection', 'value' => json_encode(['distance_m' => 100, 'hours' => 48])]);
    }

    public function down(): void
    {
        foreach (['critical_site_history', 'incident_history', 'incident_evidence', 'incidents', 'critical_sites', 'operational_settings', 'incident_status_transitions', 'incident_statuses', 'operational_zones', 'incident_types'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
