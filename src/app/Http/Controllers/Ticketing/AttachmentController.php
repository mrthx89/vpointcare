<?php

namespace App\Http\Controllers\Ticketing;

use App\Http\Controllers\Controller;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function ticket(string $attachment): StreamedResponse
    {
        abort_unless(FilamentAccess::can(AccessPermissions::TICKET_VIEW), 403);

        return $this->download('TTicketDLampiran', $attachment);
    }

    public function task(string $attachment): StreamedResponse
    {
        abort_unless(FilamentAccess::can(AccessPermissions::TASK_VIEW), 403);

        return $this->download('TTaskDLampiran', $attachment);
    }

    private function download(string $table, string $id): StreamedResponse
    {
        $file = DB::table($table)->where('Id', $id)->first();
        abort_if(! $file || ! Storage::disk('attachments')->exists($file->PathFile), 404);

        return response()->streamDownload(function () use ($file): void {
            $stream = Storage::disk('attachments')->readStream($file->PathFile);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $file->NamaFile, [
            'Content-Type' => $file->TipeFile ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
