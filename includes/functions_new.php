<?php
// 共通関数（新しい検索ロジック）

/**
 * 現在地検索用の関数
 */
function searchBuildingsByLocation($userLat, $userLng, $radiusKm = 5, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja', $limit = 10) {
    $db = getDB();
    $offset = ($page - 1) * $limit;
    
    // テーブル名の定義
    $buildings_table = 'buildings_table_2';
    $building_architects_table = 'building_architects';
    $architect_compositions_table = 'architect_compositions_2';
    $individual_architects_table = 'individual_architects_3';
    
    // WHERE句の構築
    $whereClauses = [];
    $params = [];
    
    // 位置情報による検索（Haversine公式を使用）
    $whereClauses[] = "(6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(b.lat)) * COS(RADIANS(b.lng) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(b.lat)))) < ?";
    $params[] = $userLat;
    $params[] = $userLng;
    $params[] = $userLat;
    $params[] = $radiusKm;
    
    // 座標が有効なデータのみ
    $whereClauses[] = "b.lat IS NOT NULL AND b.lng IS NOT NULL AND b.lat != 0 AND b.lng != 0";
    
    // 写真フィルター
    if ($hasPhotos) {
        $whereClauses[] = "b.has_photo IS NOT NULL AND b.has_photo != ''";
    }
    
    // 動画フィルター
    if ($hasVideos) {
        $whereClauses[] = "b.youtubeUrl IS NOT NULL AND b.youtubeUrl != ''";
    }
    
    // WHERE句を構築
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
    
    // --- 件数取得 ---
    $countSql = "
        SELECT COUNT(DISTINCT b.building_id)
        FROM $buildings_table b
        LEFT JOIN $building_architects_table ba ON b.building_id = ba.building_id
        LEFT JOIN $architect_compositions_table ac ON ba.architect_id = ac.architect_id
        LEFT JOIN $individual_architects_table ia ON ac.individual_architect_id = ia.individual_architect_id
        $whereSql
    ";
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $limit);
    
    // --- データ取得クエリ（距離順でソート） ---
    $sql = "
        SELECT b.*,
               GROUP_CONCAT(
                   DISTINCT ia.name_ja 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ' / '
               ) AS architectJa,
               GROUP_CONCAT(
                   DISTINCT ba.architect_id 
                   ORDER BY ba.architect_order 
                   SEPARATOR ','
               ) AS architectIds,
               (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(b.lat)) * COS(RADIANS(b.lng) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(b.lat)))) AS distance
        FROM $buildings_table b
        LEFT JOIN $building_architects_table ba ON b.building_id = ba.building_id
        LEFT JOIN $architect_compositions_table ac ON ba.architect_id = ac.architect_id
        LEFT JOIN $individual_architects_table ia ON ac.individual_architect_id = ia.individual_architect_id
        $whereSql
        GROUP BY b.building_id
        ORDER BY distance ASC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    // 距離計算用のパラメータを追加
    $distanceParams = [$userLat, $userLng, $userLat];
    $allParams = array_merge($distanceParams, $params);
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($allParams);
        
        $buildings = [];
        while ($row = $stmt->fetch()) {
            $building = transformBuildingDataNew($row, $lang);
            $building['distance'] = round($row['distance'], 2); // 距離を追加
            $buildings[] = $building;
        }
        
        return [
            'buildings' => $buildings,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'limit' => $limit
        ];
        
    } catch (PDOException $e) {
        error_log("Location search error: " . $e->getMessage());
        return ['buildings' => [], 'total' => 0, 'totalPages' => 0];
    }
}

/**
 * 建築家の建築物を取得
 */
