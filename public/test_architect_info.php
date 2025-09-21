<?php
// 建築家情報の動作確認テスト

echo "<h1>建築家情報の動作確認テスト</h1>";

try {
    require_once '../config/database.php';
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ ファイル読み込み成功</p>";
    
    // 建築家検索のテスト
    echo "<h2>建築家検索のテスト</h2>";
    
    $architectSlug = 'kajima-corporation';
    $result = searchBuildingsByArchitectSlug($architectSlug, 1, 'ja', 5);
    
    echo "<h3>検索結果の構造確認</h3>";
    echo "<p>結果のキー: " . implode(', ', array_keys($result)) . "</p>";
    
    if (isset($result['architectInfo'])) {
        echo "<p style='color: green;'>✓ architectInfo キー存在</p>";
        
        if ($result['architectInfo']) {
            echo "<p style='color: green;'>✓ 建築家情報取得成功</p>";
            echo "<p>建築家名（日本語）: " . htmlspecialchars($result['architectInfo']['nameJa'] ?? 'N/A') . "</p>";
            echo "<p>建築家名（英語）: " . htmlspecialchars($result['architectInfo']['nameEn'] ?? 'N/A') . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠ 建築家情報がnull（スラッグが存在しない可能性）</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ architectInfo キーが存在しません</p>";
    }
    
    if (isset($result['buildings'])) {
        echo "<p style='color: green;'>✓ buildings キー存在 - " . count($result['buildings']) . "件の結果</p>";
    } else {
        echo "<p style='color: red;'>✗ buildings キーが存在しません</p>";
    }
    
    if (isset($result['total'])) {
        echo "<p style='color: green;'>✓ total キー存在 - " . $result['total'] . "件</p>";
    } else {
        echo "<p style='color: red;'>✗ total キーが存在しません</p>";
    }
    
    if (isset($result['totalPages'])) {
        echo "<p style='color: green;'>✓ totalPages キー存在 - " . $result['totalPages'] . "ページ</p>";
    } else {
        echo "<p style='color: red;'>✗ totalPages キーが存在しません</p>";
    }
    
    if (isset($result['currentPage'])) {
        echo "<p style='color: green;'>✓ currentPage キー存在 - " . $result['currentPage'] . "ページ目</p>";
    } else {
        echo "<p style='color: red;'>✗ currentPage キーが存在しません</p>";
    }
    
    // エラーケースのテスト
    echo "<h2>エラーケースのテスト</h2>";
    
    $invalidResult = searchBuildingsByArchitectSlug('non-existent-architect', 1, 'ja', 5);
    
    if (isset($invalidResult['architectInfo'])) {
        echo "<p style='color: green;'>✓ エラーケースでもarchitectInfo キー存在</p>";
        if ($invalidResult['architectInfo'] === null) {
            echo "<p style='color: green;'>✓ エラーケースでarchitectInfo がnull</p>";
        } else {
            echo "<p style='color: orange;'>⚠ エラーケースでarchitectInfo がnull以外</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ エラーケースでarchitectInfo キーが存在しません</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>テスト完了</h2>";
echo "<p><a href='index.php'>メインページに戻る</a></p>";
?>
