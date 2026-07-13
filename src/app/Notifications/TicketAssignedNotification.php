<?php

namespace App\Notifications;

use App\Filament\Resources\Operational\Tickets\TicketResource;
use App\Models\Ticketing\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Ticket ditugaskan', 'body' => $this->ticket->NomorTicket.' - '.$this->ticket->JudulTicket, 'url' => TicketResource::getUrl()];
    }
}
