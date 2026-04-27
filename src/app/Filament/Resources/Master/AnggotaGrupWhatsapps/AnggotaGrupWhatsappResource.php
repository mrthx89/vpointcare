<?php

namespace App\Filament\Resources\Master\AnggotaGrupWhatsapps;

use App\Filament\Resources\Master\AnggotaGrupWhatsapps\Pages\ManageAnggotaGrupWhatsapps;
use App\Models\Master\AnggotaGrupWhatsapp;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnggotaGrupWhatsappResource extends Resource
{
    protected static ?string $model = AnggotaGrupWhatsapp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('IdGrupWhatsapp')
                    ->required(),
                TextInput::make('IdNomorWhatsapp')
                    ->required(),
                TextInput::make('IdCustomer'),
                TextInput::make('PeranAnggota'),
                Toggle::make('NonAktif')
                    ->required(),
                DateTimePicker::make('TglBuat')
                    ->required(),
                TextInput::make('DibuatOleh'),
                DateTimePicker::make('TglEdit'),
                TextInput::make('DieditOleh'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Id'),
                TextColumn::make('IdGrupWhatsapp'),
                TextColumn::make('IdNomorWhatsapp'),
                TextColumn::make('IdCustomer'),
                TextColumn::make('PeranAnggota')
                    ->searchable(),
                IconColumn::make('NonAktif')
                    ->boolean(),
                TextColumn::make('TglBuat')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('DibuatOleh'),
                TextColumn::make('TglEdit')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('DieditOleh'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAnggotaGrupWhatsapps::route('/'),
        ];
    }
}
