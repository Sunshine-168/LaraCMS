<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_quick_panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->string('item_key', 64);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['admin_user_id', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_quick_panels');
    }
};
