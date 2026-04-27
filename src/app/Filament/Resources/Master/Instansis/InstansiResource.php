<?php

namespace App\Filament\Resources\Master\Instansis;

use App\Filament\Resources\Master\Instansis\Pages\ManageInstansis;
use App\Models\Master\Instansi;
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

class InstansiResource extends Resource
{
    protected static ?string $model = Instansi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('KodeInstansi')
                    ->required(),
                TextInput::make('NamaInstansi')
                    ->required(),
                TextInput::make('Alamat'),
                TextInput::make('Kota'),
                TextInput::make('Provinsi'),
                TextInput::make('Negara'),
                TextInput::make('KodePos'),
                TextInput::make('Telepon'),
                TextInput::make('Email'),
                TextInput::make('Website'),
                TextInput::make('SumberData'),
                TextInput::make('IdExternal'),
                DateTimePicker::make('TglSinkronTerakhir'),
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
                TextColumn::make('KodeInstansi')
                    ->searchable(),
                TextColumn::make('NamaInstansi')
                    ->searchable(),
                TextColumn::make('Alamat')
                    ->searchable(),
                TextColumn::make('Kota')
                    ->searchable(),
                TextColumn::make('Provinsi')
                    ->searchable(),
                TextColumn::make('Negara')
                    ->searchable(),
                TextColumn::make('KodePos')
                    ->searchable(),
                TextColumn::make('Telepon')
                    ->searchable(),
                TextColumn::make('Email')
                    ->searchable(),
                TextColumn::make('Website')
                    ->searchable(),
                TextColumn::make('SumberData')
                    ->searchable(),
                TextColumn::make('IdExternal')
                    ->searchable(),
                TextColumn::make('TglSinkronTerakhir')
                    ->dateTime()
                    ->sortable(),
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
            'index' => ManageInstansis::route('/'),
        ];
    }
}
