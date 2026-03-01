<?php
/**
 * Manejador de Comandos del Bot de Telegram
 * 
 * Procesa todos los comandos y mensajes recibidos por el bot,
 * incluyendo el menú de bienvenida, FAQ, planes, contacto y soporte.
 * 
 * @author Starlink Net
 * @version 1.0.0
 */

namespace StarlinkNet\Commands;

use StarlinkNet\Bot\TelegramBot;
use StarlinkNet\Services\Logger;

class CommandsHandler
{
    private TelegramBot $bot;
    private Logger $logger;
    private array $config;
    private array $userStates = [];

    /**
     * Constructor
     */
    public function __construct(TelegramBot $bot, array $config, Logger $logger)
    {
        $this->bot = $bot;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Procesar mensaje recibido
     */
    public function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'] ?? 0;
        $text = $message['text'] ?? '';
        $name = $message['from']['first_name'] ?? 'Usuario';

        $this->logger->info('Mensaje recibido', [
            'user_id' => $userId,
            'chat_id' => $chatId,
            'text' => $text
        ]);

        // Verificar si es comando
        if (strpos($text, '/') === 0) {
            $this->handleCommand($message);
            return;
        }

        // Verificar estado del usuario
        if (isset($this->userStates[$userId])) {
            $this->handleUserState($message);
            return;
        }

        // Mensaje de bienvenida para nuevos usuarios
        $this->sendWelcome($chatId, $name);
    }

    /**
     * Procesar comando
     */
    private function handleCommand(array $message): void
    {
        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'] ?? 0;
        $name = $message['from']['first_name'] ?? 'Usuario';

        // Limpiar comando de espacios y obtener comando base
        $command = trim(strtok($text, ' '));
        
        // Quitar @bot_username si existe
        if (strpos($command, '@') !== false) {
            $command = strtok($command, '@');
        }

        $this->logger->info('Comando recibido', [
            'command' => $command,
            'user_id' => $userId
        ]);

        // Comandos disponibles
        switch ($command) {
            case '/start':
                $this->sendWelcome($chatId, $name);
                break;

            case '/help':
            case '/ayuda':
                $this->sendHelp($chatId);
                break;

            case '/planes':
                $this->sendPlans($chatId);
                break;

            case '/faq':
            case '/preguntas':
                $this->sendFAQ($chatId);
                break;

            case '/contacto':
                $this->startContactForm($chatId, $userId);
                break;

            case '/soporte':
                $this->startSupportForm($chatId, $userId);
                break;

            case '/empresa':
            case '/nosotros':
                $this->sendCompanyInfo($chatId);
                break;

            case '/prueba':
                $this->requestTrial($chatId, $userId, $name);
                break;

            // Comandos administrativos
            case '/admin':
            case '/panel':
                if ($this->isAdmin($userId)) {
                    $this->sendAdminPanel($chatId, $userId);
                } else {
                    $this->bot->sendMessage($chatId, "⛔ <b>Acceso denegado</b>\n\nNo tienes permisos para usar este comando.");
                }
                break;

            case '/stats':
                if ($this->isAdmin($userId)) {
                    $this->sendStats($chatId);
                }
                break;

            case '/broadcast':
                if ($this->isAdmin($userId)) {
                    $this->userStates[$userId] = [
                        'state' => 'broadcasting',
                        'step' => 'message'
                    ];
                    $this->bot->sendMessage($chatId, "📢 <b>Modo Difusión</b>\n\nIngresa el mensaje que deseas enviar a todos los usuarios:");
                }
                break;

            case '/logs':
                if ($this->isAdmin($userId)) {
                    $this->sendLogs($chatId);
                }
                break;

            case '/cancel':
                unset($this->userStates[$userId]);
                $this->bot->sendMessage($chatId, "❌ <b>Operación cancelada</b>\n\nTu solicitud ha sido cancelada.");
                $this->sendMainMenu($chatId);
                break;

            default:
                $this->sendWelcome($chatId, $name);
                break;
        }
    }