function getBuildingsByArchitectSlug($architectSlug, $lang = 'ja', $limit = 20) {
    $db = getDB();
    
    // テーブル名の定義
    $buildings_table = 'buildings_table_2';
    $building_architects_table = 'building_architects';
    $architect_compositions_table = 'architect_compositions_2';
    $individual_architects_table = 'individual_architects_3';
    
    // デバッグ情報を追加
    error_log("getBuildingsByArchitectSlug: Looking for architect slug: " . $architectSlug);
    
    $sql = "
        SELECT 
            b.*,
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
            ) AS architectIds,
            GROUP_CONCAT(
                DISTINCT ia.slug 
                ORDER BY ba.architect_order, ac.order_index 
                SEPARATOR ','
            ) AS architectSlugs
        FROM $individual_architects_table ia
        INNER JOIN $architect_compositions_table ac ON ia.individual_architect_id = ac.individual_architect_id
        INNER JOIN $building_architects_table ba ON ac.architect_id = ba.architect_id
        INNER JOIN $buildings_table b ON ba.building_id = b.building_id
        WHERE ia.slug = :architect_slug
        GROUP BY b.building_id
        ORDER BY b.has_photo DESC, b.building_id DESC
        LIMIT {$limit}
    ";
    
    try {
        error_log("getBuildingsByArchitectSlug SQL: " . $sql);
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':architect_slug', $architectSlug);
        $stmt->execute();
        
        $buildings = [];
        $rowCount = 0;
        while ($row = $stmt->fetch()) {
            error_log("getBuildingsByArchitectSlug: Raw row data: " . print_r($row, true));
            $transformedBuilding = transformBuildingDataNew($row, $lang);
            error_log("getBuildingsByArchitectSlug: Transformed building: " . print_r($transformedBuilding, true));
            $buildings[] = $transformedBuilding;
            $rowCount++;
        }
        
        error_log("getBuildingsByArchitectSlug: Found " . $rowCount . " buildings for architect slug: " . $architectSlug);
        
        // デバッグ用：建築家の建築物の関連テーブルを直接確認
        $debugStmt = $db->prepare("
            SELECT 
                ia.individual_architect_id,
                ia.name_ja,
                ia.slug,
                ac.architect_id,
                ba.building_id,
                b.title
            FROM individual_architects_3 ia
            LEFT JOIN architect_compositions_2 ac ON ia.individual_architect_id = ac.individual_architect_id
            LEFT JOIN building_architects ba ON ac.architect_id = ba.architect_id
            LEFT JOIN buildings_table_2 b ON ba.building_id = b.building_id
            WHERE ia.slug = ?
            LIMIT 10
        ");
        $debugStmt->execute([$architectSlug]);
        $debugResults = $debugStmt->fetchAll();
        error_log("getBuildingsByArchitectSlug: Debug query results: " . print_r($debugResults, true));
        
        return $buildings;
        
    } catch (PDOException $e) {
        error_log("Architect buildings search error: " . $e->getMessage());
        return [];
    }
}

/**
 * 建築物を検索する（新しいロジック）
 */
