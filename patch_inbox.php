<?php
$file = "D:\GIT VPOINT\2026-WACS\src\app\Filament\Pages\InboxWhatsapp.php";
$content = file_get_contents($file);

// 1. Perbaiki fallback nama grup: prioritaskan snapshot WAHA
$content = str_replace(
    '$groupName = $row->NamaGrupMaster ?: $row->NamaGrupWhatsapp;',
    '$groupName = $snapshotGroupName ?: ($row->NamaGrupMaster ?: $row->NamaGrupWhatsapp);',
    $content
);

// 2. Perbaiki fallback nama kontak: prioritaskan snapshot WAHA
$content = str_replace(
    '$contactName = $row->NamaKontakMaster ?: $row->NamaKontak;',
    '$contactName = $snapshotContactName ?: ($row->NamaKontakMaster ?: $row->NamaKontak);',
    $content
);

// 3. Tambahkan logika sumber data setelah kontak
$insertPoint = strpos($content, '$contactName = $snapshotContactName ?: ($row->NamaKontakMaster ?: $row->NamaKontak);');
if ($insertPoint !== false) {
    $endOfLine = strpos($content, "\n", $insertPoint);
    $sourceLogic = '
        // Tentukan sumber data untuk badge
        if ($isGroup) {
            $source = $snapshotGroupName ? \'waha\' : ($row->NamaGrupMaster ? \'internal\' : ($row->NamaGrupWhatsapp ? \'payload\' : \'jid\'));
        } else {
            $source = $snapshotContactName ? \'waha\' : ($row->NamaKontakMaster ? \'internal\' : ($row->NamaKontak ? \'payload\' : \'jid\'));
        }
';
    $content = substr_replace($content, $sourceLogic, $endOfLine + 1, 0);
}

// 4. Tambahkan Source ke return array
$returnStart = strpos($content, "return [");
if ($returnStart !== false) {
    $closingBracket = strpos($content, "];", $returnStart);
    if ($closingBracket !== false) {
        $sourceField = '
            \'Source\' => $source,';
        $content = substr_replace($content, $sourceField, $closingBracket, 0);
    }
}

file_put_contents($file, $content);
echo "Patch applied successfully.\n";
