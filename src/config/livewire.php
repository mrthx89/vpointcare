<?php

return [
    'temporary_file_upload' => [
        'rules' => ['required', 'file', 'max:'.env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_KB', 51200)],
        'max_upload_time' => (int) env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_TIME', 10),
    ],
];
