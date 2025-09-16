<?php
// 共通関数

/**
 * 建築物を検索する
 */
function searchBuildings($query, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja') {
    $db = getDB();
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // キーワードを分割（全角・半角スペースで分割）
    $keywords = preg_split('/[\s　]+/', trim($query));
    $keywords = array_filter($keywords, function($keyword) {
        return !empty(trim($keyword));
    });
    
    if (empty($keywords)) {
        return ['buildings' => [], 'total' => 0, 'totalPages' => 0];
    }
    
    // 検索条件の構築
    $whereConditions = [];
    $params = [];
    
    // 各キーワードに対して8フィールド横断検索
    foreach ($keywords as $index => $keyword) {
        $keywordParam = "keyword{$index}";
        $whereConditions[] = "(
            b.title LIKE :{$keywordParam} OR
            b.titleEn LIKE :{$keywordParam} OR
            b.buildingTypes LIKE :{$keywordParam} OR
            b.buildingTypesEn LIKE :{$keywordParam} OR
            b.location LIKE :{$keywordParam} OR
            b.locationEn LIKE :{$keywordParam} OR
            b.architectDetails LIKE :{$keywordParam} OR
            ia.name_ja LIKE :{$keywordParam} OR
            ia.name_en LIKE :{$keywordParam}
        )";
        $params[$keywordParam] = "%{$keyword}%";
    }
    
    // メディアフィルター
    if ($hasPhotos) {
        $whereConditions[] = "b.thumbnailUrl IS NOT NULL AND b.thumbnailUrl != ''";
    }
    
    if ($hasVideos) {
        $whereConditions[] = "b.youtubeUrl IS NOT NULL AND b.youtubeUrl != ''";
    }
    
    // 座標が存在するもののみ
    $whereConditions[] = "b.lat IS NOT NULL AND b.lng IS NOT NULL";
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // 建築家情報を含むクエリ
    $sql = "
        SELECT DISTINCT
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
            b.locationEn,
            b.architectDetails,
            b.lat,
            b.lng,
            b.likes,
            b.created_at,
            b.updated_at,
            GROUP_CONCAT(DISTINCT ia.name_ja ORDER BY ac.order_index SEPARATOR '　') as architect_names_ja,
            GROUP_CONCAT(DISTINCT ia.name_en ORDER BY ac.order_index SEPARATOR '　') as architect_names_en,
            GROUP_CONCAT(DISTINCT ia.slug ORDER BY ac.order_index SEPARATOR ',') as architect_slugs
        FROM buildings_table_2 b
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id
        LEFT JOIN architect_compositions ac ON ba.architect_id = ac.architect_id
        LEFT JOIN individual_architects ia ON ac.individual_architect_id = ia.individual_architect_id
        WHERE {$whereClause}
        GROUP BY b.building_id
        ORDER BY b.building_id DESC
        LIMIT :limit OFFSET :offset
    ";
    
    // 総件数取得
    $countSql = "
        SELECT COUNT(DISTINCT b.building_id) as total
        FROM buildings_table_2 b
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id
        LEFT JOIN architect_compositions ac ON ba.architect_id = ac.architect_id
        LEFT JOIN individual_architects ia ON ac.individual_architect_id = ia.individual_architect_id
        WHERE {$whereClause}
    ";
    
    try {
        // 総件数取得
        $countStmt = $db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(":{$key}", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];
        
        // データ取得
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $buildings = [];
        while ($row = $stmt->fetch()) {
            $buildings[] = transformBuildingData($row, $lang);
        }
        
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
 * 最近の建築物を取得
 */
function getRecentBuildings($limit = 20, $lang = 'ja') {
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
            b.locationEn,
            b.architectDetails,
            b.lat,
            b.lng,
            b.likes,
            b.created_at,
            b.updated_at,
            GROUP_CONCAT(DISTINCT ia.name_ja ORDER BY ac.order_index SEPARATOR '　') as architect_names_ja,
            GROUP_CONCAT(DISTINCT ia.name_en ORDER BY ac.order_index SEPARATOR '　') as architect_names_en,
            GROUP_CONCAT(DISTINCT ia.slug ORDER BY ac.order_index SEPARATOR ',') as architect_slugs
        FROM buildings_table_2 b
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id
        LEFT JOIN architect_compositions ac ON ba.architect_id = ac.architect_id
        LEFT JOIN individual_architects ia ON ac.individual_architect_id = ia.individual_architect_id
        WHERE b.lat IS NOT NULL AND b.lng IS NOT NULL
        GROUP BY b.building_id
        ORDER BY b.building_id DESC
        LIMIT :limit
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $buildings = [];
        while ($row = $stmt->fetch()) {
            $buildings[] = transformBuildingData($row, $lang);
        }
        
        return $buildings;
        
    } catch (PDOException $e) {
        error_log("Recent buildings error: " . $e->getMessage());
        return [];
    }
}

/**
 * 建築物データを変換
 */