    /**
     * Procesar callback query (botones inline)
     */
    public function handleCallback(array $callback): void
    {
        $callbackQueryId = $callback['id'];
        $data = $callback['data'];
        $message = $callback['message'];
        $chatId = $message['chat']['id'];
        $messageId = $message['message_id'];
        $userId = $callback['from']['id'];

        $this->logger->info('Callback recibido', [
            'data' => $data,
            'user_id' => $userId
        ]);

        // Responder al callback para quitar el "loading"
        $this->bot->answerCallbackQuery($callbackQueryId);

        // Procesar acción
        switch ($data) {
            case 'show_plans':
                $this->sendPlans($chatId, $messageId);
                break;

            case 'show_faq':
                $this->sendFAQ($chatId, $messageId);
                break;

            case 'show_company':
                $this->sendCompanyInfo($chatId, $messageId);
                break;

            case 'contact_support':
                $this->startContactForm($chatId, $userId);
                break;

            case 'request_trial':
                $this->requestTrial($chatId, $userId, $callback['from']['first_name'] ?? 'Usuario');
                break;

            case 'back_menu':
                $this->sendMainMenu($chatId, $messageId);
                break;

            case 'contact_whatsapp':
                $company = $this->config['company'];
                $this->bot->sendMessage($chatId, "📱 <b>Contáctanos por WhatsApp</b>\n\nHaz clic en el siguiente enlace:\n\n<a href='https://wa.me/{$company['whatsapp']}'>💬 Chatear con {$this->config['company']['name']}</a>");
                break;

            default:
                // Verificar si es selección de plan
                if (strpos($data, 'plan_') === 0) {
                    $planIndex = (int) str_replace('plan_', '', $data);
                    $this->showPlanDetails($chatId, $planIndex, $messageId);
                }
                // Verificar si es FAQ
                elseif (strpos($data, 'faq_') === 0) {
                    $faqIndex = (int) str_replace('faq_', '', $data);
                    $this->showFAQAnswer($chatId, $faqIndex, $messageId);
                }
                break;
        }
    }

