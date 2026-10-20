<?php

require_once '../config/database.php';
require_once '../config/auth.php';

require_role('admin');

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Tambah Area
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_area = trim($_POST['nama_area'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');

    if ($nama_area === '') {
        $error = 'Nama area wajib diisi.';
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO areas (nama_area, deskripsi, lokasi)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $nama_area,
            $deskripsi !== '' ? $deskripsi : null,
            $lokasi !== '' ? $lokasi : null
        ]);

        $success = 'Area berhasil ditambahkan.';
    }
}

/*
|--------------------------------------------------------------------------
| Hapus Area
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    $id_area = (int) $_GET['hapus'];

    try {

        $stmt = $pdo->prepare("
            DELETE FROM areas
            WHERE id_area = ?
        ");

        $stmt->execute([$id_area]);

        $success = 'Area berhasil dihapus.';

    } catch (PDOException $e) {

        $error = 'Area tidak dapat dihapus karena sudah digunakan pada jadwal.';
    }
}

/*
|--------------------------------------------------------------------------
| Ambil Semua Area
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT *
    FROM areas
    ORDER BY id_area DESC
");

$areas = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Area - Sistem Pengangkutan Sampah</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-success">

    <div class="container">

        <a
            class="navbar-brand"
            href="dashboard.php">
            Sistem Pengangkutan Sampah
        </a>

        <div class="text-white">

            <?= htmlspecialchars($_SESSION['user']['nama']) ?>

            <a
                href="../logout.php"
                class="btn btn-sm btn-light ms-3">
                Logout
            </a>

        </div>

    </div>

</nav>


<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2>Kelola Area</h2>

            <p class="text-muted mb-0">
                Tambahkan dan kelola area pengangkutan sampah.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="btn btn-secondary">
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


    <!-- Form Tambah Area -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-success text-white">

            <h5 class="mb-0">
                Tambah Area
            </h5>

        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label
                            for="nama_area"
                            class="form-label">
                            Nama Area
                        </label>

                        <input
                            type="text"
                            name="nama_area"
                            id="nama_area"
                            class="form-control"
                            placeholder="Contoh: RT 01"
                            required>

                    </div>


                    <div class="col-md-4 mb-3">

                        <label
                            for="lokasi"
                            class="form-label">
                            Lokasi
                        </label>

                        <input
                            type="text"
                            name="lokasi"
                            id="lokasi"
                            class="form-control"
                            placeholder="Contoh: Jalan Mawar">

                    </div>


                    <div class="col-md-4 mb-3">

                        <label
                            for="deskripsi"
                            class="form-label">
                            Deskripsi
                        </label>

                        <input
                            type="text"
                            name="deskripsi"
                            id="deskripsi"
                            class="form-control"
                            placeholder="Keterangan area">

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn btn-success">
                    + Tambah Area
                </button>

            </form>

        </div>

    </div>


    <!-- Daftar Area -->

    <div class="card shadow-sm">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Daftar Area
            </h5>

        </div>

        <div class="card-body">

            <?php if (count($areas) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead>

                            <tr>

                                <th>No</th>
                                <th>Nama Area</th>
                                <th>Lokasi</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($areas as $index => $area): ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($area['nama_area']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($area['lokasi'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($area['deskripsi'] ?? '-') ?>
                                    </td>

                                    <td>

                                        <a
                                            href="area.php?hapus=<?= $area['id_area'] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Yakin ingin menghapus area ini?');">
                                            Hapus
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="alert alert-info mb-0">

                    Belum ada data area.

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>



