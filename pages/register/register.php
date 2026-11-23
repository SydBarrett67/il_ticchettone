<html>
<?php
session_start();

$error = '';
$enteredEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $enteredEmail = $email;

    $jsonPath = __DIR__ . '/../../data/users.json';

    // 1. Input validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Inserisci un indirizzo email valido.';
    } elseif (strlen($password) < 4) {
        $error = 'La password deve avere almeno 4 caratteri.';
    } else {
        // 2. Read existing users
        $users = [];
        if (file_exists($jsonPath)) {
            $content = file_get_contents($jsonPath);
            if ($content !== false && trim($content) !== '') {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $users = $decoded;
                }
            }
        }

        // 3. Check duplicate email and compute next ID
        $emailExists = false;
        $maxId = 0;

        foreach ($users as $user) {
            if (isset($user['email']) && strtolower($user['email']) === $email) {
                $emailExists = true;
                break;
            }
            if (isset($user['id']) && (int)$user['id'] > $maxId) {
                $maxId = (int)$user['id'];
            }
        }

        if ($emailExists) {
            $error = 'Questa email è già registrata.';
        } else {
            // 4. Create new user with hashed password
            $newUser = [
                'id' => $nextId,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'auth_level' => '0',
            ];
            $users[] = $newUser;

            // 5. Save to JSON with LOCK_EX
            $jsonOutput = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $saved = file_put_contents($jsonPath, $jsonOutput . PHP_EOL, LOCK_EX);

            if ($saved === false) {
                $error = 'Errore durante il salvataggio dei dati.';
            } else {
                $_SESSION['user'] = [
                    'id' => $newUser['id'],
                    'email' => $newUser['email']
                ];
                header('Location: ../home/home.php');
                exit;
            }
        }
    }
}
?>

<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
<link rel="stylesheet" href="style.css">
</head>
<body>


    <main class="login-wrapper">
        <form id="form" method="POST" action="">
            <h2 class="mb-3 text-dark">Registrati</h2>

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
