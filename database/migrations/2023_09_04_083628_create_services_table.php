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
        Schema::create("services", function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->integer("price")->default(0);
            $table->integer("guide_id")->default(0);
            $table->integer("agency_id")->default(0);
            $table->integer("category_id")->default(0);
            $table->text("short_description")->nullable();
            $table->text("long_description")->nullable();
            $table->string("address")->nullable();
            $table->integer("discount")->nullable();
            $table->text("thumbnail")->nullable();
            $table->string("image")->nullable();
            $table->text("metadata")->nullable();
            $table->integer("booking_count")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("services");
    }
};
