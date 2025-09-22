-- 人気検索ワード機能用テーブル作成
-- MySQL用のスキーマ定義

-- 全ユーザーの検索履歴を保存するテーブル
CREATE TABLE IF NOT EXISTS global_search_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    query TEXT NOT NULL,
    search_type VARCHAR(20) NOT NULL CHECK (search_type IN ('text', 'architect', 'prefecture')),
    user_id BIGINT NULL, -- 匿名ユーザーはNULL
    user_session_id VARCHAR(255) NULL, -- セッション識別用（匿名ユーザー向け）
    ip_address VARCHAR(45) NULL, -- IPアドレス（重複防止用）
    filters JSON NULL, -- フィルター情報
    searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- インデックス
    INDEX idx_query (query(100)),
    INDEX idx_search_type (search_type),
    INDEX idx_searched_at (searched_at),
    INDEX idx_user_session (user_session_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_query_type_date (query(50), search_type, searched_at)
);

-- 人気検索の集計ビュー（過去30日間）
CREATE OR REPLACE VIEW popular_searches_view AS
SELECT 
    query,
    search_type,
    COUNT(*) as total_searches,
    COUNT(DISTINCT COALESCE(user_id, user_session_id, ip_address)) as unique_users,
    MAX(searched_at) as last_searched,
    AVG(TIMESTAMPDIFF(HOUR, searched_at, NOW())) as hours_since_last_search
FROM global_search_history
WHERE searched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY query, search_type
HAVING COUNT(*) >= 2 -- 最低2回以上の検索のみ
ORDER BY 
    total_searches DESC, 
    unique_users DESC,
    hours_since_last_search ASC; -- 最近検索されたものを優先

-- 人気検索の集計関数（過去7日間）
DELIMITER //
CREATE FUNCTION get_popular_searches(days INT) 
RETURNS TEXT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE result TEXT DEFAULT '';
    DECLARE done INT DEFAULT FALSE;
    DECLARE query_text TEXT;
    DECLARE search_type_val VARCHAR(20);
    DECLARE total_searches_val BIGINT;
    DECLARE unique_users_val BIGINT;
    DECLARE last_searched_val TIMESTAMP;
    
    DECLARE cur CURSOR FOR
        SELECT 
            query,
            search_type,
            COUNT(*) as total_searches,
            COUNT(DISTINCT COALESCE(user_id, user_session_id, ip_address)) as unique_users,
            MAX(searched_at) as last_searched
        FROM global_search_history
        WHERE searched_at >= DATE_SUB(NOW(), INTERVAL days DAY)
        GROUP BY query, search_type
        HAVING COUNT(*) >= 2
        ORDER BY 
            total_searches DESC, 
            unique_users DESC,
            last_searched DESC
        LIMIT 50;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO query_text, search_type_val, total_searches_val, unique_users_val, last_searched_val;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        IF result != '' THEN
            SET result = CONCAT(result, ',');
        END IF;
        
        SET result = CONCAT(result, 
            JSON_OBJECT(
                'query', query_text,
                'search_type', search_type_val,
                'total_searches', total_searches_val,
                'unique_users', unique_users_val,
                'last_searched', last_searched_val
            )
        );
    END LOOP;
    CLOSE cur;
    
    RETURN CONCAT('[', result, ']');
END//
DELIMITER ;

-- サンプルデータの挿入（テスト用）
INSERT INTO global_search_history (query, search_type, user_session_id, ip_address, filters) VALUES
('安藤忠雄', 'text', 'session_001', '192.168.1.1', '{"query": "安藤忠雄"}'),
('安藤忠雄', 'text', 'session_002', '192.168.1.2', '{"query": "安藤忠雄"}'),
('安藤忠雄', 'text', 'session_003', '192.168.1.3', '{"query": "安藤忠雄"}'),
('美術館', 'text', 'session_001', '192.168.1.1', '{"query": "美術館"}'),
('美術館', 'text', 'session_004', '192.168.1.4', '{"query": "美術館"}'),
('東京', 'text', 'session_002', '192.168.1.2', '{"query": "東京"}'),
('東京', 'text', 'session_005', '192.168.1.5', '{"query": "東京"}'),
('現代建築', 'text', 'session_003', '192.168.1.3', '{"query": "現代建築"}'),
('コンクリート', 'text', 'session_001', '192.168.1.1', '{"query": "コンクリート"}'),
('隈研吾', 'architect', 'session_002', '192.168.1.2', '{"architects": ["隈研吾"]}'),
('隈研吾', 'architect', 'session_004', '192.168.1.4', '{"architects": ["隈研吾"]}'),
('図書館', 'text', 'session_003', '192.168.1.3', '{"query": "図書館"}'),
('駅舎', 'text', 'session_005', '192.168.1.5', '{"query": "駅舎"}'),
('大阪', 'prefecture', 'session_001', '192.168.1.1', '{"prefectures": ["大阪"]}'),
('大阪', 'prefecture', 'session_002', '192.168.1.2', '{"prefectures": ["大阪"]}'),
('京都', 'prefecture', 'session_003', '192.168.1.3', '{"prefectures": ["京都"]}'),
('京都', 'prefecture', 'session_004', '192.168.1.4', '{"prefectures": ["京都"]}'),
('京都', 'prefecture', 'session_005', '192.168.1.5', '{"prefectures": ["京都"]}');
