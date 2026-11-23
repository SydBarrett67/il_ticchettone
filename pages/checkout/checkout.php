<?php
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user']['email'])) {
    header('Location: ../login/login.php');
    exit;
}

require_once __DIR__ . '/../../logic/checkout_service.php';

$user = $_SESSION['user'];
$userEmail = (string)$user['email'];
$userRole = (string)($user['role'] ?? 'user');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartItems = $_SESSION['cart'];
$totalTickets = 0;
$totalAmount = 0.0;

foreach ($cartItems as $item) {
    $count = is_array($item['seats'] ?? null) ? count($item['seats']) : 0;
    $price = (float)($item['price'] ?? 0);
    $totalTickets += $count;
    $totalAmount += $count * $price;
}

$errorMessage = '';
$completedOrder = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $holderName = trim((string)($_POST['holder_name'] ?? ''));
    $paymentMethod = trim((string)($_POST['payment_method'] ?? 'Carta simulata'));

    if ($holderName === '') {
        $errorMessage = 'Inserisci il nome dell\'intestatario per completare l\'acquisto simulato.';
    } else {
        $result = processSimulatedCheckout($cartItems, $userEmail, $paymentMethod);
        if ($result['ok']) {
            $_SESSION['cart'] = [];
            $cartItems = [];
            $completedOrder = [
                'holder_name' => $holderName,
                'payment_method' => $paymentMethod,
                'purchased_at' => $result['purchased_at'],
                'tickets' => $result['tickets'],
            ];
        } else {
            $errorMessage = (string)($result['error'] ?? 'Errore durante la simulazione dell\'acquisto.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acquisto Simulato - Il Ticchettone</title>
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
                            <a class="nav-link" href="../cart/cart.php">Carrello</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../account/dashboard.php">Dashboard</a>
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
                <h1 class="mb-1">Checkout Simulato</h1>
                <p class="mb-0 small text-light-emphasis">Ambiente di prova: nessun addebito reale verrà effettuato.</p>
            </div>
            <a href="../cart/cart.php" class="btn btn-outline-light btn-sm">&larr; Torna al carrello</a>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <?php if ($completedOrder !== null): ?>
            <?php
            $orderTotal = 0.0;
            foreach ($completedOrder['tickets'] as $t) {
                $orderTotal += (float)($t['price'] ?? 0);
            }
            ?>
            <div class="card checkout-panel">
                <div class="card-body">
                    <div class="alert alert-success mb-4">
                        <strong>Acquisto simulato completato!</strong> I tuoi biglietti sono stati salvati nell'account.
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Intestatario</div>
                            <div class="fw-bold"><?php echo htmlspecialchars((string)$completedOrder['holder_name']); ?></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Metodo simulato</div>
                            <div class="fw-bold"><?php echo htmlspecialchars((string)$completedOrder['payment_method']); ?></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Data e totale</div>
                            <div class="fw-bold">
                                <?php echo htmlspecialchars((string)$completedOrder['purchased_at']); ?> &mdash;
                                &euro; <?php echo number_format($orderTotal, 2, ',', '.'); ?>
                            </div>
                        </div>
                    </div>

                    <h2 class="h5 mb-3">Biglietti emessi</h2>
                    <div class="table-responsive mb-4">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Codice</th>
                                    <th>Evento</th>
                                    <th>Posto</th>
                                    <th>Prezzo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completedOrder['tickets'] as $ticket): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars((string)$ticket['ticket_code']); ?></code></td>
                                        <td><?php echo htmlspecialchars((string)$ticket['event_title']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars((string)$ticket['seat_id']); ?></span></td>
                                        <td>&euro; <?php echo number_format((float)$ticket['price'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="../account/dashboard.php" class="btn btn-primary">Vai alla Dashboard</a>
                        <a href="../home/home.php" class="btn btn-outline-secondary">Torna alla Home</a>
                    </div>
                </div>
            </div>
        <?php elseif (empty($cartItems)): ?>
            <div class="card checkout-panel">
                <div class="card-body text-center py-5">
                    <h2 class="h4 mb-2">Nessun articolo da acquistare</h2>
                    <p class="text-muted mb-4">Aggiungi prima dei posti al carrello per procedere alla simulazione.</p>
                    <a href="../home/home.php#events-section" class="btn btn-primary">Esplora eventi</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <div class="card checkout-panel">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Dati di pagamento simulato</h2>
                            <form method="POST" action="checkout.php">
                                <div class="mb-3">
                                    <label for="holder_name" class="form-label">Nome e cognome intestatario</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="holder_name"
                                        name="holder_name"
                                        value="<?php echo htmlspecialchars((string)($_POST['holder_name'] ?? ($user['name'] ?? ''))); ?>"
                                        placeholder="Es. Mario Rossi"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Metodo di pagamento simulato</label>
                                    <select class="form-select" id="payment_method" name="payment_method">
                                        <option value="Carta simulata (Visa **** 4242)">Carta simulata (Visa **** 4242)</option>
                                        <option value="PayPal Sandbox">PayPal Sandbox</option>
                                        <option value="Bonifico Demo">Bonifico Demo</option>
                                    </select>
                                </div>

                                <div class="alert alert-info small mb-4">
                                    Account acquirente: <strong><?php echo htmlspecialchars($userEmail); ?></strong>.
                                    Confermando verranno generati i biglietti e i posti verranno bloccati sull'evento.
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    Conferma acquisto simulato (&euro; <?php echo number_format($totalAmount, 2, ',', '.'); ?>)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="card checkout-panel">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Riepilogo posti</h2>
                            <?php foreach ($cartItems as $item): ?>
                                <?php
                                $seats = $item['seats'] ?? [];
                                $price = (float)($item['price'] ?? 0);
                                $sub = count($seats) * $price;
                                ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="fw-bold"><?php echo htmlspecialchars((string)($item['event_title'] ?? 'Evento')); ?></div>
                                    <div class="small text-muted mb-1">
                                        Posti: <?php echo htmlspecialchars(implode(', ', array_map('strval', $seats))); ?>
                                    </div>
                                    <div class="d-flex justify-content-between small">
                                        <span><?php echo count($seats); ?> &times; &euro; <?php echo number_format($price, 2, ',', '.'); ?></span>
                                        <span class="fw-bold">&euro; <?php echo number_format($sub, 2, ',', '.'); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Totale (<?php echo (int)$totalTickets; ?> biglietti)</span>
                                <span class="fs-5 fw-bold">&euro; <?php echo number_format($totalAmount, 2, ',', '.'); ?></span>
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
