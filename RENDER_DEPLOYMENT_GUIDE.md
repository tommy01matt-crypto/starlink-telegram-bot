# Guía Completa para Desplegar un Bot en Render.com

Esta guía te proporciona todo lo necesario para desplegar tu bot de Telegram en Render.com, desde la comprensión de la plataforma hasta las mejores prácticas de optimización.

---

## 1. ¿Qué es Render.com?

Render.com es una plataforma de nube unificada que ofrece hosting para aplicaciones web, bases de datos, servicios background y tareas programadas. Fue fundada en 2019 y se ha convertido en una opción popular por su facilidad de uso y tier gratuito generoso.

### Características Principales

- **Despliegue automático** desde GitHub, GitLab o Bitbucket
- **SSL gratuito** para todos los dominios
- **CDN integrado** para contenido estático
- **Bases de datos gestionadas** (PostgreSQL, Redis, MySQL)
- **Escalado automático** en planes de pago

---

## 2. Tipos de Servicios en Render.com

Render.com ofrece diferentes tipos de servicios, cada uno diseñado para casos de uso específicos:

### 2.1 Web Services

Los **Web Services** son el tipo más común y adequado para aplicaciones HTTP que necesitan responder a peticiones.

**Características:**
- Expuestos a través de una URL pública HTTPS
- Soportan cualquier lenguaje y framework
- Ideal para APIs REST, aplicaciones web, bots con webhook
- Compatible con HTTP/2 y WebSockets

**Casos de uso para tu bot:**
- Bot con webhook de Telegram (recibe actualizaciones vía HTTP POST)
- API REST para comandos del bot
- Panel de administración web

### 2.2 Background Workers

Los **Background Workers** son procesos de larga duración que no exposed una URL pública pero pueden ejecutarse continuamente.

**Características:**
- No tienen URL pública (solo acceso interno)
- Se ejecutan constantemente una vez iniciados
- Ideales para procesamiento de colas, tareas asíncronas
- Se comunican con otros servicios vía Redis, RabbitMQ, o base de datos

**Casos de uso para tu bot:**
- Bot que hace polling continuo a la API de Telegram
- Procesamiento de mensajes en cola
- Tareas de mantenimiento programadas

### 2.3 Cron Jobs

Los **Cron Jobs** son tareas programadas que se ejecutan en intervalos específicos.

**Características:**
- Se ejecutan en horarios definidos (formato cron)
- No permanecen corriendo entre ejecuciones
- Útiles para tareas periódicas
- Costo por ejecución (tienen límites gratuitos)

**Casos de uso para tu bot:**
- Envío de digest diario/semanal
- Limpieza de datos antiguos
- Sincronización periódica con APIs externas

### 2.4 Private Services

Los **Private Services** son similares a Web Services pero sin exposición pública.

**Características:**
- Solo accesibles desde otros servicios de Render
- Útiles para microservicios internos
- No tienen URL pública

---

## 3. Modelo de Precios de Render.com

### 3.1 Plan Gratuito (Free Tier)

El plan gratuito de Render es uno de los más generosos entre las plataformas cloud:

| Recurso | Límite |
|---------|--------|
| **Web Services** | 750 horas/mes |
| **Cron Jobs** | 500 ejecuciones/mes |
| **Databases** | 1 base de datos gratuita (PostgreSQL o Redis) |
| **Ancho de banda** | 100 GB/mes |
| **SSL** | Incluido |
| **Certificados** | Automáticos |

**Importante:** El tiempo gratuito se renueva mensualmente y cualquier hora no usada no se acumula.

### 3.2 Límites de Recursos en Plan Gratuito

| Recurso | Límite en Free |
|---------|---------------|
| **RAM** | 512 MB (Web Services), 256 MB (Workers) |
| **CPU** | Comparte 0.1 vCPU (limitado a ~0.5% de un núcleo) |
| **Disco** | 1 GB para servicios, 1 GB para databases |
| **Build time** | 500 minutos/mes |
| **Sleep** | Los servicios duermen después de 15 minutos de inactividad |

---

## 4. ¿Es Posible Mantener el Bot 24/7 en el Plan Gratuito?

### 4.1 El Problema del Inactivity Sleep

**Sí, es posible pero con limitaciones importantes.** Render.com pone a dormir los servicios gratuitos después de **15 minutos de inactividad** (sin peticiones HTTP entrantes). Esto significa:

- **Si usas webhook:** Tu bot responderá normalmente cuando Telegram envíe actualizaciones
- **Si usas polling:** Tu bot se "duermirá" después de 15 minutos sin mensajes

