
<?php
require_once '../config/database.php';
require_once '../config/auth.php';

require_login();

$user = $_SESSION['user'];
$error = '';

// Tandai notifikasi sebagai sudah dibaca
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_notifikasi = (int) ($_POST['id_notifikasi'] ?? 0);

    if ($id_notifikasi > 0) {
        $stmt = $pdo->prepare("
            UPDATE notifikasis
            SET status_baca = 'Dibaca'
            WHERE id_notifikasi = ?
              AND id_user = ?
        ");

        $stmt->execute([$id_notifikasi, $user['id_user']]);
    }

    header('Location: notifikasi.php');
    exit;
}

// Ambil notifikasi milik pengguna yang sedang login
$stmt = $pdo->prepare("
    SELECT
        n.id_notifikasi,
        n.id_jadwal,
        n.pesan,
        n.waktu_kirim,
        n.status_baca,
        j.tanggal,
        j.jam,
        a.nama_area
    FROM notifikasis n
    LEFT JOIN jadwals j ON n.id_jadwal = j.id_jadwal
    LEFT JOIN areas a ON j.id_area = a.id_area
    WHERE n.id_user = ?
    ORDER BY n.waktu_kirim DESC
");

$stmt->execute([$user['id_user']]);
$notifikasis = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifikasi - Sistem Pengangkutan Sampah</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            Sistem Pengangkutan Sampah
        </a>

        <div class="text-white">
            <?= htmlspecialchars($user['nama']) ?>
            <a href="../logout.php" class="btn btn-light btn-sm ms-3">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Notifikasi Saya</h2>
            <p class="text-muted mb-0">
                Informasi jadwal pengangkutan sampah.
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-secondary">
            Kembali ke Dashboard
        </a>
    </div>

    <?php if (count($notifikasis) > 0): ?>

        <?php foreach ($notifikasis as $n): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <h5 class="card-title">Pemberitahuan Jadwal</h5>

                        <?php if ($n['status_baca'] === 'Dibaca'): ?>
                            <span class="badge bg-success">Sudah dibaca</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">
                                Belum dibaca
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="mb-2">
                        <?= htmlspecialchars($n['pesan']) ?>
                    </p>

                    <?php if ($n['tanggal'] !== null): ?>
                        <p class="mb-1">
                            <strong>Tanggal:</strong>
                            <?= htmlspecialchars($n['tanggal']) ?>
                        </p>
                        <p class="mb-1">
                            <strong>Jam:</strong>
                            <?= htmlspecialchars($n['jam']) ?>
                        </p>
                        <p class="mb-1">
                            <strong>Area:</strong>
                            <?= htmlspecialchars($n['nama_area'] ?? '-') ?>
                        </p>
                    <?php endif; ?>

                    <small class="text-muted">
                        Dikirim:
                        <?= htmlspecialchars($n['waktu_kirim']) ?>
                    </small>

                    <?php if ($n['status_baca'] !== 'Dibaca'): ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="id_notifikasi"
                                   value="<?= (int) $n['id_notifikasi'] ?>">
                            <button type="submit" class="btn btn-sm btn-success">
                                Tandai Sudah Dibaca
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <div class="alert alert-info">
            Belum ada notifikasi untuk akun ini.
        </div>
    <?php endif; ?>
</div>

</body>
</html>



