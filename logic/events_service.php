<?php

require_once __DIR__ . '/../backend/json_handler.php';

function getEventsDirectory(): string
{
    return __DIR__ . '/../data/events';
}

function getAllEvents(): array
{
    $files = getEventFiles(getEventsDirectory());
    $events = [];

    foreach ($files as $file) {
        $event = readJsonFile($file);
        if (!empty($event)) {
            if (empty($event['slug'])) {
                $event['slug'] = pathinfo($file, PATHINFO_FILENAME);
            }
            $events[] = $event;
        }
    }

    usort($events, function (array $a, array $b): int {
        return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
    });

    return $events;
}

function filterEventsByQuery(array $events, string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return $events;
    }

    return array_values(array_filter($events, function (array $event) use ($query): bool {
        $title = (string)($event['title'] ?? '');
        $venue = (string)($event['venue'] ?? '');
        $slug = (string)($event['slug'] ?? '');

        return stripos($title, $query) !== false
            || stripos($venue, $query) !== false
            || stripos($slug, $query) !== false;
    }));
}

function getEventBySlug(string $slug): ?array
{
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }

    foreach (getAllEvents() as $event) {
        if (($event['slug'] ?? '') === $slug) {
            return $event;
        }
    }

    return null;
}

function countAvailableSeats(array $event): int
{
    $seats = $event['seats'] ?? [];
    if (!is_array($seats) || empty($seats)) {
        return 0;
    }

    $available = 0;
    foreach ($seats as $seat) {
        if (!is_array($seat)) {
            continue;
        }
        $status = (string)($seat['status'] ?? 'available');
        if ($status === 'available' && empty($seat['booked_by'])) {
            $available++;
        }
    }

    return $available;
}

function getEventSeats(array $event, int $rows = 4, int $cols = 6): array
{
    if (!empty($event['seats']) && is_array($event['seats'])) {
        return $event['seats'];
    }

    $seats = [];
    $rowLetters = range('A', chr(ord('A') + max(0, $rows - 1)));

    foreach ($rowLetters as $row) {
        for ($c = 1; $c <= $cols; $c++) {
            $seats[] = [
                'id' => $row . $c,
                'status' => 'available',
            ];
        }
    }

    return $seats;
}
