<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class InboxWhatsapp extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string | \UnitEnum | null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Inbox WhatsApp';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Inbox WhatsApp';

    protected string $view = 'filament.pages.inbox-whatsapp';
}
