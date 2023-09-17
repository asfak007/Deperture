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
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->integer("agency_id")->default(0);
            $table->string("first_name");
            $table->string("last_name")->nullable();
            $table->string("email")->unique();
            $table->string("password");
            $table->string("phone", 15)->unique();
            $table->string("image")->nullable();
            $table->string("city")->nullable();
            $table->string("country")->nullable();
            $table->text("metadata")->nullable();
            $table->enum("provider", ["credential", "facebook", "google"])->default("credential");
            $table->string("provider_id")->nullable();
            $table->string("access_token")->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string("firebase_token")->nullable();
            $table->boolean("status")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
