<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-5">
            @foreach ([['Baru', $stats['new']], ['Dikerjakan', $stats['progress']], ['Overdue', $stats['overdue']], ['Selesai', $stats['done']], [__('ui.ticketing.my_tickets'), $myTickets]] as [$label, $value])
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-sm text-gray-500">{{ $label }}</div><div class="mt-2 text-2xl font-bold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        <div class="flex gap-3">
            <a class="fi-btn fi-btn-color-primary" href="{{ \App\Filament\Resources\Operational\Tickets\TicketResource::getUrl() }}">{{ __('ui.ticketing.tickets') }}</a>
            <a class="fi-btn fi-btn-color-gray" href="{{ \App\Filament\Resources\Operational\Tasks\TaskResource::getUrl() }}">{{ __('ui.ticketing.tasks') }}</a>
        </div>
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-full text-sm"><thead><tr class="border-b text-left"><th class="p-3">Nomor</th><th class="p-3">Judul</th><th class="p-3">Status</th><th class="p-3">PIC</th><th class="p-3">Target</th></tr></thead>
                <tbody>@forelse($recentTickets as $ticket)<tr class="border-b border-gray-100 dark:border-gray-800"><td class="p-3 font-semibold">{{ $ticket->NomorTicket }}</td><td class="p-3">{{ $ticket->JudulTicket }}</td><td class="p-3">{{ $ticket->NamaStatusTicket ?: '-' }}</td><td class="p-3">{{ $ticket->NamaPengguna ?: '-' }}</td><td class="p-3">{{ $ticket->TglTargetSelesai ?: '-' }}</td></tr>@empty<tr><td colspan="5" class="p-6 text-center text-gray-500">Belum ada ticket.</td></tr>@endforelse</tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
