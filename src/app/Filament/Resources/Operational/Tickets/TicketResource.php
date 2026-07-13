<?php

namespace App\Filament\Resources\Operational\Tickets;

use App\Filament\Resources\Operational\Tickets\Pages\ManageTickets;
use App\Models\Ticketing\Ticket;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\NavigationHelper;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return NavigationHelper::iconFor(AccessPermissions::TICKET_VIEW, 'heroicon-o-ticket');
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::TICKET_VIEW, __('ui.navigation.operasional'));
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function getNavigationLabel(): string
    {
        return __('ui.ticketing.tickets');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::TICKET_VIEW);
    }

    public static function canCreate(): bool
    {
        return FilamentAccess::can(AccessPermissions::TICKET_MANAGE);
    }

    public static function canEdit($record): bool
    {
        return static::canCreate();
    }

    public static function canDelete($record): bool
    {
        return static::canCreate();
    }

    private static function options(string $table, string $label): array
    {
        return DB::table($table)->where('NonAktif', false)->orderBy($label)->pluck($label, 'Id')->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('NomorTicket')->disabled()->dehydrated(false), TextInput::make('JudulTicket')->required()->maxLength(255)->columnSpanFull(), Textarea::make('DeskripsiMasalah')->rows(5)->columnSpanFull(),
            Select::make('IdStatusTicket')->options(fn () => self::options('MStatusTicket', 'NamaStatusTicket'))->required()->searchable(), Select::make('IdKategoriTicket')->options(fn () => self::options('MKategoriTicket', 'NamaKategori'))->searchable(), Select::make('IdPrioritasTicket')->options(fn () => self::options('MPrioritasTicket', 'NamaPrioritas'))->searchable(),
            Select::make('DitugaskanKepada')->options(fn () => self::options('MPengguna', 'NamaPengguna'))->searchable(), DateTimePicker::make('TglTargetSelesai')->native(false), Select::make('IdCustomer')->options(fn () => self::options('MCustomer', 'NamaCustomer'))->searchable(), Select::make('IdInstansi')->options(fn () => self::options('MInstansi', 'NamaInstansi'))->searchable(),
            Repeater::make('activities')->relationship()->label(__('ui.ticketing.progress_note'))->schema([Select::make('JenisAktivitas')->options(['Catatan' => 'Catatan'])->default('Catatan')->required(), Textarea::make('IsiAktivitas')->required()])->columnSpanFull(),
            Repeater::make('attachments')->relationship()->label(__('ui.ticketing.attachments'))->schema([FileUpload::make('PathFile')->disk('attachments')->directory('tickets')->maxSize(3072)->required(), TextInput::make('NamaFile')->required(), TextInput::make('TipeFile'), TextInput::make('UkuranFile')->numeric()])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('NomorTicket')->searchable()->sortable(), TextColumn::make('JudulTicket')->searchable()->limit(50), TextColumn::make('status')->state(fn ($r) => DB::table('MStatusTicket')->where('Id', $r->IdStatusTicket)->value('NamaStatusTicket') ?: '-')->badge(), TextColumn::make('assignee')->label(__('ui.ticketing.assigned_to'))->state(fn ($r) => DB::table('MPengguna')->where('Id', $r->DitugaskanKepada)->value('NamaPengguna') ?: '-'), TextColumn::make('TglTargetSelesai')->dateTime()->color(fn ($r) => $r->TglTargetSelesai?->isPast() ? 'danger' : null)->sortable()])->filters([SelectFilter::make('IdStatusTicket')->options(fn () => self::options('MStatusTicket', 'NamaStatusTicket')), Filter::make('mine')->label(__('ui.ticketing.my_tickets'))->query(fn (Builder $q) => $q->where('DitugaskanKepada', Auth::id()))])->recordActions([EditAction::make()])->defaultSort('TglBuat', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageTickets::route('/')];
    }
}
