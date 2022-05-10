<?php

function saveZip(ZipArchive $zipArchive, string $to) {
    $zipFilename = $zipArchive->filename;
    echo "Writing archive " . basename($zipFilename) . "\n";
    $zipArchive->close();
    copy($zipFilename, $to . '/' . basename($zipFilename));
    unlink($zipFilename);
}
