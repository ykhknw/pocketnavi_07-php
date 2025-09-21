<?php
// 検索機能のデバッグ用スクリプト

require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';

echo "<h1>検索機能デバッグ</h1>";

// 1. データベース接続確認
echo "<h2>1. データベース接続確認</h2>";
try {
    $db = getDB();
    echo "<p style='color: green;'>✓ データベース接続成功</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ データベース接続エラー: " . $e->getMessage() . "</p>";
    exit;
}

// 2. テーブル存在確認
echo "<h2>2. テーブル存在確認</h2>";
$tables = ['buildings_table_3', 'building_architects', 'architect_compositions_2', 'individual_architects_3'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<p style='color: green;'>✓ $table: $count 件</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ $table: " . $e->getMessage() . "</p>";
    }
}

// 3. 基本的な検索テスト
echo "<h2>3. 基本的な検索テスト</h2>";

// 空の検索（全件取得）
echo "<h3>3.1 空の検索（全件取得）</h3>";
try {
    $result = searchBuildings('', 1, false, false, 'ja', 5);
    echo "<p>結果数: " . $result['total'] . "</p>";
    echo "<p>ページ数: " . $result['totalPages'] . "</p>";
    echo "<p>現在のページ: " . $result['currentPage'] . "</p>";
    
    if (!empty($result['buildings'])) {
        echo "<p style='color: green;'>✓ 建築物データ取得成功</p>";
        echo "<h4>取得した建築物（最初の3件）:</h4>";
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
    echo "<p style='color: red;'>✗ 検索エラー: " . $e->getMessage() . "</p>";
}

// キーワード検索テスト
echo "<h3>3.2 キーワード検索テスト（東京）</h3>";
try {
    $result = searchBuildings('東京', 1, false, false, 'ja', 5);
    echo "<p>結果数: " . $result['total'] . "</p>";
    
    if (!empty($result['buildings'])) {
        echo "<p style='color: green;'>✓ キーワード検索成功</p>";
        foreach (array_slice($result['buildings'], 0, 3) as $building) {
            echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
            echo "<p><strong>タイトル:</strong> " . htmlspecialchars($building['title']) . "</p>";
            echo "<p><strong>場所:</strong> " . htmlspecialchars($building['location']) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'>✗ キーワード検索で結果がありません</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ キーワード検索エラー: " . $e->getMessage() . "</p>";
}

// 4. 複数条件検索テスト
echo "<h2>4. 複数条件検索テスト</h2>";
try {
    $result = searchBuildingsWithMultipleConditions('', '', '', '', false, false, 1, 'ja', 5);
    echo "<p>結果数: " . $result['total'] . "</p>";
    
    if (!empty($result['buildings'])) {
        echo "<p style='color: green;'>✓ 複数条件検索成功</p>";
    } else {
        echo "<p style='color: red;'>✗ 複数条件検索で結果がありません</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 複数条件検索エラー: " . $e->getMessage() . "</p>";
}

// 5. 生のSQLクエリテスト
echo "<h2>5. 生のSQLクエリテスト</h2>";
try {
    $sql = "SELECT building_id, title, titleEn, location, completionYears FROM buildings_table_3 LIMIT 5";
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();
    
    if (!empty($rows)) {
        echo "<p style='color: green;'>✓ 生SQLクエリ成功 - " . count($rows) . "件</p>";
        foreach ($rows as $row) {
            echo "<p>" . htmlspecialchars($row['title']) . " - " . htmlspecialchars($row['location']) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ 生SQLクエリで結果がありません</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 生SQLクエリエラー: " . $e->getMessage() . "</p>";
}

echo "<h2>6. 次のステップ</h2>";
echo "<p>このデバッグ結果を確認して、問題の原因を特定してください。</p>";
?>
