<?php
/**
 * Clase Principal del Bot de Telegram
 * 
 * Maneja toda la comunicación con la API de Telegram, incluyendo
 * envío de mensajes, manejo de webhooks y comandos.
 * 
 * @author Starlink Net
 * @version 1.0.0
 */

namespace StarlinkNet\Bot;

use StarlinkNet\Services\Logger;

class TelegramBot
{
    private string $token;
    private string $apiUrl;
    private Logger $logger;
    private array $config;

    /**
     * Constructor
     */
    public function __construct(string $token, array $config, Logger $logger)
    {
        $this->token = $token;
        $this->apiUrl = $config['api_url'] ?? 'https://api.telegram.org';
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Establecer webhook
     */
    public function setWebhook(string $url): array
    {
        $this->logger->info('Estableciendo webhook', ['url' => $url]);
        
        return $this->request('setWebhook', [
            'url' => $url,
            'allowed_updates' => ['message', 'edited_message', 'callback_query'],
            'drop_pending_updates' => true
        ]);
    }

    /**
     * Eliminar webhook
     */
    public function deleteWebhook(): array
    {
        $this->logger->info('Eliminando webhook');
        
        return $this->request('deleteWebhook', [
            'drop_pending_updates' => true
        ]);
    }

    /**
     * Obtener información del webhook
     */
    public function getWebhookInfo(): array
    {
        return $this->request('getWebhookInfo');
    }

    /**
     * Obtener información del bot
     */
    public function getMe(): array
    {
        return $this->request('getMe');
    }

    /**
     * Enviar mensaje de texto
     */
    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ], $options);

        $this->logger->debug('Enviando mensaje', [
            'chat_id' => $chatId,
            'text_length' => strlen($text)
        ]);

        return $this->request('sendMessage', $params);
    }

    /**
     * Enviar mensaje con teclado inline
     */
    public function sendMessageWithKeyboard(int $chatId, string $text, array $keyboard, array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($keyboard)
        ], $options);

        return $this->request('sendMessage', $params);
    }

    /**
     * Responder a callback query
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
    {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert
        ]);
    }

    /**
     * Editar mensaje
     */
    public function editMessageText(int $chatId, int $messageId, string $text, array $keyboard = []): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if (!empty($keyboard)) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('editMessageText', $params);
    }

    /**
     * Eliminar mensaje
     */
    public function deleteMessage(int $chatId, int $messageId): array
    {
        return $this->request('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ]);
    }

    /**
     * Obtener actualizaciones (para polling)
     */
    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 0): array
    {
        return $this->request('getUpdates', [
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => $timeout,
            'allowed_updates' => ['message', 'edited_message', 'callback_query']
        ]);
    }

    /**
     * Establecer comando de menú
     */
    public function setMyCommands(array $commands): array
    {
        return $this->request('setMyCommands', [
            'commands' => json_encode($commands)
        ]);
    }

    /**
     * Obtener comandos del menú
     */
    public function getMyCommands(): array
    {
        return $this->request('getMyCommands');
    }

    /**
     * Obtener perfil de usuario por ID
     */
    public function getChat(int $chatId): array
    {
        return $this->request('getChat', [
            'chat_id' => $chatId
        ]);
    }

    /**
     * Obtener administradores del grupo
     */
    public function getChatAdministrators(int $chatId): array
    {
        return $this->request('getChatAdministrators', [
            'chat_id' => $chatId
        ]);
    }

    /**
     * Obtener miembro del chat
     */
    public function getChatMember(int $chatId, int $userId): array
    {
        return $this->request('getChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId
        ]);
    }

    /**
     * Realizar request a la API de Telegram
     */
    private function request(string $method, array $params = []): array
    {
        $url = "{$this->apiUrl}/bot{$this->token}/{$method}";
        
        $this->logger->debug("Request a API de Telegram", [
            'method' => $method,
            'url' => $url
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            $this->logger->error('Error en request cURL', [
                'error' => $error,
                'method' => $method
            ]);
            
            return [
                'ok' => false,
                'error_code' => 0,
                'description' => 'cURL Error: ' . $error
            ];
        }

        $result = json_decode($response, true);

        if (!$result['ok']) {
            $this->logger->warning('API de Telegram retornó error', [
                'method' => $method,
                'error_code' => $result['error_code'] ?? 0,
                'description' => $result['description'] ?? 'Unknown error'
            ]);
        }

        return $result ?? ['ok' => false, 'description' => 'Empty response'];
    }

    /**
     * Procesar actualización del webhook
     */
    public function processUpdate(array $update): ?array
    {
        $this->logger->debug('Procesando actualización', [
            'update_id' => $update['update_id'] ?? 'unknown'
        ]);

        // Procesar mensaje
        if (isset($update['message'])) {
            return [
                'type' => 'message',
                'data' => $update['message']
            ];
        }

        // Procesar mensaje editado
        if (isset($update['edited_message'])) {
            return [
                'type' => 'edited_message',
                'data' => $update['edited_message']
            ];
        }

        // Procesar callback query
        if (isset($update['callback_query'])) {
            return [
                'type' => 'callback_query',
                'data' => $update['callback_query']
            ];
        }

        return null;
    }
}
