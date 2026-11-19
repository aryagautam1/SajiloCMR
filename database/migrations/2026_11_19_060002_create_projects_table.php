<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('project_name', 160)->index();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('planning')->index();
            $table->string('priority', 20)->default('medium')->index();
            $table->date('start_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['client_id', 'status']);
            $table->index('expected_completion_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
