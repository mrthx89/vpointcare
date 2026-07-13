<?php

namespace App\Http\Controllers\Ticketing;

use App\Http\Controllers\Controller;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function ticket(string $attachment): Response
    {
        abort_unless(FilamentAccess::can(AccessPermissions::TICKET_VIEW), 403);

        return $this->download('TTicketDLampiran', $attachment);
    }

    public function task(string $attachment): Response
    {
        abort_unless(FilamentAccess::can(AccessPermissions::TASK_VIEW), 403);

        return $this->download('TTaskDLampiran', $attachment);
    }

    private function download(string $table, string $id): Response
    {
        $file = DB::table($table)->where('Id', $id)->first();
        abort_if(! $file || ! Storage::disk('attachments')->exists($file->PathFile), 404);

        return response(Storage::disk('attachments')->get($file->PathFile), 200, [
            'Content-Type' => $file->TipeFile ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', $file->NamaFile).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
