<?php

/**
 * エラーハンドリングクラス
 */
class ErrorHandler {
    const LOG_LEVEL_ERROR = 'ERROR';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_INFO = 'INFO';
    const LOG_LEVEL_DEBUG = 'DEBUG';
    
    /**
     * エラーを処理する
     */
    public static function handle($exception, $context = []) {
        $message = $exception->getMessage();
        $trace = $exception->getTraceAsString();
        
        // ログに記録
        self::log($message, self::LOG_LEVEL_ERROR, [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $trace,
            'context' => $context
        ]);
        
        // 本番環境では詳細なエラー情報を隠す
        if (self::isProduction()) {
            return "システムエラーが発生しました。しばらく時間をおいて再度お試しください。";
        } else {
            return "エラー: " . $message;
        }
    }
    
    /**
     * ログを記録する
     */
    public static function log($message, $level = self::LOG_LEVEL_INFO, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        
        // ログファイルに書き込み
        $logFile = self::getLogFile();
        error_log($logLine, 3, $logFile);
    }
    
    /**
     * データベースエラーを処理する
     */
    public static function handleDatabaseError($exception, $query = null) {
        $context = [];
        if ($query) {
            $context['query'] = $query;
        }
        
        return self::handle($exception, $context);
    }
    
    /**
     * 検索エラーを処理する
     */
    public static function handleSearchError($exception, $query = null, $params = []) {
        $context = [
            'search_query' => $query,
            'search_params' => $params
        ];
        
        return self::handle($exception, $context);
    }
    
    /**
     * バリデーションエラーを処理する
     */
    public static function handleValidationError($field, $message) {
        $error = "バリデーションエラー: {$field} - {$message}";
        self::log($error, self::LOG_LEVEL_WARNING, [
            'field' => $field,
            'message' => $message
        ]);
        
        return $error;
    }
    
    /**
     * 本番環境かどうかを判定
     */
    private static function isProduction() {
        // 環境変数または設定ファイルから判定
        $env = getenv('APP_ENV') ?: 'development';
        return $env === 'production';
    }
    
    /**
     * ログファイルのパスを取得
     */
    private static function getLogFile() {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        return $logDir . '/app_' . date('Y-m-d') . '.log';
    }
    
    /**
     * ログレベルに応じた色を取得
     */
    public static function getLogColor($level) {
        switch ($level) {
            case self::LOG_LEVEL_ERROR:
                return 'red';
            case self::LOG_LEVEL_WARNING:
                return 'orange';
            case self::LOG_LEVEL_INFO:
                return 'blue';
            case self::LOG_LEVEL_DEBUG:
                return 'gray';
            default:
                return 'black';
        }
    }
    
    /**
     * ログを表示用にフォーマット
     */
    public static function formatLogForDisplay($logEntry) {
        $color = self::getLogColor($logEntry['level']);
        $timestamp = $logEntry['timestamp'];
        $level = $logEntry['level'];
        $message = $logEntry['message'];
        
        return "<span style='color: {$color};'>[{$timestamp}] {$level}: {$message}</span>";
    }
}
