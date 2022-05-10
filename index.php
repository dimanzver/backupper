<?php

//find . -newermt "2022-05-10 12:50:10" -type f -o -newerct "2022-05-10 12:50:10" -type f
error_reporting(E_ALL);
require_once __DIR__ . '/bootstrap.php';

$type = $argv[1];
$from = $argv[2];
$to = $argv[3];
$maxChunkSize = $argv[4];

// TODO: increment support
$tmpName = __DIR__ . '/backup-files-' . $type . time();
exec("find \"$from\" -type f > $tmpName");
$fs = fopen($tmpName, 'r');

$zipArchive = null;
while (($filepath = fgets($fs)) !== false) {
    if(!$zipArchive) {
        $archiveName = $type . '-' . date('Y-m-d H:i:s') . uniqid() . '.zip';
        $archiveTmpPath = __DIR__ . '/' . $archiveName;
        $zipArchive = new ZipArchive();
        $zipArchive->open($archiveTmpPath, ZipArchive::CREATE);
        $archiveFilesSize = 0;
    }

    $filepath = trim($filepath);
    $archiveFilesSize += filesize($filepath);
    $zipArchive->addFile($filepath, trim($filepath, '/'));
    // TODO: save to DB

    if ($archiveFilesSize > $maxChunkSize) {
        echo "Switching archive " . basename($zipArchive->filename) . "\n";
        saveZip($zipArchive, $to);
        $zipArchive = null;
    }
}

if($zipArchive) {
    saveZip($zipArchive, $to);
}

fclose($fs);
unlink($tmpName);