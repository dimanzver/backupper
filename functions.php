<?php

function saveZip(ZipArchive $zipArchive, string $to) {
    $zipFilename = $zipArchive->filename;
    echo "Writing archive " . basename($zipFilename) . "\n";
    $zipArchive->close();
    copy($zipFilename, $to . '/' . basename($zipFilename));
    unlink($zipFilename);
}

function getFileId(string $path) {
    $existingRowStmt = DB::getConnection()->prepare('SELECT * FROM `files` WHERE path=:path');
    $existingRowStmt->execute(compact('path'));
    $existingRows = $existingRowStmt->fetchAll(PDO::FETCH_OBJ);
    if(!empty($existingRows))
        return intval($existingRows[0]->id);

    DB::getConnection()->prepare('INSERT INTO files (path) VALUES (:path)')->execute(compact('path'));
    return intval(DB::getConnection()->lastInsertId());
}

function getLastBackupTime() {
    $row = DB::getConnection()->query('SELECT MAX(finish_date) AS max FROM `backups`')->fetch(PDO::FETCH_OBJ);
    return $row ? $row->max : null;
}
