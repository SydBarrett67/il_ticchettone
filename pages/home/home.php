<?php
    session_start();

    require_once __DIR__ . '/../../logic/events_service.php';

    $searchQuery = trim($_GET['q'] ?? '');
    $allEvents = getAllEvents();
    $featuredEvents = array_slice($allEvents, 0, 3);
    $filteredEvents = filterEventsByQuery($allEvents, $searchQuery);

    $isLoggedIn = isset($_SESSION['user']);
    $userEmail = $isLoggedIn ? ($_SESSION['user']['email'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Il Ticchettone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navbar -->
    <nav id="navbar" class="navbar md:navbar-expand bg-1">
        <div class="container-fluid">
            <a class="navbar-brand" href="home.php">Il Ticchettone</a>
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
                            <a class="nav-link active" aria-current="page" href="home.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#events-section">Eventi</a>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" type="button" data-bs-toggle="menu" aria-expanded="false">
                                Account
                            </button>
                            <div class="menu">
                                <?php if ($isLoggedIn): ?>
                                    <span class="menu-item disabled"><?php echo htmlspecialchars($userEmail); ?></span>
                                    <a class="menu-item" href="../account/dashboard.php">Dashboard</a>
                                    <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
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
                    <form id="searchbar" method="GET" action="home.php" role="search" class="d-flex gap-2">
                        <input
                            class="form-control"
                            type="search"
                            name="q"
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            placeholder="Cerca evento..."
                            aria-label="Search"
                        />
                        <button class="btn-solid theme-secondary" type="submit">Cerca</button>
                    </form>
                </div>
            </dialog>
        </div>
    </nav>

    <!-- Carousel -->
<div id="carousel" class="carousel carousel-overlay slide" data-bs-ride="carousel" data-bs-interval="1000">        <div class="carousel-inner rounded-5">
            <?php if (!empty($featuredEvents)): ?>
                <?php foreach ($featuredEvents as $index => $event): ?>
                    <?php
                    $imgSrc = !empty($event['image'])
                        ? '../../imgs/' . ltrim($event['image'], '/')
                        : '../../imgs/fightclub.jpg';
                    ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($event['title'] ?? 'Evento'); ?>">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="carousel-item active">
                    <img src="../../imgs/fightclub.jpg" class="d-block w-100" alt="Evento in evidenza">
                </div>
                <div class="carousel-item">
                    <img src="../../imgs/fightclub.jpg" class="d-block w-100" alt="Evento in evidenza">
                </div>
                <div class="carousel-item">
                    <img src="../../imgs/fightclub.jpg" class="d-block w-100" alt="Evento in evidenza">
                </div>
            <?php endif; ?>
        </div>
        <div class="carousel-overlay-controls">
            <div class="carousel-indicators">
                <?php $indicatorCount = !empty($featuredEvents) ? count($featuredEvents) : 3; ?>
                <?php for ($i = 0; $i < $indicatorCount; $i++): ?>
                    <button
                        type="button"
                        data-bs-target="#carousel"
                        data-bs-slide-to="<?php echo $i; ?>"
                        class="<?php echo $i === 0 ? 'active' : ''; ?>"
                        <?php echo $i === 0 ? 'aria-current="true"' : ''; ?>
                        aria-label="Slide <?php echo $i + 1; ?>"
                    ></button>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <main id="events-section" class="container my-4">
        <h1>
            <?php if ($searchQuery !== ''): ?>
                Risultati per "<?php echo htmlspecialchars($searchQuery); ?>"
            <?php else: ?>
                Eventi disponibili
            <?php endif; ?>
        </h1>

        <?php if (empty($filteredEvents)): ?>
            <p>Nessun evento trovato.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($filteredEvents as $event): ?>
                    <?php
                    $eventImg = !empty($event['image'])
                        ? '../../imgs/' . ltrim($event['image'], '/')
                        : '../../imgs/fightclub.jpg';
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($eventImg); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event['title'] ?? 'Evento'); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title'] ?? 'Senza titolo'); ?></h5>
                                <p class="card-text">
                                    <?php echo htmlspecialchars($event['date'] ?? ''); ?>
                                    <?php if (!empty($event['venue'])): ?>
                                        — <?php echo htmlspecialchars($event['venue']); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if (isset($event['price'])): ?>
                                    <p class="card-text fw-bold">&euro; <?php echo number_format((float)$event['price'], 2, ',', '.'); ?></p>
                                <?php endif; ?>
                                <a href="../event/event.php?slug=<?php echo urlencode($event['slug'] ?? ''); ?>" class="btn btn-primary">Scegli posti</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
    <script src="script.js"></script>
</body>
</html>
