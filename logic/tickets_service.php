<?php

require_once __DIR__ . '/../backend/json_handler.php';

function getTicketsFilePath(): string
{
    return __DIR__ . '/../data/tickets.json';
}

function getAllTickets(): array
{
    $tickets = readJsonFile(getTicketsFilePath());
    return is_array($tickets) ? $tickets : [];
}

function getUserTickets(string $userEmail): array
{
    $userEmail = strtolower(trim($userEmail));
    if ($userEmail === '') {
        return [];
    }

    $userTickets = array_values(array_filter(getAllTickets(), function (array $ticket) use ($userEmail): bool {
        return strtolower(trim((string)($ticket['user_email'] ?? ''))) === $userEmail;
    }));

    usort($userTickets, function (array $a, array $b): int {
        return strcmp((string)($b['purchased_at'] ?? ''), (string)($a['purchased_at'] ?? ''));
    });

    return $userTickets;
}

function getAccountStats(array $tickets): array
{
    $totalTickets = count($tickets);
    $totalSpent = 0.0;
    $eventsCount = [];

    foreach ($tickets as $ticket) {
        $totalSpent += (float)($ticket['price'] ?? 0);
        $slug = (string)($ticket['event_slug'] ?? '');
        if ($slug !== '') {
            $eventsCount[$slug] = true;
        }
    }

    return [
        'total_tickets' => $totalTickets,
        'total_spent' => $totalSpent,
        'distinct_events' => count($eventsCount),
        'last_purchase_at' => $tickets[0]['purchased_at'] ?? null,
    ];
}
