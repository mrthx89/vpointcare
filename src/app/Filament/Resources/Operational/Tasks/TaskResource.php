<?php

namespace App\Filament\Resources\Operational\Tasks;

use App\Filament\Resources\Operational\Tasks\Pages\ManageTasks;
use App\Models\Ticketing\Task;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\NavigationHelper;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return NavigationHelper::iconFor(AccessPermissions::TASK_VIEW, 'heroicon-o-check-circle');
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::TASK_VIEW, __('ui.navigation.operasional'));
    }

    public static function getNavigationSort(): ?int
    {
        return 21;
    }

    public static function getNavigationLabel(): string
    {
        return __('ui.ticketing.tasks');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::TASK_VIEW);
    }

    public static function canCreate(): bool
    {
        return FilamentAccess::can(AccessPermissions::TASK_MANAGE);
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
            Section::make('Informasi Utama Task')
                ->schema([
                    TextInput::make('NomorTask')->disabled()->dehydrated(false),
                    TextInput::make('JudulTask')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('DeskripsiTask')->rows(5)->columnSpanFull(),
                ])
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->collapsible(),

            Section::make('Klasifikasi & Penugasan Task')
                ->schema([
                    Select::make('IdStatusTask')->options(fn () => self::options('MStatusTask', 'NamaStatusTask'))->required()->searchable(),
                    Select::make('IdTicket')->options(fn () => DB::table('TTicket')->orderByDesc('TglBuat')->limit(500)->pluck('NomorTicket', 'Id')->all())->searchable(),
                    Select::make('IdChat')->options(fn () => DB::table('TChat')->orderByDesc('TglChatTerakhir')->limit(500)->pluck('NamaKontak', 'Id')->all())->searchable(),
                    Select::make('IdCustomer')->options(fn () => self::options('MCustomer', 'NamaCustomer'))->searchable(),
                    Select::make('IdKategoriTicket')->options(fn () => self::options('MKategoriTicket', 'NamaKategori'))->searchable(),
                    Select::make('IdPrioritasTicket')->options(fn () => self::options('MPrioritasTicket', 'NamaPrioritas'))->searchable(),
                    Select::make('DitugaskanKepada')->options(fn () => self::options('MPengguna', 'NamaPengguna'))->searchable(),
                    DateTimePicker::make('TglTargetSelesai')->native(false),
                    TextInput::make('EstimasiMenit')->numeric()->minValue(0),
                ])
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->collapsible(),

            Section::make('Checklist & Komentar Progres')
                ->schema([
                    Repeater::make('checklist')->relationship()->schema([
                        TextInput::make('JudulItem')->required(),
                        Toggle::make('Selesai'),
                        TextInput::make('Urutan')->numeric()->default(0)
                    ])->columnSpanFull(),

                    Repeater::make('comments')->relationship()->label(__('ui.ticketing.progress_note'))->schema([
                        Textarea::make('IsiKomentar')->required()
                    ])->columnSpanFull(),

                    Repeater::make('assignments')->relationship()->label(__('ui.ticketing.assignment_history'))->schema([
                        Select::make('DitugaskanDari')->options(fn () => self::options('MPengguna', 'NamaPengguna'))->disabled(),
                        Select::make('DitugaskanKepada')->options(fn () => self::options('MPengguna', 'NamaPengguna'))->disabled(),
                        TextInput::make('AlasanPenugasan')->disabled(),
                        DateTimePicker::make('TglPenugasan')->disabled()
                    ])->addable(false)->deletable(false)->reorderable(false)->columnSpanFull(),
                ])
                ->collapsible(),

            Section::make('Lampiran Task')
                ->schema([
                    Repeater::make('attachments')->relationship()->label(__('ui.ticketing.attachments'))->schema([
                        FileUpload::make('PathFile')->disk('attachments')->directory('tasks')->maxSize(3072)->acceptedFileTypes(['image/*', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->storeFileNamesIn('NamaFile')->required(),
                        Placeholder::make('download')->content(fn ($record) => $record ? new HtmlString('<a href="'.route('admin.attachments.tasks.download', $record->Id).'">'.e(__('ui.ticketing.download')).'</a>') : '')
                    ])->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => self::attachmentMetadata($data))->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('NomorTask')->searchable()->sortable(),
            TextColumn::make('JudulTask')->searchable()->limit(50),
            TextColumn::make('status')->state(fn ($r) => DB::table('MStatusTask')->where('Id', $r->IdStatusTask)->value('NamaStatusTask') ?: '-')->badge(),
            TextColumn::make('assignee')->label(__('ui.ticketing.assigned_to'))->state(fn ($r) => DB::table('MPengguna')->where('Id', $r->DitugaskanKepada)->value('NamaPengguna') ?: '-'),
            TextColumn::make('TglTargetSelesai')->dateTime()->color(fn ($r) => $r->TglTargetSelesai?->isPast() && ! DB::table('MStatusTask')->where('Id', $r->IdStatusTask)->value('StatusFinal') ? 'danger' : null)
        ])->filters([
            SelectFilter::make('IdStatusTask')->options(fn () => self::options('MStatusTask', 'NamaStatusTask')),
            Filter::make('mine')->label(__('ui.ticketing.my_tickets'))->query(fn (Builder $q) => $q->where('DitugaskanKepada', Auth::id()))
        ])->recordActions([
            EditAction::make()
        ])->defaultSort('TglBuat', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageTasks::route('/')];
    }

    private static function attachmentMetadata(array $data): array
    {
        $path = $data['PathFile'];
        $data['TipeFile'] = Storage::disk('attachments')->mimeType($path);
        $data['UkuranFile'] = Storage::disk('attachments')->size($path);

        return $data;
    }
}