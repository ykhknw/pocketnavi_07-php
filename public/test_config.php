<?php
// 設定管理システムのテストページ
require_once '../src/Utils/Config.php';

// セキュリティヘッダーを設定
header('Content-Type: text/html; charset=UTF-8');

echo "<h1>設定管理システムテスト</h1>";

// Configクラスを初期化
Config::load();

echo "<h2>1. 基本的な設定取得テスト</h2>";

// アプリケーション設定
echo "<h3>アプリケーション設定</h3>";
echo "<p>アプリケーション名: " . htmlspecialchars(Config::get('app.name')) . "</p>";
echo "<p>環境: " . htmlspecialchars(Config::get('app.env')) . "</p>";
echo "<p>デバッグモード: " . (Config::get('app.debug') ? 'ON' : 'OFF') . "</p>";
echo "<p>URL: " . htmlspecialchars(Config::get('app.url')) . "</p>";
echo "<p>タイムゾーン: " . htmlspecialchars(Config::get('app.timezone')) . "</p>";
echo "<p>ロケール: " . htmlspecialchars(Config::get('app.locale')) . "</p>";

// データベース設定
echo "<h3>データベース設定</h3>";
$dbConfig = Config::getDatabaseConfig();
echo "<p>ホスト: " . htmlspecialchars($dbConfig['host']) . "</p>";
echo "<p>ポート: " . $dbConfig['port'] . "</p>";
echo "<p>データベース: " . htmlspecialchars($dbConfig['database']) . "</p>";
echo "<p>ユーザー名: " . htmlspecialchars($dbConfig['username']) . "</p>";
echo "<p>パスワード: " . (empty($dbConfig['password']) ? '(空)' : '***') . "</p>";

// ログ設定
echo "<h3>ログ設定</h3>";
echo "<p>ログレベル: " . htmlspecialchars(Config::get('logging.default_level')) . "</p>";

echo "<h2>2. 環境判定テスト</h2>";

echo "<p>本番環境: " . (Config::isProduction() ? 'YES' : 'NO') . "</p>";
echo "<p>開発環境: " . (Config::isDevelopment() ? 'YES' : 'NO') . "</p>";
echo "<p>デバッグモード: " . (Config::isDebug() ? 'YES' : 'NO') . "</p>";

echo "<h2>3. 設定値の存在チェック</h2>";

$testKeys = [
    'app.name',
    'app.env',
    'app.debug',
    'database.connections.mysql.host',
    'logging.default_level',
    'nonexistent.key'
];

foreach ($testKeys as $key) {
    $exists = Config::has($key);
    $value = Config::get($key, 'NOT_FOUND');
    echo "<p>{$key}: " . ($exists ? 'EXISTS' : 'NOT_EXISTS') . " = " . htmlspecialchars($value) . "</p>";
}

echo "<h2>4. 設定値の設定・取得テスト</h2>";

// 設定値を設定
Config::set('test.value', 'test_value_123');
Config::set('test.nested.value', 'nested_value_456');

// 設定値を取得
echo "<p>test.value: " . htmlspecialchars(Config::get('test.value')) . "</p>";
echo "<p>test.nested.value: " . htmlspecialchars(Config::get('test.nested.value')) . "</p>";

echo "<h2>5. 設定検証テスト</h2>";

try {
    ConfigValidator::validate(Config::all());
    echo "<p style='color: green;'>✅ 設定検証成功</p>";
} catch (InvalidConfigurationException $e) {
    echo "<p style='color: red;'>❌ 設定検証失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>6. 環境変数テスト</h2>";

echo "<p>APP_NAME: " . htmlspecialchars(getenv('APP_NAME')) . "</p>";
echo "<p>APP_ENV: " . htmlspecialchars(getenv('APP_ENV')) . "</p>";
echo "<p>APP_DEBUG: " . htmlspecialchars(getenv('APP_DEBUG')) . "</p>";
echo "<p>DB_HOST: " . htmlspecialchars(getenv('DB_HOST')) . "</p>";
echo "<p>DB_DATABASE: " . htmlspecialchars(getenv('DB_DATABASE')) . "</p>";
echo "<p>LOG_LEVEL: " . htmlspecialchars(getenv('LOG_LEVEL')) . "</p>";

echo "<h2>7. 設定のJSON出力（デバッグ用）</h2>";

echo "<h3>全設定（JSON形式）</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars(Config::toJson(true));
echo "</pre>";

echo "<h2>8. パフォーマンステスト</h2>";

$startTime = microtime(true);

// 100回設定値を取得
for ($i = 0; $i < 100; $i++) {
    Config::get('app.name');
    Config::get('database.connections.mysql.host');
    Config::get('logging.default_level');
}

$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000; // ミリ秒

echo "<p>100回の設定取得時間: " . number_format($executionTime, 2) . " ms</p>";

echo "<h2>9. 設定ファイルの存在確認</h2>";

$configFiles = [
    'config/app.php',
    'config/database.php',
    'config/logging.php',
    'config/cache.php',
    'config/env.php',
    'src/Utils/Config.php',
    'src/Utils/ConfigValidator.php'
];

foreach ($configFiles as $file) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) : 0;
    echo "<p>{$file}: " . ($exists ? 'EXISTS' : 'NOT_EXISTS') . " (" . $size . " bytes)</p>";
}

echo "<h2>テスト完了</h2>";
echo "<p>設定管理システムのテストが完了しました。</p>";

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; }
</style>
