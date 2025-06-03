<?php
class Logger {
    private $log_file;
    private $context;

    public function __construct($context) {
        $this->context = $context;
        $this->log_file = dirname(__DIR__) . '/logs/' . date('Y-m-d') . '_' . $context . '.log';
        
        // Create logs directory if it doesn't exist
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }

    private function write($level, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = sprintf(
            "[%s] %s [%s]: %s\n",
            $timestamp,
            strtoupper($level),
            $this->context,
            $message
        );
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
    }

    public function info($message) {
        $this->write('info', $message);
    }

    public function error($message) {
        $this->write('error', $message);
    }

    public function warning($message) {
        $this->write('warning', $message);
    }

    public function debug($message) {
        if (defined('DEBUG') && DEBUG) {
            $this->write('debug', $message);
        }
    }
} 