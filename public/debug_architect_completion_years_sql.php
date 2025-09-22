<?php
// 建築家ページでの完成年フィルタリングSQLデバッグ
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

echo "<h1>建築家ページでの完成年フィルタリングSQLデバッグ</h1>";
echo "<h2>パラメータ</h2>";
echo "<p>architectSlug: " . htmlspecialchars($architectSlug) . "</p>";
echo "<p>completionYears: " . htmlspecialchars($completionYears) . "</p>";
echo "<p>lang: " . htmlspecialchars($lang) . "</p>";

$db = getDB();

echo "<h2>1. 建築家情報の確認</h2>";
$architectSql = "SELECT * FROM individual_architects_3 WHERE slug = ?";
$stmt = $db->prepare($architectSql);
$stmt->execute([$architectSlug]);
$architect = $stmt->fetch();
if ($architect) {
    echo "<p>建築家ID: " . $architect['individual_architect_id'] . "</p>";
    echo "<p>建築家名（日本語）: " . htmlspecialchars($architect['name_ja']) . "</p>";
    echo "<p>建築家名（英語）: " . htmlspecialchars($architect['name_en']) . "</p>";
} else {
    echo "<p>建築家が見つかりませんでした。</p>";
    exit;
}

echo "<h2>2. 建築家の建築物一覧（全件）</h2>";
$allBuildingsSql = "
    SELECT b.building_id, b.title, b.completionYears, b.buildingTypes
    FROM buildings_table_3 b
    INNER JOIN building_architects ba ON b.building_id = ba.building_id
    INNER JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    INNER JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    WHERE ia.slug = ?
    ORDER BY b.building_id DESC
";
$stmt = $db->prepare($allBuildingsSql);
$stmt->execute([$architectSlug]);
$allBuildings = $stmt->fetchAll();
echo "<p>建築家の建築物総数: " . count($allBuildings) . "件</p>";

echo "<h3>建築物一覧（完成年別）</h3>";
$buildingsByYear = [];
foreach ($allBuildings as $building) {
    $year = $building['completionYears'];
    if (!isset($buildingsByYear[$year])) {
        $buildingsByYear[$year] = [];
    }
    $buildingsByYear[$year][] = $building;
}

ksort($buildingsByYear, SORT_NUMERIC);
foreach ($buildingsByYear as $year => $buildings) {
    echo "<h4>完成年: " . $year . " (" . count($buildings) . "件)</h4>";
    foreach (array_slice($buildings, 0, 3) as $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>用途: " . htmlspecialchars($building['buildingTypes']) . "</p>";
        echo "</div>";
    }
    if (count($buildings) > 3) {
        echo "<p>... 他 " . (count($buildings) - 3) . "件</p>";
    }
}

echo "<h2>3. 完成年「" . htmlspecialchars($completionYears) . "」でのフィルタリング結果</h2>";

// PHP関数での検索結果を取得
$result = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit, $completionYears, '', '');

echo "<p>PHP関数での検索結果件数: " . $result['total'] . "件</p>";
echo "<p>totalPages: " . $result['totalPages'] . "</p>";

