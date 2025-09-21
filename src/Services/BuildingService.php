<?php

/**
 * 建築物検索サービス
 */
class BuildingService {
    private $db;
    private $buildings_table = 'buildings_table_3';
    private $building_architects_table = 'building_architects';
    private $architect_compositions_table = 'architect_compositions_2';
    private $individual_architects_table = 'individual_architects_3';
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * 建築物を検索する
     */
    public function search($query, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja', $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // キーワードを分割（全角・半角スペースで分割）
        $keywords = $this->parseKeywords($query);
        
        // WHERE句の構築
        $whereClauses = [];
        $params = [];
        
        // キーワード検索条件の追加
        $this->addKeywordConditions($whereClauses, $params, $keywords);
        
        // メディアフィルターの追加
        $this->addMediaFilters($whereClauses, $hasPhotos, $hasVideos);
        
        // WHERE句の構築
        $whereSql = $this->buildWhereClause($whereClauses);
        
        // カウントクエリ
        $countSql = $this->buildCountQuery($whereSql);
        
        // データ取得クエリ
        $sql = $this->buildSearchQuery($whereSql, $limit, $offset);
        
        try {
            // カウント実行
            $total = $this->executeCountQuery($countSql, $params);
            
            // データ取得実行
            $rows = $this->executeSearchQuery($sql, $params);
            
            // データ変換
            $buildings = $this->transformBuildingData($rows, $lang);
            
            $totalPages = ceil($total / $limit);
            
            return [
                'buildings' => $buildings,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return [
                'buildings' => [],
                'total' => 0,
                'totalPages' => 0,
                'currentPage' => $page
            ];
        }
    }
    
    /**
     * 複数条件での建築物検索
     */
    public function searchWithMultipleConditions($query, $completionYears, $prefectures, $buildingTypes, $hasPhotos, $hasVideos, $page = 1, $lang = 'ja', $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // WHERE句の構築
        $whereClauses = [];
        $params = [];
        
        // キーワード検索条件の追加
        $keywords = $this->parseKeywords($query);
        $this->addKeywordConditions($whereClauses, $params, $keywords);
        
        // 完成年条件の追加
        $this->addCompletionYearConditions($whereClauses, $params, $completionYears);
        
        // 都道府県条件の追加
        $this->addPrefectureConditions($whereClauses, $params, $prefectures);
        
        // 建築種別条件の追加
        $this->addBuildingTypeConditions($whereClauses, $params, $buildingTypes);
        
        // メディアフィルターの追加
        $this->addMediaFilters($whereClauses, $hasPhotos, $hasVideos);
        
        // WHERE句の構築
        $whereSql = $this->buildWhereClause($whereClauses);
        
        // カウントクエリ
        $countSql = $this->buildCountQuery($whereSql);
        
        // データ取得クエリ
        $sql = $this->buildSearchQuery($whereSql, $limit, $offset);
        
        try {
            // カウント実行
            $total = $this->executeCountQuery($countSql, $params);
            
            // データ取得実行
            $rows = $this->executeSearchQuery($sql, $params);
            
            // データ変換
            $buildings = $this->transformBuildingData($rows, $lang);
            
            $totalPages = ceil($total / $limit);
            
            return [
                'buildings' => $buildings,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return [
                'buildings' => [],
                'total' => 0,
                'totalPages' => 0,
                'currentPage' => $page
            ];
        }
    }
    
    /**
     * 位置情報による建築物検索
     */
    public function searchByLocation($userLat, $userLng, $radiusKm = 5, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja', $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // WHERE句の構築
        $whereClauses = [];
        $params = [];
        
        // 位置情報条件の追加
        $this->addLocationConditions($whereClauses, $params, $userLat, $userLng, $radiusKm);
        
        // メディアフィルターの追加
        $this->addMediaFilters($whereClauses, $hasPhotos, $hasVideos);
        
        // WHERE句の構築
        $whereSql = $this->buildWhereClause($whereClauses);
        
        // カウントクエリ
        $countSql = $this->buildCountQuery($whereSql);
        
        // データ取得クエリ（距離順でソート）
        $sql = $this->buildLocationSearchQuery($whereSql, $limit, $offset);
        
        try {
            // カウント実行
            $total = $this->executeCountQuery($countSql, $params);
            
            // データ取得実行
            $rows = $this->executeSearchQuery($sql, $params);
            
            // データ変換
            $buildings = $this->transformBuildingData($rows, $lang);
            
            $totalPages = ceil($total / $limit);
            
            return [
                'buildings' => $buildings,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Location search error: " . $e->getMessage());
            return [
                'buildings' => [],
                'total' => 0,
                'totalPages' => 0,
                'currentPage' => $page
            ];
        }
    }
    
    /**
     * 建築家による建築物検索
     */
    public function searchByArchitectSlug($architectSlug, $page = 1, $lang = 'ja', $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // WHERE句の構築
        $whereClauses = [];
        $params = [];
        
        // 建築家条件の追加
        $this->addArchitectConditions($whereClauses, $params, $architectSlug);
        
        // WHERE句の構築
        $whereSql = $this->buildWhereClause($whereClauses);
        
        // カウントクエリ
        $countSql = $this->buildCountQuery($whereSql);
        
        // データ取得クエリ
        $sql = $this->buildSearchQuery($whereSql, $limit, $offset);
        
        try {
            // カウント実行
            $total = $this->executeCountQuery($countSql, $params);
            
            // データ取得実行
            $rows = $this->executeSearchQuery($sql, $params);
            
            // データ変換
            $buildings = $this->transformBuildingData($rows, $lang);
            
            $totalPages = ceil($total / $limit);
            
            return [
                'buildings' => $buildings,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Architect search error: " . $e->getMessage());
            return [
                'buildings' => [],
                'total' => 0,
                'totalPages' => 0,
                'currentPage' => $page
            ];
        }
    }
    
    /**
     * スラッグで建築物を取得
     */
    public function getBySlug($slug, $lang = 'ja') {
        $sql = "
            SELECT b.building_id,
                   b.uid,
                   b.title,
                   b.titleEn,
                   b.slug,
                   b.lat,
                   b.lng,
                   b.location,
                   b.locationEn_from_datasheetChunkEn as locationEn,
                   b.completionYears,
                   b.buildingTypes,
                   b.buildingTypesEn,
                   b.prefectures,
                   b.prefecturesEn,
                   b.has_photo,
                   b.thumbnailUrl,
                   b.youtubeUrl,
                   b.created_at,
                   b.updated_at,
                   0 as likes,
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
            FROM {$this->buildings_table} b
            LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
            LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
            LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
            WHERE b.slug = ?
            GROUP BY b.building_id
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            
            if ($row) {
                return transformBuildingData($row, $lang);
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Get building by slug error: " . $e->getMessage());
            return null;
        }
    }
    
    // プライベートメソッド群
    
    /**
     * キーワードを分割
     */
    private function parseKeywords($query) {
        if (empty($query)) {
            return [];
        }
        
        $temp = str_replace('　', ' ', $query);
        return array_filter(explode(' ', trim($temp)));
    }
    
    /**
     * キーワード検索条件を追加
     */
    private function addKeywordConditions(&$whereClauses, &$params, $keywords) {
        if (empty($keywords)) {
            return;
        }
        
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
    
    /**
     * 完成年条件を追加
     */
    private function addCompletionYearConditions(&$whereClauses, &$params, $completionYears) {
        if (empty($completionYears)) {
            return;
        }
        
        $yearConditions = [];
        foreach ($completionYears as $year) {
            $yearConditions[] = "b.completionYears LIKE ?";
            $params[] = '%' . $year . '%';
        }
        
        if (!empty($yearConditions)) {
            $whereClauses[] = '(' . implode(' OR ', $yearConditions) . ')';
        }
    }
    
    /**
     * 都道府県条件を追加
     */
    private function addPrefectureConditions(&$whereClauses, &$params, $prefectures) {
        if (empty($prefectures)) {
            return;
        }
        
        $prefectureConditions = [];
        foreach ($prefectures as $prefecture) {
            $prefectureConditions[] = "b.prefectures LIKE ?";
            $params[] = '%' . $prefecture . '%';
        }
        
        if (!empty($prefectureConditions)) {
            $whereClauses[] = '(' . implode(' OR ', $prefectureConditions) . ')';
        }
    }
    
    /**
     * 建築種別条件を追加
     */
    private function addBuildingTypeConditions(&$whereClauses, &$params, $buildingTypes) {
        if (empty($buildingTypes)) {
            return;
        }
        
        $typeConditions = [];
        foreach ($buildingTypes as $type) {
            $typeConditions[] = "b.buildingTypes LIKE ?";
            $params[] = '%' . $type . '%';
        }
        
        if (!empty($typeConditions)) {
            $whereClauses[] = '(' . implode(' OR ', $typeConditions) . ')';
        }
    }
    
    /**
     * メディアフィルターを追加
     */
    private function addMediaFilters(&$whereClauses, $hasPhotos, $hasVideos) {
        if ($hasPhotos) {
            $whereClauses[] = "b.has_photo IS NOT NULL AND b.has_photo != ''";
        }
        
        if ($hasVideos) {
            $whereClauses[] = "b.youtubeUrl IS NOT NULL AND b.youtubeUrl != ''";
        }
    }
    
    /**
     * 位置情報条件を追加
     */
    private function addLocationConditions(&$whereClauses, &$params, $userLat, $userLng, $radiusKm) {
        $whereClauses[] = "b.lat IS NOT NULL AND b.lng IS NOT NULL";
        $whereClauses[] = "(
            6371 * acos(
                cos(radians(?)) * cos(radians(b.lat)) * 
                cos(radians(b.lng) - radians(?)) + 
                sin(radians(?)) * sin(radians(b.lat))
            )
        ) <= ?";
        
        $params[] = $userLat;
        $params[] = $userLng;
        $params[] = $userLat;
        $params[] = $radiusKm;
    }
    
    /**
     * 建築家条件を追加
     */
    private function addArchitectConditions(&$whereClauses, &$params, $architectSlug) {
        $whereClauses[] = "ia.slug = ?";
        $params[] = $architectSlug;
    }
    
    /**
     * WHERE句を構築
     */
    private function buildWhereClause($whereClauses) {
        if (empty($whereClauses)) {
            return '';
        }
        return 'WHERE ' . implode(' AND ', $whereClauses);
    }
    
    /**
     * カウントクエリを構築
     */
    private function buildCountQuery($whereSql) {
        return "
            SELECT COUNT(DISTINCT b.building_id) as total
            FROM {$this->buildings_table} b
            LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
            LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
            LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
            $whereSql
        ";
    }
    
    /**
     * 検索クエリを構築
     */
    private function buildSearchQuery($whereSql, $limit, $offset) {
        return "
            SELECT b.building_id,
                   b.uid,
                   b.title,
                   b.titleEn,
                   b.slug,
                   b.lat,
                   b.lng,
                   b.location,
                   b.locationEn_from_datasheetChunkEn as locationEn,
                   b.completionYears,
                   b.buildingTypes,
                   b.buildingTypesEn,
                   b.prefectures,
                   b.prefecturesEn,
                   b.has_photo,
                   b.thumbnailUrl,
                   b.youtubeUrl,
                   b.created_at,
                   b.updated_at,
                   0 as likes,
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
            FROM {$this->buildings_table} b
            LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
            LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
            LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
            $whereSql
            GROUP BY b.building_id
            ORDER BY b.has_photo DESC, b.building_id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
    }
    
    /**
     * 位置情報検索クエリを構築
     */
    private function buildLocationSearchQuery($whereSql, $limit, $offset) {
        return "
            SELECT b.building_id,
                   b.uid,
                   b.title,
                   b.titleEn,
                   b.slug,
                   b.lat,
                   b.lng,
                   b.location,
                   b.locationEn_from_datasheetChunkEn as locationEn,
                   b.completionYears,
                   b.buildingTypes,
                   b.buildingTypesEn,
                   b.prefectures,
                   b.prefecturesEn,
                   b.has_photo,
                   b.thumbnailUrl,
                   b.youtubeUrl,
                   b.created_at,
                   b.updated_at,
                   0 as likes,
                   (
                       6371 * acos(
                           cos(radians(?)) * cos(radians(b.lat)) * 
                           cos(radians(b.lng) - radians(?)) + 
                           sin(radians(?)) * sin(radians(b.lat))
                       )
                   ) AS distance,
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
            FROM {$this->buildings_table} b
            LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
            LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
            LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
            $whereSql
            GROUP BY b.building_id
            ORDER BY distance ASC
            LIMIT {$limit} OFFSET {$offset}
        ";
    }
    
    /**
     * カウントクエリを実行
     */
    private function executeCountQuery($sql, $params) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }
    
    /**
     * 検索クエリを実行
     */
    private function executeSearchQuery($sql, $params) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * 建築物データを変換
     */
    private function transformBuildingData($rows, $lang) {
        if (is_array($rows) && isset($rows[0])) {
            // 複数行の場合
            $buildings = [];
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $buildings[] = transformBuildingData($row, $lang);
                }
            }
            return $buildings;
        } else {
            // 単一行の場合
            if (is_array($rows)) {
                return transformBuildingData($rows, $lang);
            }
            return [];
        }
    }
}
