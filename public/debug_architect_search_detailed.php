<?php
// 建築家検索の詳細デバッグ
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';
require_once '../src/Services/BuildingService.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// 言語設定
$lang = InputValidator::validateLanguage($_GET['lang'] ?? 'ja');

// 建築家スラッグを取得
$architectSlug = $_GET['architect_slug'] ?? 'u-architects';

echo "<h1>建築家検索の詳細デバッグ</h1>";
echo "<h2>建築家スラッグ: " . htmlspecialchars($architectSlug) . "</h2>";

try {
    $buildingService = new BuildingService();
    
    // 建築家による建築物検索を実行
    echo "<h3>BuildingService::searchByArchitectSlug の実行</h3>";
    $result = $buildingService->searchByArchitectSlug($architectSlug, 1, $lang, 10);
    
    echo "<h4>検索結果</h4>";
    echo "<p>総件数: " . $result['total'] . "</p>";
    echo "<p>建築物数: " . count($result['buildings']) . "</p>";
    
    if (!empty($result['buildings'])) {
        echo "<h4>建築物の詳細データ</h4>";
        foreach ($result['buildings'] as $index => $building) {
            echo "<div style='border: 1px solid #ccc; margin: 10px 0; padding: 10px;'>";
            echo "<h5>建築物 " . ($index + 1) . ": " . htmlspecialchars($building['title']) . "</h5>";
            
            // 建築家データの詳細表示
            echo "<h6>建築家データ（生データ）:</h6>";
            echo "<pre>";
            if (isset($building['architects'])) {
                print_r($building['architects']);
            } else {
                echo "建築家データがありません";
            }
            echo "</pre>";
            
            // 建築家バッジの表示テスト
            echo "<h6>建築家バッジ表示テスト:</h6>";
            if (!empty($building['architects'])) {
                echo "<div style='display: flex; flex-wrap: wrap; gap: 5px;'>";
                foreach ($building['architects'] as $architect) {
                    echo "<span style='background: #f0f4ff; color: #4263eb; border: 1px solid #e6eeff; border-radius: 16px; padding: 4px 12px; font-size: 12px;'>";
                    echo htmlspecialchars($lang === 'ja' ? $architect['architectJa'] : $architect['architectEn']);
                    echo "</span>";
                }
                echo "</div>";
            } else {
                echo "<p style='color: red;'>建築家バッジが表示されません</p>";
            }
            
            echo "</div>";
        }
    } else {
        echo "<p>建築物が見つかりませんでした。</p>";
    }
    
    // 直接SQLクエリでデバッグ
    echo "<h3>直接SQLクエリでのデバッグ</h3>";
    
    $db = getDB();
    
    // 建築家スラッグで建築物を検索するクエリ（BuildingServiceと同じ）
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
        GROUP BY b.building_id, b.uid, b.title, b.titleEn, b.slug, b.lat, b.lng, b.location, b.locationEn_from_datasheetChunkEn, b.completionYears, b.buildingTypes, b.buildingTypesEn, b.prefectures, b.prefecturesEn, b.has_photo, b.thumbnailUrl, b.youtubeUrl, b.created_at, b.updated_at
        ORDER BY b.has_photo DESC, b.building_id DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$architectSlug]);
    $rows = $stmt->fetchAll();
    
    echo "<p>SQLクエリ結果件数: " . count($rows) . "</p>";
    
    foreach ($rows as $index => $row) {
        echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 10px;'>";
        echo "<h5>建築物 " . ($index + 1) . ": " . htmlspecialchars($row['title']) . "</h5>";
        echo "<p><strong>architectJa:</strong> " . htmlspecialchars($row['architectJa'] ?? 'NULL') . "</p>";
        echo "<p><strong>architectEn:</strong> " . htmlspecialchars($row['architectEn'] ?? 'NULL') . "</p>";
        echo "<p><strong>architectIds:</strong> " . htmlspecialchars($row['architectIds'] ?? 'NULL') . "</p>";
        echo "<p><strong>architectSlugs:</strong> " . htmlspecialchars($row['architectSlugs'] ?? 'NULL') . "</p>";
        
        // 建築家データの配列変換テスト（BuildingServiceと同じロジック）
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
        
        echo "<h6>変換後の建築家データ:</h6>";
        echo "<pre>";
        print_r($architects);
        echo "</pre>";
        
        echo "<h6>建築家バッジ表示テスト:</h6>";
        if (!empty($architects)) {
            echo "<div style='display: flex; flex-wrap: wrap; gap: 5px;'>";
            foreach ($architects as $architect) {
                echo "<span style='background: #f0f4ff; color: #4263eb; border: 1px solid #e6eeff; border-radius: 16px; padding: 4px 12px; font-size: 12px;'>";
                echo htmlspecialchars($lang === 'ja' ? $architect['architectJa'] : $architect['architectEn']);
                echo "</span>";
            }
            echo "</div>";
        } else {
            echo "<p style='color: red;'>建築家データがありません</p>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3, h4, h5, h6 { color: #333; }
pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
</style>
