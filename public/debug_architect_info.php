<?php
// 建築家情報のデバッグ

echo "<h1>建築家情報のデバッグ</h1>";

try {
    require_once '../config/database.php';
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ ファイル読み込み成功</p>";
    
    // 建築家slugのテスト
    $architectSlug = 'mozuna-kikoo-architects-associates';
    echo "<h2>建築家slug: $architectSlug</h2>";
    
    // 直接ArchitectServiceをテスト
    echo "<h3>1. ArchitectService直接テスト</h3>";
    $architectService = new ArchitectService();
    $architectInfo = $architectService->getBySlug($architectSlug, 'ja');
    
    if ($architectInfo) {
        echo "<p style='color: green;'>✓ 建築家情報取得成功</p>";
        echo "<h4>建築家情報の詳細:</h4>";
        echo "<pre>";
        print_r($architectInfo);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>✗ 建築家情報取得失敗</p>";
    }
    
    // searchBuildingsByArchitectSlug関数をテスト
    echo "<h3>2. searchBuildingsByArchitectSlug関数テスト</h3>";
    $result = searchBuildingsByArchitectSlug($architectSlug, 1, 'ja', 5);
    
    echo "<h4>検索結果の構造:</h4>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if (isset($result['architectInfo'])) {
        echo "<p style='color: green;'>✓ architectInfo キー存在</p>";
        
        if ($result['architectInfo']) {
            echo "<p style='color: green;'>✓ 建築家情報取得成功</p>";
            
            // フィルターバッジ用の建築家名をテスト
            $architectName = 'ja' === 'ja' ? 
                ($result['architectInfo']['nameJa'] ?? $result['architectInfo']['nameEn'] ?? '') : 
                ($result['architectInfo']['nameEn'] ?? $result['architectInfo']['nameJa'] ?? '');
            echo "<p><strong>フィルターバッジ用の建築家名:</strong> '" . htmlspecialchars($architectName) . "'</p>";
            
            if (empty($architectName)) {
                echo "<p style='color: red;'>✗ 建築家名が空です</p>";
                echo "<p>nameJa: '" . ($result['architectInfo']['nameJa'] ?? 'NULL') . "'</p>";
                echo "<p>nameEn: '" . ($result['architectInfo']['nameEn'] ?? 'NULL') . "'</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ 建築家情報がnull</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ architectInfo キーが存在しません</p>";
    }
    
    // データベースから直接確認
    echo "<h3>3. データベース直接確認</h3>";
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM individual_architects_3 WHERE slug = ?");
    $stmt->execute([$architectSlug]);
    $dbRow = $stmt->fetch();
    
    if ($dbRow) {
        echo "<p style='color: green;'>✓ データベースから直接取得成功</p>";
        echo "<h4>データベースの生データ:</h4>";
        echo "<pre>";
        print_r($dbRow);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>✗ データベースから直接取得失敗</p>";
        
        // 類似するslugを検索
        $stmt = $db->prepare("SELECT slug, name_ja, name_en FROM individual_architects_3 WHERE slug LIKE ? LIMIT 10");
        $stmt->execute(['%' . $architectSlug . '%']);
        $similarRows = $stmt->fetchAll();
        
        if ($similarRows) {
            echo "<p>類似するslug:</p>";
            echo "<pre>";
            print_r($similarRows);
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
    echo "<p>スタックトレース:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>デバッグ完了</h2>";
?>
