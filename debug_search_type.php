<?php
require_once 'src/Utils/Database.php';

try {
    $db = getDB();
    
    // search_typeがnullまたは空のレコードを確認
    $sql = "SELECT query, search_type, COUNT(*) as count FROM global_search_history WHERE search_type IS NULL OR search_type = '' GROUP BY query, search_type ORDER BY count DESC LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "search_typeがnullまたは空のレコード:\n";
    foreach ($results as $row) {
        echo "Query: " . $row['query'] . ", search_type: " . ($row['search_type'] ?? 'NULL') . ", count: " . $row['count'] . "\n";
    }
    
    // 全レコードのsearch_type分布を確認
    $sql2 = "SELECT search_type, COUNT(*) as count FROM global_search_history GROUP BY search_type ORDER BY count DESC";
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute();
    $results2 = $stmt2->fetchAll();
    
    echo "\n全レコードのsearch_type分布:\n";
    foreach ($results2 as $row) {
        echo "search_type: " . ($row['search_type'] ?? 'NULL') . ", count: " . $row['count'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
