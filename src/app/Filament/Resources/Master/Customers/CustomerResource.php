<?php

namespace App\Filament\Resources\Master\Customers;

use App\Filament\Resources\Master\Customers\Pages\ManageCustomers;
use App\Models\Master\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('IdInstansi'),
                TextInput::make('KodeCustomer')
                    ->required(),
                TextInput::make('NamaCustomer')
                    ->required(),
                TextInput::make('Email'),
                TextInput::make('Telepon'),
                TextInput::make('Jabatan'),
                TextInput::make('Catatan'),
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
                TextColumn::make('IdInstansi'),
                TextColumn::make('KodeCustomer')
                    ->searchable(),
                TextColumn::make('NamaCustomer')
                    ->searchable(),
                TextColumn::make('Email')
                    ->searchable(),
                TextColumn::make('Telepon')
                    ->searchable(),
                TextColumn::make('Jabatan')
                    ->searchable(),
                TextColumn::make('Catatan')
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
            'index' => ManageCustomers::route('/'),
        ];
    }
}
