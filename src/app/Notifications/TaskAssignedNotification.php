<?php

namespace App\Notifications;

use App\Filament\Resources\Operational\Tasks\TaskResource;
use App\Models\Ticketing\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Task $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => __('ui.ticketing.task_assigned'), 'body' => $this->task->NomorTask.' - '.$this->task->JudulTask, 'url' => TaskResource::getUrl()];
    }
}
