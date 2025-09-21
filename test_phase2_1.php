<?php
// Phase 2.1 統合後の動作確認テスト

echo "<h1>Phase 2.1: 関数ファイル統合後の動作確認</h1>";

// 1. ファイル存在確認
echo "<h2>1. ファイル存在確認</h2>";

$files_to_check = [
    'src/Views/includes/functions.php' => '統合された関数ファイル',
    'src/Views/includes/functions_old.php' => '旧functions.php（バックアップ）',
    'src/Views/includes/functions_new_old.php' => '旧functions_new.php（バックアップ）'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $description ($file)</p>";
    } else {
        echo "<p style='color: red;'>✗ $description ($file) - ファイルが見つかりません</p>";
    }
}

// 2. 関数読み込みテスト
echo "<h2>2. 関数読み込みテスト</h2>";
try {
    require_once 'src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ 統合された関数ファイル読み込み成功</p>";
    
    // 主要関数の存在確認
    $functions_to_check = [
        'getDatabaseConnection',
        'generateThumbnailUrl',
        'searchBuildings',
        'searchBuildingsByLocation',
        'transformBuildingData',
        'getBuildingBySlug',
        'getArchitectBySlug',
        'getPopularSearches',
        't',
        'debugDatabase'
    ];
    
    foreach ($functions_to_check as $function) {
        if (function_exists($function)) {
            echo "<p style='color: green;'>✓ $function() 関数存在</p>";
        } else {
            echo "<p style='color: red;'>✗ $function() 関数が見つかりません</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 関数ファイル読み込みエラー: " . $e->getMessage() . "</p>";
}

// 3. データベース接続テスト
echo "<h2>3. データベース接続テスト</h2>";
try {
    $db = getDB();
    if ($db) {
        echo "<p style='color: green;'>✓ データベース接続成功</p>";
    } else {
        echo "<p style='color: red;'>✗ データベース接続失敗</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ データベース接続エラー: " . $e->getMessage() . "</p>";
}

// 4. 検索機能テスト
echo "<h2>4. 検索機能テスト</h2>";
try {
    $result = searchBuildings('東京', 1, false, false, 'ja', 5);
    if (isset($result['buildings']) && is_array($result['buildings'])) {
        echo "<p style='color: green;'>✓ 検索機能動作確認 - " . count($result['buildings']) . "件の結果</p>";
    } else {
        echo "<p style='color: red;'>✗ 検索機能エラー</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 検索機能エラー: " . $e->getMessage() . "</p>";
}

// 5. 翻訳機能テスト
echo "<h2>5. 翻訳機能テスト</h2>";
$ja_text = t('search', 'ja');
$en_text = t('search', 'en');
echo "<p>日本語: $ja_text</p>";
echo "<p>英語: $en_text</p>";

echo "<h2>6. 次のステップ</h2>";
echo "<p>Phase 2.1が完了したら、<a href='public/index.php'>メインページ</a>で動作確認してください。</p>";
echo "<p>問題がなければ、Phase 2.2（長大な関数の分割）に進みます。</p>";
?>
