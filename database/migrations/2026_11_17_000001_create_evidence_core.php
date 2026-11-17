<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 24)->default('consultor');
            $table->boolean('active')->default(true);
        });
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->char('sha256', 64)->unique();
            $table->string('original_name');
            $table->string('private_path');
            $table->string('status', 24)->default('preview');
            $table->json('summary');
            $table->json('payload');
            $table->text('failure')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->text('title');
            $table->unsignedSmallInteger('year');
            $table->text('locator');
            $table->text('period');
            $table->text('url');
            $table->text('access_note');
            $table->date('consulted_on');
            $table->foreignId('import_batch_id')->constrained()->restrictOnDelete();
        });
        Schema::create('documentary_records', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->foreignId('source_id')->constrained()->restrictOnDelete();
            $table->foreignId('import_batch_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('source_row');
            $table->unsignedSmallInteger('year')->index();
            $table->text('label');
            $table->decimal('value', 24, 10)->nullable();
            $table->string('unit', 32);
            $table->string('operator', 4);
            $table->string('process', 64)->index();
            $table->string('waste_type', 32)->nullable();
            $table->string('territory')->default('Amarilis');
            $table->text('period');
            $table->string('temporal_precision', 32);
            $table->text('note')->nullable();
            $table->text('locator');
            $table->json('restrictions');
            $table->string('qualifier', 32)->nullable();
        });
        Schema::create('documentary_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->restrictOnDelete();
            $table->foreignId('import_batch_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('source_row');
            $table->unsignedTinyInteger('number');
            $table->text('place');
            $table->unsignedInteger('participants');
            $table->string('day');
            $table->string('schedule');
            $table->string('waste_type');
            $table->string('period');
            $table->unique(['source_id', 'number']);
        });
        Schema::create('quality_issues', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->foreignId('source_id')->constrained()->restrictOnDelete();
            $table->text('finding');
            $table->text('treatment');
            $table->text('required_evidence');
            $table->json('record_codes');
        });
        Schema::create('evidence_files', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('original_name');
            $table->char('sha256', 64)->unique();
            $table->string('private_path');
            $table->string('mime', 128);
            $table->unsignedBigInteger('bytes');
            $table->timestamps();
        });
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action', 80);
            $table->string('subject', 160);
            $table->json('detail')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        foreach (['audit_events', 'evidence_files', 'quality_issues', 'documentary_routes', 'documentary_records', 'sources', 'import_batches'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'active']));
    }
};
