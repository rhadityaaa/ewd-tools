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
        Schema::create('borrower_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrower_id')->constrained('borrowers')->cascadeOnDelete();
            $table->string('facility_name');
            $table->decimal('limit', 12, 2);
            $table->decimal('outstanding', 12, 2);
            $table->decimal('interest_rate', 5, 2); // Suku Bunga Efektif (%)
            $table->decimal('principal_arrears', 12, 2); // Tunggakan Pokok
            $table->decimal('interest_arrears', 12, 2); // Tunggakan Bunga
            $table->integer('pdo_days');
            $table->date('maturity_date', 12, 2); // Tanggal Jatuh Tempo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrower_facilities');
    }
};
