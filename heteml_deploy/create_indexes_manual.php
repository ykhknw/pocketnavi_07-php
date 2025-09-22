<?php
// インデックスを個別に作成
require_once '../config/database.php';

try {
    $db = getDB();
    
    echo "<h1>インデックス個別作成</h1>";
    
    // インデックス作成のSQL配列
    $indexes = [
        "CREATE INDEX idx_buildings_coords_photo ON buildings_table_3(lat, lng, has_photo, building_id)" => "座標検索用複合インデックス",
        "CREATE INDEX idx_buildings_search_optimized ON buildings_table_3(prefectures, buildingTypes, completionYears, has_photo)" => "検索用複合インデックス",
        "CREATE INDEX idx_buildings_architect_search ON buildings_table_3(has_photo, building_id, lat, lng)" => "建築家検索用インデックス",
        "CREATE INDEX idx_buildings_title_search ON buildings_table_3(title, has_photo)" => "タイトル検索用インデックス",
        "CREATE INDEX idx_buildings_title_en_search ON buildings_table_3(titleEn, has_photo)" => "英語タイトル検索用インデックス",
        "CREATE INDEX idx_buildings_location_search ON buildings_table_3(location, has_photo)" => "場所検索用インデックス",
        "CREATE INDEX idx_buildings_year_range ON buildings_table_3(completionYears, has_photo, building_id)" => "完成年範囲検索用インデックス",
        "CREATE INDEX idx_buildings_type_search ON buildings_table_3(buildingTypes, has_photo, lat, lng)" => "建築タイプ検索用インデックス",
        "CREATE INDEX idx_buildings_prefecture_search ON buildings_table_3(prefectures, has_photo, lat, lng)" => "都道府県別検索用インデックス",
        "CREATE INDEX idx_buildings_photo_priority ON buildings_table_3(has_photo DESC, building_id DESC)" => "写真有無での並び替え最適化"
    ];
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($indexes as $sql => $description) {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            echo "<p style='color: green;'>✓ {$description} - 作成成功</p>";
            $successCount++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: orange;'>⚠ {$description} - 既に存在</p>";
            } else {
                echo "<p style='color: red;'>✗ {$description} - エラー: " . $e->getMessage() . "</p>";
                $errorCount++;
            }
        }
    }
    
    // 統計情報更新
    echo "<h2>統計情報更新</h2>";
    $tables = ['buildings_table_3', 'individual_architects_3', 'building_architects', 'architect_compositions_2'];
    
    foreach ($tables as $table) {
        try {
            $sql = "ANALYZE TABLE {$table}";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            echo "<p style='color: blue;'>✓ {$table} - 統計情報更新完了</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ {$table} - エラー: " . $e->getMessage() . "</p>";
        }
    }
    
    // 最適化後のインデックス状況を確認
    echo "<h2>最適化後のインデックス状況</h2>";
    $sql = "SHOW INDEX FROM buildings_table_3";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $indexes = $stmt->fetchAll();
    
    echo "<p>現在のインデックス数: " . count($indexes) . "</p>";
    
    // インデックス一覧を表示
    echo "<h3>インデックス一覧</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Key_name</th><th>Column_name</th><th>Seq_in_index</th><th>Cardinality</th></tr>";
    
    foreach ($indexes as $index) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Seq_in_index']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Cardinality']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // パフォーマンステスト
    echo "<h2>パフォーマンステスト</h2>";
    
    // 座標検索のEXPLAIN分析
    $explainSql = "
        EXPLAIN SELECT b.building_id, b.title, b.lat, b.lng, b.location
        FROM buildings_table_3 b
        WHERE b.lat IS NOT NULL AND b.lng IS NOT NULL
        AND (6371 * acos(cos(radians(35.14961)) * cos(radians(b.lat)) * 
             cos(radians(b.lng) - radians(137.035537)) + 
             sin(radians(35.14961)) * sin(radians(b.lat)))) <= 5
        ORDER BY b.has_photo DESC, b.building_id DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($explainSql);
    $stmt->execute();
    $explain = $stmt->fetchAll();
    
    echo "<h3>座標検索クエリのEXPLAIN結果</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>id</th><th>select_type</th><th>table</th><th>type</th><th>possible_keys</th><th>key</th><th>key_len</th><th>ref</th><th>rows</th><th>Extra</th></tr>";
    foreach ($explain as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['select_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['table']) . "</td>";
        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['possible_keys']) . "</td>";
        echo "<td>" . htmlspecialchars($row['key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['key_len']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ref']) . "</td>";
        echo "<td>" . htmlspecialchars($row['rows']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 実行時間の測定
    echo "<h3>クエリ実行時間測定</h3>";
    $startTime = microtime(true);
    
    $testSql = "
        SELECT b.building_id, b.title, b.lat, b.lng, b.location
        FROM buildings_table_3 b
        WHERE b.lat IS NOT NULL AND b.lng IS NOT NULL
        AND (6371 * acos(cos(radians(35.14961)) * cos(radians(b.lat)) * 
             cos(radians(b.lng) - radians(137.035537)) + 
             sin(radians(35.14961)) * sin(radians(b.lat)))) <= 5
        ORDER BY b.has_photo DESC, b.building_id DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($testSql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // ミリ秒
    
    echo "<p>実行時間: " . round($executionTime, 2) . "ms</p>";
    echo "<p>取得件数: " . count($results) . "件</p>";
    
    if ($executionTime < 100) {
        echo "<p style='color: green;'>✓ パフォーマンス良好（100ms未満）</p>";
    } elseif ($executionTime < 500) {
        echo "<p style='color: orange;'>⚠ パフォーマンス改善の余地あり（100-500ms）</p>";
    } else {
        echo "<p style='color: red;'>✗ パフォーマンス要改善（500ms以上）</p>";
    }
    
    echo "<h2>最適化完了</h2>";
    echo "<p>成功: {$successCount}件, エラー: {$errorCount}件</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
