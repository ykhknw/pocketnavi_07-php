<?php
// 建築家SLUGテスト用ファイル
require_once '../config/database.php';
require_once '../src/Services/ArchitectService.php';

echo "<h2>建築家SLUGテスト</h2>";

try {
    $architectService = new ArchitectService();
    
    // takenaka-corporationでテスト
    $slug = 'takenaka-corporation';
    echo "<h3>テストSLUG: {$slug}</h3>";
    
    $architectInfo = $architectService->getBySlug($slug, 'ja');
    
    if ($architectInfo) {
        echo "<h4>建築家情報取得成功</h4>";
        echo "<pre>";
        print_r($architectInfo);
        echo "</pre>";
        
        // 必要なキーが存在するかチェック
        $requiredKeys = ['individual_architect_id', 'name_ja', 'individual_website', 'website_title'];
        echo "<h4>キー存在チェック</h4>";
        foreach ($requiredKeys as $key) {
            $exists = isset($architectInfo[$key]);
            $value = $architectInfo[$key] ?? 'NOT SET';
            echo "{$key}: " . ($exists ? "✓" : "✗") . " (値: {$value})<br>";
        }
    } else {
        echo "<h4>建築家情報取得失敗</h4>";
    }
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
    echo "スタックトレース: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>テスト完了</h3>";
?>
