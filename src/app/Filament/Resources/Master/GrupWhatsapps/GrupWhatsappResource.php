<?php

namespace App\Filament\Resources\Master\GrupWhatsapps;

use App\Filament\Resources\Master\GrupWhatsapps\Pages\ManageGrupWhatsapps;
use App\Models\Master\GrupWhatsapp;
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

class GrupWhatsappResource extends Resource
{
    protected static ?string $model = GrupWhatsapp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('IdInstansi')
                    ->required(),
                TextInput::make('KodeGrup')
                    ->required(),
                TextInput::make('NamaGrup')
                    ->required(),
                TextInput::make('IdGrupWaha'),
                TextInput::make('NomorGrupWhatsapp'),
                TextInput::make('Deskripsi'),
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
                TextColumn::make('IdInstansi'),
                TextColumn::make('KodeGrup')
                    ->searchable(),
                TextColumn::make('NamaGrup')
                    ->searchable(),
                TextColumn::make('IdGrupWaha')
                    ->searchable(),
                TextColumn::make('NomorGrupWhatsapp')
                    ->searchable(),
                TextColumn::make('Deskripsi')
                    ->searchable(),
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
            'index' => ManageGrupWhatsapps::route('/'),
        ];
    }
}
