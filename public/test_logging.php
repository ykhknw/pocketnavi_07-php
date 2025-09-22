<?php
// ログ機能のテストページ
require_once '../src/Utils/Logger.php';
require_once '../src/Utils/ErrorHandler.php';

// セキュリティヘッダーを設定
header('Content-Type: text/html; charset=UTF-8');

echo "<h1>ログ機能テスト</h1>";

// Loggerを初期化
Logger::init();

echo "<h2>1. 基本的なログテスト</h2>";

// 各レベルのログをテスト
Logger::debug("これはデバッグメッセージです", ['test' => 'debug']);
Logger::info("これは情報メッセージです", ['test' => 'info']);
Logger::warning("これは警告メッセージです", ['test' => 'warning']);
Logger::error("これはエラーメッセージです", ['test' => 'error']);
Logger::critical("これは重大なエラーメッセージです", ['test' => 'critical']);

echo "<p>✅ 基本的なログテスト完了</p>";

echo "<h2>2. エラーハンドラーのテスト</h2>";

try {
    // 意図的にエラーを発生させる
    throw new Exception("テスト用の例外です", 123);
} catch (Exception $e) {
    $result = ErrorHandler::handle($e, ['test' => 'exception']);
    echo "<p>エラーハンドラー結果: " . htmlspecialchars($result) . "</p>";
}

echo "<p>✅ エラーハンドラーテスト完了</p>";

echo "<h2>3. データベースエラーのテスト</h2>";

try {
    // データベースエラーをシミュレート
    throw new PDOException("SQLSTATE[42000]: Syntax error", 42000);
} catch (PDOException $e) {
    ErrorHandler::handleDatabaseError($e, "SELECT * FROM non_existent_table");
    echo "<p>✅ データベースエラーテスト完了</p>";
}

echo "<h2>4. ログ設定の確認</h2>";

echo "<p>現在のログレベル: " . Logger::getLogLevel() . "</p>";
echo "<p>ログディレクトリ: " . (is_dir('logs/') ? 'logs/' : '作成されていません') . "</p>";

if (is_dir('logs/')) {
    $logFiles = glob('logs/*.log');
    echo "<p>ログファイル数: " . count($logFiles) . "</p>";
    
    if (!empty($logFiles)) {
        echo "<h3>ログファイル一覧</h3>";
        foreach ($logFiles as $file) {
            $size = filesize($file);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo "<p>" . htmlspecialchars(basename($file)) . " - サイズ: " . $size . " bytes - 更新日時: " . $modified . "</p>";
        }
    }
}

echo "<h2>5. ログファイルの内容確認</h2>";

$todayLogFile = 'logs/application_' . date('Y-m-d') . '.log';
if (file_exists($todayLogFile)) {
    echo "<h3>今日のログファイル内容（最後の5行）</h3>";
    $lines = file($todayLogFile);
    $lastLines = array_slice($lines, -5);
    
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    foreach ($lastLines as $line) {
        $logData = json_decode($line, true);
        if ($logData) {
            echo htmlspecialchars(json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "\n";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>今日のログファイルが見つかりません: " . $todayLogFile . "</p>";
}

echo "<h2>6. パフォーマンステスト</h2>";

$startTime = microtime(true);
$startMemory = memory_get_usage();

// 大量のログを生成
for ($i = 0; $i < 100; $i++) {
    Logger::info("パフォーマンステストメッセージ #{$i}", ['iteration' => $i]);
}

$endTime = microtime(true);
$endMemory = memory_get_usage();

$executionTime = ($endTime - $startTime) * 1000; // ミリ秒
$memoryUsed = $endMemory - $startMemory;

echo "<p>100件のログ生成時間: " . number_format($executionTime, 2) . " ms</p>";
echo "<p>メモリ使用量: " . number_format($memoryUsed / 1024, 2) . " KB</p>";

echo "<h2>7. ログレベル変更テスト</h2>";

echo "<p>現在のログレベル: " . Logger::getLogLevel() . "</p>";

// ログレベルをWARNINGに変更
Logger::setLogLevel(Logger::LEVEL_WARNING);
echo "<p>ログレベルをWARNINGに変更</p>";

// DEBUGとINFOのログは記録されないはず
Logger::debug("このメッセージは記録されません", ['test' => 'debug_filtered']);
Logger::info("このメッセージは記録されません", ['test' => 'info_filtered']);
Logger::warning("このメッセージは記録されます", ['test' => 'warning_recorded']);

echo "<p>✅ ログレベル変更テスト完了</p>";

// ログレベルを元に戻す
Logger::setLogLevel(Logger::LEVEL_INFO);

echo "<h2>テスト完了</h2>";
echo "<p>すべてのログ機能テストが完了しました。</p>";

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; }
</style>
