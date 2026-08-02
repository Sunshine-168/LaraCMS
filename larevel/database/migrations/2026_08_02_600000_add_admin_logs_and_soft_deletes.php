<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_admin_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_name')->nullable();
            $table->string('method', 16);
            $table->string('path', 500);
            $table->string('ip', 45)->nullable();
            $table->unsignedSmallInteger('status')->default(200);
            $table->timestamps();
        });

        Schema::table('cms_entries', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('cms_entries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::dropIfExists('cms_admin_logs');
    }
};
