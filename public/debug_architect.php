<?php
// 建築家ページのデバッグ用スクリプト

require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';

echo "<h1>建築家ページデバッグ</h1>";

$architectSlug = 'kajima-corporation';
$lang = 'ja';

echo "<h2>1. 建築家情報の取得テスト</h2>";
try {
    $architectInfo = getArchitectBySlug($architectSlug, $lang);
    if ($architectInfo) {
        echo "<p style='color: green;'>✓ 建築家情報取得成功</p>";
        echo "<p><strong>建築家名（日本語）:</strong> " . htmlspecialchars($architectInfo['name_ja']) . "</p>";
        echo "<p><strong>建築家名（英語）:</strong> " . htmlspecialchars($architectInfo['name_en']) . "</p>";
        echo "<p><strong>ウェブサイト:</strong> " . htmlspecialchars($architectInfo['individual_website']) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ 建築家情報が取得できませんでした</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築家情報取得エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>2. 建築家の建築物検索テスト</h2>";
try {
    $result = searchBuildingsByArchitectSlug($architectSlug, 1, $lang, 10);
    echo "<p>結果数: " . $result['total'] . "</p>";
    echo "<p>ページ数: " . $result['totalPages'] . "</p>";
    echo "<p>現在のページ: " . $result['currentPage'] . "</p>";
    
    if (!empty($result['buildings'])) {
        echo "<p style='color: green;'>✓ 建築物データ取得成功</p>";
        echo "<h3>取得した建築物（最初の3件）:</h3>";
        foreach (array_slice($result['buildings'], 0, 3) as $building) {
            echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
            echo "<p><strong>タイトル:</strong> " . htmlspecialchars($building['title']) . "</p>";
            echo "<p><strong>タイトルEn:</strong> " . htmlspecialchars($building['titleEn']) . "</p>";
            echo "<p><strong>場所:</strong> " . htmlspecialchars($building['location']) . "</p>";
            echo "<p><strong>完成年:</strong> " . $building['completionYears'] . "</p>";
            echo "<p><strong>建築家:</strong> " . htmlspecialchars(implode(', ', array_column($building['architects'], 'architectJa'))) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'>✗ 建築物データが取得できませんでした</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築物検索エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>3. 生のSQLクエリテスト</h2>";
try {
    $db = getDB();
    
    // 建築家のスラッグで建築物を検索するSQL
    $sql = "
        SELECT b.building_id, b.title, b.titleEn, b.location, b.completionYears,
               GROUP_CONCAT(DISTINCT ia.name_ja ORDER BY ba.architect_order, ac.order_index SEPARATOR ' / ') AS architectJa
        FROM buildings_table_3 b
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id
        LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
        LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
        WHERE ia.slug = :architect_slug
        GROUP BY b.building_id
        LIMIT 5
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':architect_slug', $architectSlug);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    if (!empty($rows)) {
        echo "<p style='color: green;'>✓ 生SQLクエリ成功 - " . count($rows) . "件</p>";
        foreach ($rows as $row) {
            echo "<p>" . htmlspecialchars($row['title']) . " - " . htmlspecialchars($row['location']) . " - " . htmlspecialchars($row['architectJa']) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ 生SQLクエリで結果がありません</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 生SQLクエリエラー: " . $e->getMessage() . "</p>";
}

echo "<h2>4. 建築家スラッグの存在確認</h2>";
try {
    $db = getDB();
    $sql = "SELECT slug, name_ja, name_en FROM individual_architects_3 WHERE slug LIKE '%kajima%' LIMIT 10";
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();
    
    if (!empty($rows)) {
        echo "<p style='color: green;'>✓ 鹿島関連の建築家が見つかりました</p>";
        foreach ($rows as $row) {
            echo "<p>スラッグ: " . htmlspecialchars($row['slug']) . " - 名前: " . htmlspecialchars($row['name_ja']) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ 鹿島関連の建築家が見つかりませんでした</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築家スラッグ確認エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>5. 次のステップ</h2>";
echo "<p>このデバッグ結果を確認して、問題の原因を特定してください。</p>";
?>
