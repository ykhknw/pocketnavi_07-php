<?php
// 複数の建築家がいる建築物のデバッグ
require_once '../config/database.php';

echo "<h1>複数の建築家がいる建築物のデバッグ</h1>";

try {
    $db = getDB();
    
    // 複数の建築家がいる建築物を検索
    $sql = "
        SELECT b.building_id,
               b.title,
               b.titleEn,
               COUNT(DISTINCT ia.individual_architect_id) as architect_count,
               GROUP_CONCAT(
                   DISTINCT ia.name_ja 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ' / '
               ) AS architectJa,
               GROUP_CONCAT(
                   DISTINCT ia.name_en 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ' / '
               ) AS architectEn,
               GROUP_CONCAT(
                   DISTINCT ba.architect_id 
                   ORDER BY ba.architect_order 
                   SEPARATOR ','
               ) AS architectIds,
               GROUP_CONCAT(
                   DISTINCT ia.slug 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ','
               ) AS architectSlugs
        FROM buildings_table_3 b
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id
        LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
        LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
        WHERE b.has_photo = 1
        GROUP BY b.building_id, b.title, b.titleEn
        HAVING architect_count > 1
        ORDER BY architect_count DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    echo "<p>複数の建築家がいる建築物数: " . count($rows) . "</p>";
    
    foreach ($rows as $index => $row) {
        echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 10px;'>";
        echo "<h4>建築物 " . ($index + 1) . ": " . htmlspecialchars($row['title']) . "</h4>";
        echo "<p><strong>建築家数:</strong> " . $row['architect_count'] . "</p>";
        echo "<p><strong>architectJa:</strong> " . htmlspecialchars($row['architectJa'] ?? 'NULL') . "</p>";
        echo "<p><strong>architectEn:</strong> " . htmlspecialchars($row['architectEn'] ?? 'NULL') . "</p>";
        echo "<p><strong>architectIds:</strong> " . htmlspecialchars($row['architectIds'] ?? 'NULL') . "</p>";
        echo "<p><strong>architectSlugs:</strong> " . htmlspecialchars($row['architectSlugs'] ?? 'NULL') . "</p>";
        
        // 建築家データの配列変換テスト
        $architects = [];
        if (!empty($row['architectJa']) && $row['architectJa'] !== '') {
            $namesJa = explode(' / ', $row['architectJa']);
            $namesEn = !empty($row['architectEn']) && $row['architectEn'] !== '' ? explode(' / ', $row['architectEn']) : [];
            $architectIds = !empty($row['architectIds']) && $row['architectIds'] !== '' ? explode(',', $row['architectIds']) : [];
            $architectSlugs = !empty($row['architectSlugs']) && $row['architectSlugs'] !== '' ? explode(',', $row['architectSlugs']) : [];
            
            for ($i = 0; $i < count($namesJa); $i++) {
                $architects[] = [
                    'architect_id' => isset($architectIds[$i]) ? intval($architectIds[$i]) : 0,
                    'architectJa' => trim($namesJa[$i]),
                    'architectEn' => isset($namesEn[$i]) ? trim($namesEn[$i]) : trim($namesJa[$i]),
                    'slug' => isset($architectSlugs[$i]) ? trim($architectSlugs[$i]) : ''
                ];
            }
        }
        
        echo "<h5>変換後の建築家データ:</h5>";
        echo "<pre>";
        print_r($architects);
        echo "</pre>";
        
        echo "<h5>建築家バッジ表示テスト:</h5>";
        if (!empty($architects)) {
            echo "<div style='display: flex; flex-wrap: wrap; gap: 5px;'>";
            foreach ($architects as $architect) {
                echo "<span style='background: #f0f4ff; color: #4263eb; border: 1px solid #e6eeff; border-radius: 16px; padding: 4px 12px; font-size: 12px;'>";
                echo htmlspecialchars($architect['architectJa']);
                echo "</span>";
            }
            echo "</div>";
        } else {
            echo "<p style='color: red;'>建築家データがありません</p>";
        }
        
        echo "</div>";
    }
    
    // 特定の建築家の建築物を検索
    echo "<h3>特定の建築家の建築物検索</h3>";
    
    $architectSlug = 'u-architects';
    $sql2 = "
        SELECT b.building_id,
               b.title,
               b.titleEn,
               GROUP_CONCAT(
                   DISTINCT ia.name_ja 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ' / '
               ) AS architectJa,
               GROUP_CONCAT(
                   DISTINCT ia.name_en 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ' / '
               ) AS architectEn,
               GROUP_CONCAT(
                   DISTINCT ba.architect_id 
                   ORDER BY ba.architect_order 
                   SEPARATOR ','
               ) AS architectIds,
               GROUP_CONCAT(
                   DISTINCT ia.slug 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ','
               ) AS architectSlugs
        FROM buildings_table_3 b
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id
        LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
        LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
        WHERE ia.slug = ?
        GROUP BY b.building_id, b.title, b.titleEn
        ORDER BY b.has_photo DESC, b.building_id DESC
        LIMIT 5
    ";
    
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute([$architectSlug]);
    $rows2 = $stmt2->fetchAll();
    
    echo "<p>建築家 '{$architectSlug}' の建築物数: " . count($rows2) . "</p>";
    
    foreach ($rows2 as $index => $row) {
        echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 10px;'>";
        echo "<h4>建築物 " . ($index + 1) . ": " . htmlspecialchars($row['title']) . "</h4>";
        echo "<p><strong>architectJa:</strong> " . htmlspecialchars($row['architectJa'] ?? 'NULL') . "</p>";
        echo "<p><strong>architectEn:</strong> " . htmlspecialchars($row['architectEn'] ?? 'NULL') . "</p>";
        echo "<p><strong>architectIds:</strong> " . htmlspecialchars($row['architectIds'] ?? 'NULL') . "</p>";
        echo "<p><strong>architectSlugs:</strong> " . htmlspecialchars($row['architectSlugs'] ?? 'NULL') . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3, h4, h5 { color: #333; }
pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
</style>
