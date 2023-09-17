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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->integer("guide_id")->default(0);
            $table->integer("agency_id")->default(0);
            $table->integer("customer_id")->default(0);
            $table->integer("service_id")->default(0);
            $table->string("payment_method")->default(0);
            $table->string("trx_id")->default(0);
            $table->text("calculation")->nullable();
            $table->text("metadata")->nullable();
            $table->enum("status", ["pending", "accepted", "rejected", "progressing", "progressed", "cancelled", "completed"])->default("pending");
            $table->integer("total_amount")->default(0);
            $table->integer("total_discount")->default(0);
            $table->boolean("is_rated")->default(false);
            $table->timestamp("check_in");
            $table->timestamp("check_out");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
