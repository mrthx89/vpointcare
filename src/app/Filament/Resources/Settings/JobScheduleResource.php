<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\JobScheduleResource\Pages;
use App\Models\JobSchedule;
use App\Support\FilamentAccess;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;

class JobScheduleResource extends Resource
{
    protected static ?string $model = JobSchedule::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clock';
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }
    
    public static function getNavigationLabel(): string
    {
        return 'Penjadwalan Jobs';
    }
    
    public static function canViewAny(): bool
    {
        return FilamentAccess::isRoot() || FilamentAccess::isCSLeader();
    }

    public static function canCreate(): bool
    {
        return false; // User tidak bisa menambah job baru
    }

    public static function canEdit($record): bool
    {
        return FilamentAccess::isRoot() || FilamentAccess::isCSLeader();
    }

    public static function canDelete($record): bool
    {
        return false; // User tidak bisa menghapus
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Job')
                    ->disabled(),
                TextInput::make('command')
                    ->label('Perintah Artisan')
                    ->disabled(),
                Select::make('cron_expression')
                    ->label('Jadwal Eksekusi')
                    ->options([
                        'everySecond' => 'Setiap Detik (1s)',
                        'everyTwoSeconds' => 'Setiap 2 Detik (2s)',
                        'everyFiveSeconds' => 'Setiap 5 Detik (5s)',
                        'everyTenSeconds' => 'Setiap 10 Detik (10s)',
                        'everyFifteenSeconds' => 'Setiap 15 Detik (15s)',
                        'everyTwentySeconds' => 'Setiap 20 Detik (20s)',
                        'everyThirtySeconds' => 'Setiap 30 Detik (30s)',
                        'everyMinute' => 'Setiap Menit',
                        'everyFiveMinutes' => 'Setiap 5 Menit',
                        'everyTenMinutes' => 'Setiap 10 Menit',
                        'hourly' => 'Setiap Jam',
                        'daily' => 'Setiap Hari',
                    ])
                    ->required()
                    ->default('everyMinute'),
                Toggle::make('is_active')
                    ->label('Aktif?')
                    ->default(true),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Job'),
                TextColumn::make('command')
                    ->label('Perintah')
                    ->color('gray')
                    ->size('sm'),
                TextColumn::make('cron_expression')
                    ->label('Interval')
                    ->formatStateUsing(function (string $state) {
                        $labels = [
                            'everySecond' => 'Setiap Detik (1s)',
                            'everyTwoSeconds' => 'Setiap 2 Detik (2s)',
                            'everyFiveSeconds' => 'Setiap 5 Detik (5s)',
                            'everyTenSeconds' => 'Setiap 10 Detik (10s)',
                            'everyFifteenSeconds' => 'Setiap 15 Detik (15s)',
                            'everyTwentySeconds' => 'Setiap 20 Detik (20s)',
                            'everyThirtySeconds' => 'Setiap 30 Detik (30s)',
                            'everyMinute' => 'Setiap Menit',
                            'everyFiveMinutes' => 'Setiap 5 Menit',
                            'everyTenMinutes' => 'Setiap 10 Menit',
                            'hourly' => 'Setiap Jam',
                            'daily' => 'Setiap Hari',
                        ];
                        return $labels[$state] ?? $state;
                    })
                    ->badge()
                    ->color('info'),
                ToggleColumn::make('is_active')
                    ->label('Status'),
            ])
            ->paginated(false)
            ->recordActions([
                \Filament\Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageJobSchedules::route('/'),
        ];
    }
}
