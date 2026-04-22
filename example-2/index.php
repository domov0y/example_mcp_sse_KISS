<?php
$tools=[];
include('fn/db.php');
include('fn/notes.php');
db_con();

function show_tool_list()
{
  global $tools;
  $tmp=[ 'tools'=>[] ];
  foreach($tools as $row)
  {
    $tmp['tools'][]=$row;
  }
  return $tmp;
}


function exec_tool($toolName, $args)
{
  $result=array();
  global $tools;
  if (isset($tools[$toolName])) 
    $result = call_user_func($toolName,$args);
  else
    return false; 
  return $result;
}



function report_format($rawResult)
{
   // Превращаем результат в читаемую строку
    $textResponse = '';
    if (is_string($rawResult)) {
        $textResponse = $rawResult;
    } elseif (is_array($rawResult) || is_object($rawResult)) {
        $textResponse = json_encode($rawResult, JSON_UNESCAPED_UNICODE);
    } elseif ($rawResult === null) {
        $textResponse = 'OK';  // или ''
    } else {
        $textResponse = (string)$rawResult;
    }

    return $textResponse;
}




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

    switch ($request['method']) {
        case 'initialize':
            $response['result'] = [
                "protocolVersion" => "2024-11-05",
                "capabilities" => [
                    "tools" => new stdClass()
                ],
                "serverInfo" => [
                    "name" => "SimplePHP-MCP",
                    "version" => "1.0.0"
                ]
            ];
            break;
        case "tools/list":
            $response['result']=show_tool_list();  
            break;

        case 'tools/call':
            $content = [];
            $toolName = $request['params']['name'] ?? '';
            $args = $request['params']['arguments'] ?? [];

            $rawResult = exec_tool($toolName, $args);
            if ($rawResult===false)
            {
               $response['error'] = [
                    "code" => -32601,
                    "message" => "Tool not found: $toolName"
                ];

            }
            else
            {
              $textResponse = report_format($rawResult);
              $content = [  // ← инициализируем массив явно
                 [
                   "type" => "text",
                   "text" => $textResponse  // ← без json_encode!
                 ]
              ];
            }

            if (!isset($response['error'])) {
                $response['result'] = [
                    "content" => $content,
                    "isError" => false
                ];
            }


            break;

        default:
            $response['error'] = [
                "code" => -32601,
                "message" => "Method not found"
            ];


    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);

