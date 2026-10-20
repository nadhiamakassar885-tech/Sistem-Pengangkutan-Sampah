
<?php

require_once '../config/database.php';
require_once '../config/auth.php';

require_role('admin');

$user = $_SESSION['user'];

$sql = "
    SELECT
        r.id_riwayat,
        r.waktu_selesai,
        r.keterangan,
        r.dokumen,
        j.id_area,
        j.tanggal,
        j.jam,
        u.nama AS nama_petugas
    FROM riwayats r
    JOIN jadwals j ON j.id_jadwal = r.id_jadwal
    JOIN users u ON u.id_user = j.id_user_petugas
    ORDER BY r.waktu_selesai DESC, j.tanggal DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$riwayats = $stmt->fetchAll();

foreach ($riwayats as $index => $riwayat) {
    $areaStmt = $pdo->prepare("SELECT nama_area FROM areas WHERE id_area = ? LIMIT 1");
    $areaStmt->execute([(int) ($riwayat['id_area'] ?? 0)]);
    $areaData = $areaStmt->fetch();
    $riwayats[$index]['nama_area'] = $areaData['nama_area'] ?? '-';
}

function formatDokumenUrl(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if (strpos($path, '/sistem-pengangkutan-sampah/') === 0) {
        return $path;
    }

    if (strpos($path, '/') === 0) {
        return 'http://localhost' . $path;
    }

    return 'http://localhost/sistem-pengangkutan-sampah/' . ltrim($path, '/');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Admin - Sistem Pengangkutan Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Sistem Pengangkutan Sampah</a>
        <div class="text-white">
            <?= htmlspecialchars($user['nama']) ?>
            <a href="../logout.php" class="btn btn-sm btn-light ms-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Riwayat Dokumentasi</h2>
            <p class="text-muted mb-0">Pantauan semua bukti pengangkutan yang telah dikirim petugas.</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (count($riwayats) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Area</th>
                                <th>Petugas</th>
                                <th>Keterangan</th>
                                <th>Dokumentasi</th>
                                <th>Waktu Selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayats as $index => $riwayat): ?>
                                <?php
                                    $docUrl = formatDokumenUrl((string) ($riwayat['dokumen'] ?? ''));
                                    $ext = strtolower(pathinfo(parse_url($docUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($riwayat['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($riwayat['jam']) ?></td>
                                    <td><?= htmlspecialchars($riwayat['nama_area']) ?></td>
                                    <td><?= htmlspecialchars($riwayat['nama_petugas']) ?></td>
                                    <td><?= htmlspecialchars($riwayat['keterangan'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($docUrl !== ''): ?>
                                            <?php if ($isImage): ?>
                                                <a href="<?= htmlspecialchars($docUrl) ?>" target="_blank">
                                                    <img src="<?= htmlspecialchars($docUrl) ?>" alt="Dokumentasi" class="img-thumbnail" style="max-width: 120px; height: auto;">
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= htmlspecialchars($docUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat Dokumen</a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($riwayat['waktu_selesai']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">Belum ada riwayat dokumentasi dari petugas.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>



