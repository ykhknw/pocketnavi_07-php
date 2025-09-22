<?php
// 建築家ページでのキーワード検索の詳細デバッグ
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// パラメータ設定
$architectSlug = $_GET['architect_slug'] ?? 'inui-architects';
$query = $_GET['q'] ?? '美術館';
$lang = 'ja';
$page = 1;
$limit = 10;

echo "<h1>建築家ページでのキーワード検索の詳細デバッグ</h1>";
echo "<h2>パラメータ</h2>";
echo "<p>architectSlug: " . htmlspecialchars($architectSlug) . "</p>";
echo "<p>query: " . htmlspecialchars($query) . "</p>";
echo "<p>lang: " . htmlspecialchars($lang) . "</p>";

echo "<h2>1. searchBuildingsByArchitectSlug関数の動作確認</h2>";

// まず、キーワードなしで建築家検索を実行
echo "<h3>1-1. キーワードなしでの建築家検索</h3>";
$resultWithoutQuery = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit, '', '', '');
echo "<p>結果件数: " . $resultWithoutQuery['total'] . "件</p>";

if (!empty($resultWithoutQuery['buildings'])) {
    echo "<h4>建築物一覧（最初の5件）</h4>";
    foreach (array_slice($resultWithoutQuery['buildings'], 0, 5) as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>用途: " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
        echo "</div>";
    }
}

// 次に、キーワードありで建築家検索を実行
echo "<h3>1-2. キーワード「" . htmlspecialchars($query) . "」ありでの建築家検索</h3>";
$resultWithQuery = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit, '', '', $query);
echo "<p>結果件数: " . $resultWithQuery['total'] . "件</p>";

if (!empty($resultWithQuery['buildings'])) {
    echo "<h4>検索結果詳細</h4>";
    foreach ($resultWithQuery['buildings'] as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>用途: " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>検索結果がありません。</p>";
}

echo "<h2>2. 手動でのキーワードフィルタリング検証</h2>";
$queryLower = mb_strtolower($query);
$filteredCount = 0;
$filteredBuildings = [];

if (!empty($resultWithoutQuery['buildings'])) {
    foreach ($resultWithoutQuery['buildings'] as $building) {
        $include = false;
        
        // タイトル検索
        if (mb_strpos(mb_strtolower($building['title']), $queryLower) !== false) {
            $include = true;
        }
        
        // 英語タイトル検索
        if (mb_strpos(mb_strtolower($building['titleEn']), $queryLower) !== false) {
            $include = true;
        }
        
        // 用途検索
        if (!empty($building['buildingTypes'])) {
            foreach ($building['buildingTypes'] as $type) {
                if (mb_strpos(mb_strtolower($type), $queryLower) !== false) {
                    $include = true;
                    break;
                }
            }
        }
        
        // 英語用途検索
        if (!empty($building['buildingTypesEn'])) {
            foreach ($building['buildingTypesEn'] as $typeEn) {
                if (mb_strpos(mb_strtolower($typeEn), $queryLower) !== false) {
                    $include = true;
                    break;
                }
            }
        }
        
        if ($include) {
            $filteredBuildings[] = $building;
            $filteredCount++;
        }
    }
}

echo "<p>手動フィルタリング結果: " . $filteredCount . "件</p>";

if (!empty($filteredBuildings)) {
    echo "<h3>手動フィルタリング結果詳細</h3>";
    foreach ($filteredBuildings as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>用途: " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
        echo "</div>";
    }
}

echo "<h2>3. 比較結果</h2>";
echo "<p>キーワードなし検索: " . $resultWithoutQuery['total'] . "件</p>";
echo "<p>手動フィルタリング: " . $filteredCount . "件</p>";
echo "<p>PHP関数検索: " . $resultWithQuery['total'] . "件</p>";

if ($filteredCount === $resultWithQuery['total']) {
    echo "<p style='color: green;'>✅ 一致: フィルタリング結果は正しく動作しています。</p>";
} else {
    echo "<p style='color: red;'>⚠️ 不一致: フィルタリング結果に問題があります。</p>";
}

echo "<h2>4. デバッグ情報</h2>";
echo "<p>queryLower: " . htmlspecialchars($queryLower) . "</p>";
echo "<p>mb_strpos テスト: " . (mb_strpos(mb_strtolower('美術館'), $queryLower) !== false ? 'true' : 'false') . "</p>";

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
</style>
