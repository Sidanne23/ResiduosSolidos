<?php

namespace Tests\Feature;

use App\Models\DocumentaryRecord;
use App\Models\DocumentaryRoute;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\IndicatorEngine;
use App\Services\WorkbookImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DocumentarySystemTest extends TestCase
{
    use RefreshDatabase;

    private function loadEvidence(): ImportBatch
    {
        Storage::fake('local');
        $service = app(WorkbookImport::class);
        $batch = $service->preview(base_path('tests/Fixtures/amarilis-verificado.xlsx'), 'amarilis-verificado.xlsx');

        return $service->confirm($batch);
    }

    public function test_import_preview_does_not_publish_and_confirmation_is_idempotent(): void
    {
        Storage::fake('local');
        $service = app(WorkbookImport::class);
        $batch = $service->preview(base_path('tests/Fixtures/amarilis-verificado.xlsx'), 'original.xlsx');
        $this->assertDatabaseCount('documentary_records', 0);
        $this->assertSame('preview', $batch->status);
        $service->confirm($batch);
        $service->confirm($batch);
        $this->assertDatabaseCount('documentary_records', 41);
        $this->assertDatabaseCount('documentary_routes', 11);
        $this->assertDatabaseCount('sources', 7);
        $this->assertDatabaseCount('quality_issues', 9);
        $this->assertDatabaseCount('import_batches', 1);
        $this->assertEquals(1308, DocumentaryRoute::sum('participants'));
        $this->assertSame('>', DocumentaryRecord::where('code', 'R35')->first()->operator);
        $this->assertSame('0.0000000000', DocumentaryRecord::where('code', 'R20')->first()->value);
        $this->assertNotEmpty($batch->payload['raw']['Indicadores'][5][1]);
    }

    public function test_import_failure_rolls_back_curated_data_but_preserves_original(): void
    {
        Storage::fake('local');
        $service = app(WorkbookImport::class);
        $batch = $service->preview(base_path('tests/Fixtures/amarilis-verificado.xlsx'), 'original.xlsx');
        DocumentaryRecord::creating(function ($record) {
            if ($record->code === 'R05') {
                throw new \RuntimeException('Fallo ficticio de prueba');
            }
        });
        try {
            $service->confirm($batch);
            $this->fail('Debió revertirse.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo ficticio de prueba', $e->getMessage());
        } finally {
            DocumentaryRecord::flushEventListeners();
        }
        $this->assertDatabaseCount('documentary_records', 0);
        $this->assertDatabaseCount('sources', 0);
        $this->assertSame('failed', $batch->fresh()->status);
        Storage::disk('local')->assertExists($batch->private_path);
        $service->confirm($batch);
        $this->assertDatabaseCount('documentary_records', 41);
    }

    public function test_changed_strict_operator_is_rejected(): void
    {
        $book = IOFactory::load(base_path('tests/Fixtures/amarilis-verificado.xlsx'));
        $book->getSheetByName('Registros')->setCellValue('G36', 'Exacto');
        $file = tempnam(sys_get_temp_dir(), 'amarilis-test-');
        try {
            (new Xlsx($book))->save($file);
            $this->expectException(ValidationException::class);
            app(WorkbookImport::class)->preview($file, 'ficticio-solo-prueba.xlsx');
        } finally {
            @unlink($file);
            $book->disconnectWorksheets();
        }
    }

    public function test_all_sixteen_calculations_match_independent_reference(): void
    {
        $this->loadEvidence();
        $actual = app(IndicatorEngine::class)->calculate(DocumentaryRecord::with('source')->get(), DocumentaryRoute::all());
        $reference = json_decode(file_get_contents(base_path('docs/09-evidencias/verificacion-calculos.json')), true);
        foreach ($reference['checks'] as $i => $check) {
            $key = sprintf('IC%02d', $i + 1);
            $this->assertNotNull($actual[$key]['value'], $key);
            $this->assertEqualsWithDelta((float) $check['recomputed'], (float) $actual[$key]['value'], 1e-10, $key);
        }
        $this->assertSame('>', $actual['IC11']['operator']);
        $this->assertSame('262.270000000000000000000000000000', $actual['IC11']['value']);
        $this->assertSame('100,216', IndicatorEngine::display('100.2160000000', 't/día'));
    }

    public function test_missing_zero_and_incompatible_operands_do_not_produce_false_rates(): void
    {
        $this->loadEvidence();
        $records = DocumentaryRecord::with('source')->get();
        $engine = app(IndicatorEngine::class);
        $result = $engine->calculate($records->reject(fn ($r) => $r->code === 'R08'), collect());
        $this->assertNull($result['IC03']['value']);
        $records->firstWhere('code', 'R08')->value = '0';
        $this->assertNull($engine->calculate($records, collect())['IC03']['value']);
        $records->firstWhere('code', 'R08')->value = '3925440';
        $records->firstWhere('code', 'R08')->period = 'Período ficticio incompatible';
        $this->assertNull($engine->calculate($records, collect())['IC03']['value']);
        $this->assertSame('0.000000000000000000000000000000', $engine->calculate($records, collect())['IC12']['value']);
    }

    public function test_guest_and_consultor_cannot_import_or_administer(): void
    {
        $this->get('/panel')->assertRedirect('/login');
        $user = User::factory()->create(['role' => 'consultor', 'active' => true]);
        $this->actingAs($user)->get('/importaciones')->assertForbidden();
        $this->get('/usuarios')->assertForbidden();
        $this->get('/auditoria')->assertForbidden();
    }

    public function test_login_logout_and_suspension(): void
    {
        $user = User::factory()->create(['email' => 'ficticio@example.test', 'password' => 'ClaveDePrueba!123', 'role' => 'consultor', 'active' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertSessionHasErrors();
        $this->post('/login', ['email' => $user->email, 'password' => 'ClaveDePrueba!123'])->assertRedirect('/operaciones');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $user->active = false;
        $user->save();
        $this->post('/login', ['email' => $user->email, 'password' => 'ClaveDePrueba!123'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_password_reset_token_is_single_use(): void
    {
        Notification::fake();
        $user = User::factory()->create(['active' => true]);
        $token = Password::createToken($user);
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'NuevaClavePrueba!123', 'password_confirmation' => 'NuevaClavePrueba!123'];
        $this->post('/reset-password', $data)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NuevaClavePrueba!123', $user->fresh()->password));
        $this->post('/reset-password', $data)->assertSessionHasErrors();
        $this->get('/register')->assertNotFound();
    }

    public function test_expired_reset_token_is_rejected(): void
    {
        $user = User::factory()->create(['active' => true]);
        $token = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->post('/reset-password', [
            'email' => $user->email, 'token' => $token,
            'password' => 'ClaveNuevaFicticia!123', 'password_confirmation' => 'ClaveNuevaFicticia!123',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_administrator_creates_account_with_hashed_password_and_can_suspend_it(): void
    {
        $admin = User::factory()->create(['role' => 'administrador', 'active' => true]);
        $data = ['name' => 'Cuenta ficticia aislada', 'email' => 'cuenta-ficticia@example.test', 'role' => 'analista', 'active' => 1,
            'password' => 'ClaveSoloPrueba!123', 'password_confirmation' => 'ClaveSoloPrueba!123'];
        $this->actingAs($admin)->post('/usuarios', $data)->assertSessionHasNoErrors()->assertRedirect('/usuarios');
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertTrue(Hash::check($data['password'], $user->password));
        $data['active'] = 0;
        $data['password'] = $data['password_confirmation'] = null;
        $this->put('/usuarios/'.$user->id, $data)->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->active);
        $this->flushSession();
        $this->actingAs($user->fresh())->get('/panel')->assertForbidden();
    }

    public function test_documentary_pages_and_filters_render_without_inventing_data(): void
    {
        $this->loadEvidence();
        $this->actingAs(User::factory()->create(['role' => 'administrador', 'active' => true]));
        foreach (['/registros', '/registros/'.DocumentaryRecord::first()->id, '/rutas', '/fuentes', '/importaciones', '/importaciones/'.ImportBatch::first()->id, '/usuarios', '/usuarios/'.auth()->id().'/editar', '/perfil', '/auditoria'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/panel')->assertRedirect('/operaciones');
        $this->get('/registros?source=E26')->assertSee('R39')->assertDontSee('Cobertura declarada');
        $this->get('/registros?year=1900')->assertSessionHasErrors('year');
    }

    public function test_admin_cannot_remove_own_access_and_consultor_cannot_escalate_role(): void
    {
        $admin = User::factory()->create(['role' => 'administrador', 'active' => true]);
        $this->actingAs($admin)->put('/usuarios/'.$admin->id, ['name' => $admin->name, 'email' => $admin->email, 'role' => 'consultor', 'active' => 1])->assertSessionHasErrors('role');
        $this->assertSame('administrador', $admin->fresh()->role);
        $user = User::factory()->create(['role' => 'consultor', 'active' => true]);
        $this->actingAs($user)->put('/perfil', ['name' => $user->name, 'email' => $user->email, 'current_password' => 'password', 'role' => 'administrador'])->assertSessionHasNoErrors();
        $this->assertSame('consultor', $user->fresh()->role);
    }

    public function test_xlsx_and_pdf_exports_are_real_files_with_traceability(): void
    {
        $this->loadEvidence();
        $this->actingAs(User::factory()->create(['role' => 'analista', 'active' => true]));
        $response = $this->get('/reportes/xlsx?source=M26')->assertOk();
        $bytes = $response->streamedContent();
        $this->assertStringStartsWith('PK', $bytes);
        $file = tempnam(sys_get_temp_dir(), 'amarilis-export-');
        try {
            file_put_contents($file, $bytes);
            $book = IOFactory::load($file);
            $this->assertSame(3, $book->getSheetCount());
            $rows = $book->getSheet(0)->toArray();
            $row = collect($rows)->first(fn ($row) => $row[0] === 'R35');
            $this->assertSame('>', $row[3]);
            $this->assertEquals(235.78, $row[2]);
            $book->disconnectWorksheets();
        } finally {
            @unlink($file);
        }
        $pdf = $this->get('/reportes/pdf?source=M26')->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }
}
