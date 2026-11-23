<?php
session_start();

require_once __DIR__ . '/../../logic/events_service.php';

$slug = trim($_GET['slug'] ?? '');
$event = $slug !== '' ? getEventBySlug($slug) : null;

$isLoggedIn = isset($_SESSION['user']);
$userEmail = $isLoggedIn ? (string)($_SESSION['user']['email'] ?? '') : '';
$userRole = $isLoggedIn ? (string)($_SESSION['user']['role'] ?? 'user') : 'user';

$feedbackError = '';
$feedbackSuccess = '';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event !== null) {
    if (!$isLoggedIn) {
        header('Location: ../login/login.php');
        exit;
    }

    $selectedSeats = $_POST['seats'] ?? [];
    if (!is_array($selectedSeats) || empty($selectedSeats)) {
        $feedbackError = 'Seleziona almeno un posto prima di procedere.';
    } else {
        $seatsMap = [];
        foreach (getEventSeats($event) as $seat) {
            if (is_array($seat) && !empty($seat['id'])) {
                $seatsMap[(string)$seat['id']] = (string)($seat['status'] ?? 'available');
            }
        }

        $validSeats = [];
        foreach ($selectedSeats as $seatId) {
            $seatId = trim((string)$seatId);
            if (isset($seatsMap[$seatId]) && $seatsMap[$seatId] === 'available') {
                $validSeats[] = $seatId;
            }
        }

        $validSeats = array_values(array_unique($validSeats));

        if (empty($validSeats)) {
            $feedbackError = 'Uno o più posti selezionati non sono disponibili.';
        } else {
            $eventSlug = (string)($event['slug'] ?? $slug);
            $existing = $_SESSION['cart'][$eventSlug]['seats'] ?? [];
            $mergedSeats = array_values(array_unique(array_merge($existing, $validSeats)));

            $_SESSION['cart'][$eventSlug] = [
                'event_slug' => $eventSlug,
                'event_title' => (string)($event['title'] ?? 'Evento'),
                'event_date' => (string)($event['date'] ?? ''),
                'event_venue' => (string)($event['venue'] ?? ''),
                'price' => (float)($event['price'] ?? 0),
                'seats' => $mergedSeats,
            ];

            $feedbackSuccess = 'Posti aggiunti al carrello: ' . implode(', ', $validSeats) . '.';
        }
    }
}

$seatsList = $event !== null ? getEventSeats($event) : [];
$availableCount = 0;
foreach ($seatsList as $s) {
    if ((string)($s['status'] ?? 'available') === 'available' && empty($s['booked_by'])) {
        $availableCount++;
    }
}