### 4.2 Estrategias para Mantener el Bot Activo

#### Opción 1: Usar Webhook (Recomendado)

Esta es la **estrategia óptima** para bots de Telegram:

```
Telegram Server → Webhook → Tu Bot en Render
                    ↓
              (Siempre activo cuando recibe requests)
```

**Ventajas:**
- El servicio solo "despierta" cuando hay actividad
- No consume recursos cuando no hay mensajes
- Totalmente compatible con el plan gratuito
- Más eficiente y escalable

#### Opción 2: Health Check Automático

Si necesitas polling o un servicio siempre activo, implementa un health check:

```php
// Ejemplo de endpoint de health check en PHP
// En public/index.php o un archivo separado

if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'timestamp' => time()]);
    exit;
}
```

**Servicios de ping externos gratuitos:**
- [UptimeRobot](https://uptimerobot.com) - 50 monitors gratis
- [Cron-job.org](https://cron-job.org) - Ilimitados para HTTP
- [Healthchecks.io](https://healthchecks.io) - 3 checks gratis
- [Pingly](https://pingly.com) - 10 checks gratis

**Configuración recomendada:**
- Intervalo: Cada 5-10 minutos (más frecuente = menos tiempo durmiendo)
- URL: `https://tu-servicio.onrender.com/health`

#### Opción 3: Combinación Polling + Health Check

```php
// Estructura básica para polling con health check
// En tu archivo principal del bot

$bot = new TelegramBot($token);

// Health check endpoint
if (isset($_GET['health'])) {
    echo json_encode(['status' => 'running']);
    exit;
}

// Modo polling con manejo de errores
while (true) {
    try {
        $updates = $bot->getUpdates();
        foreach ($updates as $update) {
            $bot->processUpdate($update);
        }
    } catch (Exception $e) {
        logError("Error en polling: " . $e->getMessage());
    }
    sleep(2); // Espera entre peticiones
}
```

---

## 5. Configuración del Proyecto para Render.com

### 5.1 Preparación del Código

Tu proyecto ya tiene una estructura compatible. Los archivos clave son:

```
asisstentetelegram/
├── Dockerfile          # Contenedor Docker
├── composer.json       # Dependencias PHP
├── Procfile            # Comando de inicio para Render
├── public/
│   └── index.php       # Entry point para webhook
└── src/
    └── ...             # Código del bot
```

### 5.2 Dockerfile Recomendado

```dockerfile
# Usar PHP 8.2 con Apache
FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql json

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos
COPY . .

# Permisos
RUN chmod -R 755 /var/www/html \
    && chown -R www-data:www-data /var/www/html

# Exponer puerto 80
EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]
```

### 5.3 Procfile para Webhook

```procfile
web: php -S 0.0.0.0:$PORT -t public
```

### 5.4 Configuración de index.php para Webhook

Tu `public/index.php` debe manejar el webhook de Telegram:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$botToken = getenv('TELEGRAM_BOT_TOKEN');
$secret = getenv('WEBHOOK_SECRET');

// Health check endpoint
if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'service' => 'telegram-bot',
        'timestamp' => time()
    ]);
    exit;
}

// Verificar secreto del webhook (seguridad)
if ($secret && ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '') !== $secret) {
    http_response_code(403);
    exit('Forbidden');
}

// Procesar actualización de Telegram
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    // Tu lógica de procesamiento del bot
    $bot = new \App\Bot\TelegramBot($botToken);
    $bot->processUpdate($update);
}

http_response_code(200);
```

### 5.5 Configuración de Variables de Entorno

En Render.com, configura estas variables en el panel del servicio:

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `TELEGRAM_BOT_TOKEN` | Token de tu bot de BotFather | `123456789:ABCdefGHIjklMNOpqrsTUVwxyz` |
| `WEBHOOK_SECRET` | Secreto para verificar requests (recomendado) | `mi_secreto_seguro_123` |
| `APP_ENV` | Entorno de la aplicación | `production` |
| `LOG_LEVEL` | Nivel de logging | `info` |

---

## 6. Conexión con GitHub

### 6.1 Pasos para Conectar

1. **Inicia sesión en Render.com** con tu cuenta de GitHub
2. **Autoriza a Render** acceder a tus repositorios
3. **Crea un nuevo servicio**:
   - Dashboard → "New" → "Web Service"
   - Selecciona tu repositorio
   - Configura las opciones básicas

### 6.2 Configuración del Servicio

```
Name: telegram-bot
Region: Oregon (o la más cercana)
Branch: main
Build Command: composer install --no-dev --optimize-autoloader
Start Command: php -S 0.0.0.0:$PORT -t public
```

### 6.3 Usando render.yaml (Configuración como Código)

Puedes definir tu servicio en un archivo `render.yaml` para despliegue automático:

```yaml
services:
  - type: web
    name: telegram-bot
    env: docker
    region: oregon
    repo: https://github.com/tu-usuario/tu-repositorio
    branch: main
    buildCommand: composer install --no-dev --optimize-autoloader
    startCommand: php -S 0.0.0.0:$PORT -t public
    envVars:
      - key: TELEGRAM_BOT_TOKEN
        sync: false
      - key: WEBHOOK_SECRET
        sync: false
      - key: APP_ENV
        value: production
    autoDeploy: true
