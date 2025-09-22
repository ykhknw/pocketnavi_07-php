<?php
// 複数の建築家がいる建築物のテストデータを作成
require_once '../config/database.php';

echo "<h1>複数の建築家がいる建築物のテストデータ作成</h1>";

try {
    $db = getDB();
    
    // 既存の建築物を取得
    $sql = "SELECT building_id, title FROM buildings_table_3 WHERE building_id IS NOT NULL LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $building = $stmt->fetch();
    
    if (!$building) {
        echo "<p>建築物が見つかりませんでした。</p>";
        exit;
    }
    
    echo "<p>対象建築物: " . htmlspecialchars($building['title']) . " (ID: " . $building['building_id'] . ")</p>";
    
    // 既存の建築家を取得
    $sql = "SELECT individual_architect_id, name_ja, name_en, slug FROM individual_architects_3 LIMIT 3";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $architects = $stmt->fetchAll();
    
    if (count($architects) < 2) {
        echo "<p>建築家が2人以上見つかりませんでした。</p>";
        exit;
    }
    
    echo "<p>使用する建築家:</p>";
    foreach ($architects as $architect) {
        echo "<p>- " . htmlspecialchars($architect['name_ja']) . " (ID: " . $architect['individual_architect_id'] . ")</p>";
    }
    
    // 既存の建築家関連データを削除
    $sql = "DELETE FROM building_architects WHERE building_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$building['building_id']]);
    echo "<p>既存の建築家関連データを削除しました。</p>";
    
    // 複数の建築家を追加
    $architectCompositions = [];
    foreach ($architects as $index => $architect) {
        // architect_compositions_2テーブルにデータを挿入
        $sql = "INSERT INTO architect_compositions_2 (architect_id, individual_architect_id, order_index) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $architectId = 9999 + $index; // 仮のarchitect_id
        $stmt->execute([$architectId, $architect['individual_architect_id'], $index + 1]);
        $architectCompositions[] = $architectId;
    }
    
    // building_architectsテーブルにデータを挿入
    foreach ($architectCompositions as $index => $architectId) {
        $sql = "INSERT INTO building_architects (building_id, architect_id, architect_order) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$building['building_id'], $architectId, $index + 1]);
    }
    
    echo "<p>複数の建築家を追加しました。</p>";
    
    // 結果を確認
    $sql = "
        SELECT b.building_id,
               b.title,
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
        WHERE b.building_id = ?
        GROUP BY b.building_id, b.title
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$building['building_id']]);
    $result = $stmt->fetch();
    
    echo "<h3>結果確認</h3>";
    echo "<p><strong>建築物:</strong> " . htmlspecialchars($result['title']) . "</p>";
    echo "<p><strong>architectJa:</strong> " . htmlspecialchars($result['architectJa'] ?? 'NULL') . "</p>";
    echo "<p><strong>architectEn:</strong> " . htmlspecialchars($result['architectEn'] ?? 'NULL') . "</p>";
    echo "<p><strong>architectIds:</strong> " . htmlspecialchars($result['architectIds'] ?? 'NULL') . "</p>";
    echo "<p><strong>architectSlugs:</strong> " . htmlspecialchars($result['architectSlugs'] ?? 'NULL') . "</p>";
    
    // 建築家データの配列変換テスト
    $architects = [];
    if (!empty($result['architectJa']) && $result['architectJa'] !== '') {
        $namesJa = explode(' / ', $result['architectJa']);
        $namesEn = !empty($result['architectEn']) && $result['architectEn'] !== '' ? explode(' / ', $result['architectEn']) : [];
        $architectIds = !empty($result['architectIds']) && $result['architectIds'] !== '' ? explode(',', $result['architectIds']) : [];
        $architectSlugs = !empty($result['architectSlugs']) && $result['architectSlugs'] !== '' ? explode(',', $result['architectSlugs']) : [];
        
        for ($i = 0; $i < count($namesJa); $i++) {
            $architects[] = [
                'architect_id' => isset($architectIds[$i]) ? intval($architectIds[$i]) : 0,
                'architectJa' => trim($namesJa[$i]),
                'architectEn' => isset($namesEn[$i]) ? trim($namesEn[$i]) : trim($namesJa[$i]),
                'slug' => isset($architectSlugs[$i]) ? trim($architectSlugs[$i]) : ''
            ];
        }
    }
    
    echo "<h4>変換後の建築家データ:</h4>";
    echo "<pre>";
    print_r($architects);
    echo "</pre>";
    
    echo "<h4>建築家バッジ表示テスト:</h4>";
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
    
    echo "<p><a href='/debug_architect_buildings.php?architect_slug=" . urlencode($architects[0]['slug']) . "&lang=ja'>建築家ページでテスト</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3, h4 { color: #333; }
pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
</style>
