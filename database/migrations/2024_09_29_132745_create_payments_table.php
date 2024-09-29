<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('pidx')->unique();  // Payment ID from Khalti
            $table->string('purchase_order_id'); // Unique Order ID
            $table->string('transaction_id')->nullable(); // Khalti Transaction ID
            $table->string('status')->nullable(); // Payment status (Completed, Pending, etc.)
            $table->integer('amount'); // Amount paid in paisa
            $table->string('mobile')->nullable(); // Mobile number of the payer
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
