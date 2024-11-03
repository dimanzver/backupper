<?php

use Aws\S3\S3Client;

function saveZip(ZipArchive $zipArchive, string $to) {
    $toType = 'file';
    if(strpos($to, 's3:') === 0) {
        $toType = 's3';
        $to = trim(preg_replace('/^s3:/', '', $to), '/');
    }

    if(strpos($to, 'yandex-disk:') === 0) {
        $toType = 'yandex-disk';
        $to = trim(preg_replace('/^yandex-disk:/', '', $to), '/');
    }

    if(strpos($to, 'webdav:') === 0) {
        $toType = 'webdav';
        $to = trim(preg_replace('/^webdav:/', '', $to), '/');
    }

    $zipFilename = $zipArchive->filename;
    echo "Writing archive " . basename($zipFilename) . "\n";
    $zipArchive->close();

    if ($toType === 's3') {
        uploadToS3($zipFilename, $to . '/' . basename($zipFilename));
    } elseif ($toType === 'yandex-disk') {
        (new YandexDisk())->uploadFile($zipFilename, $to . '/' . basename($zipFilename));
    } elseif ($toType === 'webdav') {
        $webDav = new WebDav();
        $webDav->connect(WEBDAV_URL, WEBDAV_LOGIN, WEBDAV_PASSWORD);
        $webDav->uploadFile($zipFilename, $to . '/' . basename($zipFilename));
    }  else {
        copy($zipFilename, $to . '/' . basename($zipFilename));
    }

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

function getLastBackupTime($pathFrom) {
    $rowStmt = DB::getConnection()->prepare("
        SELECT MAX(date) AS max FROM `backups`
        WHERE finish_date IS NOT NULL AND path_from=:pathFrom
    ");
    $rowStmt->execute(compact('pathFrom'));
    $row = $rowStmt->fetch(PDO::FETCH_OBJ);
    return $row ? $row->max : null;
}

function uploadToS3(string $filepath, $s3path) {
    $client = new S3Client([
        'credentials' => [
            'key'      => S3_ID,
            'secret'   => S3_SECRET,
        ],
        'region'   => S3_REGION,
        'endpoint' => S3_ENDPOINT,
        'version'  => 'latest',
    ]);
    $client->putObject([
        'Bucket' => S3_BUCKET,
        'Key' => $s3path,
        'SourceFile' => $filepath,
        'ContentType' => mime_content_type($filepath),
    ]);
}
