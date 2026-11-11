<?php
/*
 * Records one merge: the time and how many presentations it made. Nothing else
 * is stored. The page calls this after a successful download.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function sm_reply(int $status): void
{
    http_response_code($status);
    echo $status === 204 ? '' : json_encode(['ok' => $status < 300]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    sm_reply(405);
}

// Only accept counts sent from a page on this same site.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
    $siteHost = strtolower((string) preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    if ($originHost === '' || $originHost !== $siteHost) {
        sm_reply(403);
    }
}

$raw = file_get_contents('php://input', false, null, 0, 512);
$data = json_decode($raw === false ? '' : $raw, true);
$count = is_array($data) && array_key_exists('presentations', $data)
    ? filter_var($data['presentations'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]])
    : false;
if ($count === false) {
    sm_reply(400);
}

if (!sm_configured()) {
    sm_reply(503);
}

try {
    sm_with_table(function (PDO $db) use ($count) {
        $db->prepare('INSERT INTO sm_merges (created_at, presentations) VALUES (UTC_TIMESTAMP(), ?)')->execute([$count]);
    });
} catch (Throwable $e) {
    error_log('Slide Merge count failed: ' . $e->getMessage());
    sm_reply(500);
}
sm_reply(204);
