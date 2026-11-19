<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

// Експорт доступний тільки адміністратору
if (!is_admin()) {
    http_response_code(403);
    exit('Доступ заборонено. Увійдіть через admin.php');
}

$rows = get_all_responses();
$format = $_GET['format'] ?? 'csv';
$stamp = date('Y-m-d_H-i-s');

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"responses_{$stamp}.json\"");
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// CSV (з BOM, щоб Excel правильно показав українські літери)
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"responses_{$stamp}.csv\"");
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'created_at', 'name', 'email', 'q1', 'q2', 'q3', 'q4'], ';');
foreach ($rows as $r) {
    fputcsv($out, [$r['id'], $r['created_at'], $r['name'], $r['email'], $r['q1'], $r['q2'], $r['q3'], $r['q4']], ';');
}
fclose($out);
