```php
<?php

require_once '../config/database.php';
require_once '../config/auth.php';

require_role('petugas');

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode permintaan tidak diizinkan.');
}

$id_jadwal = (int) ($_GET['id'] ?? 0);

if ($id_jadwal <= 0) {
    exit('Jadwal tidak valid.');
}

$dokumenPath = null;
$uploadDir = dirname(__DIR__) . '/uploads/riwayat';

if (isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] !== UPLOAD_ERR_NO_FILE) {
    $uploadedFile = $_FILES['dokumen'];

    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        exit('Dokumentasi gagal diunggah. Silakan coba lagi.');
    }

    $allowedMime = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf'
    ];

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $fileName = basename($uploadedFile['name']);
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($uploadedFile['tmp_name']);

    if (!in_array($mimeType, $allowedMime, true) || !in_array($extension, $allowedExt, true)) {
        http_response_code(400);
        exit('Format dokumentasi tidak didukung. Gunakan JPG, PNG, WEBP, atau PDF.');
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $safeName = time() . '_' . $user['id_user'] . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($fileName, PATHINFO_FILENAME)) . '.' . $extension;
    $destination = $uploadDir . '/' . $safeName;

    if (!move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
        http_response_code(500);
        exit('Dokumentasi gagal disimpan di server.');
    }

    $dokumenPath = '/sistem-pengangkutan-sampah/uploads/riwayat/' . $safeName;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM jadwals
        WHERE id_jadwal = ?
          AND id_user_petugas = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        $id_jadwal,
        $user['id_user']
    ]);

    $jadwal = $stmt->fetch();

    if (!$jadwal) {
        $pdo->rollBack();
        exit('Jadwal tidak ditemukan atau bukan tugas Anda.');
    }

    if ($jadwal['status'] === 'Selesai') {
        $pdo->rollBack();
        header('Location: jadwal.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE jadwals
        SET status = 'Selesai'
        WHERE id_jadwal = ?
          AND id_user_petugas = ?
    ");

    $stmt->execute([
        $id_jadwal,
        $user['id_user']
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO riwayats
            (id_jadwal, waktu_selesai, status, keterangan, dokumen)
        VALUES
            (?, NOW(), 'Selesai', ?, ?)
    ");

    $stmt->execute([
        $id_jadwal,
        $jadwal['keterangan'],
        $dokumenPath
    ]);

    $pdo->commit();
    header('Location: jadwal.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    if ($dokumenPath !== null) {
        $fileToDelete = $uploadDir . '/' . basename($dokumenPath);
        if (file_exists($fileToDelete)) {
            unlink($fileToDelete);
        }
    }

    http_response_code(500);
    exit('Gagal menyelesaikan jadwal. Silakan periksa data riwayat di database.');
}



