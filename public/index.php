<?php
/**
 * Punto de Entrada del Bot de Telegram para Starlink Net
 * 
 * Este archivo maneja las solicitudes entrantes del webhook de Telegram
 * y las procesa adecuadamente.
 * 
 * @author Starlink Net
 * @version 1.0.0
 */

// Cargar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar configuración
$config = require __DIR__ . '/../config.php';

// Inicializar logger
$logger = \StarlinkNet\Services\Logger::getInstance([
    'logPath' => $config['paths']['logs'],
    'logLevel' => $config['logging']['level'],
    'enabled' => $config['logging']['enabled']
]);

// Manejo de errores
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Establecer manejador de excepciones
set_exception_handler(function ($e) use ($logger) {
    $logger->exception($e);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Internal server error']);
});

// Log de solicitud entrante
$logger->info('Solicitud recibida', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// Verificar token del bot
$botToken = $config['bot_token'];
if (empty($botToken)) {
    $logger->error('Token del bot no configurado');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Bot token not configured']);
    exit;
}

// Inicializar bot
$bot = new \StarlinkNet\Bot\TelegramBot($botToken, $config, $logger);
$commandsHandler = new \StarlinkNet\Commands\CommandsHandler($bot, $config, $logger);

// Manejar diferentes métodos HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Leer datos del webhook
        $rawInput = file_get_contents('php://input');
        $update = json_decode($rawInput, true);
        
        if (!$update) {
            $logger->warning('Datos inválidos recibidos');
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid request data']);
            exit;
        }

        $logger->debug('Update recibido', ['update_id' => $update['update_id'] ?? 'unknown']);

        // Procesar actualización
        $processed = $bot->processUpdate($update);
        
        if (!$processed) {
            $logger->debug('Tipo de update no manejado');
            echo json_encode(['ok' => true]);
            exit;
        }

        // Delegar al manejador apropiado
        try {
            switch ($processed['type']) {
                case 'message':
                    $commandsHandler->handleMessage($processed['data']);
                    break;

                case 'edited_message':
                    $logger->info('Mensaje editado recibido', [
                        'message_id' => $processed['data']['message_id'] ?? 'unknown'
                    ]);
                    break;

                case 'callback_query':
                    $commandsHandler->handleCallback($processed['data']);
                    break;

                default:
                    $logger->warning('Tipo de mensaje desconocido', [
                        'type' => $processed['type']
                    ]);
            }
        } catch (\Exception $e) {
            $logger->exception($e, ['update_id' => $update['update_id'] ?? 'unknown']);
        }

        echo json_encode(['ok' => true]);
        break;

    case 'GET':
        // Endpoint para verificación de salud
        $botInfo = $bot->getMe();
        
        echo json_encode([
            'ok' => true,
            'bot' => $botInfo['result'] ?? null,
            'message' => 'Bot de Telegram Starlink Net activo',
            'version' => '1.0.0'
        ]);
        break;

    case 'HEAD':
        // Verificación de salud básica
        http_response_code(200);
        break;

    default:
        $logger->warning('Método HTTP no permitido', ['method' => $method]);
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
        break;
}
