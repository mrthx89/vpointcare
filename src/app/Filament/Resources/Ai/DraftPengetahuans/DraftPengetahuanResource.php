<?php

namespace App\Filament\Resources\Ai\DraftPengetahuans;

use App\Filament\Resources\Ai\DraftPengetahuans\Pages\ManageDraftPengetahuans;
use App\Models\Ai\DraftPengetahuan;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\NavigationHelper;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

class DraftPengetahuanResource extends Resource
{
    protected static ?string $model = DraftPengetahuan::class;

    public static function getNavigationIcon(): string | BackedEnum | \Illuminate\Contracts\Support\Htmlable | null
    {
        return NavigationHelper::iconFor(AccessPermissions::KNOWLEDGE_VIEW, Heroicon::OutlinedSparkles);
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationHelper::groupFor(AccessPermissions::KNOWLEDGE_VIEW, __('ui.navigation.assistant'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::KNOWLEDGE_VIEW, 21);
    }

    public static function getNavigationLabel(): string
    {
        return __('ui.ai_learning.draft_knowledge_ai');
    }

    public static function getModelLabel(): string
    {
        return __('ui.ai_learning.draft_knowledge_ai');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.ai_learning.draft_knowledge_ai');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::KNOWLEDGE_VIEW)
            && NavigationHelper::isActive(AccessPermissions::KNOWLEDGE_VIEW);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('JudulDraft')->label(__('ui.ai_learning.field_title'))->required()->maxLength(255),
            TextInput::make('TagDraft')->label(__('ui.ai_learning.field_tags'))->maxLength(500),
            TextInput::make('KategoriDraft')->label(__('ui.ai_learning.field_category'))->maxLength(100),
            Textarea::make('IsiDraft')->label(__('ui.ai_learning.field_content'))->required()->rows(8)->columnSpanFull(),
            Textarea::make('RingkasanSumber')->label(__('ui.ai_learning.field_source_summary'))->rows(3)->columnSpanFull(),
            Textarea::make('CuplikanSumberDisanitasi')->label(__('ui.ai_learning.field_source_snippet'))->rows(8)->disabled()->dehydrated(false)->columnSpanFull(),
            Textarea::make('CatatanReviewer')->label(__('ui.ai_learning.field_reviewer_note'))->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('StatusReview')->label(__('ui.ai_learning.field_status'))->formatStateUsing(fn (?string $state): string => self::statusLabel($state))->badge()->color(fn (string $state): string => match ($state) {
                    DraftPengetahuan::STATUS_APPROVED => 'success',
                    DraftPengetahuan::STATUS_REJECTED => 'danger',
                    DraftPengetahuan::STATUS_NEEDS_REVISION => 'warning',
                    DraftPengetahuan::STATUS_ARCHIVED => 'gray',
                    default => 'info',
                })->sortable(),
                TextColumn::make('JudulDraft')->label(__('ui.ai_learning.field_title'))->searchable()->sortable()->weight('semibold')->wrap(),
                TextColumn::make('TagDraft')->label(__('ui.ai_learning.field_tags'))->searchable()->wrap()->toggleable(),
                TextColumn::make('ConfidenceScore')->label(__('ui.ai_learning.field_confidence'))->numeric()->sortable(),
                TextColumn::make('ProviderAi')->label(__('ui.ai_learning.field_provider'))->toggleable(),
                TextColumn::make('TglBuat')->label(__('ui.ai_learning.field_created_at'))->dateTime(\App\Support\LocaleFormatter::tableDateTimeFormat())->sortable(),
                TextColumn::make('TglReview')->label(__('ui.ai_learning.field_reviewed_at'))->dateTime(\App\Support\LocaleFormatter::tableDateTimeFormat())->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('StatusReview')->label(__('ui.ai_learning.field_status'))->options([
                    DraftPengetahuan::STATUS_DRAFT => __('ui.ai_learning.status_draft'),
                    DraftPengetahuan::STATUS_NEEDS_REVISION => __('ui.ai_learning.status_needs_revision'),
                    DraftPengetahuan::STATUS_APPROVED => __('ui.ai_learning.status_approved'),
                    DraftPengetahuan::STATUS_REJECTED => __('ui.ai_learning.status_rejected'),
                    DraftPengetahuan::STATUS_ARCHIVED => __('ui.ai_learning.status_archived'),
                ]),
            ])
            ->defaultSort('TglBuat', 'desc')
            ->emptyStateHeading(__('ui.ai_learning.draft_empty_heading'))
            ->emptyStateDescription(__('ui.ai_learning.draft_empty_description'))
            ->striped()
            ->recordActions([
                EditAction::make()->label(__('ui.common.edit'))->visible(fn (): bool => FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE)),
                Action::make('approve')
                    ->label(__('ui.ai_learning.action_approve'))
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('ui.ai_learning.action_approve_heading'))
                    ->successNotificationTitle(__('ui.ai_learning.action_approve_success'))
                    ->visible(fn (DraftPengetahuan $record): bool => FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE) && $record->canApprove())
                    ->action(fn (DraftPengetahuan $record) => self::approve($record)),
                Action::make('needs_revision')
                    ->label(__('ui.ai_learning.action_needs_revision'))
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('ui.ai_learning.action_needs_revision_heading'))
                    ->successNotificationTitle(__('ui.ai_learning.action_needs_revision_success'))
                    ->visible(fn (DraftPengetahuan $record): bool => FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE) && $record->StatusReview !== DraftPengetahuan::STATUS_APPROVED)
                    ->action(fn (DraftPengetahuan $record) => self::review($record, DraftPengetahuan::STATUS_NEEDS_REVISION)),
                Action::make('reject')
                    ->label(__('ui.ai_learning.action_reject'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('ui.ai_learning.action_reject_heading'))
                    ->successNotificationTitle(__('ui.ai_learning.action_reject_success'))
                    ->visible(fn (DraftPengetahuan $record): bool => FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE) && $record->StatusReview !== DraftPengetahuan::STATUS_APPROVED)
                    ->action(fn (DraftPengetahuan $record) => self::review($record, DraftPengetahuan::STATUS_REJECTED)),
                Action::make('archive')
                    ->label(__('ui.ai_learning.action_archive'))
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__('ui.ai_learning.action_archive_heading'))
                    ->successNotificationTitle(__('ui.ai_learning.action_archive_success'))
                    ->visible(fn (): bool => FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE))
                    ->action(fn (DraftPengetahuan $record) => self::review($record, DraftPengetahuan::STATUS_ARCHIVED)),
            ]);
    }

    private static function approve(DraftPengetahuan $record): void
    {
        DB::transaction(function () use ($record): void {
            $codeBase = Str::upper(Str::slug(Str::limit($record->JudulDraft, 45, ''), '_')) ?: 'KNOWLEDGE_AI';
            $code = $codeBase;
            $counter = 1;
            while (DB::table('MPengetahuan')->where('KodePengetahuan', $code)->exists()) {
                $code = Str::limit($codeBase, 44, '') . '_' . $counter++;
            }

            $knowledgeId = (string) Str::orderedUuid();
            DB::table('MPengetahuan')->insert([
                'Id' => $knowledgeId,
                'KodePengetahuan' => $code,
                'JudulPengetahuan' => $record->JudulDraft,
                'Tag' => $record->TagDraft,
                'IsiPengetahuan' => $record->IsiDraft,
                'SearchKeywords' => $record->TagDraft,
                'PrioritasAi' => 0,
                'NonAktif' => false,
                'TglBuat' => now(),
            ]);

            $record->forceFill([
                'IdPengetahuan' => $knowledgeId,
                'StatusReview' => DraftPengetahuan::STATUS_APPROVED,
                'DireviewOleh' => auth()->id(),
                'TglReview' => now(),
            ])->save();
        });
    }

    private static function review(DraftPengetahuan $record, string $status): void
    {
        $record->forceFill([
            'StatusReview' => $status,
            'DireviewOleh' => auth()->id(),
            'TglReview' => now(),
        ])->save();
    }

    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            DraftPengetahuan::STATUS_DRAFT => __('ui.ai_learning.status_draft'),
            DraftPengetahuan::STATUS_NEEDS_REVISION => __('ui.ai_learning.status_needs_revision'),
            DraftPengetahuan::STATUS_APPROVED => __('ui.ai_learning.status_approved'),
            DraftPengetahuan::STATUS_REJECTED => __('ui.ai_learning.status_rejected'),
            DraftPengetahuan::STATUS_ARCHIVED => __('ui.ai_learning.status_archived'),
            default => (string) $status,
        };
    }
    public static function getPages(): array
    {
        return ['index' => ManageDraftPengetahuans::route('/')];
    }
}
