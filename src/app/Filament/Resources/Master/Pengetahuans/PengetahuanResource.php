<?php

namespace App\Filament\Resources\Master\Pengetahuans;

use App\Filament\Resources\Master\Pengetahuans\Pages\ManagePengetahuans;
use App\Models\Master\Pengetahuan;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

class PengetahuanResource extends Resource
{
    protected static ?string $model = Pengetahuan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Knowledge Base AI';

    protected static ?string $modelLabel = 'Knowledge Base AI';

    protected static ?string $pluralModelLabel = 'Knowledge Base AI';

    protected static ?int $navigationSort = 46;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('KodePengetahuan')
                    ->label('Kode')
                    ->maxLength(50)
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Str::upper(Str::slug($state, '_')) : null),
                TextInput::make('JudulPengetahuan')
                    ->label('Judul')
                    ->maxLength(200)
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, ?Model $record): void {
                        if ($record || ! $state) {
                            return;
                        }

                        $set('KodePengetahuan', Str::upper(Str::slug(Str::limit($state, 45, ''), '_')));
                    }),
                TextInput::make('Tag')
                    ->label('Tag / kata kunci')
                    ->maxLength(500)
                    ->placeholder('login,password,error,reset'),
                Textarea::make('IsiPengetahuan')
                    ->label('Isi pengetahuan / SOP')
                    ->rows(10)
                    ->required()
                    ->columnSpanFull()
                    ->helperText('AI boleh mengimprovisasi gaya bahasa, tetapi isi faktualnya akan mengacu ke data ini.'),
                Toggle::make('NonAktif')
                    ->label('Nonaktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('KodePengetahuan')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('JudulPengetahuan')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('Tag')
                    ->label('Tag')
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('IsiPengetahuan')
                    ->label('Isi')
                    ->limit(120)
                    ->searchable()
                    ->wrap(),
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
                TernaryFilter::make('NonAktif')
                    ->label('Status nonaktif'),
            ])
            ->defaultSort('JudulPengetahuan')
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
            'index' => ManagePengetahuans::route('/'),
        ];
    }
}
