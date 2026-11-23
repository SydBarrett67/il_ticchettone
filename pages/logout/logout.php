<?php
session_start();

// Svuota tutti i dati in sessione
$_SESSION = [];

// Cancella il cookie di sessione se presente
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Distrugge la sessione sul server
session_destroy();

// Reindirizza alla home
header('Location: ../home/home.php');
exit;
