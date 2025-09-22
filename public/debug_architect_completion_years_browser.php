<?php
// ブラウザでの完成年フィルタリング問題のデバッグ
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// パラメータ設定（実際のURLと同じ）
$architectSlug = 'inui-architects';
$completionYears = '2012';
$lang = 'ja';
$page = 1;
$limit = 10;

echo "<h1>ブラウザでの完成年フィルタリング問題のデバッグ</h1>";
echo "<h2>パラメータ</h2>";
echo "<p>architectSlug: " . htmlspecialchars($architectSlug) . "</p>";
echo "<p>completionYears: " . htmlspecialchars($completionYears) . "</p>";
echo "<p>lang: " . htmlspecialchars($lang) . "</p>";

echo "<h2>1. 直接SQL検索（phpMyAdminと同じ）</h2>";
$db = getDB();

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
    echo "<p>completionYears: '" . htmlspecialchars($building['completionYears']) . "'</p>";
    echo "</div>";
}

echo "<h2>2. searchBuildingsByArchitectSlug関数のテスト</h2>";
$result = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit, $completionYears, '', '');

echo "<p>searchBuildingsByArchitectSlug結果:</p>";
echo "<p>- total: " . $result['total'] . "</p>";
echo "<p>- totalPages: " . $result['totalPages'] . "</p>";
echo "<p>- buildings count: " . count($result['buildings']) . "</p>";

if (!empty($result['buildings'])) {
    echo "<h3>検索結果詳細</h3>";
    foreach ($result['buildings'] as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>順位: " . ($index + 1) . "</p>";
        echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>completionYears: '" . htmlspecialchars($building['completionYears']) . "'</p>";
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>検索結果がありません。</p>";
}

echo "<h2>3. 建築家検索（completionYearsなし）</h2>";
$resultWithoutFilter = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit, '', '', '');
echo "<p>建築家検索結果（フィルターなし）: " . $resultWithoutFilter['total'] . "件</p>";

if (!empty($resultWithoutFilter['buildings'])) {
    echo "<h3>建築物一覧（最初の10件）</h3>";
    foreach (array_slice($resultWithoutFilter['buildings'], 0, 10) as $index => $building) {
        $is2012 = $building['completionYears'] === '2012';
        $highlight = $is2012 ? 'background-color: yellow;' : '';
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px; {$highlight}'>";
        echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>completionYears: '" . htmlspecialchars($building['completionYears']) . "' " . ($is2012 ? '(2012年!)' : '') . "</p>";
        echo "</div>";
    }
}

echo "<h2>4. 手動フィルタリングテスト</h2>";
if (!empty($resultWithoutFilter['buildings'])) {
    $filteredCount = 0;
    $filteredBuildings = [];
    
    foreach ($resultWithoutFilter['buildings'] as $building) {
        if ($building['completionYears'] === '2012') {
            $filteredBuildings[] = $building;
            $filteredCount++;
        }
    }
    
    echo "<p>手動フィルタリング結果: " . $filteredCount . "件</p>";
    
    if (!empty($filteredBuildings)) {
        echo "<h3>手動フィルタリング結果詳細</h3>";
        foreach ($filteredBuildings as $building) {
            echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px; background-color: lightblue;'>";
            echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
            echo "<p>completionYears: '" . htmlspecialchars($building['completionYears']) . "'</p>";
            echo "</div>";
        }
    }
}

echo "<h2>5. 比較結果</h2>";
echo "<p>直接SQL検索: " . count($directResults) . "件</p>";
echo "<p>searchBuildingsByArchitectSlug: " . $result['total'] . "件</p>";
echo "<p>手動フィルタリング: " . (isset($filteredCount) ? $filteredCount : 'N/A') . "件</p>";

if (count($directResults) === $result['total']) {
    echo "<p style='color: green;'>✅ 一致: フィルタリング結果は正しく動作しています。</p>";
} else {
    echo "<p style='color: red;'>⚠️ 不一致: フィルタリング結果に問題があります。</p>";
}

echo "<h2>6. デバッグ情報</h2>";
echo "<p>completionYears parameter: '" . $completionYears . "' (type: " . gettype($completionYears) . ")</p>";
echo "<p>completionYears === '2012': " . ($completionYears === '2012' ? 'true' : 'false') . "</p>";

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
</style>
