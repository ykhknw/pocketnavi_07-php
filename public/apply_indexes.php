<?php
// インデックス最適化の適用
require_once '../config/database.php';

try {
    $db = getDB();
    
    echo "<h1>データベースインデックス最適化</h1>";
    
    // 現在のインデックス状況を確認
    echo "<h2>最適化前のインデックス状況</h2>";
    $sql = "SHOW INDEX FROM buildings_table_3";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $indexes = $stmt->fetchAll();
    
    echo "<p>現在のインデックス数: " . count($indexes) . "</p>";
    
    // インデックス作成のSQLを読み込み
    $indexSql = file_get_contents('../database/optimize_indexes.sql');
    $statements = explode(';', $indexSql);
    
    $successCount = 0;
    $errorCount = 0;
    
    echo "<h2>インデックス作成結果</h2>";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $stmt = $db->prepare($statement);
            $stmt->execute();
            
            if (strpos($statement, 'CREATE INDEX') !== false) {
                $indexName = extractIndexName($statement);
                echo "<p style='color: green;'>✓ インデックス作成成功: {$indexName}</p>";
                $successCount++;
            } elseif (strpos($statement, 'ANALYZE TABLE') !== false) {
                $tableName = extractTableName($statement);
                echo "<p style='color: blue;'>✓ 統計情報更新: {$tableName}</p>";
            }
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: orange;'>⚠ インデックス既存: " . extractIndexName($statement) . "</p>";
            } else {
                echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
                $errorCount++;
            }
        }
    }
    
    // 最適化後のインデックス状況を確認
    echo "<h2>最適化後のインデックス状況</h2>";
    $sql = "SHOW INDEX FROM buildings_table_3";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $newIndexes = $stmt->fetchAll();
    
    echo "<p>新しいインデックス数: " . count($newIndexes) . "</p>";
    echo "<p>追加されたインデックス数: " . (count($newIndexes) - count($indexes)) . "</p>";
    
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

// ヘルパー関数
function extractIndexName($sql) {
    if (preg_match('/CREATE INDEX\s+(\w+)/i', $sql, $matches)) {
        return $matches[1];
    }
    return 'Unknown';
}

function extractTableName($sql) {
    if (preg_match('/ANALYZE TABLE\s+(\w+)/i', $sql, $matches)) {
        return $matches[1];
    }
    return 'Unknown';
}
?>
