<?php
// 直接SQLテスト用ファイル
require_once '../config/database.php';

echo "<h2>直接SQLテスト</h2>";

try {
    $db = getDB();
    
    // テスト地点
    $userLat = 35.1722;
    $userLng = 136.974;
    $radiusKm = 5;
    
    echo "<h3>テスト地点</h3>";
    echo "緯度: {$userLat}<br>";
    echo "経度: {$userLng}<br>";
    echo "半径: {$radiusKm}km<br>";
    
    // 直接SQLで検索
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
               (
                   6371 * acos(
                       cos(radians(?)) * cos(radians(b.lat)) * 
                       cos(radians(b.lng) - radians(?)) + 
                       sin(radians(?)) * sin(radians(b.lat))
                   )
               ) AS distance
        FROM buildings_table_3 b
        WHERE b.lat IS NOT NULL AND b.lng IS NOT NULL AND (
        6371 * acos(
            cos(radians(?)) * cos(radians(b.lat)) * 
            cos(radians(b.lng) - radians(?)) + 
            sin(radians(?)) * sin(radians(b.lat))
        )
    ) <= ?
        ORDER BY distance ASC
        LIMIT 10
    ";
    
    $params = [$userLat, $userLng, $userLat, $userLat, $userLng, $userLat, $radiusKm];
    
    echo "<h3>SQLクエリ</h3>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<h3>パラメータ</h3>";
    echo "<pre>" . print_r($params, true) . "</pre>";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($params);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        echo "<h3>SQLエラー</h3>";
        echo "<pre>" . print_r($errorInfo, true) . "</pre>";
        return;
    }
    
    $rows = $stmt->fetchAll();
    
    echo "<h3>検索結果</h3>";
    echo "結果数: " . count($rows) . "<br>";
    
    if (count($rows) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>タイトル</th><th>場所</th><th>距離(km)</th><th>緯度</th><th>経度</th></tr>";
        foreach ($rows as $row) {
            $distance = round($row['distance'], 2);
            echo "<tr>";
            echo "<td>" . $row['building_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['location']) . "</td>";
            echo "<td>" . $distance . "</td>";
            echo "<td>" . $row['lat'] . "</td>";
            echo "<td>" . $row['lng'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "結果が見つかりません<br>";
    }
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
    echo "スタックトレース: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>テスト完了</h3>";
?>
