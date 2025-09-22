<?php
// 建築家検索のSQLデバッグ
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// パラメータ設定
$architectSlug = $_GET['architect_slug'] ?? 'nikken-sekkei';
$lang = 'ja';
$page = 1;
$limit = 10;

echo "<h1>建築家検索のSQLデバッグ</h1>";
echo "<h2>パラメータ</h2>";
echo "<p>architectSlug: " . htmlspecialchars($architectSlug) . "</p>";

$db = getDB();

echo "<h2>1. カウントクエリのテスト</h2>";
$countSql = "
    SELECT COUNT(DISTINCT b.building_id) as total
    FROM buildings_table_3 b
    LEFT JOIN building_architects ba ON b.building_id = ba.building_id
    LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    WHERE ia.slug = ?
";

echo "<p>カウントクエリ:</p>";
echo "<pre>" . htmlspecialchars($countSql) . "</pre>";

$stmt = $db->prepare($countSql);
$stmt->execute([$architectSlug]);
$countResult = $stmt->fetch();
echo "<p>カウント結果: " . print_r($countResult, true) . "</p>";

if ($countResult) {
    if (isset($countResult['total'])) {
        echo "<p>total: " . $countResult['total'] . "</p>";
    } elseif (isset($countResult[0])) {
        echo "<p>total (index 0): " . $countResult[0] . "</p>";
    } else {
        echo "<p>total: 0 (no valid key found)</p>";
    }
} else {
    echo "<p>カウント結果がnullです</p>";
}

echo "<h2>2. データ取得クエリのテスト</h2>";
$offset = ($page - 1) * $limit;
$dataSql = "
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
    LIMIT {$limit} OFFSET {$offset}
";

echo "<p>データ取得クエリ:</p>";
echo "<pre>" . htmlspecialchars($dataSql) . "</pre>";

$stmt = $db->prepare($dataSql);
$stmt->execute([$architectSlug]);
$dataResults = $stmt->fetchAll();

echo "<p>データ取得結果数: " . count($dataResults) . "件</p>";

if (!empty($dataResults)) {
    echo "<h3>データ取得結果詳細（最初の5件）</h3>";
    foreach (array_slice($dataResults, 0, 5) as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>順位: " . ($index + 1) . "</p>";
        echo "<p>ID: " . $building['building_id'] . "</p>";
        echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>建築家: " . htmlspecialchars($building['architectJa']) . "</p>";
        echo "</div>";
    }
}

echo "<h2>3. PHP関数での検索結果</h2>";
$result = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit);
echo "<p>PHP関数での検索結果件数: " . $result['total'] . "件</p>";
echo "<p>totalPages: " . $result['totalPages'] . "</p>";

if (!empty($result['buildings'])) {
    echo "<h3>PHP関数での検索結果詳細</h3>";
    foreach ($result['buildings'] as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>順位: " . ($index + 1) . "</p>";
        echo "<p>ID: " . $building['building_id'] . "</p>";
        echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>建築家: " . htmlspecialchars(implode(', ', array_column($building['architects'], 'architectJa'))) . "</p>";
        echo "</div>";
    }
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
</style>
