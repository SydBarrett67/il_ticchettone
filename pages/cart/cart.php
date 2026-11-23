<?php
session_start();

require_once __DIR__ . '/../../logic/events_service.php';

$isLoggedIn = isset($_SESSION['user']);
$userEmail = $isLoggedIn ? (string)($_SESSION['user']['email'] ?? '') : '';
$userRole = $isLoggedIn ? (string)($_SESSION['user']['role'] ?? 'user') : 'user';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $eventSlug = trim((string)($_POST['event_slug'] ?? ''));

    if ($action === 'remove_seat' && $eventSlug !== '') {
        $seatId = trim((string)($_POST['seat_id'] ?? ''));
        if (isset($_SESSION['cart'][$eventSlug]['seats']) && is_array($_SESSION['cart'][$eventSlug]['seats'])) {
            $_SESSION['cart'][$eventSlug]['seats'] = array_values(array_filter(
                $_SESSION['cart'][$eventSlug]['seats'],
                fn($s): bool => (string)$s !== $seatId
            ));

            if (empty($_SESSION['cart'][$eventSlug]['seats'])) {
                unset($_SESSION['cart'][$eventSlug]);
            }
        }
    } elseif ($action === 'clear_event' && $eventSlug !== '') {
        unset($_SESSION['cart'][$eventSlug]);
    } elseif ($action === 'clear_all') {
        $_SESSION['cart'] = [];
    }

    header('Location: cart.php');
    exit;
}

$cartItems = $_SESSION['cart'];
$totalTickets = 0;
$totalAmount = 0.0;

foreach ($cartItems as $item) {
    $seatsCount = is_array($item['seats'] ?? null) ? count($item['seats']) : 0;
    $unitPrice = (float)($item['price'] ?? 0);
    $totalTickets += $seatsCount;
    $totalAmount += $seatsCount * $unitPrice;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrello - Il Ticchettone</title>
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
                            <a class="nav-link active" aria-current="page" href="cart.php">Carrello (<?php echo (int)$totalTickets; ?>)</a>
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
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h1 class="mb-0">Il tuo Carrello</h1>
            <a href="../home/home.php#events-section" class="btn btn-outline-light btn-sm">&larr; Continua a esplorare</a>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="card cart-panel">
                <div class="card-body text-center py-5">
                    <h2 class="h4 mb-2">Il carrello è vuoto</h2>
                    <p class="text-muted mb-4">Scegli un evento e seleziona i posti che desideri prenotare.</p>
                    <a href="../home/home.php#events-section" class="btn btn-primary">Vai agli eventi</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <?php foreach ($cartItems as $slug => $item): ?>
                        <?php
                        $eventData = getEventBySlug((string)$slug);
                        $allSeats = $eventData !== null ? getEventSeats($eventData) : [];
                        $selectedSeats = array_values(array_map('strval', $item['seats'] ?? []));
                        $unitPrice = (float)($item['price'] ?? 0);
                        $subtotal = count($selectedSeats) * $unitPrice;
                        ?>
                        <div class="card cart-panel mb-4">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                    <div>
                                        <h2 class="h5 mb-1"><?php echo htmlspecialchars((string)($item['event_title'] ?? $slug)); ?></h2>
                                        <p class="small text-muted mb-0">
                                            <?php echo htmlspecialchars((string)($item['event_date'] ?? '')); ?>
                                            <?php if (!empty($item['event_venue'])): ?>
                                                &mdash; <?php echo htmlspecialchars((string)$item['event_venue']); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <form method="POST" action="cart.php" class="d-inline">
                                        <input type="hidden" name="action" value="clear_event">
                                        <input type="hidden" name="event_slug" value="<?php echo htmlspecialchars((string)$slug); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Rimuovi evento</button>
                                    </form>
                                </div>

                                <!-- Mappa posti riassuntiva -->
                                <?php if (!empty($allSeats)): ?>
                                    <div class="stage-bar text-center mb-3">MAPPA POSTI</div>
                                    <div class="cart-seat-map mb-3">
                                        <?php foreach ($allSeats as $seat): ?>
                                            <?php
                                            if (!is_array($seat) || empty($seat['id'])) {
                                                continue;
                                            }
                                            $seatId = (string)$seat['id'];
                                            $isChosen = in_array($seatId, $selectedSeats, true);
                                            $isTaken = ((string)($seat['status'] ?? 'available') !== 'available') || !empty($seat['booked_by']);
                                            $cls = $isChosen ? 'map-chosen' : ($isTaken ? 'map-taken' : 'map-free');
                                            ?>
                                            <span class="map-cell <?php echo $cls; ?>"><?php echo htmlspecialchars($seatId); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Posti selezionati con rimozione singola -->
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php foreach ($selectedSeats as $seatId): ?>
                                        <form method="POST" action="cart.php" class="d-inline-flex align-items-center badge bg-success p-2">
                                            <span class="me-2">Posto <?php echo htmlspecialchars($seatId); ?></span>
                                            <input type="hidden" name="action" value="remove_seat">
                                            <input type="hidden" name="event_slug" value="<?php echo htmlspecialchars((string)$slug); ?>">
                                            <input type="hidden" name="seat_id" value="<?php echo htmlspecialchars($seatId); ?>">
                                            <button type="submit" class="btn-close btn-close-white" aria-label="Rimuovi posto <?php echo htmlspecialchars($seatId); ?>" style="width:0.65rem;height:0.65rem;"></button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <a href="../event/event.php?slug=<?php echo urlencode((string)$slug); ?>" class="btn btn-sm btn-outline-primary">Modifica posti</a>
                                    <span class="fw-bold">Subtotale: &euro; <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Riepilogo ordine -->
                <div class="col-12 col-lg-4">
                    <div class="card cart-panel">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Riepilogo ordine</h2>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Biglietti totali</span>
                                <strong><?php echo (int)$totalTickets; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                <span>Totale da pagare</span>
                                <strong class="fs-5">&euro; <?php echo number_format($totalAmount, 2, ',', '.'); ?></strong>
                            </div>

                            <div class="d-grid gap-2">
                                <?php if ($isLoggedIn): ?>
                                    <a href="../checkout/checkout.php" class="btn btn-primary">Procedi all'acquisto</a>
                                <?php else: ?>
                                    <a href="../login/login.php" class="btn btn-primary">Accedi per acquistare</a>
                                <?php endif; ?>

                                <form method="POST" action="cart.php">
                                    <input type="hidden" name="action" value="clear_all">
                                    <button type="submit" class="btn btn-outline-secondary w-100">Svuota carrello</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
</body>
</html>
