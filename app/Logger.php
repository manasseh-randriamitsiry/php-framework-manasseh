<?php

namespace App;

class Logger
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    protected static $logDir = null;
    protected static $maxFileSize = 10485760; // 10MB
    protected static $maxFiles = 5;
    
    /**
     * Initialize logger
     */
    public static function init()
    {
        if (self::$logDir === null) {
            self::$logDir = __DIR__ . '/../logs/';
            
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
    }
    
    /**
     * Log emergency message
     */
    public static function emergency($message, array $context = [])
    {
        return self::log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log alert message
     */
    public static function alert($message, array $context = [])
    {
        return self::log(self::ALERT, $message, $context);
    }
    
    /**
     * Log critical message
     */
    public static function critical($message, array $context = [])
    {
        return self::log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error($message, array $context = [])
    {
        return self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, array $context = [])
    {
        return self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log notice message
     */
    public static function notice($message, array $context = [])
    {
        return self::log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log info message
     */
    public static function info($message, array $context = [])
    {
        return self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log debug message
     */
    public static function debug($message, array $context = [])
    {
        return self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log message with specified level
     */
    public static function log($level, $message, array $context = [])
    {
        self::init();
        
        // Check if logging is enabled for this level
        if (!self::shouldLog($level)) {
            return false;
        }
        
        // Format message
        $formattedMessage = self::formatMessage($level, $message, $context);
        
        // Write to file
        $logFile = self::getLogFile();
        
        // Check if rotation is needed
        if (file_exists($logFile) && filesize($logFile) > self::$maxFileSize) {
            self::rotateLogFile($logFile);
        }
        
        // Write log entry
        return file_put_contents($logFile, $formattedMessage . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Check if should log for given level
     */
    protected static function shouldLog($level)
    {
        $currentLevel = Environment::get('LOG_LEVEL', 'info');
        
        $levels = [
            self::EMERGENCY => 0,
            self::ALERT => 1,
            self::CRITICAL => 2,
            self::ERROR => 3,
            self::WARNING => 4,
            self::NOTICE => 5,
            self::INFO => 6,
            self::DEBUG => 7
        ];
        
        return $levels[$level] <= $levels[$currentLevel];
    }
    
    /**
     * Format log message
     */
    protected static function formatMessage($level, $message, array $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Replace context placeholders in message
        $message = self::interpolate($message, $context);
        
        // Add context if not empty
        $contextString = '';
        if (!empty($context)) {
            $contextString = ' ' . json_encode($context);
        }
        
        return "[{$timestamp}] {$levelUpper}: {$message}{$contextString}";
    }
    
    /**
     * Interpolate context values into message placeholders
     */
    protected static function interpolate($message, array $context = [])
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        return strtr($message, $replace);
    }
    
    /**
     * Get current log file path
     */
    protected static function getLogFile()
    {
        $date = date('Y-m-d');
        return self::$logDir . "app-{$date}.log";
    }
    
    /**
     * Rotate log file when it gets too large
     */
    protected static function rotateLogFile($logFile)
    {
        $pathInfo = pathinfo($logFile);
        $basename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        $directory = $pathInfo['dirname'];
        
        // Move existing rotated files
        for ($i = self::$maxFiles - 1; $i > 0; $i--) {
            $oldFile = $directory . '/' . $basename . '.' . $i . '.' . $extension;
            $newFile = $directory . '/' . $basename . '.' . ($i + 1) . '.' . $extension;
            
            if (file_exists($oldFile)) {
                if ($i == self::$maxFiles - 1) {
                    unlink($oldFile); // Delete oldest file
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Move current file to .1
        $rotatedFile = $directory . '/' . $basename . '.1.' . $extension;
        rename($logFile, $rotatedFile);
    }
    
    /**
     * Clear all log files
     */
    public static function clear()
    {
        self::init();
        $files = glob(self::$logDir . '*.log*');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return count($files);
    }
    
    /**
     * Get log files
     */
    public static function getLogFiles()
    {
        self::init();
        return glob(self::$logDir . '*.log*');
    }
    
    /**
     * Read log file content
     */
    public static function read($filename = null, $lines = 100)
    {
        if ($filename === null) {
            $filename = basename(self::getLogFile());
        }
        
        $filepath = self::$logDir . $filename;
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $file = new \SplFileObject($filepath);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $logEntries = [];
        
        $file->seek($startLine);
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logEntries[] = $line;
            }
            $file->next();
        }
        
        return $logEntries;
    }
    
    /**
     * Get log statistics
     */
    public static function getStats()
    {
        self::init();
        $files = self::getLogFiles();
        $totalSize = 0;
        $totalLines = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $totalLines += count(file($file));
        }
        
        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'total_lines' => $totalLines,
            'log_level' => Environment::get('LOG_LEVEL', 'info')
        ];
    }
}