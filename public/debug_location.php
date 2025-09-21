<?php
// 現在地検索のデバッグ用スクリプト

require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';

echo "<h1>現在地検索デバッグ</h1>";

$userLat = 35.1879;
$userLng = 137.0026;
$radiusKm = 5;
$lang = 'ja';

echo "<h2>1. 検索パラメータ</h2>";
echo "<p>緯度: $userLat</p>";
echo "<p>経度: $userLng</p>";
echo "<p>半径: {$radiusKm}km</p>";
echo "<p>言語: $lang</p>";

echo "<h2>2. 現在地検索テスト</h2>";
try {
    $result = searchBuildingsByLocation($userLat, $userLng, $radiusKm, 1, false, false, $lang, 10);
    echo "<p>結果数: " . $result['total'] . "</p>";
    echo "<p>ページ数: " . $result['totalPages'] . "</p>";
    echo "<p>現在のページ: " . $result['currentPage'] . "</p>";
    
    if (!empty($result['buildings'])) {
        echo "<p style='color: green;'>✓ 建築物データ取得成功</p>";
        echo "<h3>取得した建築物（最初の5件）:</h3>";
        foreach (array_slice($result['buildings'], 0, 5) as $building) {
            echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
            echo "<p><strong>タイトル:</strong> " . htmlspecialchars($building['title']) . "</p>";
            echo "<p><strong>場所:</strong> " . htmlspecialchars($building['location']) . "</p>";
            echo "<p><strong>距離:</strong> " . ($building['distance'] ?? 'N/A') . "km</p>";
            echo "<p><strong>座標:</strong> " . $building['lat'] . ", " . $building['lng'] . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'>✗ 建築物データが取得できませんでした</p>";
        
        // 生データを直接確認
        echo "<h3>生データの確認:</h3>";
        try {
            $db = getDB();
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
                       (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(b.lat)) * COS(RADIANS(b.lng) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(b.lat)))) as distance,
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
                WHERE (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(b.lat)) * COS(RADIANS(b.lng) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(b.lat)))) < ?
                AND b.lat IS NOT NULL AND b.lng IS NOT NULL AND b.lat != 0 AND b.lng != 0
                AND b.location IS NOT NULL AND b.location != ''
                GROUP BY b.building_id
                ORDER BY distance ASC
                LIMIT 3
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$userLat, $userLng, $userLat, $userLat, $userLng, $userLat, $radiusKm]);
            $rows = $stmt->fetchAll();
            
            if (!empty($rows)) {
                echo "<p style='color: orange;'>生SQLクエリでデータ取得成功 - " . count($rows) . "件</p>";
                foreach ($rows as $row) {
                    echo "<div style='border: 1px solid #orange; margin: 5px; padding: 10px; background: #fff3cd;'>";
                    echo "<p><strong>生データ - タイトル:</strong> " . htmlspecialchars($row['title']) . "</p>";
                    echo "<p><strong>生データ - 場所:</strong> " . htmlspecialchars($row['location']) . "</p>";
                    echo "<p><strong>生データ - 距離:</strong> " . round($row['distance'], 2) . "km</p>";
                    echo "<p><strong>生データ - 建築家:</strong> " . htmlspecialchars($row['architectJa']) . "</p>";
                    
                    // transformBuildingData()をテスト
                    echo "<h4>transformBuildingData()テスト:</h4>";
                    try {
                        $transformed = transformBuildingData($row, $lang);
                        echo "<p style='color: green;'>✓ 変換成功</p>";
                        echo "<p><strong>変換後 - タイトル:</strong> " . htmlspecialchars($transformed['title']) . "</p>";
                        echo "<p><strong>変換後 - 建築家数:</strong> " . count($transformed['architects']) . "</p>";
                    } catch (Exception $e) {
                        echo "<p style='color: red;'>✗ 変換エラー: " . $e->getMessage() . "</p>";
                    }
                    echo "</div>";
                }
            } else {
                echo "<p style='color: red;'>生SQLクエリでもデータが取得できませんでした</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>生データ確認エラー: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 現在地検索エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>3. 生のSQLクエリテスト（距離計算）</h2>";
try {
    $db = getDB();
    
    // 距離計算のSQLクエリ
    $sql = "
        SELECT building_id, title, location, lat, lng,
               (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(lat)) * COS(RADIANS(lng) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(lat)))) as distance
        FROM buildings_table_3 
        WHERE lat IS NOT NULL AND lng IS NOT NULL 
        AND lat != 0 AND lng != 0
        AND location IS NOT NULL AND location != ''
        HAVING distance < ?
        ORDER BY distance ASC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$userLat, $userLng, $userLat, $radiusKm]);
    $rows = $stmt->fetchAll();
    
    if (!empty($rows)) {
        echo "<p style='color: green;'>✓ 生SQLクエリ成功 - " . count($rows) . "件</p>";
        foreach ($rows as $row) {
            echo "<p>" . htmlspecialchars($row['title']) . " - " . htmlspecialchars($row['location']) . " - 距離: " . round($row['distance'], 2) . "km</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ 生SQLクエリで結果がありません</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 生SQLクエリエラー: " . $e->getMessage() . "</p>";
}

echo "<h2>4. 座標範囲の確認</h2>";
try {
    $db = getDB();
    
    // 指定座標周辺の建築物を確認（距離計算なし）
    $sql = "
        SELECT building_id, title, location, lat, lng
        FROM buildings_table_3 
        WHERE lat BETWEEN ? AND ?
        AND lng BETWEEN ? AND ?
        AND lat IS NOT NULL AND lng IS NOT NULL 
        AND lat != 0 AND lng != 0
        LIMIT 10
    ";
    
    // 緯度・経度の範囲を計算（約5km）
    $latRange = 0.045; // 約5km
    $lngRange = 0.045; // 約5km
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $userLat - $latRange, $userLat + $latRange,
        $userLng - $lngRange, $userLng + $lngRange
    ]);
    $rows = $stmt->fetchAll();
    
    if (!empty($rows)) {
        echo "<p style='color: green;'>✓ 座標範囲内に建築物が見つかりました - " . count($rows) . "件</p>";
        foreach ($rows as $row) {
            echo "<p>" . htmlspecialchars($row['title']) . " - " . htmlspecialchars($row['location']) . " - 座標: " . $row['lat'] . ", " . $row['lng'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ 座標範囲内に建築物が見つかりませんでした</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 座標範囲確認エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>5. 次のステップ</h2>";
echo "<p>このデバッグ結果を確認して、問題の原因を特定してください。</p>";
?>
