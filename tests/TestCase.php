<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \App\Support\Locale::forgetCurrent();
        \App\Support\Locale::forgetCache();
    }

    protected function tearDown(): void
    {
        \App\Support\Locale::forgetCurrent();
        try {
            \App\Support\Locale::forgetCache();
        } catch (\Throwable) {
        }
        parent::tearDown();
    }

    protected function seededEntry(string $slug): \App\Models\Cms\Entry
    {
        $entry = \App\Models\Cms\Entry::query()
            ->with(['category', 'modelDef', 'categories', 'tags'])
            ->get()
            ->first(fn (\App\Models\Cms\Entry $row) => $row->t('slug') === $slug);
        $this->assertInstanceOf(\App\Models\Cms\Entry::class, $entry);

        return $entry;
    }
}
