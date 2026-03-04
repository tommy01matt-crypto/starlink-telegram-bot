<?php
/**
 * Script de configuración automática del webhook
 * 
 * Este script se ejecuta al iniciar el servicio para asegurar
 * que el webhook de Telegram esté correctamente configurado.
 * 
 * Puedes ejecutarlo manualmente o configurarlo en el startCommand de Render.com
 */

// Cargar configuración
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

$config = require __DIR__ . '/config.php';

// Obtener token y URL del webhook
$botToken = $config['bot_token'] ?? getenv('BOT_TOKEN');
$webhookUrl = $config['webhook_url'] ?? getenv('WEBHOOK_URL');

// Obtener URL automáticamente si no está configurada
if (empty($webhookUrl) && isset($_ENV['RENDER_EXTERNAL_URL'])) {
    $webhookUrl = $_ENV['RENDER_EXTERNAL_URL'] . '/webhook';
}

// Si estamos en local o no tenemos URL, intentar detectar
if (empty($webhookUrl) && php_sapi_name() !== 'cli') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $webhookUrl = $scheme . '://' . $host . '/webhook';
}

if (empty($botToken)) {
    echo "❌ Error: BOT_TOKEN no configurado\n";
    exit(1);
}

if (empty($webhookUrl)) {
    echo "❌ Error: WEBHOOK_URL no configurado y no se puede detectar automáticamente\n";
    echo "   Configura la variable WEBHOOK_URL en el panel de Render.com\n";
    exit(1);
}

echo "🤖 Configurando webhook para bot: " . substr($botToken, 0, 10) . "...\n";
echo "🔗 URL del webhook: $webhookUrl\n";

// Realizar request a la API de Telegram
$url = "https://api.telegram.org/bot{$botToken}/setWebhook";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'url' => $webhookUrl,
    'allowed_updates' => ['message', 'edited_message', 'callback_query'],
    'drop_pending_updates' => false  // No descartamos updates pendientes
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Error de cURL: $error\n";
    exit(1);
}

$result = json_decode($response, true);

if ($result['ok']) {
    echo "✅Webhook configurado correctamente\n";
    echo "📋 Respuesta: " . ($result['description'] ?? 'OK') . "\n";
    
    // Verificar configuración
    $infoCh = curl_init();
    curl_setopt($infoCh, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getWebhookInfo");
    curl_setopt($infoCh, CURLOPT_RETURNTRANSFER, true);
    $infoResponse = curl_exec($infoCh);
    curl_close($infoCh);
    
    $info = json_decode($infoResponse, true);
    if ($info['ok'] && !empty($info['result']['url'])) {
        echo "🔗 Webhook activo en: " . $info['result']['url'] . "\n";
        echo "📬 Updates pendientes: " . $info['result']['pending_update_count'] . "\n";
    }
    
    exit(0);
} else {
    echo "❌ Error de Telegram: " . ($result['description'] ?? 'Unknown error') . "\n";
    echo "   Código de error: " . ($result['error_code'] ?? 'N/A') . "\n";
    exit(1);
}
