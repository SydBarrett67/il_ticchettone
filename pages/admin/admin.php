<?php
session_start();

require_once __DIR__ . '/../../logic/admin_service.php';

$isLoggedIn = isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['email']);
$currentUser = $isLoggedIn ? $_SESSION['user'] : [];
$currentEmail = (string)($currentUser['email'] ?? '');
$currentAuthLevel = (string)($currentUser['auth_level'] ?? (isUserAdmin($currentUser) ? '1' : '0'));
$hasAdminAccess = $isLoggedIn && ($currentAuthLevel === '1');

$feedbackError = '';
$feedbackSuccess = '';
$createdEventJson = '';

if ($hasAdminAccess && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = (string)($_POST['form_action'] ?? '');

    if ($formAction === 'create_event') {
        $result = createEventJson($_POST, $currentEmail);
        if ($result['ok']) {
            $feedbackSuccess = "Evento creato in data/events/{$result['file']} (slug: {$result['slug']}).";
            $createdEventJson = json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $feedbackError = (string)($result['error'] ?? 'Errore nella creazione dell\'evento.');
        }
    } elseif ($formAction === 'set_auth_level') {
        $targetEmail = (string)($_POST['user_email'] ?? '');
        $newLevel = (string)($_POST['auth_level'] ?? '0');

        $result = updateUserAuthLevel($targetEmail, $newLevel);
        if ($result['ok']) {
            $feedbackSuccess = (string)$result['message'];
            // Se l'admin ha aggiornato il proprio livello in sessione, sincronizza
            if (strtolower(trim($targetEmail)) === strtolower(trim($currentEmail))) {
                $_SESSION['user']['auth_level'] = ($newLevel === '1') ? '1' : '0';
                $_SESSION['user']['role'] = ($newLevel === '1') ? 'admin' : 'user';
                $currentAuthLevel = $_SESSION['user']['auth_level'];
                $hasAdminAccess = ($currentAuthLevel === '1');
            }
        } else {
            $feedbackError = (string)($result['error'] ?? 'Errore nell\'aggiornamento del livello.');
        }
    }
}

