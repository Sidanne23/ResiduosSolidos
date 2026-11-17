<?php

namespace Tests\Feature;

use App\Models\CriticalSite;
use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\User;
use App\Services\Geography;
use App\Services\IncidentWorkflow;
use App\Services\OperationalIndicators;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_home_and_navigation_replace_documentary_section(): void
    {
        $this->actingAs($this->actor());
        $this->get('/')->assertRedirect('/operaciones');
        $this->get('/panel')->assertRedirect('/operaciones');
        $this->get('/operaciones')->assertOk()
            ->assertSee('Centro de control ambiental')
            ->assertDontSee('BASE DOCUMENTAL')
            ->assertDontSee('Consultar BI documental')
            ->assertDontSee('href="'.route('imports.index').'"', false)
            ->assertDontSee('href="'.route('sources.index').'"', false);
        $this->get('/incidencias/nueva')->assertOk()
            ->assertSeeInOrder(['data-location-message', 'data-location-map'], false)
            ->assertSee('Acerque el mapa con +');
    }

    private function actor(string $role = 'administrador'): User
    {
        return User::factory()->create(['name' => 'Cuenta ficticia de pruebas', 'role' => $role, 'active' => true]);
    }

    private function data(array $extra = []): array
    {
        [$lat,$lng] = app(Geography::class)->feature()['properties']['center'];

        return array_replace(['incident_type_id' => IncidentType::first()->id, 'title' => '[FICTICIO] Reporte aislado de prueba', 'description' => 'Descripción ficticia de un caso de prueba, sin evidencia municipal real.',
            'detected_at' => now()->timezone('America/Lima')->subHour()->format('Y-m-d\TH:i'), 'address' => 'Referencia ficticia solo para pruebas', 'latitude' => $lat, 'longitude' => $lng, 'location_source' => 'mapa', 'reported_by_label' => 'Equipo ficticio QA'], $extra);
    }

    private function createIncident(User $user, array $extra = []): Incident
    {
        $this->actingAs($user)->post(route('incidents.store'), $this->data($extra))->assertSessionHasNoErrors()->assertRedirect();

        return Incident::latest('id')->firstOrFail();
    }

    private function move(Incident $r, string $state, array $extra = [])
    {
        return $this->post(route('incidents.transition', $r), array_replace(['status_code' => $state, 'lock_version' => $r->fresh()->lock_version, 'note' => 'Movimiento ficticio para verificación automatizada.'], $extra));
    }

    public function test_create_preserves_author_photo_and_history_without_automatic_verification(): void
    {
        Storage::fake('local');
        $user = $this->actor('registrador');
        $r = $this->createIncident($user, ['photos' => [UploadedFile::fake()->image('evidencia-ficticia.jpg')]]);
        $this->assertSame('reportada', $r->status_code);
        $this->assertNull($r->verified_at);
        $this->assertNull($r->location_verified_at);
        $this->assertSame($user->id, $r->reported_by);
        $this->assertNull($r->priority);
        $photo = $r->evidence()->firstOrFail();
        Storage::disk('local')->assertExists($photo->private_path);
        $this->assertSame(hash_file('sha256', Storage::disk('local')->path($photo->private_path)), $photo->sha256);
        $this->assertSame(1, $r->history()->count());
        $this->assertStringStartsWith('INC-', $r->code);
        $this->get(route('incidents.photo', $photo))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_no_coordinates_remain_null_and_outside_or_partial_coordinates_are_rejected(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $r = $this->createIncident($user, ['latitude' => null, 'longitude' => null, 'location_source' => null]);
        $this->assertNull($r->latitude);
        $this->assertNull($r->longitude);
        $this->post(route('incidents.store'), $this->data(['latitude' => 0, 'longitude' => 0]))->assertSessionHasErrors('latitude');
        $this->post(route('incidents.store'), $this->data(['longitude' => null]))->assertSessionHasErrors('longitude');
        $this->post(route('incidents.store'), $this->data(['latitude' => 999]))->assertSessionHasErrors('latitude');
        $this->assertDatabaseCount('incidents', 1);
    }

    public function test_heatmap_is_empty_until_report_and_location_are_both_verified(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $r = $this->createIncident($user);
        $this->getJson(route('incidents.map-data'))->assertOk()->assertJsonCount(1, 'features')->assertJsonPath('meta.verified_geolocated', 0)->assertJsonPath('features.0.properties.verified', false);
        $this->getJson(route('incidents.map-data', ['verified_only'=>1]))->assertJsonCount(0, 'features');
        $this->move($r, 'verificada')->assertSessionHasNoErrors();
        $this->getJson(route('incidents.map-data'))->assertJsonCount(1, 'features')->assertJsonPath('meta.verified_geolocated', 0);
        $this->move($r, 'verificada', ['verify_location' => 1])->assertSessionHasNoErrors();
        $this->getJson(route('incidents.map-data'))->assertJsonPath('meta.verified_geolocated', 1)->assertJsonPath('features.0.properties.location_verified', true);
        $this->getJson(route('incidents.map-data', ['zone_id' => 999]))->assertUnprocessable();
    }

    public function test_urgent_report_is_visible_before_verification_and_respects_priority_filter(): void
    {
        $user = $this->actor();
        $r = $this->createIncident($user, ['priority' => 'urgente', 'priority_reason' => 'Prioridad ficticia para comprobar el mapa.']);
        $this->getJson(route('incidents.map-data'))->assertOk()->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.id', $r->id)
            ->assertJsonPath('features.0.properties.priority', 'urgente')
            ->assertJsonPath('features.0.properties.verified', false)
            ->assertJsonPath('meta.verified_geolocated', 0);
        $this->getJson(route('incidents.map-data', ['priority'=>'baja']))->assertJsonCount(0, 'features');
        $this->get('/operaciones')->assertOk()->assertSee('Prioridad del reporte')->assertSee('priority-urgente', false)
            ->assertSee('images/municipalidad-amarilis.png', false);
    }

    public function test_workflow_requires_verification_assignment_attention_and_justified_close(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $r = $this->createIncident($user);
        $this->move($r, 'cerrada')->assertSessionHasErrors('status_code');
        $this->move($r, 'verificada', ['verify_location' => 1])->assertSessionHasNoErrors();
        $this->move($r, 'asignada')->assertSessionHasErrors('assigned_to');
        $this->move($r, 'asignada', ['assigned_to' => $user->id])->assertSessionHasNoErrors();
        $this->move($r, 'en_atencion')->assertSessionHasNoErrors();
        $this->move($r, 'atendida')->assertSessionHasErrors('attention_summary');
        $this->move($r, 'atendida', ['attention_summary' => 'Intervención ficticia documentada únicamente para comprobar el flujo.'])->assertSessionHasNoErrors();
        $this->move($r, 'cerrada')->assertSessionHasNoErrors();
        $this->assertNotNull($r->fresh()->closed_at);
        $this->assertSame(6, $r->history()->count());
        $this->move($r, 'pendiente_verificacion')->assertSessionHasNoErrors();
        $this->assertNull($r->fresh()->verified_at);
        $this->assertNull($r->fresh()->attended_at);
    }

    public function test_rejection_requires_a_reason_and_registrador_cannot_verify_or_set_priority(): void
    {
        Storage::fake('local');
        $user = $this->actor('registrador');
        $r = $this->createIncident($user);
        $this->move($r, 'verificada')->assertSessionHasErrors('status_code');
        $this->post(route('incidents.store'), $this->data(['priority' => 'urgente', 'priority_reason' => 'Prioridad ficticia no autorizada']))->assertSessionHasErrors('priority');
        $this->move($r, 'pendiente_verificacion')->assertSessionHasNoErrors();
        $this->actingAs($this->actor())->post(route('incidents.transition', $r), ['status_code' => 'rechazada', 'lock_version' => $r->fresh()->lock_version, 'note' => ''])->assertSessionHasErrors('note');
        $this->move($r, 'rechazada')->assertSessionHasNoErrors();
        $this->assertSame('rechazada', $r->fresh()->status_code);
    }

    public function test_permissions_and_private_photos_are_enforced(): void
    {
        Storage::fake('local');
        $owner = $this->actor('registrador');
        $r = $this->createIncident($owner, ['photos' => [UploadedFile::fake()->image('ficticio.jpg')]]);
        $photo = $r->evidence()->first();
        $other = $this->actor('registrador');
        $this->actingAs($other)->get(route('incidents.show', $r))->assertForbidden();
        $this->get(route('incidents.photo', $photo))->assertForbidden();
        $consultor = $this->actor('consultor');
        $this->actingAs($consultor)->get(route('incidents.create'))->assertForbidden();
        $this->post(route('incidents.store'), $this->data())->assertForbidden();
        $this->get(route('incident-catalogs.index'))->assertForbidden();
    }

    public function test_substantive_edit_resets_verification_and_stale_updates_are_rejected(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $r = $this->createIncident($user);
        $this->move($r, 'verificada', ['verify_location' => 1])->assertSessionHasNoErrors();
        $version = $r->fresh()->lock_version;
        $this->put(route('incidents.update', $r), $this->data(['title' => '[FICTICIO] Descripción corregida', 'lock_version' => $version]))->assertSessionHasNoErrors();
        $this->assertNull($r->fresh()->verified_at);
        $this->assertNull($r->fresh()->location_verified_at);
        $this->assertSame('pendiente_verificacion', $r->fresh()->status_code);
        $this->put(route('incidents.update', $r), $this->data(['lock_version' => $version]))->assertSessionHasErrors('lock_version');
    }

    public function test_possible_duplicates_are_suggested_without_deletion_and_cycles_are_rejected(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $a = $this->createIncident($user);
        $b = $this->createIncident($user);
        $this->get(route('incidents.show', $b))->assertOk()->assertSee($a->code)->assertSee('coincidencias sugeridas');
        $this->move($b, 'reportada', ['duplicate_of_id' => $a->id])->assertSessionHasNoErrors();
        $this->move($a, 'reportada', ['duplicate_of_id' => $b->id])->assertSessionHasErrors('duplicate_of_id');
        $this->assertDatabaseCount('incidents', 2);
    }

    public function test_site_requires_verification_and_is_not_automatically_closed(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $r = $this->createIncident($user);
        $data = ['name' => '[FICTICIO] Sitio de ensayo', 'criterion' => 'Criterio ficticio de prueba para revisar asociaciones y recurrencia.', 'lock_version' => $r->lock_version];
        $this->post(route('sites.store', $r), $data)->assertSessionHasErrors('criterion');
        $this->move($r, 'verificada', ['verify_location' => 1])->assertSessionHasNoErrors();
        $data['lock_version'] = $r->fresh()->lock_version;
        $this->post(route('sites.store', $r), $data)->assertSessionHasNoErrors();
        $site = CriticalSite::firstOrFail();
        $this->assertSame($site->id, $r->fresh()->critical_site_id);
        $this->move($r, 'asignada', ['assigned_to' => $user->id, 'critical_site_id' => $site->id])->assertSessionHasNoErrors();
        $this->move($r, 'en_atencion')->assertSessionHasNoErrors();
        $this->move($r, 'atendida', ['attention_summary' => 'Atención ficticia exclusiva de pruebas del expediente.'])->assertSessionHasNoErrors();
        $this->move($r, 'cerrada')->assertSessionHasNoErrors();
        $this->assertSame('activo', $site->fresh()->status);
        $this->get(route('sites.show', $site))->assertOk();
        $this->put(route('sites.update', $site), ['status' => 'cerrado', 'note' => 'Cierre ficticio con intervención documentada para pruebas.', 'lock_version' => $site->lock_version])->assertSessionHasNoErrors();
    }

    public function test_pages_and_configuration_render_with_empty_operational_tables(): void
    {
        $this->actingAs($this->actor());
        foreach (['operations.dashboard', 'incidents.index', 'incidents.create', 'incidents.map', 'sites.index', 'incident-catalogs.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->getJson(route('incidents.map-data'))->assertJsonPath('meta.message', 'No hay incidencias con coordenadas para los filtros seleccionados.');
        $this->post(route('incident-catalogs.store'), ['catalog' => 'zone', 'name' => 'Zona FICTICIA exclusiva de prueba'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_local_date_filters_and_database_months_do_not_mix_utc_days(): void
    {
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-11-17 12:00:00', 'UTC'));
        $user = $this->actor();
        $r = $this->createIncident($user, ['detected_at' => '2026-10-31T19:30']);
        $this->assertSame('2026-11-01 00:30', $r->detected_at->utc()->format('Y-m-d H:i'));
        $summary = app(OperationalIndicators::class)->summarize($user, ['from' => '2026-10-31', 'to' => '2026-10-31']);
        $this->assertSame(1, $summary['counts']['registradas']);
        $this->assertSame('2026-10', $summary['charts']['month'][0]['label']);
        $this->getJson(route('incidents.map-data', ['verified_only' => 0, 'to' => '2026-10-31']))->assertOk()->assertJsonPath('meta.reports', 1);
        $this->getJson(route('incidents.map-data', ['verified_only' => 0, 'from' => '2026-11-01']))->assertOk()->assertJsonPath('meta.reports', 0);
        $this->getJson(route('incidents.map-data', ['from' => '2026-11-02', 'to' => '2026-11-01']))->assertUnprocessable();
    }

    public function test_configured_transition_can_be_disabled_without_bypassing_workflow(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $r = $this->createIncident($user);
        $labels = IncidentStatus::pluck('name', 'code')->all();
        $this->put(route('incident-catalogs.settings'), ['distance_m' => 75, 'hours' => 24, 'labels' => $labels, 'transitions' => []])->assertSessionHasNoErrors();
        $this->move($r, 'verificada')->assertSessionHasErrors('status_code');
        $this->assertSame('reportada', $r->fresh()->status_code);
    }

    public function test_geocoder_is_cached_and_limits_results_to_the_available_boundary(): void
    {
        $this->actingAs($this->actor());
        [$lat,$lng] = app(Geography::class)->feature()['properties']['center'];
        Http::fake(['*' => Http::response([['lat' => (string) $lat, 'lon' => (string) $lng, 'display_name' => 'Resultado FICTICIO de proveedor simulado'], ['lat' => '0', 'lon' => '0', 'display_name' => 'Fuera de ámbito']], 200)]);
        $this->getJson(route('geography.search', ['q' => 'Consulta ficticia de prueba']))->assertOk()->assertJsonCount(1, 'results');
        $this->getJson(route('geography.search', ['q' => 'Consulta ficticia de prueba']))->assertOk();
        Http::assertSentCount(1);
    }

    public function test_upload_rollback_removes_new_files_and_rejects_non_image(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $this->actingAs($user)->post(route('incidents.store'), $this->data(['photos' => [UploadedFile::fake()->create('prueba.pdf', 10, 'application/pdf')]]))->assertSessionHasErrors('photos.0');
        IncidentHistory::creating(fn () => throw new \RuntimeException('Fallo FICTICIO de auditoría de prueba'));
        try {
            app(IncidentWorkflow::class)->create($this->data(), [UploadedFile::fake()->image('ficticia.jpg')], $user);
            $this->fail('Debió revertirse.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo FICTICIO de auditoría de prueba', $e->getMessage());
        } finally {
            IncidentHistory::flushEventListeners();
        }
        $this->assertDatabaseCount('incidents', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }
}
