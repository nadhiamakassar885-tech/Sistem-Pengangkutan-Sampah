<?php

require_once 'config/database.php';
require_once 'config/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([$username]);

        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {

            $_SESSION['user'] = [
                'id_user' => $user['id_user'],
                'nama' => $user['nama'],
                'username' => $user['username'],
                'role' => $user['role'],
                'kontak' => $user['kontak']
            ];

            $redirect = ($user['role'] === 'admin')
                ? 'admin/dashboard.php'
                : 'petugas/dashboard.php';

            header('Location: ' . $redirect);
            exit;

        } else {
            $error = 'Username atau password salah.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - Sistem Pengangkutan Sampah</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center align-items-center login-shell">

        <div class="col-md-5">

            <div class="card shadow">

                <div class="card-body p-4">

                    <div class="text-center mb-4">

                        <h3>
                            Sistem Pengangkutan Sampah
                        </h3>

                        <p class="text-muted">
                            Silakan login untuk melanjutkan
                        </p>

                    </div>

                    <?php if ($error): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">

                            <label
                                for="username"
                                class="form-label">
                                Username
                            </label>

                            <input
                                type="text"
                                name="username"
                                id="username"
                                class="form-control"
                                placeholder="Masukkan username"
                                required>

                        </div>

                        <div class="mb-3">

                            <label
                                for="password"
                                class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                placeholder="Masukkan password"
                                required>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-success w-100">

                            Login

                        </button>

                    </form>

                    <div class="text-center mt-4">

                        <small class="text-muted">
                            Sistem Informasi Penjadwalan dan
                            Pengingat Pengangkutan Sampah
                        </small>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>