```

---

## 7. Límites y Restricciones del Plan Gratuito

### 7.1 Recursos de Computación

| Recurso | Límite |
|---------|--------|
| **CPU** | ~0.1 vCPU (compartido, rendimiento limitado) |
| **RAM** | 512 MB máximo |
| **Tiempo de ejecución** | 750 horas/mes |
| **Build time** | 500 minutos/mes |

**Nota:** El CPU del plan gratuito puede ser lento. Para un bot de Telegram con webhook, esto generalmente no es un problema ya que el procesamiento es mínimo por request.

### 7.2 Comportamiento de Sleep

```
┌─────────────────────────────────────────────────────────────┐
│                    PLAN Gratuito                            │
├─────────────────────────────────────────────────────────────┤
│  15 minutos sin actividad → Servicio se duerme              │
│  Primer request después de sleep → ~30-60 segundos extra   │
│  Wake up automático en cada request                        │
└─────────────────────────────────────────────────────────────┘
```

### 7.3 Cuotas de Construcción

| Recurso | Límite |
|---------|--------|
| **Builds mensuales** | Ilimitados |
| **Tiempo de build** | 500 minutos/mes |
| **Timeout de build** | 30 minutos |

---

## 8. Alternativas de Pago

### 8.1 Plan Starter

**Precio:** $7/mensual por servicio

| Recurso | Límite |
|---------|--------|
| RAM | 512 MB - 2.5 GB |
| CPU | 0.5 - 2 vCPUs |
| Sleep | Nunca (siempre activo) |
| Builds | 1,000 minutos/mes |
| Soporte | Email |

### 8.2 Plan Pro

**Precio:** $25/mensual por servicio

| Recurso | Límite |
|---------|--------|
| RAM | 2 - 14 GB |
| CPU | 1 - 4 vCPUs |
| Auto-scaling | Configurable |
| Builds | Ilimitados |
| Soporte | Prioritario |

### 8.3 Recomendación para 24/7

Si necesitas **uptime garantizado 24/7** sin preocupaciones:

- **Opción económica:** Plan Starter ($7/mes) - Siempre activo
- **Opción profesional:** Plan Pro con auto-scaling ($25+/mes)

---

## 9. Configuración del Webhook de Telegram

### 9.1 Establecer Webhook

Una vez desplegado tu servicio, configura el webhook:

```bash
curl -X POST "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook" \
  -d "url=https://tu-servicio.onrender.com/webhook" \
  -d "secret_token=tu_secreto"
```

### 9.2 Verificar Webhook

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getWebhookInfo"
```

### 9.3 Eliminar Webhook (si es necesario)

```bash
curl -X POST "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/deleteWebhook"
```

---

## 10. Mejores Prácticas y Optimización

### 10.1 Optimización de Rendimiento

```php
// Usa Composer con optimización de autoloader
// En composer.json:
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true
    }
}
```

### 10.2 Manejo de Errores

```php
// Implementa manejo de errores robusto
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logError("Fatal: " . $error['message']);
    }
});
```

### 10.3 Logging Eficiente

```php
// Usa un sistema de logging apropiado
// Ejemplo con Monolog (ya incluido vía Composer)

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('telegram-bot');
$log->pushHandler(new StreamHandler(
    __DIR__ . '/../logs/app.log',
    Logger::INFO
));

// En producción, considera enviar logs a un servicio externo
// como Papertrail, Loggly, o simplemente a stderr
```

### 10.4 Configuración de PHP para Producción

```ini
; php.ini - Configuración para producción
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
log_errors = On
memory_limit = 256M
max_execution_time = 30
```

### 10.5 Seguridad

```php
// 1. Valida siempre el secreto del webhook
$secret = getenv('WEBHOOK_SECRET');
$tokenHeader = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';

if (!hash_equals($secret, $tokenHeader)) {
    http_response_code(403);
    exit('Unauthorized');
}

// 2. Limita el acceso a endpoints sensibles
// Usa autenticación adicional para paneles de admin

// 3. No expongas información sensible en logs
```

