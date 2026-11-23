<?php

require_once __DIR__ . '/../backend/json_handler.php';
require_once __DIR__ . '/events_service.php';

function getUsersFilePath(): string
{
    return __DIR__ . '/../data/users.json';
}

function isUserAdmin(array $user): bool
{
    $authLevel = (string)($user['auth_level'] ?? '');
    if ($authLevel === '1') {
        return true;
    }
    return (($user['role'] ?? '') === 'admin');
}

function getAllUsers(): array
{
    $path = getUsersFilePath();
    if (!file_exists($path)) {
        return [];
    }
    $users = readJsonFile($path);
    return is_array($users) ? $users : [];
}

function updateUserAuthLevel(string $email, string $newLevel): array
{
    $email = strtolower(trim($email));
    $newLevel = ($newLevel === '1') ? '1' : '0';

    if ($email === '') {
        return ['ok' => false, 'error' => 'Email utente non valida.'];
    }

    $users = getAllUsers();
    if (empty($users)) {
        return ['ok' => false, 'error' => 'Nessun utente trovato in data/users.json.'];
    }

    // Conta quanti admin ci sono attualmente
    $adminCount = 0;
    $currentTargetLevel = '0';
    foreach ($users as $u) {
        $uEmail = strtolower(trim((string)($u['email'] ?? '')));
        $uLevel = (string)($u['auth_level'] ?? ((($u['role'] ?? '') === 'admin') ? '1' : '0'));
        if ($uLevel === '1') {
            $adminCount++;
        }
        if ($uEmail === $email) {
            $currentTargetLevel = $uLevel;
        }
    }

    if ($currentTargetLevel === '1' && $newLevel === '0' && $adminCount <= 1) {
        return ['ok' => false, 'error' => 'Deve restare almeno un amministratore con livello "1".'];
    }

    $updated = false;
    foreach ($users as $idx => $u) {
        $uEmail = strtolower(trim((string)($u['email'] ?? '')));
        if ($uEmail === $email) {
            $users[$idx]['auth_level'] = $newLevel;
            $users[$idx]['role'] = ($newLevel === '1') ? 'admin' : 'user';
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        return ['ok' => false, 'error' => 'Utente non trovato.'];
    }

    if (!writeJsonFile(getUsersFilePath(), $users)) {
        return ['ok' => false, 'error' => 'Errore durante il salvataggio in data/users.json.'];
    }

    return ['ok' => true, 'message' => "Livello di autorizzazione aggiornato a \"{$newLevel}\" per {$email}."];
}

function slugifyEventTitle(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string)$text, '-');
}

function buildSeatsGrid(int $rows, int $cols, array $takenSeatIds = []): array
{
    $rows = max(1, min(15, $rows));
    $cols = max(1, min(20, $cols));

    $takenLookup = [];
    foreach ($takenSeatIds as $id) {
        $cleanId = strtoupper(trim((string)$id));
        if ($cleanId !== '') {
            $takenLookup[$cleanId] = true;
        }
    }

    $seats = [];
    $rowLetters = range('A', chr(ord('A') + $rows - 1));

    foreach ($rowLetters as $row) {
        for ($c = 1; $c <= $cols; $c++) {
            $seatId = $row . $c;
            $isTaken = isset($takenLookup[$seatId]);
            $seats[] = [
                'id' => $seatId,
                'row' => $row,
                'number' => $c,
                'status' => $isTaken ? 'booked' : 'available',
                'booked_by' => $isTaken ? 'admin_reserved' : null,
                'booked_at' => null,
            ];
        }
    }

    return $seats;
}

function createEventJson(array $input, string $adminEmail): array
{
    $title = trim((string)($input['title'] ?? ''));
    $rawSlug = trim((string)($input['slug'] ?? ''));
    $slug = slugifyEventTitle($rawSlug !== '' ? $rawSlug : $title);
    $date = trim((string)($input['date'] ?? ''));
    $venue = trim((string)($input['venue'] ?? ''));
    $price = (float)($input['price'] ?? 0);
    $image = trim((string)($input['image'] ?? 'fightclub.jpg'));
    $description = trim((string)($input['description'] ?? ''));
    $rows = (int)($input['rows'] ?? 4);
    $cols = (int)($input['cols'] ?? 6);
    $takenRaw = (string)($input['taken_seats'] ?? '');

    if ($title === '') {
        return ['ok' => false, 'error' => 'Il titolo dell\'evento è obbligatorio.'];
    }
    if ($slug === '') {
        return ['ok' => false, 'error' => 'Impossibile generare uno slug valido.'];
    }
    if ($date === '') {
        return ['ok' => false, 'error' => 'La data dell\'evento è obbligatoria.'];
    }
    if ($venue === '') {
        return ['ok' => false, 'error' => 'Il luogo (venue) è obbligatorio.'];
    }
    if ($price < 0) {
        return ['ok' => false, 'error' => 'Il prezzo non può essere negativo.'];
    }

    $dir = getEventsDirectory();
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Impossibile creare la cartella data/events.'];
    }

    $filePath = $dir . '/' . $slug . '.json';
    if (file_exists($filePath)) {
        return ['ok' => false, 'error' => "Esiste già un evento con slug \"{$slug}\"."];
    }

    $existingEvents = getAllEvents();
    $maxId = 0;
    foreach ($existingEvents as $ev) {
        $evId = (int)($ev['id'] ?? 0);
        if ($evId > $maxId) {
            $maxId = $evId;
        }
    }

    $takenList = $takenRaw !== '' ? preg_split('/[\s,;]+/', $takenRaw) : [];
    $seats = buildSeatsGrid($rows, $cols, is_array($takenList) ? $takenList : []);

    $eventPayload = [
        'id' => $maxId + 1,
        'slug' => $slug,
        'title' => $title,
        'date' => $date,
        'venue' => $venue,
        'price' => round($price, 2),
        'image' => $image !== '' ? ltrim($image, '/') : 'fightclub.jpg',
        'description' => $description,
        'created_by' => $adminEmail,
        'created_at' => date('Y-m-d H:i:s'),
        'seats' => $seats,
    ];

    if (!writeJsonFile($filePath, $eventPayload)) {
        return ['ok' => false, 'error' => 'Errore durante la scrittura del file JSON dell\'evento.'];
    }

    return [
        'ok' => true,
        'slug' => $slug,
        'file' => basename($filePath),
        'payload' => $eventPayload,
    ];
}
