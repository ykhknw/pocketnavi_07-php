<?php
// 共通関数（新しい検索ロジック）

/**
 * 建築物を検索する（新しいロジック）
 */
function searchBuildingsNew($query, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja') {
    $db = getDB();
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // テーブル名の定義
    $buildings_table = 'buildings_table_2';
    $building_architects_table = 'building_architects';
    $architect_compositions_table = 'architect_compositions_2';
    $individual_architects_table = 'individual_architects_3';
    
    // キーワードを分割（全角・半角スペースで分割）
    $temp = str_replace('　', ' ', $query);
    $keywords = array_filter(explode(' ', trim($temp)));
    
    if (empty($keywords)) {
        return ['buildings' => [], 'total' => 0, 'totalPages' => 0];
    }
    
    // WHERE句の構築
    $whereClauses = [];
    $params = [];
    
    // 横断検索の処理
    if (!empty($keywords)) {
        // 各キーワードに対してOR条件を構築し、全体をANDで結合
        $keywordConditions = [];
        foreach ($keywords as $keyword) {
            $escapedKeyword = '%' . $keyword . '%';
            $fieldConditions = [
                "b.title LIKE ?",
                "b.titleEn LIKE ?",
                "b.buildingTypes LIKE ?",
                "b.buildingTypesEn LIKE ?",
                "b.location LIKE ?",
                "b.locationEn_from_datasheetChunkEn LIKE ?",
                "ia.name_ja LIKE ?",
                "ia.name_en LIKE ?"
            ];
            $keywordConditions[] = '(' . implode(' OR ', $fieldConditions) . ')';
            
            // パラメータを8回追加（各フィールド用）
            for ($i = 0; $i < 8; $i++) {
                $params[] = $escapedKeyword;
            }
        }
        
        if (!empty($keywordConditions)) {
            $whereClauses[] = '(' . implode(' AND ', $keywordConditions) . ')';
        }
    }
    
    // メディアフィルター
    if ($hasPhotos) {
        $whereClauses[] = "b.thumbnailUrl IS NOT NULL AND b.thumbnailUrl != ''";
    }
    
    if ($hasVideos) {
        $whereClauses[] = "b.youtubeUrl IS NOT NULL AND b.youtubeUrl != ''";
    }
    
    // 座標が存在するもののみ
    $whereClauses[] = "b.lat IS NOT NULL AND b.lng IS NOT NULL";
    
    // WHERE句を構築
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
    
    // 件数取得
    $countSql = "
        SELECT COUNT(DISTINCT b.building_id)
        FROM $buildings_table b
        LEFT JOIN $building_architects_table ba ON b.building_id = ba.building_id
        LEFT JOIN $architect_compositions_table ac ON ba.architect_id = ac.architect_id
        LEFT JOIN $individual_architects_table ia ON ac.individual_architect_id = ia.individual_architect_id
        $whereSql
    ";
    
    // データ取得クエリ
    $sql = "
        SELECT b.*,
               GROUP_CONCAT(
                   DISTINCT ia.name_ja 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ' / '
               ) AS architectJa,
               GROUP_CONCAT(
                   DISTINCT ia.name_en 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ' / '
               ) AS architectEn,
               GROUP_CONCAT(
                   DISTINCT ba.architect_id 
                   ORDER BY ba.architect_order 
                   SEPARATOR ','
               ) AS architectIds
        FROM $buildings_table b
        LEFT JOIN $building_architects_table ba ON b.building_id = ba.building_id
        LEFT JOIN $architect_compositions_table ac ON ba.architect_id = ac.architect_id
        LEFT JOIN $individual_architects_table ia ON ac.individual_architect_id = ia.individual_architect_id
        $whereSql
        GROUP BY b.building_id
        ORDER BY b.uid DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    try {
        // デバッグ情報を出力
        error_log("Search query: " . $query);
        error_log("Search SQL: " . $sql);
        error_log("Search params: " . print_r($params, true));
        
        // 総件数取得
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        error_log("Total count: " . $total);
        
        // データ取得
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $buildings = [];
        $rowCount = 0;
        while ($row = $stmt->fetch()) {
            $buildings[] = transformBuildingDataNew($row, $lang);
            $rowCount++;
        }
        
        error_log("Fetched rows: " . $rowCount);
        
        return [
            'buildings' => $buildings,
            'total' => $total,
            'totalPages' => ceil($total / $limit)
        ];
        
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        return ['buildings' => [], 'total' => 0, 'totalPages' => 0];
    }
}

/**
 * 建築物データを変換（新しい形式）
 */
