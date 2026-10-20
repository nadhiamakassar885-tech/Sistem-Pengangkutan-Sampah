
<?php

require_once '../config/database.php';
require_once '../config/auth.php';

require_login();

$user = $_SESSION['user'];

$error = '';
$success = '';

// Proses tambah jadwal: khusus Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'admin') {

    $tanggal = $_POST['tanggal'] ?? '';
    $jam = $_POST['jam'] ?? '';
    $id_area = (int) ($_POST['id_area'] ?? 0);
    $id_user_petugas = (int) ($_POST['id_user_petugas'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($tanggal === '' || $jam === '' || $id_area === 0 || $id_user_petugas === 0) {
        $error = 'Tanggal, jam, area, dan petugas wajib diisi.';
    } else {

        // Pastikan area yang dipilih tersedia
        $cekArea = $pdo->prepare("
            SELECT nama_area
            FROM areas
            WHERE id_area = ?
        ");
        $cekArea->execute([$id_area]);
        $areaTerpilih = $cekArea->fetch();

        // Pastikan pengguna yang dipilih adalah petugas
        $cekPetugas = $pdo->prepare("
            SELECT id_user
            FROM users
            WHERE id_user = ? AND role = 'petugas'
        ");
        $cekPetugas->execute([$id_user_petugas]);
        $petugasTerpilih = $cekPetugas->fetch();

        if (!$areaTerpilih || !$petugasTerpilih) {
            $error = 'Area atau petugas yang dipilih tidak valid.';
        } else {

            try {
                // Simpan jadwal dan notifikasi dalam satu transaksi
                $pdo->beginTransaction();

                // 1. Simpan jadwal baru
                $stmt = $pdo->prepare("
                    INSERT INTO jadwals
                        (tanggal, jam, id_area, id_user_petugas, status, keterangan)
                    VALUES (?, ?, ?, ?, 'Belum', ?)
                ");

                $stmt->execute([
                    $tanggal,
                    $jam,
                    $id_area,
                    $id_user_petugas,
                    $keterangan !== '' ? $keterangan : null
                ]);

                // 2. Ambil ID jadwal yang baru dibuat
                $id_jadwal_baru = (int) $pdo->lastInsertId();

                // 3. Buat pesan notifikasi
                $pesan = "Jadwal pengangkutan sampah baru untuk area "
                       . $areaTerpilih['nama_area']
                       . " pada tanggal "
                       . $tanggal
                       . " pukul "
                       . $jam
                       . ".";

                // 4. Simpan notifikasi untuk petugas yang dipilih
              $stmtNotif = $pdo->prepare("
    INSERT INTO notifikasis
        (id_jadwal, id_user, pesan, waktu_kirim, status_baca)
    VALUES (?, ?, ?, NOW(), 0)
");

                $stmtNotif->execute([
                    $id_jadwal_baru,
                    $id_user_petugas,
                    $pesan
                ]);

                // 5. Simpan perubahan jika keduanya berhasil
                $pdo->commit();

                header('Location: jadwal.php?berhasil=1');
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($e->getMessage());

                $error = 'Jadwal atau notifikasi gagal disimpan. Periksa struktur tabel notifikasis dan coba lagi.';
            }
        }
    }
}

// Pesan jika jadwal berhasil ditambahkan
if (isset($_GET['berhasil'])) {
    $success = 'Jadwal dan notifikasi berhasil ditambahkan.';
}

// Ambil daftar area
$stmt = $pdo->query("
    SELECT id_area, nama_area
    FROM areas
    ORDER BY nama_area ASC
");
$areas = $stmt->fetchAll();

// Ambil daftar petugas
$stmt = $pdo->query("
    SELECT id_user, nama
    FROM users
    WHERE role = 'petugas'
    ORDER BY nama ASC
");
$petugas = $stmt->fetchAll();

// Ambil jadwal sesuai role pengguna
$sql = "
    SELECT
        j.id_jadwal,
        j.tanggal,
        j.jam,
        j.status,
        j.keterangan,
        a.nama_area,
        u.nama AS nama_petugas
    FROM jadwals j
    JOIN areas a ON j.id_area = a.id_area
    JOIN users u ON j.id_user_petugas = u.id_user
";

if ($user['role'] === 'petugas') {
    $sql .= " WHERE j.id_user_petugas = ?";
}

$sql .= " ORDER BY j.tanggal DESC, j.jam DESC";

$stmt = $pdo->prepare($sql);

if ($user['role'] === 'petugas') {
    $stmt->execute([$user['id_user']]);
} else {
    $stmt->execute();
}

$jadwals = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - Sistem Pengangkutan Sampah</title>

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

            <a href="../logout.php" class="btn btn-sm btn-light ms-3">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>
                <?= $user['role'] === 'admin' ? 'Kelola Jadwal' : 'Jadwal Saya' ?>
            </h2>
            <p class="text-muted mb-0">
                Sistem Informasi Penjadwalan dan Pengingat Pengangkutan Sampah.
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-secondary">
            Kembali ke Dashboard
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($user['role'] === 'admin'): ?>

        <!-- Form tambah jadwal khusus Admin -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tambah Jadwal</h5>
            </div>

            <div class="card-body">
                <form method="POST">
                    <div class="row">

                        <div class="col-md-3 mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input
                                type="date"
                                name="tanggal"
                                id="tanggal"
                                class="form-control"
                                required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="jam" class="form-label">Jam</label>
                            <input
                                type="time"
                                name="jam"
                                id="jam"
                                class="form-control"
                                required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="id_area" class="form-label">Area</label>
                            <select
                                name="id_area"
                                id="id_area"
                                class="form-select"
                                required>
                                <option value="">-- Pilih Area --</option>

                                <?php foreach ($areas as $area): ?>
                                    <option value="<?= (int) $area['id_area'] ?>">
                                        <?= htmlspecialchars($area['nama_area']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="id_user_petugas" class="form-label">
                                Petugas
                            </label>

                            <select
                                name="id_user_petugas"
                                id="id_user_petugas"
                                class="form-select"
                                required>
                                <option value="">-- Pilih Petugas --</option>

                                <?php foreach ($petugas as $p): ?>
                                    <option value="<?= (int) $p['id_user'] ?>">
                                        <?= htmlspecialchars($p['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label for="keterangan" class="form-label">
                            Keterangan
                        </label>

                        <input
                            type="text"
                            name="keterangan"
                            id="keterangan"
                            class="form-control"
                            placeholder="Contoh: Pengangkutan rutin">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        + Tambah Jadwal
                    </button>
                </form>
            </div>
        </div>

    <?php endif; ?>

    <!-- Tabel jadwal -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Daftar Jadwal</h5>
        </div>

        <div class="card-body">
            <?php if (count($jadwals) > 0): ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Area</th>
                                <th>Petugas</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($jadwals as $index => $jadwal): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($jadwal['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($jadwal['jam']) ?></td>
                                    <td><?= htmlspecialchars($jadwal['nama_area']) ?></td>
                                    <td><?= htmlspecialchars($jadwal['nama_petugas']) ?></td>

                                    <td>
                                        <?php if ($jadwal['status'] === 'Selesai'): ?>
                                            <span class="badge bg-success">Selesai</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Belum</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($jadwal['keterangan'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?php if (
                                            $user['role'] === 'petugas'
                                            && $jadwal['status'] === 'Belum'
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="selesai.php?id=<?= (int) $jadwal['id_jadwal'] ?>"
                                                enctype="multipart/form-data"
                                                onsubmit="return confirm('Apakah jadwal ini sudah selesai dikerjakan?');">

                                                <div class="mb-2">
                                                    <label for="dokumen_<?= (int) $jadwal['id_jadwal'] ?>" class="form-label small text-muted">
                                                        Dokumentasi (opsional)
                                                    </label>
                                                    <input
                                                        type="file"
                                                        name="dokumen"
                                                        id="dokumen_<?= (int) $jadwal['id_jadwal'] ?>"
                                                        class="form-control form-control-sm"
                                                        accept="image/*,.pdf">
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-success">
                                                    Selesaikan
                                                </button>
                                            </form>

                                        <?php elseif ($jadwal['status'] === 'Selesai'): ?>

                                            <span class="text-success">
                                                Sudah selesai
                                            </span>

                                        <?php else: ?>

                                            <span class="text-muted">-</span>

                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="alert alert-info mb-0">
                    Belum ada jadwal pengangkutan sampah untuk ditampilkan.
                </div>

            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>



