<?php

namespace App\Filament\Resources\Settings\JobSchedules;

use App\Filament\Resources\Settings\JobSchedules\Pages\CreateJobSchedule;
use App\Filament\Resources\Settings\JobSchedules\Pages\EditJobSchedule;
use App\Filament\Resources\Settings\JobSchedules\Pages\ListJobSchedules;
use App\Filament\Resources\Settings\JobSchedules\Schemas\JobScheduleForm;
use App\Filament\Resources\Settings\JobSchedules\Tables\JobSchedulesTable;
use App\Models\Settings\JobSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JobScheduleResource extends Resource
{
    protected static ?string $model = JobSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return JobScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobSchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobSchedules::route('/'),
            'create' => CreateJobSchedule::route('/create'),
            'edit' => EditJobSchedule::route('/{record}/edit'),
        ];
    }
}
