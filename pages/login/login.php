<html>
<?php
    session_start();

    $error = '';

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Users file path
    $json_path = __DIR__ . '/../../data/users.json';
    $users = json_decode(file_get_contents($json_path), true) ?? [];

    // Verify password
    $found = null;
    foreach ($users as $user) {
        if ($user['email'] === $email && password_verify($password, $user['password_hash'])) {
            $found = $user;
            break;
        }
    }

    // If the password is correct, saves in session
    if ($found !== null) {
        $_SESSION['user'] = [
            'id' => $found['id'],
            'email' => $found['email']
        ];
        header('Location: ../home/home.html');
        exit;
    } else {
        $error = 'Email o password non corretti.';
    }

?>

<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
<link rel="stylesheet" href="style.css">
</head>
<body>


    <main class="login-wrapper">
        <form id="form" method="POST" action="">
            <h2 class="mb-3 text-dark">Accedi</h2>

            <?php if ($error !== ''): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <div class="mb-3">
                <label for="email" class="form-label text-dark">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label text-dark">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" id="loginBtn" class="w-100">Entra</button>
        </form>
    </main>

    <footer class="bg-dark text-light py-3 mt-auto">
        <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span class="small mb-0">&copy; 2026 Il Ticchettone</span>
            <ul class="nav small">
                <li class="nav-item"><a href="#" class="nav-link px-2 text-light-50 text-white-50">Privacy</a></li>
                <li class="nav-item"><a href="#" class="nav-link px-2 text-light-50 text-white-50">Termini</a></li>
                <li class="nav-item"><a href="#" class="nav-link px-2 text-light-50 text-white-50">Contatti</a></li>
            </ul>
        </div>
    </footer>
<script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
</body>
</html>
