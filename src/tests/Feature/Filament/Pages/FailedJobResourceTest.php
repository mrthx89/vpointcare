<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Resources\Settings\FailedJobResource;
use App\Filament\Resources\Settings\FailedJobResource\Pages\ManageFailedJobs;
use App\Support\AccessPermissions;
use Filament\Tables\Table;
use Tests\TestCase;
use Throwable;

class FailedJobResourceTest extends TestCase
{
    public function test_manage_failed_jobs_page_uses_breadcrumbs_trait_and_correct_menu_code(): void
    {
        $traits = class_uses(ManageFailedJobs::class);
        $this->assertArrayHasKey('App\Filament\Concerns\HasMenuBreadcrumbs', $traits);

        $ref = new \ReflectionClass(ManageFailedJobs::class);
        $this->assertTrue($ref->hasProperty('breadcrumbMenuCode'));
        $property = $ref->getProperty('breadcrumbMenuCode');
        $property->setAccessible(true);
        $this->assertSame(AccessPermissions::QUEUE_MONITOR_VIEW, $property->getValue());
    }

    public function test_failed_job_resource_contains_no_hardcoded_untranslated_strings(): void
    {
        $content = file_get_contents(base_path('app/Filament/Resources/Settings/FailedJobResource.php'));
        $this->assertStringNotContainsString("'Retry Failed'", $content);
        $this->assertStringNotContainsString('"Retry Failed"', $content);
    }

    public function test_failed_job_table_uses_available_filament_actions(): void
    {
        try {
            $table = FailedJobResource::table(Table::make(app(ManageFailedJobs::class)));
        } catch (Throwable $exception) {
            $this->fail($exception->getMessage());
        }

        $this->assertSame(
            ['view', 'retry', 'delete'],
            array_map(fn ($action) => $action->getName(), $table->getRecordActions()),
        );
        $this->assertSame(
            ['delete', 'retry_bulk'],
            array_map(fn ($action) => $action->getName(), $table->getToolbarActions()),
        );
    }

    public function test_queue_monitor_translations_are_available_in_both_locales(): void
    {
        $originalLocale = app()->getLocale();

        try {
            foreach (['id', 'en'] as $locale) {
                app()->setLocale($locale);

                foreach ([
                    'ui.queue_monitor.label',
                    'ui.queue_monitor.plural',
                    'ui.queue_monitor.subtitle',
                    'ui.queue_monitor.id',
                    'ui.queue_monitor.uuid',
                    'ui.queue_monitor.connection',
                    'ui.queue_monitor.queue',
                    'ui.queue_monitor.payload',
                    'ui.queue_monitor.exception',
                    'ui.queue_monitor.failed_at',
                    'ui.queue_monitor.retry',
                    'ui.queue_monitor.retry_selected',
                    'ui.queue_monitor.retry_all',
                    'ui.queue_monitor.flush_all',
                    'ui.queue_monitor.retry_success',
                    'ui.queue_monitor.retry_failed',
                    'ui.queue_monitor.retry_all_success',
                    'ui.queue_monitor.flush_all_success',
                    'ui.queue_monitor.empty_heading',
                    'ui.queue_monitor.empty_description',
                    'ui.queue_monitor.confirm_retry_heading',
                    'ui.queue_monitor.confirm_retry_description',
                    'ui.queue_monitor.confirm_retry_bulk_heading',
                    'ui.queue_monitor.confirm_retry_bulk_description',
                    'ui.queue_monitor.confirm_retry_all_heading',
                    'ui.queue_monitor.confirm_retry_all_description',
                    'ui.queue_monitor.confirm_flush_all_heading',
                    'ui.queue_monitor.confirm_flush_all_description',
                ] as $key) {
                    $this->assertNotSame($key, __($key), "Translation key {$key} is missing in locale {$locale}");
                }
            }
        } finally {
            app()->setLocale($originalLocale);
        }
    }
}
