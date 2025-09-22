<?php
require_once '../src/Utils/Database.php';

try {
    $db = getDB();
    
    // search_typeがnullまたは空のレコードを確認
    $sql = "SELECT query, search_type, COUNT(*) as count FROM global_search_history WHERE search_type IS NULL OR search_type = '' GROUP BY query, search_type ORDER BY count DESC LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "<h2>search_typeがnullまたは空のレコード:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Query</th><th>search_type</th><th>count</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['query']) . "</td>";
        echo "<td>" . ($row['search_type'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 全レコードのsearch_type分布を確認
    $sql2 = "SELECT search_type, COUNT(*) as count FROM global_search_history GROUP BY search_type ORDER BY count DESC";
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute();
    $results2 = $stmt2->fetchAll();
    
    echo "<h2>全レコードのsearch_type分布:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>search_type</th><th>count</th></tr>";
    foreach ($results2 as $row) {
        echo "<tr>";
        echo "<td>" . ($row['search_type'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
