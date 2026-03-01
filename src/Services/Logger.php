<?php
/**
 * Sistema de Logs para el Bot de Telegram
 * 
 * Proporciona funcionalidad de logging para monitorear el comportamiento
 * del bot y diagnosticar problemas.
 * 
 * @author Starlink Net
 * @version 1.0.0
 */

namespace StarlinkNet\Services;

class Logger
{
    private static ?Logger $instance = null;
    private string $logPath;
    private string $logLevel;
    private bool $enabled;
    private array $levels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];

    /**
     * Constructor privado para singleton
     */
    private function __construct(array $config = [])
    {
        $this->logPath = $config['logPath'] ?? __DIR__ . '/../../logs';
        $this->logLevel = $config['logLevel'] ?? 'info';
        $this->enabled = $config['enabled'] ?? true;

        // Crear directorio de logs si no existe
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Obtener instancia singleton
     */
    public static function getInstance(array $config = []): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Escribir mensaje de log
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $level = strtolower($level);
        
        // Verificar si el nivel de log es suficiente
        if (!isset($this->levels[$level])) {
            $level = 'info';
        }

        if ($this->levels[$level] < $this->levels[$this->logLevel]) {
            return;
        }

        // Formatear mensaje
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = $this->formatMessage($level, $message, $context);

        // Escribir en archivo
        $filename = $this->logPath . '/bot_' . date('Y-m-d') . '.log';
        $logEntry = "[{$timestamp}] [{$level}] {$formattedMessage}\n";
        
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Formatear mensaje con contexto
     */
    private function formatMessage(string $level, string $message, array $context): string
    {
        $formatted = $message;
        
        if (!empty($context)) {
            $formatted .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        // Agregar emoji según nivel
        $emojis = [
            'debug' => '🔍',
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'error' => '❌',
            'critical' => '🚨'
        ];

        $emoji = $emojis[$level] ?? '📝';
        return $emoji . ' ' . $formatted;
    }

    /**
     * Log de nivel debug
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log de nivel info
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log de nivel warning
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log de nivel error
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log de nivel critical
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Log de excepciones
     */
    public function exception(\Throwable $e, array $context = []): void
    {
        $message = sprintf(
            "Excepción: %s | Archivo: %s | Línea: %d",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        
        $this->log('error', $message, $context);
        
        if ($this->levels['debug'] >= $this->levels[$this->logLevel]) {
            $this->log('debug', 'Stack Trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Obtener logs recientes
     */
    public function getRecentLogs(int $lines = 100): array
    {
        $filename = $this->logPath . '/bot_' . date('Y-m-d') . '.log';
        
        if (!file_exists($filename)) {
            return [];
        }

        $file = file($filename);
        $total = count($file);
        
        if ($total <= $lines) {
            return $file;
        }

        return array_slice($file, -$lines);
    }

    /**
     * Limpiar logs antiguos
     */
    public function cleanup(int $daysToKeep = 30): int
    {
        $count = 0;
        $files = glob($this->logPath . '/bot_*.log');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $dateStr = preg_replace('/bot_(\d{4}-\d{2}-\d{2})\.log/', '$1', $filename);
            $fileDate = strtotime($dateStr);
            $threshold = strtotime("-{$daysToKeep} days");
            
            if ($fileDate < $threshold) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}
