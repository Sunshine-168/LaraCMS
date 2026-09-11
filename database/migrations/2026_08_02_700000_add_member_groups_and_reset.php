<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_member_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('cms_members', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('is_active')->constrained('cms_member_groups')->nullOnDelete();
            $table->string('reset_token', 64)->nullable()->after('remember_token');
            $table->timestamp('reset_token_expires_at')->nullable()->after('reset_token');
        });
    }

    public function down(): void
    {
        Schema::table('cms_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn(['reset_token', 'reset_token_expires_at']);
        });
        Schema::dropIfExists('cms_member_groups');
    }
};
