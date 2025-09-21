<?php
// 建築家表示の動作確認テスト

echo "<h1>建築家表示の動作確認テスト</h1>";

try {
    require_once '../config/database.php';
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ ファイル読み込み成功</p>";
    
    // 建築家slugのテスト
    $architectSlug = 'u-architects';
    echo "<h2>建築家slug: $architectSlug</h2>";
    
    // 建築家検索のテスト
    $result = searchBuildingsByArchitectSlug($architectSlug, 1, 'ja', 5);
    
    echo "<h3>検索結果の構造確認</h3>";
    echo "<p>結果のキー: " . implode(', ', array_keys($result)) . "</p>";
    
    if (isset($result['architectInfo'])) {
        echo "<p style='color: green;'>✓ architectInfo キー存在</p>";
        
        if ($result['architectInfo']) {
            echo "<p style='color: green;'>✓ 建築家情報取得成功</p>";
            echo "<h4>建築家情報の詳細:</h4>";
            echo "<ul>";
            foreach ($result['architectInfo'] as $key => $value) {
                echo "<li><strong>$key:</strong> " . htmlspecialchars($value ?? 'N/A') . "</li>";
            }
            echo "</ul>";
            
            // フィルターバッジ用の建築家名
            $architectName = 'ja' === 'ja' ? 
                ($result['architectInfo']['nameJa'] ?? $result['architectInfo']['nameEn'] ?? '') : 
                ($result['architectInfo']['nameEn'] ?? $result['architectInfo']['nameJa'] ?? '');
            echo "<p><strong>フィルターバッジ用の建築家名:</strong> " . htmlspecialchars($architectName) . "</p>";
            
        } else {
            echo "<p style='color: orange;'>⚠ 建築家情報がnull（スラッグが存在しない可能性）</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ architectInfo キーが存在しません</p>";
    }
    
    if (isset($result['buildings'])) {
        echo "<p style='color: green;'>✓ buildings キー存在 - " . count($result['buildings']) . "件の結果</p>";
        
        if (!empty($result['buildings'])) {
            echo "<h4>建築物の例（最初の1件）:</h4>";
            $building = $result['buildings'][0];
            echo "<ul>";
            foreach ($building as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    echo "<li><strong>$key:</strong> " . htmlspecialchars($value) . "</li>";
                }
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>✗ buildings キーが存在しません</p>";
    }
    
    // 他の建築家slugでもテスト
    echo "<h2>他の建築家slugのテスト</h2>";
    $otherSlugs = ['kajima-corporation', 'tadao-ando'];
    
    foreach ($otherSlugs as $slug) {
        echo "<h3>建築家slug: $slug</h3>";
        $otherResult = searchBuildingsByArchitectSlug($slug, 1, 'ja', 3);
        
        if (isset($otherResult['architectInfo']) && $otherResult['architectInfo']) {
            $name = $otherResult['architectInfo']['nameJa'] ?? $otherResult['architectInfo']['nameEn'] ?? 'N/A';
            echo "<p style='color: green;'>✓ 建築家名: " . htmlspecialchars($name) . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠ 建築家情報なし</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>テスト完了</h2>";
echo "<p><a href='index.php?architects_slug=u-architects&lang=ja'>メインページで確認</a></p>";
?>
