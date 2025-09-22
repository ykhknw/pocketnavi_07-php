<?php
// 建築家ページのパラメータデバッグ
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// 言語設定
$lang = InputValidator::validateLanguage($_GET['lang'] ?? 'ja');

echo "<h1>建築家ページのパラメータデバッグ</h1>";

echo "<h2>現在のURL</h2>";
echo "<p><strong>REQUEST_URI:</strong> " . htmlspecialchars($_SERVER['REQUEST_URI']) . "</p>";
echo "<p><strong>QUERY_STRING:</strong> " . htmlspecialchars($_SERVER['QUERY_STRING']) . "</p>";

echo "<h2>GET パラメータ</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>建築家ページ判定</h2>";
$isArchitectPage = isset($_GET['architects_slug']) && !empty($_GET['architects_slug']);
echo "<p><strong>isArchitectPage:</strong> " . ($isArchitectPage ? 'true' : 'false') . "</p>";

if ($isArchitectPage) {
    echo "<p><strong>architects_slug:</strong> " . htmlspecialchars($_GET['architects_slug']) . "</p>";
} else {
    echo "<p><strong>architects_slug:</strong> 設定されていません</p>";
}

echo "<h2>URL生成テスト</h2>";

// テスト用の建築物データ
$testBuilding = [
    'prefectures' => '愛知県',
    'prefecturesEn' => 'Aichi',
    'completionYears' => '2015',
    'buildingTypes' => ['事務所', '展示場施設'],
    'buildingTypesEn' => ['Office', 'Exhibition Hall']
];

echo "<h3>prefecturesバッジのURL生成</h3>";
if ($isArchitectPage) {
    $architectSlug = $_GET['architects_slug'];
    $urlParams = ['prefectures' => $testBuilding['prefecturesEn'], 'lang' => $lang];
    
    // 既存のパラメータを保持
    if (isset($_GET['completionYears']) && !empty($_GET['completionYears'])) {
        $urlParams['completionYears'] = $_GET['completionYears'];
    }
    if (isset($_GET['photos']) && !empty($_GET['photos'])) {
        $urlParams['photos'] = $_GET['photos'];
    }
    if (isset($_GET['videos']) && !empty($_GET['videos'])) {
        $urlParams['videos'] = $_GET['videos'];
    }
    if (isset($_GET['q']) && !empty($_GET['q'])) {
        $urlParams['q'] = $_GET['q'];
    }
    
    $url = "/architects/{$architectSlug}/?" . http_build_query($urlParams);
    echo "<p><strong>建築家ページ用URL:</strong> " . htmlspecialchars($url) . "</p>";
} else {
    $urlParams = ['prefectures' => $testBuilding['prefecturesEn'], 'lang' => $lang];
    if (isset($_GET['q']) && $_GET['q']) {
        $urlParams['q'] = $_GET['q'];
    }
    if (isset($_GET['completionYears']) && $_GET['completionYears']) {
        $urlParams['completionYears'] = $_GET['completionYears'];
    }
    if (isset($_GET['photos']) && $_GET['photos']) {
        $urlParams['photos'] = $_GET['photos'];
    }
    if (isset($_GET['videos']) && $_GET['videos']) {
        $urlParams['videos'] = $_GET['videos'];
    }
    $url = "/index.php?" . http_build_query($urlParams);
    echo "<p><strong>通常ページ用URL:</strong> " . htmlspecialchars($url) . "</p>";
}

echo "<h2>リライトルールの確認</h2>";
echo "<p>.htaccessのリライトルール: <code>RewriteRule ^architects/([^/]+)/?$ index.php?architects_slug=$1 [L,QSA]</code></p>";
echo "<p>このルールにより、<code>/architects/act-planning-1/?lang=ja</code> は <code>index.php?architects_slug=act-planning-1&lang=ja</code> に変換されるはずです。</p>";

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
code { background: #f8f9fa; padding: 0.2rem 0.4rem; border-radius: 3px; font-family: monospace; }
</style>
