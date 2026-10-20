<?php

require_once '../config/database.php';
require_once '../config/auth.php';

require_login();

$user = $_SESSION['user'];

/*
|--------------------------------------------------------------------------
| Mengambil jumlah data jadwal
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM jadwals");
$total_jadwal = $stmt->fetch()['total'];

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM jadwals
    WHERE status = 'Selesai'
");
$total_selesai = $stmt->fetch()['total'];

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM jadwals
    WHERE status = 'Belum'
");
$total_belum = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Jumlah area
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM areas");
$total_area = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Mengambil jadwal terbaru
|--------------------------------------------------------------------------
*/

if ($user['role'] === 'admin') {

    $stmt = $pdo->query("
        SELECT
            j.id_jadwal,
            j.tanggal,
            j.jam,
            j.status,
            a.nama_area,
            u.nama AS nama_petugas
        FROM jadwals j
        JOIN areas a
            ON j.id_area = a.id_area
        JOIN users u
            ON j.id_user_petugas = u.id_user
        ORDER BY j.tanggal DESC, j.jam DESC
        LIMIT 5
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT
            j.id_jadwal,
            j.tanggal,
            j.jam,
            j.status,
            a.nama_area,
            u.nama AS nama_petugas
        FROM jadwals j
        JOIN areas a
            ON j.id_area = a.id_area
        JOIN users u
            ON j.id_user_petugas = u.id_user
        WHERE j.id_user_petugas = ?
        ORDER BY j.tanggal DESC, j.jam DESC
        LIMIT 5
    ");

    $stmt->execute([$user['id_user']]);
}

$jadwals = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Sistem Pengangkutan Sampah</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="bg-light">

<!-- NAVBAR -->

<nav class="navbar navbar-dark bg-success">

    <div class="container">

        <a class="navbar-brand" href="dashboard.php">
            Sistem Pengangkutan Sampah
        </a>

        <div class="text-white">

            <?= htmlspecialchars($user['nama']) ?>

            <span class="badge bg-light text-success ms-2">
                <?= htmlspecialchars($user['role']) ?>
            </span>

            <a
                href="../logout.php"
                class="btn btn-sm btn-light ms-3">
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- CONTENT -->

<div class="container py-4">

    <div class="mb-4">

        <h2>
            Dashboard
        </h2>

        <p class="text-muted">

            Selamat datang,
            <strong>
                <?= htmlspecialchars($user['nama']) ?>
            </strong>.

        </p>

    </div>


    <!-- MENU -->

    <div class="mb-4">

        <?php if ($user['role'] === 'admin'): ?>

            <a
                href="area.php"
                class="btn btn-success me-2">
                Kelola Area
            </a>

            <a
                href="jadwal.php"
                class="btn btn-primary me-2">
                Kelola Jadwal
            </a>

            <a
                href="riwayat.php"
                class="btn btn-info me-2">
                Riwayat
            </a>

        <?php else: ?>

            <a
                href="jadwal.php"
                class="btn btn-primary me-2">
                Lihat Jadwal
            </a>

            <a
                href="riwayat.php"
                class="btn btn-info me-2">
                Riwayat
            </a>

        <?php endif; ?>


        <a
            href="notifikasi.php"
            class="btn btn-warning me-2">
            Notifikasi
        </a>

    </div>


    <!-- STATISTIK -->

    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Jadwal
                    </h6>

                    <h2>
                        <?= $total_jadwal ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Jadwal Selesai
                    </h6>

                    <h2 class="text-success">
                        <?= $total_selesai ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Belum Selesai
                    </h6>

                    <h2 class="text-danger">
                        <?= $total_belum ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Area
                    </h6>

                    <h2 class="text-primary">
                        <?= $total_area ?>
                    </h2>

                </div>

            </div>

        </div>

    </div>


    <!-- JADWAL TERBARU -->

    <div class="card shadow-sm">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Jadwal Terbaru
            </h5>

        </div>

        <div class="card-body">

            <?php if (count($jadwals) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead>

                            <tr>

                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Area</th>
                                <th>Petugas</th>
                                <th>Status</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($jadwals as $index => $jadwal): ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($jadwal['tanggal']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($jadwal['jam']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($jadwal['nama_area']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($jadwal['nama_petugas']) ?>
                                    </td>

                                    <td>

                                        <?php if ($jadwal['status'] === 'Selesai'): ?>

                                            <span class="badge bg-success">
                                                Selesai
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-danger">
                                                Belum
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="alert alert-info mb-0">

                    Belum ada jadwal pengangkutan sampah.

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


</body>

</html>