function transformBuildingDataNew($row, $lang = 'ja') {
    // 建築家情報の処理
    $architects = [];
    if (!empty($row['architectJa'])) {
        $namesJa = explode(' / ', $row['architectJa']);
        $namesEn = !empty($row['architectEn']) ? explode(' / ', $row['architectEn']) : [];
        $architectIds = !empty($row['architectIds']) ? explode(',', $row['architectIds']) : [];
        
        for ($i = 0; $i < count($namesJa); $i++) {
            $architects[] = [
                'architect_id' => isset($architectIds[$i]) ? intval($architectIds[$i]) : 0,
                'architectJa' => trim($namesJa[$i]),
                'architectEn' => isset($namesEn[$i]) ? trim($namesEn[$i]) : trim($namesJa[$i]),
                'slug' => '' // 必要に応じて設定
            ];
        }
    }
    
    // 建物用途の配列変換
    $buildingTypes = !empty($row['buildingTypes']) ? 
        array_filter(explode('/', $row['buildingTypes']), function($type) {
            return !empty(trim($type));
        }) : [];
    
    $buildingTypesEn = !empty($row['buildingTypesEn']) ? 
        array_filter(explode('/', $row['buildingTypesEn']), function($type) {
            return !empty(trim($type));
        }) : [];
    
    return [
        'building_id' => $row['building_id'] ?? 0, // building_card.phpで期待されるキー名に変更
        'id' => $row['building_id'] ?? 0, // 後方互換性のため残す
        'uid' => $row['uid'] ?? '',
        'slug' => $row['slug'] ?? $row['uid'] ?? '',
        'title' => $row['title'] ?? '',
        'titleEn' => $row['titleEn'] ?? $row['title'] ?? '',
        'thumbnailUrl' => $row['thumbnailUrl'] ?? '',
        'youtubeUrl' => $row['youtubeUrl'] ?? '',
        'completionYears' => parseYear($row['completionYears'] ?? ''),
        'buildingTypes' => $buildingTypes,
        'buildingTypesEn' => $buildingTypesEn,
        'prefectures' => $row['prefectures'] ?? '',
        'prefecturesEn' => $row['prefecturesEn'] ?? null,
        'areas' => $row['areas'] ?? '',
        'location' => $row['location'] ?? '',
        'locationEn' => ($row['locationEn'] ?? $row['locationEn_from_datasheetChunkEn'] ?? '') ?: ($row['location'] ?? ''),
        'architectDetails' => $row['architectDetails'] ?? '',
        'lat' => floatval($row['lat'] ?? 0),
        'lng' => floatval($row['lng'] ?? 0),
        'architects' => $architects,
        'photos' => [], // 写真は別途取得
        'likes' => 0, // likesカラムがない場合は0
        'created_at' => $row['created_at'] ?? '',
        'updated_at' => $row['updated_at'] ?? ''
    ];
}

/**
 * スラッグで建築物を取得（新しい形式）
 */
function getBuildingBySlugNew($slug, $lang = 'ja') {
    $db = getDB();
    
    $sql = "
        SELECT 
            b.building_id,
            b.uid,
            b.slug,
            b.title,
            b.titleEn,
            b.thumbnailUrl,
            b.youtubeUrl,
            b.completionYears,
            b.buildingTypes,
            b.buildingTypesEn,
            b.prefectures,
            b.prefecturesEn,
            b.areas,
            b.location,
            b.locationEn_from_datasheetChunkEn as locationEn,
            b.architectDetails,
            b.lat,
            b.lng,
            0 as likes,
            b.created_at,
            b.updated_at,
            GROUP_CONCAT(DISTINCT ia.name_ja ORDER BY ac.order_index SEPARATOR ' / ') as architectJa,
            GROUP_CONCAT(DISTINCT ia.name_en ORDER BY ac.order_index SEPARATOR ' / ') as architectEn,
            GROUP_CONCAT(DISTINCT ba.architect_id ORDER BY ba.architect_order SEPARATOR ',') as architectIds
        FROM buildings_table_2 b
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id
        LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
        LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
        WHERE b.slug = :slug
        GROUP BY b.building_id
        LIMIT 1
    ";
    
    try {
        // デバッグ情報を追加
        error_log("getBuildingBySlugNew: Looking for slug: " . $slug);
        error_log("getBuildingBySlugNew SQL: " . $sql);
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        $stmt->execute();
        
        $row = $stmt->fetch();
        error_log("getBuildingBySlugNew: Row found: " . ($row ? 'Yes' : 'No'));
        
        if ($row) {
            error_log("getBuildingBySlugNew: Row data: " . print_r($row, true));
            error_log("getBuildingBySlugNew: locationEn_from_datasheetChunkEn = " . ($row['locationEn_from_datasheetChunkEn'] ?? 'NULL'));
            return transformBuildingDataNew($row, $lang);
        }
        
        // スラッグが見つからない場合の追加検索は削除
        
        return null;
        
    } catch (PDOException $e) {
        error_log("getBuildingBySlugNew error: " . $e->getMessage());
        return null;
    }
}

// parseYear関数は既にfunctions.phpで定義されているため、ここでは削除
?>
