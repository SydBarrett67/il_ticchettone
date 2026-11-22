<?php
function readJsonFile(string $filePath): array
{
    if (!file_exists($filePath)) {
        return [];
    }

    $content = file_get_contents($filePath);
    if ($content === false || trim($content) === '') {
        return [];
    }

    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function writeJsonFile(string $filePath, array $data): bool
{
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    return file_put_contents($filePath, $json . PHP_EOL, LOCK_EX) !== false;
}

function getEventFiles(string $eventsDir): array
{
    if (!is_dir($eventsDir)) {
        return [];
    }

    $files = glob(rtrim($eventsDir, '/\\') . '/*.json');
    return is_array($files) ? $files : [];
}