function searchBuildingsNew($query, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja', $limit = 10) {
    $db = getDB();
    $offset = ($page - 1) * $limit;
    
    // テーブル名の定義
    $buildings_table = 'buildings_table_2';
    $building_architects_table = 'building_architects';
    $architect_compositions_table = 'architect_compositions_2';
    $individual_architects_table = 'individual_architects_3';
    
    // キーワードを分割（全角・半角スペースで分割）
    $temp = str_replace('　', ' ', $query);
    $keywords = array_filter(explode(' ', trim($temp)));
    
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
        $whereClauses[] = "b.has_photo IS NOT NULL AND b.has_photo != ''";
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
               ) AS architectIds,
               GROUP_CONCAT(
                   DISTINCT ia.slug 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ','
               ) AS architectSlugs
        FROM $buildings_table b
        LEFT JOIN $building_architects_table ba ON b.building_id = ba.building_id
        LEFT JOIN $architect_compositions_table ac ON ba.architect_id = ac.architect_id
        LEFT JOIN $individual_architects_table ia ON ac.individual_architect_id = ia.individual_architect_id
        $whereSql
        GROUP BY b.building_id
        ORDER BY b.has_photo DESC, b.building_id DESC
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
            'totalPages' => ceil($total / $limit),
            'currentPage' => $page,
            'limit' => $limit
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
        $architectSlugs = !empty($row['architectSlugs']) ? explode(',', $row['architectSlugs']) : [];
        
        for ($i = 0; $i < count($namesJa); $i++) {
            $architects[] = [
                'architect_id' => isset($architectIds[$i]) ? intval($architectIds[$i]) : 0,
                'architectJa' => trim($namesJa[$i]),
                'architectEn' => isset($namesEn[$i]) ? trim($namesEn[$i]) : trim($namesJa[$i]),
                'slug' => isset($architectSlugs[$i]) ? trim($architectSlugs[$i]) : ''
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
        'thumbnailUrl' => generateThumbnailUrl($row['uid'] ?? '', $row['has_photo'] ?? null),
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
            b.has_photo,
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
            GROUP_CONCAT(DISTINCT ba.architect_id ORDER BY ba.architect_order SEPARATOR ',') as architectIds,
            GROUP_CONCAT(DISTINCT ia.slug ORDER BY ac.order_index SEPARATOR ',') as architectSlugs
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

/**
 * 建物slug検索用の関数（index.php用）
 */
function searchBuildingsBySlug($buildingSlug, $lang = 'ja', $limit = 10) {
    $db = getDB();
    
    // テーブル名の定義
    $buildings_table = 'buildings_table_2';
    $building_architects_table = 'building_architects';
    $architect_compositions_table = 'architect_compositions_2';
    $individual_architects_table = 'individual_architects_3';
    
    // WHERE句の構築
    $whereClauses = [];
    $params = [];
    
    // slug検索条件
    $whereClauses[] = "b.slug = ?";
    $params[] = $buildingSlug;
    
    // WHERE句を構築
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
    
    // --- 件数取得 ---
    $countSql = "
        SELECT COUNT(DISTINCT b.building_id)
        FROM $buildings_table b
        LEFT JOIN $building_architects_table ba ON b.building_id = ba.building_id
        LEFT JOIN $architect_compositions_table ac ON ba.architect_id = ac.architect_id
        LEFT JOIN $individual_architects_table ia ON ac.individual_architect_id = ia.individual_architect_id
        $whereSql
    ";
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $limit);
    
    // --- データ取得クエリ ---
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
                   DISTINCT ia.slug 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ','
               ) AS architectSlugs,
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
        ORDER BY b.building_id DESC
        LIMIT {$limit}
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $buildings = [];
        while ($row = $stmt->fetch()) {
            $buildings[] = transformBuildingDataNew($row, $lang);
        }
        
        return [
            'buildings' => $buildings,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => 1
        ];
        
    } catch (PDOException $e) {
        error_log("searchBuildingsBySlug error: " . $e->getMessage());
        return [
            'buildings' => [],
            'total' => 0,
            'totalPages' => 0,
            'currentPage' => 1
        ];
    }
}

/**
 * 都道府県検索用の関数（index.php用）
 */
function searchBuildingsByPrefecture($prefecture, $page = 1, $lang = 'ja', $limit = 10) {
    $db = getDB();
    $offset = ($page - 1) * $limit;
    
    // テーブル名の定義
    $buildings_table = 'buildings_table_2';
    $building_architects_table = 'building_architects';
    $architect_compositions_table = 'architect_compositions_2';
    $individual_architects_table = 'individual_architects_3';
    
    // WHERE句の構築
    $whereClauses = [];
    $params = [];
    
    // 都道府県検索条件（prefecturesEnカラムで検索）
    $whereClauses[] = "b.prefecturesEn = ?";
    $params[] = $prefecture;
    
    // WHERE句を構築
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
    
    // --- 件数取得 ---
    $countSql = "
        SELECT COUNT(DISTINCT b.building_id)
        FROM $buildings_table b
        LEFT JOIN $building_architects_table ba ON b.building_id = ba.building_id
        LEFT JOIN $architect_compositions_table ac ON ba.architect_id = ac.architect_id
        LEFT JOIN $individual_architects_table ia ON ac.individual_architect_id = ia.individual_architect_id
        $whereSql
    ";
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $limit);
    
    // --- データ取得クエリ ---
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
                   DISTINCT ia.slug 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ','
               ) AS architectSlugs,
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
        ORDER BY b.building_id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $buildings = [];
        while ($row = $stmt->fetch()) {
            $buildings[] = transformBuildingDataNew($row, $lang);
        }
        
        return [
            'buildings' => $buildings,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];
        
    } catch (PDOException $e) {
        error_log("searchBuildingsByPrefecture error: " . $e->getMessage());
        return [
            'buildings' => [],
            'total' => 0,
            'totalPages' => 0,
            'currentPage' => $page
        ];
    }
}

// parseYear関数は既にfunctions.phpで定義されているため、ここでは削除
?>
