<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Upload image produit (MVP shop).
 * Note core 2.0 : prévoir une règle Validator upload (mime, taille, extension).
 */
final class ProductImageUploader
{
    private const MAX_BYTES = 2_097_152; // 2 Mo

    /** @var list<string> */
    private const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /** @var array<string, string> */
    private const MIME_EXT = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * @param array<string, mixed>|null $file entrée $_FILES['…']
     * @return array{0: ?string, 1: ?string} [chemin relatif public, erreur]
     */
    public function store(?array $file, string $publicDir): array
    {
        if ($file === null || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return [null, 'Échec de l’upload (code ' . $error . ').'];
        }

        $tmp  = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $name = (string) ($file['name'] ?? '');

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return [null, 'Fichier upload invalide.'];
        }

        if ($size < 1 || $size > self::MAX_BYTES) {
            return [null, 'Image trop lourde (max. 2 Mo).'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmp);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return [null, 'Type non autorisé (JPEG, PNG, WebP ou GIF).'];
        }

        $ext = self::MIME_EXT[$mime] ?? null;
        if ($ext === null) {
            return [null, 'Extension impossible à déterminer.'];
        }

        // Ignore le nom client pour éviter path traversal
        unset($name);

        $dir = rtrim($publicDir, '/\\') . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'products';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return [null, 'Impossible de créer le dossier media/products.'];
        }

        $filename = 'p_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest     = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmp, $dest)) {
            return [null, 'Impossible d’enregistrer l’image.'];
        }

        return ['media/products/' . $filename, null];
    }
}
