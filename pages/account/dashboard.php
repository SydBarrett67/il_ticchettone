<?php
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user']['email'])) {
    header('Location: ../login/login.php');
    exit;
}

require_once __DIR__ . '/../../logic/tickets_service.php';

$user = $_SESSION['user'];
$userEmail = (string)$user['email'];
$userRole = (string)($user['role'] ?? 'user');

$userTickets = getUserTickets($userEmail);
$recentPurchases = array_slice($userTickets, 0, 5);
$stats = getAccountStats($userTickets);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Account - Il Ticchettone</title>
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
                        <li class="nav-item">
                            <a class="nav-link" href="../home/home.php#events-section">Eventi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="dashboard.php">Dashboard</a>
                        </li>
                        <?php if ($userRole === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/admin.php">Admin</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout/logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </dialog>
        </div>
    </nav>

    <main class="container my-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h1 class="mb-1">Il tuo Account</h1>
                <p class="mb-0 text-light-emphasis"><?php echo htmlspecialchars($userEmail); ?></p>
            </div>
            <a href="../home/home.php#events-section" class="btn btn-primary">Esplora eventi</a>
        </div>

        <!-- Statistiche -->
        <section class="row g-3 mb-4" aria-label="Statistiche account">
            <div class="col-12 col-md-4">
                <div class="card dash-card h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-2">Biglietti acquistati</h6>
                        <p class="display-6 fw-bold mb-0"><?php echo (int)$stats['total_tickets']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card dash-card h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-2">Spesa totale</h6>
                        <p class="display-6 fw-bold mb-0">&euro; <?php echo number_format((float)$stats['total_spent'], 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card dash-card h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-2">Eventi distinti</h6>
                        <p class="display-6 fw-bold mb-0"><?php echo (int)$stats['distinct_events']; ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ultimi acquisti -->
        <section class="mb-5">
            <h2 class="h4 mb-3">Ultimi acquisti</h2>
            <?php if (empty($recentPurchases)): ?>
                <div class="card dash-card">
                    <div class="card-body">
                        <p class="mb-0">Non hai ancora effettuato acquisti.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card dash-card">
                    <div class="card-body table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Evento</th>
                                    <th>Posto</th>
                                    <th>Importo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPurchases as $purchase): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string)($purchase['purchased_at'] ?? '-')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($purchase['event_title'] ?? $purchase['event_slug'] ?? 'Evento')); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string)($purchase['seat_id'] ?? '-')); ?></span></td>
                                        <td>&euro; <?php echo number_format((float)($purchase['price'] ?? 0), 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Biglietti salvati -->
        <section>
            <h2 class="h4 mb-3">Biglietti salvati</h2>
            <?php if (empty($userTickets)): ?>
                <div class="card dash-card">
                    <div class="card-body">
                        <p class="mb-0">Nessun biglietto salvato al momento.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($userTickets as $ticket): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card dash-card h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars((string)($ticket['event_title'] ?? 'Evento')); ?></h5>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars((string)($ticket['seat_id'] ?? 'Posto')); ?></span>
                                        </div>
                                        <p class="card-text small text-muted mb-2">
                                            Codice: <strong><?php echo htmlspecialchars((string)($ticket['ticket_code'] ?? 'N/D')); ?></strong>
                                        </p>
                                        <p class="card-text small mb-0">
                                            Acquistato il: <?php echo htmlspecialchars((string)($ticket['purchased_at'] ?? '-')); ?>
                                        </p>
                                    </div>
                                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">&euro; <?php echo number_format((float)($ticket['price'] ?? 0), 2, ',', '.'); ?></span>
                                        <?php if (!empty($ticket['event_slug'])): ?>
                                            <a href="../event/event.php?slug=<?php echo urlencode((string)$ticket['event_slug']); ?>" class="btn btn-sm btn-outline-primary">Vedi evento</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
</body>
</html>
