<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Resources\Settings\FailedJobResource;
use App\Filament\Resources\Settings\FailedJobResource\Pages\ManageFailedJobs;
use Filament\Tables\Table;
use Tests\TestCase;
use Throwable;

class FailedJobResourceTest extends TestCase
{
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
