<?php
// ログ機能のデバッグページ
require_once '../src/Utils/Logger.php';

echo "<h1>ログ機能デバッグ</h1>";

// Loggerを初期化
Logger::init();

echo "<h2>1. ログディレクトリの確認</h2>";
echo "<p>ログディレクトリ: logs/</p>";
echo "<p>ディレクトリ存在: " . (is_dir('logs/') ? 'YES' : 'NO') . "</p>";
echo "<p>書き込み権限: " . (is_writable('logs/') ? 'YES' : 'NO') . "</p>";

if (is_dir('logs/')) {
    echo "<p>ディレクトリ内容:</p>";
    $files = scandir('logs/');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<p>- " . htmlspecialchars($file) . "</p>";
        }
    }
}

echo "<h2>2. ログファイル作成テスト</h2>";

$testLogFile = 'logs/test_' . date('Y-m-d_H-i-s') . '.log';
$testContent = "Test log entry at " . date('Y-m-d H:i:s') . "\n";

echo "<p>テストファイル: " . $testLogFile . "</p>";

$result = @file_put_contents($testLogFile, $testContent, FILE_APPEND | LOCK_EX);
if ($result !== false) {
    echo "<p style='color: green;'>✅ ファイル作成成功: " . $result . " bytes</p>";
} else {
    echo "<p style='color: red;'>❌ ファイル作成失敗</p>";
    echo "<p>エラー詳細: " . error_get_last()['message'] . "</p>";
}

echo "<h2>3. Logger直接テスト</h2>";

// ログレベルをDEBUGに設定
Logger::setLogLevel(Logger::LEVEL_DEBUG);

echo "<p>ログレベル: " . Logger::getLogLevel() . "</p>";

// テストログを記録
Logger::debug("デバッグテストメッセージ", ['test' => 'debug']);
Logger::info("情報テストメッセージ", ['test' => 'info']);

echo "<p>✅ ログ記録完了</p>";

echo "<h2>4. ログファイル確認</h2>";

$todayLogFile = 'logs/application_' . date('Y-m-d') . '.log';
echo "<p>期待されるログファイル: " . $todayLogFile . "</p>";
echo "<p>ファイル存在: " . (file_exists($todayLogFile) ? 'YES' : 'NO') . "</p>";

if (file_exists($todayLogFile)) {
    $size = filesize($todayLogFile);
    $content = file_get_contents($todayLogFile);
    echo "<p>ファイルサイズ: " . $size . " bytes</p>";
    echo "<p>ファイル内容（最後の3行）:</p>";
    $lines = explode("\n", trim($content));
    $lastLines = array_slice($lines, -3);
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    foreach ($lastLines as $line) {
        if (!empty($line)) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>ログファイルが作成されていません</p>";
}

echo "<h2>5. 権限テスト</h2>";

$testDir = 'logs/test_dir';
if (@mkdir($testDir, 0755, true)) {
    echo "<p style='color: green;'>✅ ディレクトリ作成可能</p>";
    rmdir($testDir);
} else {
    echo "<p style='color: red;'>❌ ディレクトリ作成不可</p>";
}

$testFile = 'logs/test_write.txt';
if (@file_put_contents($testFile, 'test') !== false) {
    echo "<p style='color: green;'>✅ ファイル書き込み可能</p>";
    unlink($testFile);
} else {
    echo "<p style='color: red;'>❌ ファイル書き込み不可</p>";
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; }
</style>
