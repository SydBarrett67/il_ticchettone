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