$usersList = $hasAdminAccess ? getAllUsers() : [];
$eventsList = $hasAdminAccess ? getAllEvents() : [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Admin - Il Ticchettone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <link rel="stylesheet" href="../home/style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav id="navbar" class="navbar md:navbar-expand bg-1">
        <div class="container-fluid">
            <a class="navbar-brand" href="../home/home.php">Il Ticchettone</a>
            <button class="btn-icon navbar-toggler" type="button" data-bs-toggle="drawer" data-bs-target="#navbarDrawer" aria-controls="navbarDrawer" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" aria-hidden="true"></span>
            </button>
            <dialog class="drawer drawer-end" tabindex="-1" id="navbarDrawer" aria-labelledby="navbarDrawerLabel">
                <div class="drawer-header">
                    <h5 class="drawer-title" id="navbarDrawerLabel">Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
                </div>
                <div class="drawer-body mb-2 md:mb-0">
                    <ul class="nav navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="../home/home.php">Home</a>
                        </li>
                        <?php if ($isLoggedIn): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../account/dashboard.php">Dashboard</a>
                            </li>
                            <?php if ($currentAuthLevel === '1'): ?>
                                <li class="nav-item">
                                    <a class="nav-link active" aria-current="page" href="admin.php">Admin</a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../logout/logout.php">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../login/login.php">Accedi</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </dialog>
        </div>
    </nav>

    <main class="container my-4">
        <?php if (!$hasAdminAccess): ?>
            <div class="card admin-panel">
                <div class="card-body p-4">
                    <h1 class="h3 text-danger mb-2">Accesso negato — Livello di autorizzazione insufficiente</h1>
                    <?php if (!$isLoggedIn): ?>
                        <p class="mb-3">Devi effettuare il login con un account avente livello di autorizzazione <strong>"1" (Admin)</strong> per accedere a questa pagina.</p>
                        <a href="../login/login.php" class="btn btn-primary">Vai al login</a>
                    <?php else: ?>
                        <p class="mb-2">
                            Account attuale: <strong><?php echo htmlspecialchars($currentEmail); ?></strong>
                            (Livello attuale: <span class="badge bg-secondary">"<?php echo htmlspecialchars($currentAuthLevel); ?>"</span>).
                        </p>
                        <p class="text-muted mb-3">
                            Solo gli utenti con livello <code>"1"</code> possono visualizzare e utilizzare il pannello di amministrazione.
                        </p>
                        <a href="../home/home.php" class="btn btn-primary">Torna alla Home</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h1 class="mb-1">Pannello Amministrazione</h1>
                    <p class="mb-0 small text-light-emphasis">
                        Autenticato come <strong><?php echo htmlspecialchars($currentEmail); ?></strong> &middot; Livello: <span class="badge bg-success">"1" Admin</span>
                    </p>
                </div>
                <a href="../home/home.php#events-section" class="btn btn-outline-light btn-sm">Vedi eventi in Home</a>
            </div>

            <?php if ($feedbackError !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($feedbackError); ?></div>
            <?php endif; ?>

            <?php if ($feedbackSuccess !== ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($feedbackSuccess); ?></div>
            <?php endif; ?>

            <div class="row g-4 mb-5">
                <!-- Form Nuovo Evento (tutti i dati del JSON) -->
                <div class="col-12 col-lg-7">
                    <div class="card admin-panel">
                        <div class="card-body">
                            <h2 class="h4 mb-3">Nuovo Evento (JSON)</h2>
                            <form method="POST" action="admin.php">
                                <input type="hidden" name="form_action" value="create_event">

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="title" class="form-label">Titolo evento (`title`) *</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars((string)($_POST['title'] ?? '')); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="slug" class="form-label">Slug file JSON (`slug`)</label>
                                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars((string)($_POST['slug'] ?? '')); ?>" placeholder="es. concerto-rock-2026">
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="date" class="form-label">Data e ora (`date`) *</label>
                                        <input type="text" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars((string)($_POST['date'] ?? '')); ?>" placeholder="es. 2026-12-15 21:00" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="venue" class="form-label">Luogo / Sala (`venue`) *</label>
                                        <input type="text" class="form-control" id="venue" name="venue" value="<?php echo htmlspecialchars((string)($_POST['venue'] ?? '')); ?>" placeholder="es. Teatro Centrale" required>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="price" class="form-label">Prezzo biglietto EUR (`price`) *</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars((string)($_POST['price'] ?? '25.00')); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="image" class="form-label">File immagine in `imgs/` (`image`)</label>
                                        <input type="text" class="form-control" id="image" name="image" value="<?php echo htmlspecialchars((string)($_POST['image'] ?? 'fightclub.jpg')); ?>">
                                    </div>

                                    <div class="col-6 col-md-4">
                                        <label for="rows" class="form-label">Righe posti (A..)</label>
                                        <input type="number" min="1" max="15" class="form-control" id="rows" name="rows" value="<?php echo htmlspecialchars((string)($_POST['rows'] ?? '4')); ?>">
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label for="cols" class="form-label">Colonne posti (1..)</label>
                                        <input type="number" min="1" max="20" class="form-control" id="cols" name="cols" value="<?php echo htmlspecialchars((string)($_POST['cols'] ?? '6')); ?>">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="taken_seats" class="form-label">Posti già occupati</label>
                                        <input type="text" class="form-control" id="taken_seats" name="taken_seats" value="<?php echo htmlspecialchars((string)($_POST['taken_seats'] ?? '')); ?>" placeholder="es. A1, A2, B3">
                                    </div>

                                    <div class="col-12">
                                        <label for="description" class="form-label">Descrizione (`description`)</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Descrizione dell'evento..."><?php echo htmlspecialchars((string)($_POST['description'] ?? '')); ?></textarea>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">Salva Evento in JSON</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Anteprima JSON / Eventi esistenti -->
                <div class="col-12 col-lg-5">
                    <div class="card admin-panel mb-4">
                        <div class="card-body">
                            <h2 class="h5 mb-2">Eventi presenti (<?php echo count($eventsList); ?>)</h2>
                            <?php if (empty($eventsList)): ?>
                                <p class="text-muted small mb-0">Nessun file JSON presente in `data/events/`.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush small">
                                    <?php foreach ($eventsList as $ev): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                            <div>
                                                <strong><?php echo htmlspecialchars((string)($ev['title'] ?? 'Evento')); ?></strong>
                                                <div class="text-muted"><code><?php echo htmlspecialchars((string)($ev['slug'] ?? '')); ?>.json</code></div>
                                            </div>
                                            <span class="badge bg-primary">&euro; <?php echo number_format((float)($ev['price'] ?? 0), 2, ',', '.'); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($createdEventJson !== ''): ?>
                        <div class="card admin-panel">
                            <div class="card-body">
                                <h2 class="h6 mb-2">JSON salvato</h2>
                                <pre class="json-preview mb-0"><code><?php echo htmlspecialchars($createdEventJson); ?></code></pre>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gestione Livello Autorizzazione Utenti ("0" / "1") -->
            <section class="card admin-panel">
                <div class="card-body">
                    <h2 class="h4 mb-2">Autorizzazioni Utenti (`auth_level`: "0" = Utente, "1" = Admin)</h2>
                    <p class="text-muted small mb-3">
                        Modifica il livello di autorizzazione degli utenti salvati in <code>data/users.json</code>. Solo gli account con livello <code>"1"</code> possono accedere a questa pagina.
                    </p>

                    <?php if (empty($usersList)): ?>
                        <p class="text-muted mb-0">Nessun utente trovato in <code>data/users.json</code>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Nome</th>
                                        <th>Livello attuale</th>
                                        <th>Imposta livello</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usersList as $u): ?>
                                        <?php
                                        $uEmail = (string)($u['email'] ?? '');
                                        $uName = (string)($u['name'] ?? '-');
                                        $uLevel = (string)($u['auth_level'] ?? (isUserAdmin($u) ? '1' : '0'));
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($uEmail); ?></td>
                                            <td><?php echo htmlspecialchars($uName); ?></td>
                                            <td>
                                                <?php if ($uLevel === '1'): ?>
                                                    <span class="badge bg-success">"1" — Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">"0" — Utente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="admin.php" class="d-flex align-items-center gap-2">
                                                    <input type="hidden" name="form_action" value="set_auth_level">
                                                    <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($uEmail); ?>">
                                                    <select name="auth_level" class="form-select form-select-sm" style="width: 150px;">
                                                        <option value="0" <?php echo $uLevel === '0' ? 'selected' : ''; ?>>0 — Utente normale</option>
                                                        <option value="1" <?php echo $uLevel === '1' ? 'selected' : ''; ?>>1 — Admin</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Aggiorna</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
</body>
</html>