---

## 11. Monitoreo y Diagnóstico

### 11.1 Ver Logs en Render

```bash
# Usando CLI de Render
render logs -s telegram-bot

# O desde el dashboard
# Dashboard → Tu servicio → Logs
```

### 11.2 Métricas Importantes

| Métrica | Qué观察 | Alerta si |
|---------|--------|-----------|
| Response Time | Tiempo de respuesta | > 5 segundos |
| Memory Usage | Uso de RAM | > 400 MB (80%) |
| CPU Usage | Uso de CPU | > 80% sostenido |
| Error Rate | Porcentaje de errores | > 1% |

### 11.3 Health Check Robusto

```php
// Health check que verifica componentes críticos
if ($_SERVER['REQUEST_URI'] === '/health') {
    $health = [
        'status' => 'ok',
        'timestamp' => time(),
        'checks' => []
    ];
    
    // Verificar conexión a base de datos (si aplica)
    try {
        // $pdo->query("SELECT 1");
        $health['checks']['database'] = 'ok';
    } catch (Exception $e) {
        $health['checks']['database'] = 'error';
        $health['status'] = 'degraded';
    }
    
    // Verificar variables de entorno críticas
    $required = ['TELEGRAM_BOT_TOKEN'];
    foreach ($required as $var) {
        if (!getenv($var)) {
            $health['checks']['env'] = 'error';
            $health['status'] = 'degraded';
        }
    }
    
    $code = $health['status'] === 'ok' ? 200 : 503;
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($health);
    exit;
}
```

---

## 12. Comparativa: Webhook vs Polling

| Aspecto | Webhook | Polling |
|---------|---------|---------|
| **Recursos** | Bajo (solo cuando hay actividad) | Alto (siempre corriendo) |
| **Plan gratuito** | ✅ Ideal | ❌ Problemas de sleep |
| **Latencia** | Instantánea | 1-30 segundos |
| **Complejidad** | Media ( necesita HTTPS) | Baja |
| **Escalabilidad** | Excelente | Limitada |
| **Comandos/24h** | Ilimitados | Limitados por rate limits |

**Recomendación:** Usa **webhook** para el plan gratuito de Render.

---

## 13. Resumen: Configuración Recomendada

```
┌─────────────────────────────────────────────────────────────┐
│              CONFIGURACIÓN ÓPTIMA PARA FREE                │
├─────────────────────────────────────────────────────────────┤
│  Tipo de servicio:     Web Service                          │
│  Entorno:              Docker (o PHP Básico)                │
│  Comando de inicio:    php -S 0.0.0.0:$PORT -t public       │
│  Health check:         Implementado en /health              │
│  Webhook de Telegram:  https://tu-servicio.onrender.com    │
│  Ping externo:         UptimeRobot (5 min interval)        │
│  Variables:            TELEGRAM_BOT_TOKEN, WEBHOOK_SECRET  │
└─────────────────────────────────────────────────────────────┘
```

---

## 14. Solución de Problemas Comunes

### 14.1 El servicio no responde

1. Verifica los logs en el dashboard de Render
2. Confirma que el contenedor está corriendo
3. Verifica las variables de entorno
4. Prueba el health check manualmente

### 14.2 Build falla

1. Revisa `composer.json` tiene sintaxis correcta
2. Verifica que las dependencias son compatibles con PHP 8.x
3. Aumenta el timeout de build si es necesario

### 14.3 Webhook no funciona

1. Confirma que la URL de webhook es accesible públicamente
2. Verifica que el certificado SSL está vigente
3. Prueba con `getWebhookInfo` para ver el estado

### 14.4 El bot se "duerme" frecuentemente

1. Implementa health check en `/health`
2. Configura UptimeRobot para hacer ping cada 5 minutos
3. Considera cambiar a webhook si usas polling

---

## 15. Conclusión

Render.com es una excelente opción para desplegar bots de Telegram de forma gratuita. Con la configuración correcta (webhook + health check), puedes mantener tu bot funcionando efectivamente en el plan gratuito. Las limitaciones principales son:

- **15 minutos de inactividad** → Mitigado con health check
- **Recursos limitados** → Suficiente para bots de Telegram
- **No hay uptime garantizado** → Aceptable para uso personal

Si necesitas disponibilidad 24/7 garantizada, el plan Starter de $7/mes es la opción más económica.

---

*Esta guía fue creada para el proyecto asisstentetelegram - Bot de Asistencia para Telegram*
