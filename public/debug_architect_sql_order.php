<?php
// 建築家検索のSQL順序デバッグ
require_once '../config/database.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// パラメータ設定
$architectSlug = $_GET['architect_slug'] ?? 'inui-architects';
$query = $_GET['q'] ?? '美術館';
$lang = 'ja';
$page = 1;
$limit = 10;

echo "<h1>建築家検索のSQL順序デバッグ</h1>";
echo "<h2>パラメータ</h2>";
echo "<p>architectSlug: " . htmlspecialchars($architectSlug) . "</p>";
echo "<p>query: " . htmlspecialchars($query) . "</p>";

$db = getDB();

echo "<h2>1. 建築家検索のSQL（ORDER BY確認）</h2>";
$offset = ($page - 1) * $limit;
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
    LIMIT {$limit} OFFSET {$offset}
";

echo "<p>SQL:</p>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";

$stmt = $db->prepare($sql);
$stmt->execute([$architectSlug]);
$results = $stmt->fetchAll();

echo "<p>結果件数: " . count($results) . "件</p>";

echo "<h3>検索結果（全件）</h3>";
foreach ($results as $index => $building) {
    $isMuseum = (mb_strpos(mb_strtolower($building['title']), '美術館') !== false) || 
                (mb_strpos(mb_strtolower($building['buildingTypes']), '美術館') !== false);
    $highlight = $isMuseum ? 'background-color: yellow;' : '';
    
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px; {$highlight}'>";
    echo "<p>順位: " . ($index + 1) . " (ID: " . $building['building_id'] . ")</p>";
    echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
    echo "<p>用途: " . htmlspecialchars($building['buildingTypes']) . "</p>";
    echo "<p>has_photo: " . ($building['has_photo'] ? '1' : '0') . "</p>";
    echo "<p>美術館マッチ: " . ($isMuseum ? 'YES' : 'NO') . "</p>";
    echo "</div>";
}

echo "<h2>2. 美術館を含む建築物の検索</h2>";
$museumSql = "
    SELECT b.building_id, b.title, b.buildingTypes, b.has_photo
    FROM buildings_table_3 b
    INNER JOIN building_architects ba ON b.building_id = ba.building_id
    INNER JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    INNER JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    WHERE ia.slug = ? 
    AND (b.title LIKE ? OR b.buildingTypes LIKE ?)
    ORDER BY b.has_photo DESC, b.building_id DESC
";

$stmt = $db->prepare($museumSql);
$stmt->execute([$architectSlug, '%美術館%', '%美術館%']);
$museumResults = $stmt->fetchAll();

echo "<p>美術館を含む建築物: " . count($museumResults) . "件</p>";

foreach ($museumResults as $index => $building) {
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px; background-color: lightgreen;'>";
    echo "<p>ID: " . $building['building_id'] . "</p>";
    echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
    echo "<p>用途: " . htmlspecialchars($building['buildingTypes']) . "</p>";
    echo "<p>has_photo: " . ($building['has_photo'] ? '1' : '0') . "</p>";
    echo "</div>";
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
</style>
