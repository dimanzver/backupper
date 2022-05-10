<?php

error_reporting(E_ALL);
require_once __DIR__ . '/bootstrap.php';

$type = $argv[1];
$from = $argv[2];
$to = $argv[3];
$maxChunkSize = $argv[4];
$lastBackupTime = getLastBackupTime();

DB::getConnection()->prepare('INSERT INTO backups (type) VALUES (:type)')->execute(compact('type'));
$backupId = intval(DB::getConnection()->lastInsertId());

$tmpName = __DIR__ . '/backup-files-' . $type . time();
if ($type === 'inc' && $lastBackupTime) {
    exec("find \"$from\" -newermt \"$lastBackupTime\" -type f -o -newerct \"$lastBackupTime\" -type f > $tmpName");
} else {
    exec("find \"$from\" -type f > $tmpName");
}
$fs = fopen($tmpName, 'r');

$zipArchive = null;
while (($filepath = fgets($fs)) !== false) {
    if(!$zipArchive) {
        $archiveName = $type . '-' . date('Y-m-d H:i:s') . uniqid() . '.zip';
        $archiveTmpPath = __DIR__ . '/' . $archiveName;
        $zipArchive = new ZipArchive();
        $zipArchive->open($archiveTmpPath, ZipArchive::CREATE);
        $archiveFilesSize = 0;
        DB::getConnection()->prepare('
            INSERT INTO archives (backup_id, archive_rel_path) VALUES (:backup_id, :archive_rel_path)'
        )->execute([
            'backup_id' => $backupId,
            'archive_rel_path' => $archiveName,
        ]);
        $archiveId = intval(DB::getConnection()->lastInsertId());
    }

    $filepath = trim($filepath);
    $archiveFilesSize += filesize($filepath);
    $zipArchive->addFile($filepath, trim($filepath, '/'));
    $fileId = getFileId($filepath);
    DB::getConnection()->prepare('
        INSERT INTO backup_files (file_id, archive_id, hash) VALUES (:file_id, :archive_id, :hash)
    ')->execute([
        'file_id' => $fileId,
        'archive_id' => $archiveId,
        'hash' => md5_file($filepath),
    ]);

    if ($archiveFilesSize > $maxChunkSize) {
        echo "Switching archive " . basename($zipArchive->filename) . "\n";
        saveZip($zipArchive, $to);
        $zipArchive = null;
    }
    usleep(10000);
}

if($zipArchive) {
    saveZip($zipArchive, $to);
}

fclose($fs);
unlink($tmpName);

DB::getConnection()->exec("UPDATE backups SET finish_date=NOW() WHERE id=$backupId");