$eventSlugSafe = (string)($event['slug'] ?? $slug);
$inCartSeats = $_SESSION['cart'][$eventSlugSafe]['seats'] ?? [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string)($event['title'] ?? 'Dettaglio Evento')); ?> - Il Ticchettone</title>
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
                            <button class="nav-link" type="button" data-bs-toggle="menu" aria-expanded="false">
                                Account
                            </button>
                            <div class="menu">
                                <?php if ($isLoggedIn): ?>
                                    <span class="menu-item disabled"><?php echo htmlspecialchars($userEmail); ?></span>
                                    <a class="menu-item" href="../account/dashboard.php">Dashboard</a>
                                    <?php if ($userRole === 'admin'): ?>
                                        <a class="menu-item" href="../admin/admin.php">Admin</a>
                                    <?php endif; ?>
                                    <hr class="menu-divider">
                                    <a class="menu-item" href="../logout/logout.php">Logout</a>
                                <?php else: ?>
                                    <a class="menu-item" href="../login/login.php">Accedi</a>
                                    <a class="menu-item" href="../register/register.php">Registrati</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    </ul>
                </div>
            </dialog>
        </div>
    </nav>

    <main class="container my-4">
        <div class="mb-3">
            <a href="../home/home.php" class="btn btn-outline-light btn-sm">&larr; Torna agli eventi</a>
        </div>

        <?php if ($event === null): ?>
            <div class="card event-panel">
                <div class="card-body">
                    <h1 class="h3 mb-2">Evento non trovato</h1>
                    <p class="mb-3">L'evento richiesto non esiste o è stato rimosso.</p>
                    <a href="../home/home.php" class="btn btn-primary">Vai alla Home</a>
                </div>
            </div>
        <?php else: ?>
            <?php
            $eventImg = !empty($event['image'])
                ? '../../imgs/' . ltrim((string)$event['image'], '/')
                : '../../imgs/fightclub.jpg';
            $unitPrice = (float)($event['price'] ?? 0);
            ?>

            <?php if ($feedbackError !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($feedbackError); ?></div>
            <?php endif; ?>

            <?php if ($feedbackSuccess !== ''): ?>
                <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><?php echo htmlspecialchars($feedbackSuccess); ?></span>
                    <a href="../cart/cart.php" class="btn btn-sm btn-success">Vai al carrello</a>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Dettagli evento -->
                <div class="col-12 col-lg-5">
                    <div class="card event-panel h-100">
                        <img src="<?php echo htmlspecialchars($eventImg); ?>" class="card-img-top event-hero-img" alt="<?php echo htmlspecialchars((string)($event['title'] ?? 'Evento')); ?>">
                        <div class="card-body">
                            <h1 class="h3 card-title"><?php echo htmlspecialchars((string)($event['title'] ?? 'Senza titolo')); ?></h1>
                            <p class="card-text text-muted mb-2">
                                <?php echo htmlspecialchars((string)($event['date'] ?? 'Data da definire')); ?>
                                <?php if (!empty($event['venue'])): ?>
                                    &mdash; <?php echo htmlspecialchars((string)$event['venue']); ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($event['description'])): ?>
                                <p class="card-text"><?php echo htmlspecialchars((string)$event['description']); ?></p>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold fs-5">&euro; <?php echo number_format($unitPrice, 2, ',', '.'); ?></span>
                                <span class="badge bg-info text-dark"><?php echo (int)$availableCount; ?> posti liberi</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selezione posti -->
                <div class="col-12 col-lg-7">
                    <div class="card event-panel h-100">
                        <div class="card-body">
                            <h2 class="h4 mb-2">Seleziona i tuoi posti</h2>
                            <p class="text-muted small mb-3">Clicca sui posti disponibili per selezionarli, poi aggiungili al carrello.</p>

                            <div class="d-flex flex-wrap gap-3 mb-3 small">
                                <span class="d-inline-flex align-items-center gap-1">
                                    <span class="legend-box legend-available"></span> Disponibile
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <span class="legend-box legend-selected"></span> Nel carrello
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <span class="legend-box legend-taken"></span> Occupato
                                </span>
                            </div>

                            <form method="POST" action="event.php?slug=<?php echo urlencode($eventSlugSafe); ?>">
                                <div class="stage-bar text-center mb-4">PALCO / SCHERMO</div>

                                <div class="seats-grid mb-4">
                                    <?php foreach ($seatsList as $seat): ?>
                                        <?php
                                        if (!is_array($seat) || empty($seat['id'])) {
                                            continue;
                                        }
                                        $seatId = (string)$seat['id'];
                                        $status = (string)($seat['status'] ?? 'available');
                                        $isTaken = ($status !== 'available') || !empty($seat['booked_by']);
                                        $isInCart = in_array($seatId, $inCartSeats, true);
                                        $inputId = 'seat_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $seatId);
                                        ?>
                                        <label
                                            for="<?php echo htmlspecialchars($inputId); ?>"
                                            class="seat-item <?php echo $isTaken ? 'is-taken' : ($isInCart ? 'is-in-cart' : 'is-available'); ?>"
                                        >
                                            <input
                                                type="checkbox"
                                                id="<?php echo htmlspecialchars($inputId); ?>"
                                                name="seats[]"
                                                value="<?php echo htmlspecialchars($seatId); ?>"
                                                <?php echo $isTaken ? 'disabled' : ''; ?>
                                                <?php echo $isInCart ? 'checked' : ''; ?>
                                            >
                                            <span><?php echo htmlspecialchars($seatId); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <button type="submit" class="btn btn-primary" <?php echo $availableCount === 0 ? 'disabled' : ''; ?>>
                                        Aggiungi al carrello
                                    </button>
                                    <a href="../cart/cart.php" class="btn btn-outline-secondary">
                                        Vai al carrello (<?php echo count($inCartSeats); ?>)
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
</body>
</html>
