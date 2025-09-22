<?php
// 建築家検索のprefecturesフィルタリングテスト
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// 言語設定
$lang = InputValidator::validateLanguage($_GET['lang'] ?? 'ja');

// パラメータ設定
$architectSlug = 'furuya-design-architect-office';
$prefectures = 'Nagano';
$page = 1;
$limit = 10;

echo "<h1>建築家検索のprefecturesフィルタリングテスト</h1>";
echo "<h2>パラメータ</h2>";
echo "<p>architectSlug: " . htmlspecialchars($architectSlug) . "</p>";
echo "<p>prefectures: " . htmlspecialchars($prefectures) . "</p>";
echo "<p>lang: " . htmlspecialchars($lang) . "</p>";

echo "<h2>検索実行</h2>";

// 修正前の関数（prefecturesフィルタリングなし）
echo "<h3>修正前（prefecturesフィルタリングなし）</h3>";
$result1 = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit);
echo "<p>検索結果件数: " . $result1['total'] . "件</p>";

// 修正後の関数（prefecturesフィルタリングあり）
echo "<h3>修正後（prefecturesフィルタリングあり）</h3>";
$result2 = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit, '', $prefectures, '');
echo "<p>検索結果件数: " . $result2['total'] . "件</p>";

echo "<h2>検索結果詳細</h2>";

if (!empty($result2['buildings'])) {
    echo "<h3>長野県の建築物</h3>";
    foreach ($result2['buildings'] as $building) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<h4>" . htmlspecialchars($building['title']) . "</h4>";
        echo "<p>都道府県: " . htmlspecialchars($building['prefectures']) . " / " . htmlspecialchars($building['prefecturesEn']) . "</p>";
        echo "<p>建築年: " . htmlspecialchars($building['completionYears']) . "</p>";
        echo "<p>用途: " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>長野県の建築物が見つかりませんでした。</p>";
}

echo "<h2>デバッグ情報</h2>";
echo "<h3>修正前の結果（最初の5件）</h3>";
if (!empty($result1['buildings'])) {
    foreach (array_slice($result1['buildings'], 0, 5) as $building) {
        echo "<p>" . htmlspecialchars($building['title']) . " - " . htmlspecialchars($building['prefectures']) . "</p>";
    }
}

echo "<h3>修正後の結果（最初の5件）</h3>";
if (!empty($result2['buildings'])) {
    foreach (array_slice($result2['buildings'], 0, 5) as $building) {
        echo "<p>" . htmlspecialchars($building['title']) . " - " . htmlspecialchars($building['prefectures']) . "</p>";
    }
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
</style>
