<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', static function (Blueprint $table) {
            $table->id();
	        $table->foreignId('client_id')->constrained('clients');
	        $table->dateTime('appointment_at')->index();
	        $table->text('description')->nullable();
	        $table->string('notification_type');
	        $table->timestamps();
			$table->index(['client_id', 'appointment_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
