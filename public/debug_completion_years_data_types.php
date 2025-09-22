<?php
// 完成年フィルタリングのデータ型デバッグ
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// パラメータ設定
$architectSlug = $_GET['architect_slug'] ?? 'inui-architects';
$completionYears = $_GET['completionYears'] ?? '2012';
$lang = 'ja';
$page = 1;
$limit = 10;

echo "<h1>完成年フィルタリングのデータ型デバッグ</h1>";
echo "<h2>パラメータ</h2>";
echo "<p>architectSlug: " . htmlspecialchars($architectSlug) . "</p>";
echo "<p>completionYears: " . htmlspecialchars($completionYears) . " (型: " . gettype($completionYears) . ")</p>";

$db = getDB();

echo "<h2>1. 建築家検索の結果（全件）</h2>";
$result = searchBuildingsByArchitectSlug($architectSlug, 1, $lang, 1000, '', '', '');
echo "<p>建築家検索結果: " . $result['total'] . "件</p>";

if (!empty($result['buildings'])) {
    echo "<h3>完成年データの詳細確認</h3>";
    foreach ($result['buildings'] as $index => $building) {
        if ($index >= 10) break; // 最初の10件のみ表示
        
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>completionYears: '" . htmlspecialchars($building['completionYears']) . "' (型: " . gettype($building['completionYears']) . ")</p>";
        echo "<p>completionYears === '2012': " . ($building['completionYears'] === '2012' ? 'true' : 'false') . "</p>";
        echo "<p>completionYears == '2012': " . ($building['completionYears'] == '2012' ? 'true' : 'false') . "</p>";
        echo "<p>strcmp(completionYears, '2012'): " . strcmp($building['completionYears'], '2012') . "</p>";
        echo "</div>";
    }
}

echo "<h2>2. 完成年「2012」の建築物を直接検索</h2>";
$sql = "
    SELECT b.building_id, b.title, b.completionYears
    FROM buildings_table_3 b
    INNER JOIN building_architects ba ON b.building_id = ba.building_id
    INNER JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    INNER JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    WHERE ia.slug = ? AND b.completionYears = ?
    ORDER BY b.building_id DESC
";
$stmt = $db->prepare($sql);
$stmt->execute([$architectSlug, $completionYears]);
$directResults = $stmt->fetchAll();

echo "<p>直接SQL検索結果: " . count($directResults) . "件</p>";

foreach ($directResults as $building) {
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px; background-color: lightgreen;'>";
    echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
    echo "<p>completionYears: '" . htmlspecialchars($building['completionYears']) . "' (型: " . gettype($building['completionYears']) . ")</p>";
    echo "</div>";
}

echo "<h2>3. フィルタリング処理のデバッグ</h2>";
$filteredCount = 0;
$filteredBuildings = [];

if (!empty($result['buildings'])) {
    foreach ($result['buildings'] as $building) {
        $include = true;
        
        // completionYearsフィルタリング
        if (!empty($completionYears) && $include) {
            $originalInclude = $include;
            $include = $building['completionYears'] === $completionYears;
            
            if ($originalInclude && !$include) {
                echo "<p>除外: ID " . $building['building_id'] . " - completionYears: '" . $building['completionYears'] . "' !== '" . $completionYears . "'</p>";
            }
        }
        
        if ($include) {
            $filteredBuildings[] = $building;
            $filteredCount++;
        }
    }
}

echo "<p>フィルタリング結果: " . $filteredCount . "件</p>";

if (!empty($filteredBuildings)) {
    echo "<h3>フィルタリング結果詳細</h3>";
    foreach ($filteredBuildings as $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px; background-color: lightblue;'>";
        echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>completionYears: '" . htmlspecialchars($building['completionYears']) . "'</p>";
        echo "</div>";
    }
}

echo "<h2>4. 比較結果</h2>";
echo "<p>直接SQL検索: " . count($directResults) . "件</p>";
echo "<p>PHPフィルタリング: " . $filteredCount . "件</p>";

if (count($directResults) === $filteredCount) {
    echo "<p style='color: green;'>✅ 一致: フィルタリング結果は正しく動作しています。</p>";
} else {
    echo "<p style='color: red;'>⚠️ 不一致: フィルタリング結果に問題があります。</p>";
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
</style>
