# 🤖 Bot de Telegram - Starlink Net

Asistente virtual de Telegram para **Starlink Net**, agencia de internet. Este bot proporciona información sobre planes, soporte técnico, precios y contacto para la empresa.

## 📋 Características

- ✅ **Menú de bienvenida** personalizado para nuevos clientes
- ✅ **Respuestas automáticas** a preguntas frecuentes
- ✅ **Información de planes y precios** detallada
- ✅ **Formulario de contacto** para solicitudes
- ✅ **Sistema de soporte técnico** integrado
- ✅ **Panel administrativo** con comandos especiales
- ✅ **Logs de actividad** para monitoreo
- ✅ **Manejo robusto de errores**
- ✅ **Desplegable en Railway** con configuración optimizada

## 🛠️ Comandos Disponibles

| Comando | Descripción |
|---------|-------------|
| `/start` | Iniciar conversación con el bot |
| `/planes` | Ver planes y precios de internet |
| `/faq` | Preguntas frecuentes |
| `/contacto` | Formulario de contacto |
| `/soporte` | Solicitar soporte técnico |
| `/empresa` | Información de la empresa |
| `/prueba` | Solicitar período de prueba gratis |
| `/help` | Ver todos los comandos |

## 🚀 Despliegue en Railway

### Prerrequisitos

1. Cuenta en [Railway](https://railway.app)
2. Cuenta en [Telegram](https://telegram.org)
3. Bot de Telegram creado con @BotFather
4. Git instalado localmente

### Paso 1: Preparar el Repositorio

```bash
# Clonar el repositorio (o usar el código existente)
git clone https://github.com/tu-usuario/starlink-telegram-bot.git
cd starlink-telegram-bot
```

### Paso 2: Configurar Variables de Entorno

En Railway, debes configurar las siguientes variables de entorno:

1. Inicia sesión en [Railway Dashboard](https://railway.app/dashboard)
2. Crea un nuevo proyecto o selecciona uno existente
3. Ve a la pestaña **"Variables"** (Variables)
4. Agrega las siguientes variables:

| Variable | Valor | Descripción |
|----------|-------|-------------|
| `BOT_TOKEN` | `8771808408:AAHeTNKJFT3TUGJpWLg0C66PK021Ncfi4dk` | Token de tu bot |
| `WEBHOOK_URL` | (se configura automáticamente) | URL del webhook |
| `DEBUG` | `false` | Modo de depuración |
| `LOG_LEVEL` | `info` | Nivel de logs |
| `ADMIN_IDS` | (tu ID de Telegram) | IDs de administradores |

### Paso 3: Desplegar en Railway

#### Opción A: Desde GitHub

1. Sube el código a un repositorio GitHub
2. En Railway, selecciona **"New Project"** → **"Deploy from GitHub repo"**
3. Selecciona tu repositorio
4. Railway detectará automáticamente el `Procfile`

#### Opción B: Desde CLI de Railway

```bash
# Instalar CLI de Railway
npm install -g @railway/cli

# Iniciar sesión
railway login

# Inicializar proyecto
railway init

# Desplegar
railway up
```

### Paso 4: Configurar el Webhook

Una vez desplegado, Railway te proporcionará una URL (por ejemplo: `https://tu-proyecto.up.railway.app`).

Necesitas configurar el webhook de Telegram usando esa URL:

```bash
# Reemplaza TU_URL_RAILWAY con tu URL real
curl -X POST "https://api.telegram.org/bot8771808408:AAHeTNKJFT3TUGJpWLg0C66PK021Ncfi4dk/setWebhook" \
     -d "url=https://tu-proyecto.up.railway.app"
```

O alternativamente, visita este enlace en tu navegador:
```
https://api.telegram.org/bot8771808408:AAHeTNKJFT3TUGJpWLg0C66PK021Ncfi4dk/setWebhook?url=https://tu-proyecto.up.railway.app
```

### Paso 5: Verificar el Webhook

```bash
curl "https://api.telegram.org/bot8771808408:AAHeTNKJFT3TUGJpWLg0C66PK021Ncfi4dk/getWebhookInfo"
```

Deberías ver una respuesta como:
```json
{
  "ok": true,
  "result": {
    "url": "https://tu-proyecto.up.railway.app",
    "has_custom_certificate": false,
    "pending_update_count": 0,
    "max_connections": 40,
    "allow_unauthorized": false
  }
}
```

## 📁 Estructura del Proyecto

```
starlink-telegram-bot/
├── .env.example          # Ejemplo de variables de entorno
├── composer.json         # Dependencias de PHP
├── config.php            # Configuración principal
├── Procfile             # Configuración de Railway
├── README.md            # Este archivo
├── public/
│   └── index.php        # Punto de entrada del webhook
├── src/
│   ├── Bot/
│   │   └── TelegramBot.php      # Clase principal del bot
│   ├── Commands/
│   │   └── CommandsHandler.php  # Manejador de comandos
│   └── Services/
│       └── Logger.php           # Sistema de logs
└── logs/               # Archivos de logs (se crea automáticamente)
```

## 🔧 Comandos Administrativos

Si configuras tu ID de Telegram en `ADMIN_IDS`, tendrás acceso a:

| Comando | Descripción |
|---------|-------------|
| `/admin` | Abrir panel de administración |
| `/stats` | Ver estadísticas del bot |
| `/broadcast` | Enviar mensaje a todos los usuarios |
| `/logs` | Ver logs del sistema |

## 📝 Obtener tu ID de Telegram

1. Abre Telegram y busca @userinfobot
2. Envía el comando `/start`
3. El bot te mostrará tu ID de usuario

## 🐛 Solución de Problemas

### El bot no responde

1. Verifica que el webhook esté configurado:
   ```bash
   curl "https://api.telegram.org/botTOKEN/getWebhookInfo"
   ```

2. Revisa los logs en Railway:
   - Ve a Railway Dashboard → Tu Proyecto → Deployments
   - Selecciona el deployment más reciente
   - Revisa los logs

3. Verifica las variables de entorno en Railway

### Error 500

1. Verifica que PHP esté funcionando correctamente
2. Revisa los logs de errores
3. Asegúrate de que las extensiones necesarias estén instaladas

### El webhook no se configura

1. Verifica que la URL de Railway esté funcionando
2. Prueba acceder a `https://tu-proyecto.up.railway.app` en el navegador
3. Deberías ver un JSON con información del bot

## 📄 Licencia

MIT License - Starlink Net

## 👤 Autor

**Starlink Net** - Agencia de Internet
- 🌐 Website: https://starlinknet.com
- 📧 Email: soporte@starlinknet.com
- 📱 WhatsApp: +58 412-1234567