function transformBuildingData($row, $lang = 'ja') {
    // 建築家情報の処理
    $architects = [];
    if (!empty($row['architect_names_ja'])) {
        $namesJa = explode('　', $row['architect_names_ja']);
        $namesEn = !empty($row['architect_names_en']) ? explode('　', $row['architect_names_en']) : [];
        $slugs = !empty($row['architect_slugs']) ? explode(',', $row['architect_slugs']) : [];
        
        for ($i = 0; $i < count($namesJa); $i++) {
            $architects[] = [
                'architect_id' => 0, // 個別IDは使用しない
                'architectJa' => trim($namesJa[$i]),
                'architectEn' => isset($namesEn[$i]) ? trim($namesEn[$i]) : trim($namesJa[$i]),
                'slug' => isset($slugs[$i]) ? trim($slugs[$i]) : ''
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
        'id' => $row['building_id'],
        'uid' => $row['uid'],
        'slug' => $row['slug'] ?: $row['uid'],
        'title' => $row['title'],
        'titleEn' => $row['titleEn'] ?: $row['title'],
        'thumbnailUrl' => $row['thumbnailUrl'] ?: '',
        'youtubeUrl' => $row['youtubeUrl'] ?: '',
        'completionYears' => $row['completionYears'] ?: null,
        'buildingTypes' => $buildingTypes,
        'buildingTypesEn' => $buildingTypesEn,
        'prefectures' => $row['prefectures'] ?: '',
        'prefecturesEn' => $row['prefecturesEn'] ?: null,
        'areas' => $row['areas'] ?: '',
        'location' => $row['location'] ?: '',
        'locationEn' => $row['locationEn'] ?: $row['location'],
        'architectDetails' => $row['architectDetails'] ?: '',
        'lat' => floatval($row['lat']),
        'lng' => floatval($row['lng']),
        'architects' => $architects,
        'photos' => [], // 写真は別途取得
        'likes' => $row['likes'] ?: 0,
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ];
}

/**
 * 建築物詳細を取得
 */
function getBuildingBySlug($slug, $lang = 'ja') {
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
            b.locationEn,
            b.architectDetails,
            b.lat,
            b.lng,
            b.likes,
            b.created_at,
            b.updated_at,
            GROUP_CONCAT(DISTINCT ia.name_ja ORDER BY ac.order_index SEPARATOR '　') as architect_names_ja,
            GROUP_CONCAT(DISTINCT ia.name_en ORDER BY ac.order_index SEPARATOR '　') as architect_names_en,
            GROUP_CONCAT(DISTINCT ia.slug ORDER BY ac.order_index SEPARATOR ',') as architect_slugs
        FROM buildings_table_2 b
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id
        LEFT JOIN architect_compositions ac ON ba.architect_id = ac.architect_id
        LEFT JOIN individual_architects ia ON ac.individual_architect_id = ia.individual_architect_id
        WHERE b.slug = :slug OR b.uid = :slug
        GROUP BY b.building_id
        LIMIT 1
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        $stmt->execute();
        
        $row = $stmt->fetch();
        if ($row) {
            return transformBuildingData($row, $lang);
        }
        
        return null;
        
    } catch (PDOException $e) {
        error_log("Building detail error: " . $e->getMessage());
        return null;
    }
}

/**
 * 人気検索を取得
 */
function getPopularSearches($lang = 'ja') {
    // 固定の人気検索（実際のアプリでは検索ログから取得）
    return [
        ['query' => '安藤忠雄', 'count' => 45],
        ['query' => '美術館', 'count' => 38],
        ['query' => '東京', 'count' => 32],
        ['query' => '現代建築', 'count' => 28]
    ];
}

/**
 * 翻訳関数
 */
function t($key, $lang = 'ja') {
    $translations = [
        'ja' => [
            'searchPlaceholder' => '建築物名、建築家、場所で検索...',
            'search' => '検索',
            'currentLocation' => '現在地',
            'detailedSearch' => '詳細検索',
            'withPhotos' => '写真あり',
            'withVideos' => '動画あり',
            'clearFilters' => 'クリア',
            'loading' => '読み込み中...',
            'searchAround' => '周辺を検索',
            'getDirections' => '道順を取得',
            'viewOnGoogleMap' => 'Googleマップで表示',
            'buildingDetails' => '建築物詳細',
            'backToList' => '一覧に戻る',
            'architect' => '建築家',
            'location' => '所在地',
            'prefecture' => '都道府県',
            'buildingTypes' => '建物用途',
            'completionYear' => '完成年',
            'photos' => '写真',
            'videos' => '動画',
            'popularSearches' => '人気の検索',
            'noBuildingsFound' => '建築物が見つかりませんでした。',
            'loadingMap' => '地図を読み込み中...',
            'currentLocation' => '現在地'
        ],
        'en' => [
            'searchPlaceholder' => 'Search by building name, architect, location...',
            'search' => 'Search',
            'currentLocation' => 'Current Location',
            'detailedSearch' => 'Detailed Search',
            'withPhotos' => 'With Photos',
            'withVideos' => 'With Videos',
            'clearFilters' => 'Clear',
            'loading' => 'Loading...',
            'searchAround' => 'Search Around',
            'getDirections' => 'Get Directions',
            'viewOnGoogleMap' => 'View on Google Maps',
            'buildingDetails' => 'Building Details',
            'backToList' => 'Back to List',
            'architect' => 'Architect',
            'location' => 'Location',
            'prefecture' => 'Prefecture',
            'buildingTypes' => 'Building Types',
            'completionYear' => 'Completion Year',
            'photos' => 'Photos',
            'videos' => 'Videos',
            'popularSearches' => 'Popular Searches',
            'noBuildingsFound' => 'No buildings found.',
            'loadingMap' => 'Loading map...',
            'currentLocation' => 'Current Location'
        ]
    ];
    
    return $translations[$lang][$key] ?? $key;
}

/**
 * ページネーションの範囲を取得
 */
function getPaginationRange($currentPage, $totalPages, $maxVisible = 5) {
    $start = max(1, $currentPage - floor($maxVisible / 2));
    $end = min($totalPages, $start + $maxVisible - 1);
    
    if ($end - $start + 1 < $maxVisible) {
        $start = max(1, $end - $maxVisible + 1);
    }
    
    return range($start, $end);
}
?>
