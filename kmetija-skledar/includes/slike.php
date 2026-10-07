<?php
// Slike izdelkov: prikaz, nalaganje in brisanje datotek v uploads/izdelki/
require_once __DIR__ . '/ikone.php';

const UPLOAD_DIR      = 'uploads/izdelki/';      // relativno na koren projekta, tako je zapisano v bazi
const UPLOAD_MAX_SIZE = 2 * 1024 * 1024;         // 2 MB
const UPLOAD_TYPES    = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// Fotografija izdelka, če obstaja, sicer SVG ilustracija kategorije
function product_image(array $p): string
{
    if (!empty($p['image']) && is_file(__DIR__ . '/../' . $p['image'])) {
        return '<img src="' . e(url($p['image'])) . '" alt="' . e($p['name'] . ' ' . ($p['packaging'] ?? '')) . '" loading="lazy">';
    }
    return product_art((int) $p['id_category']);
}

// Preveri naloženo datoteko. Vrne null, če ni izbrana; ob napaki zapiše sporočilo v $error.
// Vrsto ugotovimo iz vsebine datoteke (finfo), ne iz končnice ali tistega, kar pošlje brskalnik.
function check_upload(?array $file, ?string &$error): ?array
{
    $error = null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > UPLOAD_MAX_SIZE) {
        $error = 'Slika je prevelika (največ 2 MB).';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Nalaganje slike ni uspelo. Poskusite znova.';
        return null;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(UPLOAD_TYPES[$mime]) || getimagesize($file['tmp_name']) === false) {
        $error = 'Dovoljene so samo slike JPG, PNG ali WEBP.';
        return null;
    }
    return ['tmp' => $file['tmp_name'], 'ext' => UPLOAD_TYPES[$mime]];
}

// Premakne preverjeno sliko v mapo z naključnim imenom (ime uporabnika zavržemo) in vrne pot za bazo
function save_upload(array $upload): string
{
    $dir = __DIR__ . '/../' . UPLOAD_DIR;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = UPLOAD_DIR . bin2hex(random_bytes(8)) . '.' . $upload['ext'];
    if (!move_uploaded_file($upload['tmp'], __DIR__ . '/../' . $path)) {
        throw new RuntimeException('Slike ni bilo mogoče shraniti.');
    }
    return $path;
}

// Izbriše datoteko, a samo če res leži v mapi za slike (nikoli kje drugje na disku)
function delete_upload(?string $path): void
{
    if ($path && str_starts_with($path, UPLOAD_DIR) && basename($path) === substr($path, strlen(UPLOAD_DIR))) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) {
            unlink($full);
        }
    }
}
