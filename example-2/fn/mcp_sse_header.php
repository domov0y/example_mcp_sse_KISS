<?php
// mcp_server.php

// 1. Заголовки CORS и JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Last-Event-ID');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 2. Обработка GET (SSE поток)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    // Отправляем событие endpoint, чтобы клиент знал, куда слать POST
    // В MCP это обычно делается автоматически, но для простоты:
    echo "event: endpoint\n";
    echo "data: " . json_encode(["url" => "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_URI']]) . "\n\n";

    // Держим соединение открытым (бесконечный цикл для демо)
    while (true) {
        echo ": keepalive\n\n";
        flush();
        sleep(5);

        // Проверка, не отключился ли клиент
        if (connection_aborted()) {
            break;
        }
    }
    exit;
}

// 3. Обработка POST (JSON-RPC запросы)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);

    if (!$request || !isset($request['method'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON-RPC"]);
        exit;
    }

    $response = [
        "jsonrpc" => "2.0",
        "id" => $request['id'] ?? null
    ];