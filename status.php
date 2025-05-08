<?php
header('Content-Type: application/json');

$statusFile = 'status.json';

if (file_exists($statusFile)) {
    $status = json_decode(file_get_contents($statusFile), true);
    echo json_encode($status);
} else {
    echo json_encode([
        'message' => 'Aguardando início da importação...',
        'imported' => 0,
        'total' => 0,
        'error' => false
    ]);
}