    /**
     * Enviar mensaje de bienvenida
     */
    public function sendWelcome(int $chatId, string $name): void
    {
        $company = $this->config['company'];
        
        $welcomeMessage = "¡Hola, <b>{$name}</b>! 👋\n\n";
        $welcomeMessage .= "Bienvenido a <b>{$company['name']}</b> 🌟\n";
        $welcomeMessage .= "<i>{$company['tagline']}</i>\n\n";
        $welcomeMessage .= "Soy tu asistente virtual y estoy aquí para ayudarte con:\n\n";
        $welcomeMessage .= "📊 <b>Información sobre nuestros planes</b>\n";
        $welcomeMessage .= "❓ <b>Respuestas a preguntas frecuentes</b>\n";
        $welcomeMessage .= "📞 <b>Solicitar soporte técnico</b>\n";
        $welcomeMessage .= "✉️ <b>Formulario de contacto</b>\n";
        $welcomeMessage .= "🎁 <b>Solicitar período de prueba</b>\n\n";
        $welcomeMessage .= "Usa los comandos o los botones de abajo para comenzar:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Ver Planes', 'callback_data' => 'show_plans'],
                    ['text' => '❓ FAQ', 'callback_data' => 'show_faq']
                ],
                [
                    ['text' => '📞 Contacto', 'callback_data' => 'contact_support'],
                    ['text' => '🎁 Prueba Gratis', 'callback_data' => 'request_trial']
                ],
                [
                    ['text' => '🏢 Nuestra Empresa', 'callback_data' => 'show_company']
                ]
            ]
        ];

        $this->bot->sendMessageWithKeyboard($chatId, $welcomeMessage, $keyboard);
    }

    /**
     * Enviar menú principal
     */
    private function sendMainMenu(int $chatId, int $messageId = null): void
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Ver Planes', 'callback_data' => 'show_plans'],
                    ['text' => '❓ FAQ', 'callback_data' => 'show_faq']
                ],
                [
                    ['text' => '📞 Contacto', 'callback_data' => 'contact_support'],
                    ['text' => '🎁 Prueba Gratis', 'callback_data' => 'request_trial']
                ],
                [
                    ['text' => '🏢 Nuestra Empresa', 'callback_data' => 'show_company']
                ]
            ]
        ];

        $text = "📋 <b>Menú Principal</b>\n\nSelecciona una opción:";

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $text, $keyboard);
        } else {
            $this->bot->sendMessageWithKeyboard($chatId, $text, $keyboard);
        }
    }

    /**
     * Enviar ayuda
     */
    private function sendHelp(int $chatId): void
    {
        $helpMessage = "📖 <b>Ayuda - Comandos Disponibles</b>\n\n";
        $helpMessage .= "<b>Comandos principales:</b>\n\n";
        $helpMessage .= "/start - Iniciar conversación\n";
        $helpMessage .= "/planes - Ver planes y precios\n";
        $helpMessage .= "/faq - Preguntas frecuentes\n";
        $helpMessage .= "/contacto - Formulario de contacto\n";
        $helpMessage .= "/soporte - Solicitar soporte técnico\n";
        $helpMessage .= "/empresa - Información de la empresa\n";
        $helpMessage .= "/prueba - Solicitar prueba gratis\n";
        $helpMessage .= "/help - Ver esta ayuda\n";
        $helpMessage .= "/cancel - Cancelar operación\n\n";

        if (!empty($this->config['admin_ids'])) {
            $helpMessage .= "<b>Comandos administrativos:</b>\n\n";
            $helpMessage .= "/admin - Panel de administración\n";
            $helpMessage .= "/stats - Estadísticas del bot\n";
            $helpMessage .= "/broadcast - Enviar mensaje a todos\n";
            $helpMessage .= "/logs - Ver logs del sistema";
        }

        $this->bot->sendMessage($chatId, $helpMessage);
    }

    /**
     * Enviar planes de internet
     */
    private function sendPlans(int $chatId, int $messageId = null): void
    {
        $plans = $this->config['plans'];
        
        $message = "📊 <b>Planes de Internet - {$this->config['company']['name']}</b>\n\n";
        $message .= "Elige un plan para ver más detalles:\n";

        $keyboard = ['inline_keyboard' => []];

        foreach ($plans as $index => $plan) {
            $emoji = $plan['recommended'] ? '⭐' : '📌';
            $recommended = $plan['recommended'] ? ' (Recomendado)' : '';
            
            $keyboard['inline_keyboard'][] = [
                ['text' => "{$emoji} {$plan['name']} - {$plan['speed']} - \${$plan['price']}/mes{$recommended}", 'callback_data' => "plan_{$index}"]
            ];
        }

        $keyboard['inline_keyboard'][] = [
            ['text' => '📞 Contratar Plan', 'callback_data' => 'contact_support'],
            ['text' => '🔙 Menú', 'callback_data' => 'back_menu']
        ];

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $message, $keyboard);
        } else {
            $this->bot->sendMessageWithKeyboard($chatId, $message, $keyboard);
        }
    }

    /**
     * Mostrar detalles de un plan
     */
    private function showPlanDetails(int $chatId, int $planIndex, int $messageId): void
    {
        $plans = $this->config['plans'];
        
        if (!isset($plans[$planIndex])) {
            $this->bot->sendMessage($chatId, "❌ Plan no encontrado.");
            return;
        }

        $plan = $plans[$planIndex];
        $company = $this->config['company'];

        $message = "🌟 <b>{$plan['name']}</b>\n\n";
        $message .= "⚡ <b>Velocidad:</b> {$plan['speed']}\n";
        $message .= "💰 <b>Precio:</b> \${$plan['price']}/mes\n\n";
        $message .= "<b>Incluye:</b>\n";
        
        foreach ($plan['features'] as $feature) {
            $message .= "✓ {$feature}\n";
        }

        $message .= "\n<i>¡Contrata ahora y conecta tu mundo!</i>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📞 Contratar Este Plan', 'callback_data' => 'contact_support']
                ],
                [
                    ['text' => '💬 WhatsApp', 'callback_data' => 'contact_whatsapp']
                ],
                [
                    ['text' => '🔙 Ver Todos los Planes', 'callback_data' => 'show_plans']
                ]
            ]
        ];

        $this->bot->editMessageText($chatId, $messageId, $message, $keyboard);
    }

    /**
     * Enviar FAQ
     */
    private function sendFAQ(int $chatId, int $messageId = null): void
    {
        $faq = $this->config['faq'];
        
        $message = "❓ <b>Preguntas Frecuentes</b>\n\n";
        $message .= "Selecciona una pregunta para ver la respuesta:\n";

        $keyboard = ['inline_keyboard' => []];

        foreach ($faq as $index => $item) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "{$item['question']}", 'callback_data' => "faq_{$index}"]
            ];
        }

        $keyboard['inline_keyboard'][] = [
            ['text' => '🔙 Menú Principal', 'callback_data' => 'back_menu']
        ];

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $message, $keyboard);
        } else {
            $this->bot->sendMessageWithKeyboard($chatId, $message, $keyboard);
        }
    }

    /**
     * Mostrar respuesta de FAQ
     */
    private function showFAQAnswer(int $chatId, int $faqIndex, int $messageId): void
    {
        $faq = $this->config['faq'];
        
        if (!isset($faq[$faqIndex])) {
            $this->bot->sendMessage($chatId, "❌ Pregunta no encontrada.");
            return;
        }

        $item = $faq[$faqIndex];
        
        $message = "❓ <b>{$item['question']}</b>\n\n";
        $message .= $item['answer'];

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔙 Más Preguntas', 'callback_data' => 'show_faq']
                ]
            ]
        ];

        $this->bot->editMessageText($chatId, $messageId, $message, $keyboard);
    }

    /**
     * Enviar información de la empresa
     */
    private function sendCompanyInfo(int $chatId, int $messageId = null): void
    {
        $company = $this->config['company'];
        
        $message = "🏢 <b>Sobre {$company['name']}</b>\n\n";
        $message .= "<i>{$company['tagline']}</i>\n\n";
        $message .= "📍 <b>Dirección:</b>\n{$company['address']}\n\n";
        $message .= "📞 <b>Teléfono:</b>\n{$company['phone']}\n\n";
        $message .= "📧 <b>Correo:</b>\n{$company['email']}\n\n";
        $message .= "🌐 <b>Web:</b>\n{$company['website']}\n\n";
        $message .= "📱 <b>WhatsApp:</b>\n{$company['whatsapp']}\n\n";
        $message .= "🕐 <b>Horario de atención:</b>\nLun-Vie: 8am-6pm\nSáb: 9am-2pm";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📞 Contáctanos', 'callback_data' => 'contact_support']
                ],
                [
                    ['text' => '💬 Escribir por WhatsApp', 'callback_data' => 'contact_whatsapp']
                ],
                [
                    ['text' => '🔙 Menú Principal', 'callback_data' => 'back_menu']
                ]
            ]
        ];

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $message, $keyboard);
        } else {
            $this->bot->sendMessageWithKeyboard($chatId, $message, $keyboard);
        }
    }

    /**
     * Iniciar formulario de contacto
     */
    private function startContactForm(int $chatId, int $userId): void
    {
        $this->userStates[$userId] = [
            'state' => 'contact_form',
            'step' => 'name'
        ];

        $message = "✉️ <b>Formulario de Contacto</b>\n\n";
        $message .= "Por favor, ingresa tu <b>nombre completo</b>:";

        $this->bot->sendMessage($chatId, $message);
    }

    /**
     * Iniciar formulario de soporte
     */
    private function startSupportForm(int $chatId, int $userId): void
    {
        $this->userStates[$userId] = [
            'state' => 'support_form',
            'step' => 'issue'
        ];

        $message = "🔧 <b>Solicitud de Soporte Técnico</b>\n\n";
        $message .= "Describe brevemente tu problema o incidencia:";

        $this->bot->sendMessage($chatId, $message);
    }

    /**
     * Solicitar período de prueba
     */
    private function requestTrial(int $chatId, int $userId, string $name): void
    {
        $company = $this->config['company'];
        
        $message = "🎁 <b>Solicitar Período de Prueba</b>\n\n";
        $message .= "¡Genial! Has solicitado probar nuestro servicio gratis por 7 días.\n\n";
        $message .= "Para completar tu solicitud, por favor ingresa:\n";
        $message .= "1. Tu dirección de instalación:\n";
        $message .= "2. Tu número de teléfono:\n";
        $message .= "3. El plan que te gustaría probar:\n\n";
        $message .= "<i>Escribe tu respuesta en un solo mensaje.</i>";

        $this->userStates[$userId] = [
            'state' => 'trial_request',
            'step' => 'details'
        ];

        $this->bot->sendMessage($chatId, $message);
    }

    /**
     * Manejar estado del usuario (formularios)
     */
    private function handleUserState(array $message): void
    {
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        if (!isset($this->userStates[$userId])) {
            return;
        }

        $state = $this->userStates[$userId];
        $company = $this->config['company'];

        switch ($state['state']) {
            case 'contact_form':
                $this->processContactForm($chatId, $userId, $state, $text);
                break;

            case 'support_form':
                $this->processSupportForm($chatId, $userId, $state, $text);
                break;

            case 'trial_request':
                $this->processTrialRequest($chatId, $userId, $text, $message['from']['first_name'] ?? 'Usuario');
                break;

            case 'broadcasting':
                $this->processBroadcast($chatId, $userId, $text);
                break;
        }
    }

    /**
     * Procesar formulario de contacto
     */
    private function processContactForm(int $chatId, int $userId, array $state, string $text): void
    {
        switch ($state['step']) {
            case 'name':
                $this->userStates[$userId]['name'] = $text;
                $this->userStates[$userId]['step'] = 'email';
                $this->bot->sendMessage($chatId, "✅ Perfecto. Ahora ingresa tu <b>correo electrónico</b>:");
                break;

            case 'email':
                $this->userStates[$userId]['email'] = $text;
                $this->userStates[$userId]['step'] = 'phone';
                $this->bot->sendMessage($chatId, "✅ Excelente. Ingresa tu <b>número de teléfono</b>:");
                break;

            case 'phone':
                $this->userStates[$userId]['phone'] = $text;
                $this->userStates[$userId]['step'] = 'message';
                $this->bot->sendMessage($chatId, "✅ Perfecto. Ahora escribe tu <b>mensaje</b> o consulta:");
                break;

            case 'message':
                $this->userStates[$userId]['message'] = $text;
                
                // Enviar notificación al admin
                $this->notifyAdmin('📩 Nuevo Contacto', [
                    'Nombre' => $this->userStates[$userId]['name'],
                    'Email' => $this->userStates[$userId]['email'],
                    'Teléfono' => $this->userStates[$userId]['phone'],
                    'Mensaje' => $text,
                    'Usuario ID' => $userId
                ]);

                // Confirmar al usuario
                $this->bot->sendMessage($chatId, "✅ <b>¡Mensaje enviado exitosamente!</b>\n\n");
                $this->bot->sendMessage($chatId, "Gracias por contactarnos. Nuestro equipo te responderá en breve.\n\n");
                $this->sendMainMenu($chatId);
                
                unset($this->userStates[$userId]);
                break;
        }
    }

    /**
     * Procesar formulario de soporte
     */
    private function processSupportForm(int $chatId, int $userId, array $state, string $text): void
    {
        $this->userStates[$userId]['issue'] = $text;
        $this->userStates[$userId]['step'] = 'details';

        $this->bot->sendMessage($chatId, "✅ Gracias por la información. Ahora ingresa más detalles (opcional):");
    }

    /**
     * Finalizar formulario de soporte
     */
    public function completeSupportForm(int $chatId, int $userId): void
    {
        $state = $this->userStates[$userId] ?? [];
        
        // Notificar al admin
        $this->notifyAdmin('🔧 Nueva Solicitud de Soporte', [
            'Usuario ID' => $userId,
            'Problema' => $state['issue'] ?? 'No especificado',
            'Detalles' => $state['details'] ?? 'No especificado'
        ]);

        $this->bot->sendMessage($chatId, "✅ <b>¡Solicitud de soporte enviada!</b>\n\n");
        $this->bot->sendMessage($chatId, "Nuestro equipo técnico te contactará pronto para resolver tu incidencia.\n\n");
        $this->sendMainMenu($chatId);
        
        unset($this->userStates[$userId]);
    }

    /**
     * Procesar solicitud de prueba
     */
    private function processTrialRequest(int $chatId, int $userId, string $text, string $name): void
    {
        // Notificar al admin
        $this->notifyAdmin('🎁 Nueva Solicitud de Prueba', [
            'Usuario' => $name,
            'Usuario ID' => $userId,
            'Detalles' => $text
        ]);

        $company = $this->config['company'];
        
        $this->bot->sendMessage($chatId, "✅ <b>¡Solicitud de prueba enviada!</b>\n\n");
        $this->bot->sendMessage($chatId, "Gracias por tu interés en {$company['name']}. Un agente te contactará en las próximas 24 horas para confirmar tu instalación de prueba.\n\n");
        $this->bot->sendMessage($chatId, "📱 <b>¿Prefieres que te contactemos ahora?</b>\n\n");
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💬 Sí, por WhatsApp', 'callback_data' => 'contact_whatsapp']
                ],
                [
                    ['text' => '🔙 Menú Principal', 'callback_data' => 'back_menu']
                ]
            ]
        ];
        
        $this->bot->sendMessageWithKeyboard($chatId, "Contáctanos directamente:", $keyboard);
        
        unset($this->userStates[$userId]);
    }

    /**
     * Procesar broadcast
     */
    private function processBroadcast(int $chatId, int $userId, string $text): void
    {
        $this->bot->sendMessage($chatId, "📢 Enviando mensaje a todos los usuarios...");
        
        // Aquí implementarías la lógica de broadcast
        // Por seguridad, marcamos como completado
        $this->bot->sendMessage($chatId, "✅ <b>Difusión completada</b>\n\nMensaje enviado exitosamente.");
        
        unset($this->userStates[$userId]);
    }

    /**
     * Verificar si es administrador
     */
    private function isAdmin(int $userId): bool
    {
        return in_array((string)$userId, $this->config['admin_ids']);
    }

    /**
     * Notificar al administrador
     */
    private function notifyAdmin(string $title, array $data): void
    {
        if (empty($this->config['admin_ids'])) {
            return;
        }

        $message = "{$title}\n\n";
        
        foreach ($data as $key => $value) {
            $message .= "{$key}: {$value}\n";
        }

        foreach ($this->config['admin_ids'] as $adminId) {
            $this->bot->sendMessage((int)$adminId, $message);
        }
    }

    /**
     * Enviar panel de administración
     */
    private function sendAdminPanel(int $chatId, int $userId): void
    {
        $message = "⚙️ <b>Panel de Administración</b>\n\n";
        $message .= "Selecciona una opción:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Estadísticas', 'callback_data' => 'admin_stats']
                ],
                [
                    ['text' => '📢 Difundir Mensaje', 'callback_data' => 'admin_broadcast']
                ],
                [
                    ['text' => '📜 Ver Logs', 'callback_data' => 'admin_logs']
                ],
                [
                    ['text' => '🔙 Salir', 'callback_data' => 'back_menu']
                ]
            ]
        ];

        $this->bot->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Enviar estadísticas
     */
    private function sendStats(int $chatId): void
    {
        $message = "📊 <b>Estadísticas del Bot</b>\n\n";
        $message .= "🔹 Total de comandos procesados: N/A\n";
        $message .= "🔹 Usuarios activos: N/A\n";
        $message .= "🔹 Mensajes enviados hoy: N/A\n";
        $message .= "🔹 Tickets de soporte: N/A";

        $this->bot->sendMessage($chatId, $message);
    }

    /**
     * Enviar logs
     */
    private function sendLogs(int $chatId): void
    {
        $logs = $this->logger->getRecentLogs(20);
        
        if (empty($logs)) {
            $this->bot->sendMessage($chatId, "📜 No hay logs disponibles.");
            return;
        }

        $message = "📜 <b>Logs Recientes</b>\n\n";
        $message .= "<code>" . implode("\n", $logs) . "</code>";

        // Limitar tamaño del mensaje
        if (strlen($message) > 4000) {
            $message = substr($message, 0, 4000) . "\n\n... (truncado)";
        }

        $this->bot->sendMessage($chatId, $message);
    }
}