if (!empty($result['buildings'])) {
    echo "<h3>検索結果詳細</h3>";
    foreach ($result['buildings'] as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>順位: " . ($index + 1) . "</p>";
        echo "<p>ID: " . $building['building_id'] . "</p>";
        echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>完成年: " . htmlspecialchars($building['completionYears']) . "</p>";
        echo "<p>用途: " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
        echo "<p>建築家: " . htmlspecialchars(implode(', ', array_column($building['architects'], 'architectJa'))) . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>検索結果がありません。</p>";
}

echo "<h2>4. 手動での完成年フィルタリング検証</h2>";
$filteredCount = 0;
$filteredBuildings = [];

foreach ($allBuildings as $building) {
    if ($building['completionYears'] === $completionYears) {
        $filteredBuildings[] = $building;
        $filteredCount++;
    }
}

echo "<p>手動フィルタリング結果: " . $filteredCount . "件</p>";

if (!empty($filteredBuildings)) {
    echo "<h3>手動フィルタリング結果詳細</h3>";
    foreach ($filteredBuildings as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>完成年: " . htmlspecialchars($building['completionYears']) . "</p>";
        echo "<p>用途: " . htmlspecialchars($building['buildingTypes']) . "</p>";
        echo "</div>";
    }
}

echo "<h2>5. 比較結果</h2>";
echo "<p>建築家の建築物総数: " . count($allBuildings) . "件</p>";
echo "<p>手動フィルタリング: " . $filteredCount . "件</p>";
echo "<p>PHP関数検索: " . $result['total'] . "件</p>";

if ($filteredCount === $result['total']) {
    echo "<p style='color: green;'>✅ 一致: フィルタリング結果は正しく動作しています。</p>";
} else {
    echo "<p style='color: red;'>⚠️ 不一致: フィルタリング結果に問題があります。</p>";
}

echo "<h2>6. 実行されるSQL処理の詳細</h2>";
echo "<h3>6-1. 建築家検索SQL（全件取得）</h3>";
$sql = "
    SELECT b.building_id,
           b.uid,
           b.title,
           b.titleEn,
           b.slug,
           b.lat,
           b.lng,
           b.location,
           b.locationEn_from_datasheetChunkEn as locationEn,
           b.completionYears,
           b.buildingTypes,
           b.buildingTypesEn,
           b.prefectures,
           b.prefecturesEn,
           b.has_photo,
           b.thumbnailUrl,
           b.youtubeUrl,
           b.created_at,
           b.updated_at,
           0 as likes,
           GROUP_CONCAT(
               DISTINCT ia2.name_ja 
               ORDER BY ba2.architect_order, ac2.order_index 
               SEPARATOR ' / '
           ) AS architectJa,
           GROUP_CONCAT(
               DISTINCT ia2.name_en 
               ORDER BY ba2.architect_order, ac2.order_index 
               SEPARATOR ' / '
           ) AS architectEn,
           GROUP_CONCAT(
               DISTINCT ba2.architect_id 
               ORDER BY ba2.architect_order 
               SEPARATOR ','
           ) AS architectIds,
           GROUP_CONCAT(
               DISTINCT ia2.slug 
               ORDER BY ba2.architect_order, ac2.order_index 
               SEPARATOR ','
           ) AS architectSlugs
    FROM buildings_table_3 b
    LEFT JOIN building_architects ba ON b.building_id = ba.building_id
    LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    LEFT JOIN building_architects ba2 ON b.building_id = ba2.building_id
    LEFT JOIN architect_compositions_2 ac2 ON ba2.architect_id = ac2.architect_id
    LEFT JOIN individual_architects_3 ia2 ON ac2.individual_architect_id = ia2.individual_architect_id
    WHERE ia.slug = ?
    GROUP BY b.building_id, b.uid, b.title, b.titleEn, b.slug, b.lat, b.lng, b.location, b.locationEn_from_datasheetChunkEn, b.completionYears, b.buildingTypes, b.buildingTypesEn, b.prefectures, b.prefecturesEn, b.has_photo, b.thumbnailUrl, b.youtubeUrl, b.created_at, b.updated_at
    ORDER BY b.has_photo DESC, b.building_id DESC
    LIMIT 1000 OFFSET 0
";

echo "<pre>" . htmlspecialchars($sql) . "</pre>";

echo "<h3>6-2. PHPフィルタリング処理</h3>";
echo "<p>1. 全件取得（limit=1000）</p>";
echo "<p>2. PHPで完成年フィルタリング: <code>\$building['completionYears'] === '{$completionYears}'</code></p>";
echo "<p>3. フィルタリング後にページネーション適用（limit={$limit}）</p>";

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
</style>
