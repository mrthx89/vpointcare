<?php

namespace App\Filament\Resources\Master\NomorWhatsapps;

use App\Filament\Resources\Master\NomorWhatsapps\Pages\ManageNomorWhatsapps;
use App\Models\Master\NomorWhatsapp;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class NomorWhatsappResource extends Resource
{
    protected static ?string $model = NomorWhatsapp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Nomor WhatsApp';

    protected static ?string $modelLabel = 'Nomor WhatsApp';

    protected static ?string $pluralModelLabel = 'Nomor WhatsApp';

    protected static ?int $navigationSort = 43;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('IdCustomer')
                    ->label('Kontak')
                    ->relationship('customer', 'NamaCustomer')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('NomorWhatsapp')
                    ->label('Nomor WhatsApp')
                    ->helperText('Gunakan format angka, contoh 6281234567890.')
                    ->maxLength(30)
                    ->required(),
                TextInput::make('IdWaha')
                    ->label('ID WAHA / JID')
                    ->helperText('Isi jika WAHA mengirim ID internal seperti 137799747518482 atau JID @lid/@c.us.')
                    ->maxLength(200),
                TextInput::make('NamaKontak')
                    ->label('Nama di WhatsApp')
                    ->maxLength(150),
                TextInput::make('JabatanKontak')
                    ->label('Jabatan')
                    ->maxLength(100),
                Toggle::make('NomorUtama')
                    ->label('Nomor utama'),
                Toggle::make('Terverifikasi')
                    ->label('Terverifikasi'),
                Toggle::make('NonAktif')
                    ->label('Nonaktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instansi.NamaInstansi')
                    ->label('Klien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.NamaCustomer')
                    ->label('Kontak')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('NomorWhatsapp')
                    ->label('Nomor WA')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('IdWaha')
                    ->label('ID WAHA')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('NamaKontak')
                    ->label('Nama WA')
                    ->searchable(),
                TextColumn::make('JabatanKontak')
                    ->label('Jabatan')
                    ->searchable(),
                ToggleColumn::make('NomorUtama')
                    ->label('Utama'),
                ToggleColumn::make('Terverifikasi')
                    ->label('Verified'),
                ToggleColumn::make('NonAktif')
                    ->label('Nonaktif'),
                TextColumn::make('TglBuat')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('TglEdit')
                    ->label('Diedit')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('IdCustomer')
                    ->label('Kontak')
                    ->relationship('customer', 'NamaCustomer')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('NonAktif')
                    ->label('Status nonaktif'),
            ])
            ->defaultSort('NamaKontak')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNomorWhatsapps::route('/'),
        ];
    }
}
