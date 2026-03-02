# Instrucciones de Despliegue en Render.com

## Archivos Modificados/Creados

Los siguientes archivos han sido actualizados para funcionar correctamente en Render.com:

| Archivo | Acción | Descripción |
|---------|--------|-------------|
| [`Dockerfile`](Dockerfile:1) | Modificado | Actualizado a PHP 8.0 con Apache |
| [`composer.json`](composer.json:1) | Modificado | PHP 8.0 y extensiones PDO |
| [`public/index.php`](public/index.php:1) | Modificado | Añadido endpoint `/health` |
| [`config.php`](config.php:1) | Modificado | Función helper para variables de entorno |
| [`.dockerignore`](.dockerignore:1) | Creado | Excluye archivos innecesarios |
| [`.env.example`](.env.example:1) | Modificado | Añadido APP_ENV |
| [`render.yaml`](render.yaml:1) | Creado | Configuración automática |

---

## Pasos para Actualizar tu Repositorio GitHub

### Paso 1: Haz commit de los cambios

```bash
# Agregar todos los archivos modificados
git add -A

# Verificar qué archivos se incluirán
git status

# Hacer commit con un mensaje descriptivo
git commit -m "Adaptación para Render.com: Dockerfile PHP 8.0 Apache, health check endpoint"

# O si es un commit inicial para este propósito:
git commit -m "feat: Configuración para despliegue en Render.com"
```

### Paso 2: Subir los cambios a GitHub

```bash
# Subir cambios a la rama main
git push origin main
```

### Paso 3: Rebuild en Render.com

1. Ve al dashboard de [Render.com](https://dashboard.render.com)
2. Selecciona tu servicio (`starlink-telegram-bot`)
3. Ve a la sección **"Deploys"**
4. Haz clic en **"Trigger deploy"** → **"Deploy latest commit"**

---

## Configuración en el Panel de Render.com

### Variables de Entorno Obligatorias

1. En tu servicio de Render, ve a **"Environment"**
2. Agrega las siguientes variables:

| Variable | Valor | Descripción |
|----------|-------|-------------|
| `BOT_TOKEN` | `[Tu token de BotFather]` | Token del bot de Telegram |
| `APP_ENV` | `production` | Entorno de ejecución |
| `DEBUG` | `false` | Desactivar depuración |
| `LOG_LEVEL` | `info` | Nivel de logs |

### Después del Primer Despliegue

1. Copia la URL de tu servicio (ej: `https://starlink-telegram-bot.onrender.com`)
2. Configura el webhook de Telegram:

```bash
curl -X POST "https://api.telegram.org/bot<TU_BOT_TOKEN>/setWebhook" \
  -d "url=https://tu-servicio.onrender.com/webhook"
```

**Reemplaza:**
- `<TU_BOT_TOKEN>` - Tu token real del bot
- `tu-servicio.onrender.com` - Tu URL real de Render

---

## Verificar que el Bot Funciona

### 1. Health Check
Visita: `https://tu-servicio.onrender.com/health`

Deberías ver:
```json
{
  "status": "ok",
  "service": "telegram-bot",
  "timestamp": 1234567890,
  "version": "1.0.0"
}
```

### 2. Endpoint GET
Visita: `https://tu-servicio.onrender.com/`

Deberías ver información del bot.

### 3. Envía un mensaje a tu bot
En Telegram, inicia una conversación con tu bot y enviale `/start`

---

## Mantener el Bot Activo (Plan Gratuito)

### Opción A: UptimeRobot (Recomendado)

1. Regístrate en [UptimeRobot](https://uptimerobot.com)
2. Crea un nuevo monitor:
   - **Type:** HTTPS
   - **URL:** `https://tu-servicio.onrender.com/health`
   - **Interval:** 5 minutes
3. Listo - UptimeRobot hará ping cada 5 minutos

### Opción B: Cron-job.org

1. Regístrate en [Cron-job.org](https://cron-job.org)
2. Crea un nuevo cron job:
   - **URL:** `https://tu-servicio.onrender.com/health`
   - **Schedule:** Every 5 minutes
3. Listo

---

## Solución de Problemas

### El build falla

1. Revisa los logs en Render → "Logs"
2. Verifica que `BOT_TOKEN` esté configurado
3. Confirma que el `Dockerfile` tiene la sintaxis correcta

### El servicio no responde

1. Verifica el health check: `https://tu-servicio.onrender.com/health`
2. Revisa los logs de errores
3. Confirma las variables de entorno

### El webhook no funciona

1. Confirma que la URL es accesible públicamente
2. Verifica el webhook:
```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

---

## Comandos Útiles de Git

```bash
# Ver estado actual
git status

# Ver diferencias
git diff

# Agregar archivos específicos
git add Dockerfile composer.json public/index.php

# Agregar todos los archivos nuevos y modificados
git add -A

# Hacer commit
git commit -m "tu mensaje"

# Ver historial
git log --oneline -5

# Subir a GitHub
git push origin main
```

---

## Actualizaciones Futuras

Para actualizar tu bot en Render.com después de hacer cambios:

```bash
# 1. Haz tus cambios localmente
# 2. Commit
git add -A
git commit -m "Descripción del cambio"

# 3. Sube a GitHub
git push origin main

# 4. Render detectará automáticamente el cambio
#    O puedes triggering manualmente desde el panel
```

---

## Notas Importantes

- ✅ El plan gratuito de Render puts a dormir el servicio después de 15 min de inactividad
- ✅ El health check en `/health` mantiene el servicio activo con un ping service
- ✅ El webhook de Telegram no necesita que el servicio esté siempre activo
- ⚠️ No expongas tu `BOT_TOKEN` en repositorios públicos
- ⚠️ Recuerda incluir `vendor/` en tu repositorio o ejecutar `composer install` en el build

¡Tu bot debería estar funcionando en Render.com!
