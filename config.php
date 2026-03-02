<?php
/**
 * Configuración del Bot de Telegram para Starlink Net
 * 
 * Este archivo contiene todas las configuraciones necesarias para el funcionamiento
 * del asistente de Telegram. Las variables sensitive se manejan mediante entorno.
 * 
 * @author Starlink Net
 * @version 1.0.0
 */

// Cargar variables de entorno si no están definidas (para desarrollo local)
if (!getenv('BOT_TOKEN') && !isset($_ENV['BOT_TOKEN'])) {
    // Cargar desde archivo .env si existe
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!getenv($key)) {
                    putenv("$key=$value");
                }
            }
        }
    }
}

// Función helper para obtener variables de entorno
function getEnvVar(string $key, $default = '') {
    // Primero buscar en $_ENV
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    // Luego en getenv() (funciona en CLI y algunos entornos)
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    // Valor por defecto
    return $default;
}

/**
 * Configuración principal del Bot
 */
return [
    // Token del Bot de Telegram (obtenido de @BotFather)
    // IMPORTANTE: En Render.com, configura esta variable en el panel del servicio
    'bot_token' => getEnvVar('BOT_TOKEN', ''),
    
    // URL base de la API de Telegram
    'api_url' => 'https://api.telegram.org',
    
    // Nombre del bot
    'bot_name' => 'Starlink Net',
    
    // Username del bot en Telegram
    'bot_username' => 'asistenteprueba2026_bot',
    
    // URL del webhook (configurada automáticamente)
    'webhook_url' => getEnvVar('WEBHOOK_URL', ''),
    
    // Modo de desarrollo (true para mostrar errores detallados)
    // En producción (Render), usar 'false'
    'debug' => filter_var(getEnvVar('DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    
    // Configuración de seguridad
    'admin_ids' => array_filter(
        array_map(
            'trim',
            explode(',', getEnvVar('ADMIN_IDS', ''))
        )
    ),
    
    // Configuración de la empresa
    'company' => [
        'name' => 'Starlink Net',
        'tagline' => 'Conectando tu mundo a la velocidad de la luz',
        'email' => getEnvVar('COMPANY_EMAIL', 'soporte@starlinknet.com'),
        'phone' => getEnvVar('COMPANY_PHONE', '+58 412-1234567'),
        'website' => getEnvVar('COMPANY_WEBSITE', 'https://starlinknet.com'),
        'address' => getEnvVar('COMPANY_ADDRESS', 'Caracas, Venezuela'),
        'whatsapp' => getEnvVar('COMPANY_WHATSAPP', '+58 412-1234567'),
    ],
    
    // Planes de internet
    'plans' => [
        [
            'name' => 'Básico',
            'speed' => '10 Mbps',
            'price' => 15.00,
            'features' => ['Navegación básica', 'Correo electrónico', 'Redes sociales'],
            'recommended' => false
        ],
        [
            'name' => 'Hogar',
            'speed' => '30 Mbps',
            'price' => 25.00,
            'features' => ['Streaming HD', 'Videollamadas', 'Múltiples dispositivos', 'Gaming casual'],
            'recommended' => true
        ],
        [
            'name' => 'Negocio',
            'speed' => '50 Mbps',
            'price' => 45.00,
            'features' => ['IP Fija', 'Soporte prioritario', 'Garantía 99.9%', 'Servidores'],
            'recommended' => false
        ],
        [
            'name' => 'Empresarial',
            'speed' => '100 Mbps',
            'price' => 80.00,
            'features' => ['IP Fija dedicada', 'Soporte 24/7', 'SLA garantizado', 'Backup automático'],
            'recommended' => false
        ]
    ],
    
    // Preguntas frecuentes
    'faq' => [
        [
            'question' => '¿Cómo puedo contratar un plan?',
            'answer' => "Puedes contratar un plan de las siguientes maneras:\n\n📱 Escribiéndonos por WhatsApp\n📧 Envíando un correo a notre@starlinknet.com\n📞 Llamando a nuestro centro de atención\n💬 Usando el comando /contacto en este bot"
        ],
        [
            'question' => '¿Cuál es el tiempo de instalación?',
            'answer' => "El tiempo de instalación depende del plan contratado:\n\n⚡ Internet Básico: 24-48 horas\n🏠 Internet Hogar: 24-72 horas\n🏢 Internet Negocio: 3-5 días\n🏢 Internet Empresarial: 5-7 días"
        ],
        [
            'question' => '¿Tienen servicio técnico?',
            'answer' => "¡Sí! Contamos con servicio técnico disponible 24/7 para todos nuestros clientes. Puedes:\n\n📞 Llamar al soporte\n💬 Usar /soporte en este bot\n📧 Enviar correo a soporte@starlinknet.com"
        ],
        [
            'question' => '¿Qué métodos de pago aceptan?',
            'answer' => "Aceptamos los siguientes métodos de pago:\n\n💵 Efectivo\n🏦 Transferencias bancarias\n💳 Tarjetas de crédito/débito\n📱 Pago móvil\n🪙 Criptomonedas (Bitcoin, Ethereum)"
        ],
        [
            'question' => '¿Tienen período de prueba?',
            'answer' => "¡Sí! Ofrecemos 7 días de prueba gratis para que puedas experimentar nuestro servicio sin compromiso. Usa el comando /prueba para solicitar tu período de prueba."
        ],
        [
            'question' => '¿La velocidad es garantizada?',
            'answer' => "Nuestras velocidades son nominales. En horas pico puedes experimentar variaciones menores. Los planes Negocio y Empresarial incluyen garantía de velocidad mediante SLA."
        ]
    ],
    
    // Rutas del proyecto
    'paths' => [
        'logs' => __DIR__ . '/logs',
        'data' => __DIR__ . '/data',
    ],
    
    // Configuración de logs
    'logging' => [
        'enabled' => true,
        'level' => getEnvVar('LOG_LEVEL', 'info'),
        'max_files' => 30,
        'max_size' => '10M'
    ]
];
