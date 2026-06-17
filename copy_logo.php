<?php
// This script copies the uploaded logo to assets/
$src = '/mnt/user-data/uploads/logo.png';
$dst = __DIR__ . '/assets/logo.png';
if (file_exists($src) && !file_exists($dst)) {
    copy($src, $dst);
    echo 'Logo copiado OK';
} else {
    echo file_exists($dst) ? 'Logo ya existe' : 'Fuente no encontrada';
}
