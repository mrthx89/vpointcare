<?php

namespace App\Filament\Resources\Master\NomorWhatsapps;

use App\Filament\Resources\Master\NomorWhatsapps\Pages\ManageNomorWhatsapps;
use App\Models\Master\NomorWhatsapp;
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

class NomorWhatsappResource extends Resource
{
    protected static ?string $model = NomorWhatsapp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('IdCustomer'),
                TextInput::make('IdInstansi'),
                TextInput::make('NomorWhatsapp')
                    ->required(),
                TextInput::make('NamaKontak'),
                TextInput::make('JabatanKontak'),
                Toggle::make('NomorUtama')
                    ->required(),
                Toggle::make('Terverifikasi')
                    ->required(),
                TextInput::make('SumberData'),
                TextInput::make('IdExternal'),
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
                TextColumn::make('IdCustomer'),
                TextColumn::make('IdInstansi'),
                TextColumn::make('NomorWhatsapp')
                    ->searchable(),
                TextColumn::make('NamaKontak')
                    ->searchable(),
                TextColumn::make('JabatanKontak')
                    ->searchable(),
                IconColumn::make('NomorUtama')
                    ->boolean(),
                IconColumn::make('Terverifikasi')
                    ->boolean(),
                TextColumn::make('SumberData')
                    ->searchable(),
                TextColumn::make('IdExternal')
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
            'index' => ManageNomorWhatsapps::route('/'),
        ];
    }
}
