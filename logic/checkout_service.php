<?php

require_once __DIR__ . '/../backend/json_handler.php';
require_once __DIR__ . '/events_service.php';
require_once __DIR__ . '/tickets_service.php';

function findEventJsonFilePath(string $slug): ?string
{
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }

    $dir = getEventsDirectory();
    $files = getEventFiles($dir);

    foreach ($files as $file) {
        $data = readJsonFile($file);
        $fileSlug = !empty($data['slug']) ? (string)$data['slug'] : pathinfo($file, PATHINFO_FILENAME);
        if ($fileSlug === $slug) {
            return $file;
        }
    }

    $candidate = $dir . '/' . basename($slug) . '.json';
    return file_exists($candidate) ? $candidate : null;
}

function processSimulatedCheckout(array $cartItems, string $userEmail, string $paymentMethod = 'Carta simulata'): array
{
    $userEmail = trim($userEmail);
    if ($userEmail === '') {
        return ['ok' => false, 'error' => 'Utente non autenticato.'];
    }

    if (empty($cartItems)) {
        return ['ok' => false, 'error' => 'Il carrello è vuoto.'];
    }

    $eventUpdates = [];
    $ticketsToCreate = [];
    $now = date('Y-m-d H:i:s');

    foreach ($cartItems as $slug => $item) {
        $slug = (string)$slug;
        $requestedSeats = array_values(array_unique(array_map('strval', $item['seats'] ?? [])));

        if (empty($requestedSeats)) {
            continue;
        }

        $event = getEventBySlug($slug);
        if ($event === null) {
            return ['ok' => false, 'error' => "L'evento \"{$slug}\" non è più disponibile."];
        }

        $seats = getEventSeats($event);
        $seatIndexById = [];
        foreach ($seats as $idx => $seat) {
            if (is_array($seat) && !empty($seat['id'])) {
                $seatIndexById[(string)$seat['id']] = $idx;
            }
        }

        foreach ($requestedSeats as $seatId) {
            if (!isset($seatIndexById[$seatId])) {
                return ['ok' => false, 'error' => "Il posto {$seatId} non esiste per l'evento {$event['title']}."];
            }

            $idx = $seatIndexById[$seatId];
            $status = (string)($seats[$idx]['status'] ?? 'available');
            $bookedBy = (string)($seats[$idx]['booked_by'] ?? '');

            if ($status !== 'available' || $bookedBy !== '') {
                return ['ok' => false, 'error' => "Il posto {$seatId} per \"{$event['title']}\" non è più disponibile."];
            }

            $seats[$idx]['id'] = $seatId;
            $seats[$idx]['status'] = 'booked';
            $seats[$idx]['booked_by'] = $userEmail;
            $seats[$idx]['booked_at'] = $now;

            $unitPrice = (float)($event['price'] ?? ($item['price'] ?? 0));
            $ticketCode = 'TIC-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            $ticketsToCreate[] = [
                'ticket_code' => $ticketCode,
                'user_email' => $userEmail,
                'event_slug' => $slug,
                'event_title' => (string)($event['title'] ?? $slug),
                'seat_id' => $seatId,
                'price' => $unitPrice,
                'payment_method' => $paymentMethod,
                'purchased_at' => $now,
            ];
        }

        $event['seats'] = $seats;
        $eventUpdates[$slug] = $event;
    }

    if (empty($ticketsToCreate)) {
        return ['ok' => false, 'error' => 'Nessun posto valido da acquistare.'];
    }

    foreach ($eventUpdates as $slug => $updatedEvent) {
        $filePath = findEventJsonFilePath($slug);
        if ($filePath === null) {
            $filePath = getEventsDirectory() . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) . '.json';
        }
        if (!writeJsonFile($filePath, $updatedEvent)) {
            return ['ok' => false, 'error' => "Impossibile aggiornare i posti per l'evento \"{$slug}\"."];
        }
    }

    $existingTickets = getAllTickets();
    $mergedTickets = array_merge($existingTickets, $ticketsToCreate);
    if (!writeJsonFile(getTicketsFilePath(), $mergedTickets)) {
        return ['ok' => false, 'error' => 'Impossibile salvare i biglietti emessi.'];
    }

    return [
        'ok' => true,
        'tickets' => $ticketsToCreate,
        'purchased_at' => $now,
    ];
}
