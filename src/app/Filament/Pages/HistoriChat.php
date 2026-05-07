<?php

namespace App\Filament\Pages;

use App\Models\ChatSession;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\LocaleFormatter;
use App\Support\NavigationHelper;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HistoriChat extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.histori-chat';

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return NavigationHelper::iconFor(AccessPermissions::CHAT_HISTORY_VIEW, Heroicon::OutlinedClock);
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::CHAT_HISTORY_VIEW, __('ui.navigation.operasional'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::CHAT_HISTORY_VIEW, 11);
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::CHAT_HISTORY_VIEW, __('ui.pages.chat_history.title'));
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('ui.pages.chat_history.title');
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::CHAT_HISTORY_VIEW, __('ui.pages.chat_history.title'));
    }

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::CHAT_HISTORY_VIEW)
            && NavigationHelper::isActive(AccessPermissions::CHAT_HISTORY_VIEW);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->chatQuery())
            ->columns([
                TextColumn::make('TglChatTerakhir')
                    ->label(__('ui.pages.chat_history.last_chat_at'))
                    ->dateTime(LocaleFormatter::tableDateTimeFormat())
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('c.TglChatTerakhir', $direction)),
                TextColumn::make('NamaKontakDisplay')
                    ->label(__('ui.pages.chat_history.contact'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $this->applyContactSearch($query, $search))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw("COALESCE(n.NamaKontak, g.NamaGrup, c.NamaKontak, c.NamaGrupWhatsapp, c.NomorWhatsapp) {$direction}"))
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('NomorWhatsappDisplay')
                    ->label(__('ui.pages.chat_history.whatsapp_number'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('c.NomorWhatsapp', 'like', "%{$search}%"))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('c.NomorWhatsapp', $direction)),
                TextColumn::make('JenisChat')
                    ->label(__('ui.pages.chat_history.chat_type'))
                    ->badge()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('c.JenisChat', $direction)),
                TextColumn::make('NamaInstansiDisplay')
                    ->label(__('ui.pages.chat_history.client'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $this->applyClientSearch($query, $search))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw("COALESCE(i.NamaInstansi, gi.NamaInstansi) {$direction}"))
                    ->placeholder(__('ui.common.not_mapped'))
                    ->wrap(),
                TextColumn::make('NamaCustomer')
                    ->label(__('ui.common.customer'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('cu.NamaCustomer', 'like', "%{$search}%"))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('cu.NamaCustomer', $direction))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('NamaStatusChat')
                    ->label(__('ui.common.status'))
                    ->badge()
                    ->placeholder(__('ui.common.completed'))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('s.NamaStatusChat', $direction)),
                TextColumn::make('NamaCS')
                    ->label(__('ui.pages.chat_history.handled_by'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('pd.NamaPengguna', 'like', "%{$search}%"))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('pd.NamaPengguna', $direction))
                    ->placeholder(__('ui.common.not_handled')),
                TextColumn::make('JumlahPesan')
                    ->label(__('ui.pages.chat_history.message_count'))
                    ->numeric()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('JumlahPesan', $direction)),
                TextColumn::make('JumlahPesanBelumDibaca')
                    ->label(__('ui.pages.chat_history.unread'))
                    ->numeric()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('c.JumlahPesanBelumDibaca', $direction))
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('period')
                    ->label(__('ui.pages.chat_history.period'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('ui.pages.chat_history.period_from'))
                            ->default(now()->subMonth()->toDateString()),
                        DatePicker::make('until')
                            ->label(__('ui.pages.chat_history.period_until'))
                            ->default(now()->toDateString()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('c.TglChatTerakhir', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('c.TglChatTerakhir', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if (! $from && ! $until) {
                            return null;
                        }

                        return __('ui.pages.chat_history.period_indicator', [
                            'from' => $from ? LocaleFormatter::date(Carbon::parse($from)) : '-',
                            'until' => $until ? LocaleFormatter::date(Carbon::parse($until)) : '-',
                        ]);
                    }),
                SelectFilter::make('JenisChat')
                    ->label(__('ui.pages.chat_history.chat_type'))
                    ->options([
                        'Pribadi' => __('ui.pages.inbox.filter_private'),
                        'Grup' => __('ui.pages.inbox.filter_group'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, string $value): Builder => $query->where('c.JenisChat', $value))),
                SelectFilter::make('IdStatusChat')
                    ->label(__('ui.common.status'))
                    ->options(fn (): array => DB::table('MStatusChat')->orderBy('NamaStatusChat')->pluck('NamaStatusChat', 'Id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, string $value): Builder => $query->where('c.IdStatusChat', $value))),
            ])
            ->defaultSort(fn (Builder $query, string $direction): Builder => $query->orderBy('c.TglChatTerakhir', $direction), 'desc')
            ->defaultKeySort(false)
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->recordActions([
                Action::make('open_session')
                    ->label(__('ui.pages.chat_history.open_session'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (ChatSession $record): string => url('/admin/view-chat-session?id=' . $record->getKey()), shouldOpenInNewTab: true),
            ]);
    }

    private function chatQuery(): Builder
    {
        return ChatSession::query()
            ->from('TChat as c')
            ->leftJoin('MStatusChat as s', 's.Id', '=', 'c.IdStatusChat')
            ->leftJoin('MNomorWhatsapp as n', 'n.Id', '=', 'c.IdNomorWhatsapp')
            ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->leftJoin('MInstansi as gi', 'gi.Id', '=', 'g.IdInstansi')
            ->leftJoin('MCustomer as cu', 'cu.Id', '=', 'c.IdCustomer')
            ->leftJoin('MPengguna as pd', 'pd.Id', '=', 'c.DiambilOleh')
            ->select([
                'c.Id',
                'c.JenisChat',
                'c.NomorWhatsapp',
                'c.JumlahPesanBelumDibaca',
                'c.TglChatTerakhir',
                DB::raw('COALESCE(n.NamaKontak, g.NamaGrup, c.NamaKontak, c.NamaGrupWhatsapp, c.NomorWhatsapp) as NamaKontakDisplay'),
                DB::raw('COALESCE(g.NomorGrupWhatsapp, g.IdGrupWaha, c.NomorWhatsapp) as NomorWhatsappDisplay'),
                DB::raw('COALESCE(i.NamaInstansi, gi.NamaInstansi) as NamaInstansiDisplay'),
                DB::raw('COALESCE(cu.NamaCustomer, \'\') as NamaCustomer'),
                DB::raw('COALESCE(s.NamaStatusChat, \'\') as NamaStatusChat'),
                DB::raw('COALESCE(pd.NamaPengguna, \'\') as NamaCS'),
            ])
            ->selectSub(
                DB::table('TChatD as d')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('d.IdChat', 'c.Id'),
                'JumlahPesan'
            );
    }

    private function applyContactSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('n.NamaKontak', 'like', "%{$search}%")
                ->orWhere('g.NamaGrup', 'like', "%{$search}%")
                ->orWhere('c.NamaKontak', 'like', "%{$search}%")
                ->orWhere('c.NamaGrupWhatsapp', 'like', "%{$search}%")
                ->orWhere('c.NomorWhatsapp', 'like', "%{$search}%");
        });
    }

    private function applyClientSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('i.NamaInstansi', 'like', "%{$search}%")
                ->orWhere('gi.NamaInstansi', 'like', "%{$search}%");
        });
    }
}
