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
}
