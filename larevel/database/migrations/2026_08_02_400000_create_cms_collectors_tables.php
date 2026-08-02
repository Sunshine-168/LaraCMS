<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_collectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 32)->default('rss'); // rss|json
            $table->string('source_url');
            $table->foreignId('model_id')->constrained('cms_models')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('cms_categories')->nullOnDelete();
            $table->unsignedInteger('interval_minutes')->default(60);
            $table->unsignedInteger('limit_items')->default(20);
            $table->boolean('is_active')->default(true);
            $table->boolean('publish_immediately')->default(true);
            $table->json('options')->nullable(); // json path mapping 等
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status', 32)->nullable();
            $table->text('last_message')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_collector_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collector_id')->constrained('cms_collectors')->cascadeOnDelete();
            $table->string('status', 32); // ok|fail|partial
            $table->unsignedInteger('fetched')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::table('cms_entries', function (Blueprint $table) {
            $table->string('source_guid', 191)->nullable()->index()->after('flags');
            $table->unsignedBigInteger('collector_id')->nullable()->index()->after('source_guid');
        });
    }

    public function down(): void
    {
        Schema::table('cms_entries', function (Blueprint $table) {
            $table->dropColumn(['source_guid', 'collector_id']);
        });
        Schema::dropIfExists('cms_collector_logs');
        Schema::dropIfExists('cms_collectors');
    }
};
