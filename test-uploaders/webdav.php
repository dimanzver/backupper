<?php

error_reporting(E_ALL);
require_once __DIR__ . '/../bootstrap.php';

$archiveName = 'test-' . date('Y-m-d-H-i-s') . uniqid() . '.zip';
$archiveTmpPath = __DIR__ . '/' . $archiveName;
$zipArchive = new ZipArchive();
$zipArchive->open($archiveTmpPath, ZipArchive::CREATE);
$zipArchive->addFromString('test.txt', 'test');

saveZip($zipArchive, 'webdav:backups');
