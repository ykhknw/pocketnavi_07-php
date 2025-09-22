<?php
// 構造整理後の動作確認テスト

echo "<h1>PocketNavi 構造整理後の動作確認</h1>";

// 1. ファイル存在確認
echo "<h2>1. ファイル存在確認</h2>";

$files_to_check = [
    'public/index.php' => 'メインページ',
    'public/about.php' => 'Aboutページ',
    'public/contact.php' => 'Contactページ',
    'public/.htaccess' => 'Apache設定ファイル',
    'config/database.php' => 'データベース設定',
    'src/Views/includes/functions.php' => '関数ファイル',
    'src/Views/includes/functions_new.php' => '新関数ファイル',
    'src/Views/includes/header.php' => 'ヘッダー',
    'src/Views/includes/footer.php' => 'フッター',
    'src/Views/includes/building_card.php' => '建築物カード',
    'public/assets/css/style.css' => 'CSSファイル',
    'public/assets/js/main.js' => 'JavaScriptファイル'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $description ($file)</p>";
    } else {
        echo "<p style='color: red;'>✗ $description ($file) - ファイルが見つかりません</p>";
    }
}

// 2. データベース接続テスト
echo "<h2>2. データベース接続テスト</h2>";
try {
    require_once 'config/database.php';
    $db = getDB();
    if ($db) {
        echo "<p style='color: green;'>✓ データベース接続成功</p>";
        
        // テーブル存在確認
        $tables = ['buildings_table_3', 'individual_architects_3', 'building_architects', 'architect_compositions_2'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✓ $table テーブル存在</p>";
            } else {
                echo "<p style='color: red;'>✗ $table テーブルが存在しません</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>✗ データベース接続失敗</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ データベース接続エラー: " . $e->getMessage() . "</p>";
}

// 3. 関数読み込みテスト
echo "<h2>3. 関数読み込みテスト</h2>";
try {
    require_once 'src/Views/includes/functions.php';
    require_once 'src/Views/includes/functions_new.php';
    echo "<p style='color: green;'>✓ 関数ファイル読み込み成功</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 関数ファイル読み込みエラー: " . $e->getMessage() . "</p>";
}

// 4. パス確認
echo "<h2>4. パス確認</h2>";
echo "<p>現在のディレクトリ: " . getcwd() . "</p>";
echo "<p>スクリプトのパス: " . __FILE__ . "</p>";

echo "<h2>5. 次のステップ</h2>";
echo "<p>テストが完了したら、<a href='public/index.php'>メインページ</a>にアクセスして動作確認してください。</p>";
?>
