import React, { useState, useMemo, useCallback, useEffect, useRef } from 'react';
import { Search, MapPin, Filter, X, ChevronDown, ChevronUp } from 'lucide-react';
import { SearchFilters } from '../types';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Checkbox } from './ui/checkbox';
import { Label } from './ui/label';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from './ui/collapsible';
import { t } from '../utils/translations';

interface SearchFormProps {
  filters: SearchFilters;
  onFiltersChange: (filters: SearchFilters) => void;
  onGetLocation: () => void;
  locationLoading: boolean;
  locationError: string | null;
  language: 'ja' | 'en';
  onSearchStart?: () => void; // 検索開始時のコールバック
  showAdvancedSearch: boolean;
  setShowAdvancedSearch: (show: boolean) => void;
}

interface CollapsibleSectionProps {
  title: string;
  selectedCount: number;
  children: React.ReactNode;
  defaultOpen?: boolean;
}

function CollapsibleSection({ title, selectedCount, children, defaultOpen = false }: CollapsibleSectionProps) {
  const [isOpen, setIsOpen] = useState(defaultOpen);

  return (
    <Collapsible open={isOpen} onOpenChange={setIsOpen}>
      <CollapsibleTrigger asChild>
        <Button
          variant="ghost"
          className="w-full justify-between p-3 h-auto text-left hover:bg-gray-50"
        >
          <div className="flex items-center gap-2">
            <span className="font-medium">{title}</span>
            {selectedCount > 0 && (
              <span className="bg-primary text-primary-foreground px-2 py-1 rounded-full text-xs">
                {selectedCount}
              </span>
            )}
          </div>
          {isOpen ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
        </Button>
      </CollapsibleTrigger>
      <CollapsibleContent className="px-3 pb-3">
        {children}
      </CollapsibleContent>
    </Collapsible>
  );
}

interface SearchableListProps {
  items: Array<{ id: number; name: string; count: number }>;
  selectedItems: string[];
  onToggle: (item: string) => void;
  searchPlaceholder: string;
  maxHeight?: string;
}

function SearchableList({ 
  items, 
  selectedItems, 
  onToggle, 
  searchPlaceholder,
  maxHeight = "200px" 
}: SearchableListProps) {
  const [searchQuery, setSearchQuery] = useState('');

  // フィルタリング処理をuseMemoで最適化
  const filteredItems = useMemo(() => 
    items.filter(item =>
      item.name.toLowerCase().includes(searchQuery.toLowerCase())
    ),
    [items, searchQuery]
  );

  return (
    <div className="space-y-2">
      <Input
        type="text"
        placeholder={searchPlaceholder}
        value={searchQuery}
        onChange={(e) => setSearchQuery(e.target.value)}
        className="text-sm"
      />
      <div 
        className="space-y-1 overflow-y-auto pr-2"
        style={{ maxHeight }}
      >
        {filteredItems.map(item => (
          <div key={item.id} className="flex items-center justify-between space-x-2 py-1">
            <div className="flex items-center space-x-2 flex-1 min-w-0">
              <Checkbox
                id={`item-${item.id}`}
                checked={selectedItems.includes(item.name)}
                onCheckedChange={() => onToggle(item.name)}
              />
              <Label 
                htmlFor={`item-${item.id}`} 
                className="text-sm truncate cursor-pointer"
              >
                {item.name}
              </Label>
            </div>
            <span className="text-xs text-muted-foreground">
              {item.count}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

function SearchFormComponent({
  filters,
  onFiltersChange,
  onGetLocation,
  locationLoading,
  locationError,
  language,
  onSearchStart,
  showAdvancedSearch,
  setShowAdvancedSearch
}: SearchFormProps) {
  // 内部状態としてshowAdvancedSearchを管理
  const [internalShowAdvancedSearch, setInternalShowAdvancedSearch] = useState(showAdvancedSearch);
  
  // 外部のshowAdvancedSearchと内部状態を同期
  useEffect(() => {
    setInternalShowAdvancedSearch(showAdvancedSearch);
  }, [showAdvancedSearch]);

  // 内部状態が変更された場合、外部にも通知
  const handleAdvancedSearchToggle = useCallback((newValue: boolean) => {
    setInternalShowAdvancedSearch(newValue);
    setShowAdvancedSearch(newValue);
  }, [setShowAdvancedSearch]);

  // アクティブなフィルターがあるかどうかを判定（useMemoで最適化）
  const hasActiveFilters = useMemo(() => 
    filters.query ||
    filters.hasPhotos ||
    filters.hasVideos,
    [filters.query, filters.hasPhotos, filters.hasVideos]
  );

  // Escキーで詳細検索を閉じる
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && internalShowAdvancedSearch) {
        handleAdvancedSearchToggle(false);
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [internalShowAdvancedSearch, handleAdvancedSearchToggle]);

  // 選択された項目数を計算（useMemoで最適化）
  const selectedCounts = useMemo(() => ({
    query: filters.query.trim() ? 1 : 0, // 検索文字列を追加
    media: (filters.hasPhotos ? 1 : 0) + (filters.hasVideos ? 1 : 0) // メディアフィルターを追加
  }), [filters.query, filters.hasPhotos, filters.hasVideos]);

  // フィルターが変更されたときに詳細検索を自動的に開く（一時的に無効化）
  // useEffect(() => {
  //   if (hasActiveFilters && !showAdvancedSearch) {
  //     setShowAdvancedSearch(true);
  //   }
  // }, [filters, hasActiveFilters, showAdvancedSearch, setShowAdvancedSearch]);

  // フィルターの変更を監視してレイアウトを適切に更新
  useEffect(() => {
    // フィルターが変更された際に強制的にレンダリングをトリガー
    // これにより、hasActiveFiltersの状態変化が即座に反映される
  }, [filters]);

  // ハンドラー関数をuseCallbackで最適化
  const handleQueryChange = useCallback((query: string) => {
    // 検索開始時のコールバックを呼び出し
    if (onSearchStart && query.trim() !== filters.query.trim()) {
      onSearchStart();
    }
    onFiltersChange({ ...filters, query });
  }, [filters, onFiltersChange, onSearchStart]);

  const handleQueryClear = useCallback(() => {
    // 検索開始時のコールバックを呼び出し
    if (onSearchStart) {
      onSearchStart();
    }
    onFiltersChange({ ...filters, query: '' });
  }, [filters, onFiltersChange, onSearchStart]);

  // キーワードを分割する関数
  const splitKeywords = useCallback((query: string): string[] => {
    // 全角スペースを半角スペースに変換して分割
    const temp = query.replace(/　/g, ' ');
    return temp.split(' ').filter(keyword => keyword.trim() !== '');
  }, []);

  // 個別のキーワードを削除するハンドラー
  const handleKeywordRemove = useCallback((keywordToRemove: string) => {
    const keywords = splitKeywords(filters.query);
    const newKeywords = keywords.filter(keyword => keyword !== keywordToRemove);
    const newQuery = newKeywords.join(' ');
    
    // 検索開始時のコールバックを呼び出し
    if (onSearchStart) {
      onSearchStart();
    }
    
    onFiltersChange({ ...filters, query: newQuery });
  }, [filters, onFiltersChange, onSearchStart, splitKeywords]);

  const handleRadiusChange = useCallback((radius: number) => {
    onFiltersChange({ ...filters, radius });
  }, [filters, onFiltersChange]);

  const handleLocationClear = useCallback(() => {
    // 検索開始時のコールバックを呼び出し
    if (onSearchStart) {
      onSearchStart();
    }
    
    // 地点検索を解除（currentLocation、lat、lng、radiusをリセット）
    onFiltersChange({
      ...filters,
      currentLocation: null,
      radius: 5 // デフォルト値に戻す
    });
  }, [filters, onFiltersChange, onSearchStart]);


  const handleMediaToggle = useCallback((type: 'photos' | 'videos', checked: boolean) => {
    onFiltersChange({
      ...filters,
      [type === 'photos' ? 'hasPhotos' : 'hasVideos']: checked
    });
  }, [filters, onFiltersChange]);

  const clearFilters = useCallback(() => {
    onFiltersChange({
      ...filters,
      query: '',
      hasPhotos: false,
      hasVideos: false
    });
  }, [filters, onFiltersChange]);


  return (
    <Card className="mb-6">
      <CardContent className="p-6">
        {(() => {
          // 下部余白が必要な条件を明確化
          // 詳細検索が開いている場合、またはエラー・地点検索がある場合は下部余白を確保
          const needBottomSpace = showAdvancedSearch || !!locationError || !!filters.currentLocation;
          const marginClass = needBottomSpace ? 'mb-4' : 'mb-0';
          return (
            <div className={`flex flex-col md:flex-row gap-4 ${marginClass}`}>
              <div className="flex-1">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                  <Input
                    type="text"
                    placeholder={t('searchPlaceholder', language)}
                    value={filters.query}
                    onChange={(e) => handleQueryChange(e.target.value)}
                    className="pl-10"
                  />
                </div>
                
                {/* 選択状態のサマリー表示（トグルを開かなくても確認可能） */}
                {hasActiveFilters && (
                  <div className="mt-3 p-3 bg-muted/50 rounded-lg">
                    <div className="text-sm text-muted-foreground">
                      <span className="font-medium">{language === 'ja' ? '選択中の項目：' : 'Selected items: '}</span>
                      {filters.query.trim() ? (
                        splitKeywords(filters.query).map((keyword, index) => (
                          <span key={`${keyword}-${index}`} className="inline-flex items-center gap-1 bg-primary/10 text-primary px-2 py-1 rounded text-xs ml-2">
                            {keyword}
                            <button
                              onClick={() => handleKeywordRemove(keyword)}
                              className="hover:bg-primary/20 rounded-full w-4 h-4 flex items-center justify-center"
                              title={language === 'ja' ? '削除' : 'Remove'}
                            >
                              <X className="h-3 w-3" />
                            </button>
                          </span>
                        ))
                      ) : null}
                      {filters.hasPhotos ? (
                        <span className="inline-flex items-center gap-1 bg-primary/10 text-primary px-2 py-1 rounded text-xs ml-2">
                          {language === 'ja' ? '写真あり' : 'With Photos'}
                          <button
                            onClick={() => handleMediaToggle('photos', false)}
                            className="hover:bg-primary/20 rounded-full w-4 h-4 flex items-center justify-center"
                            title={language === 'ja' ? '削除' : 'Remove'}
                          >
                            <X className="h-3 w-3" />
                          </button>
                        </span>
                      ) : null}
                      {filters.hasVideos ? (
                        <span className="inline-flex items-center gap-1 bg-primary/10 text-primary px-2 py-1 rounded text-xs ml-2">
                          {language === 'ja' ? '動画あり' : 'With Videos'}
                          <button
                            onClick={() => handleMediaToggle('videos', false)}
                            className="hover:bg-primary/20 rounded-full w-4 h-4 flex items-center justify-center"
                            title={language === 'ja' ? '削除' : 'Remove'}
                          >
                            <X className="h-3 w-3" />
                          </button>
                        </span>
                      ) : null}
                      {!hasActiveFilters && (
                        <span className="text-muted-foreground ml-2">
                          {language === 'ja' ? 'なし' : 'None'}
                        </span>
                      )}
                    </div>
                  </div>
                )}
              </div>
              
              <div className="flex gap-2">
                <Button
                  onClick={onGetLocation}
                  disabled={locationLoading}
                >
                  <MapPin className="h-4 w-4" />
                  {locationLoading ? t('loading', language) : t('currentLocation', language)}
                </Button>
                
                <Button
                  variant="outline"
                  onClick={() => {
                    const newValue = !internalShowAdvancedSearch;
                    
                    try {
                      handleAdvancedSearchToggle(newValue);
                    } catch (error) {
                      console.error('内部状態変更エラー:', error);
                    }
                  }}
                  className="relative"
                >
                  <Filter className="h-4 w-4" />
                  {t('detailedSearch', language)}
                  {hasActiveFilters && (
                    <span className="absolute -top-2 -right-2 bg-primary text-primary-foreground text-xs rounded-full h-5 w-5 flex items-center justify-center">
                      {Object.values(selectedCounts).reduce((sum, count) => sum + count, 0)}
                    </span>
                  )}
                </Button>
              </div>
            </div>
          );
        })()}

        {locationError && (
          <div className="mb-4 p-3 bg-destructive/10 border border-destructive/20 rounded-lg text-destructive text-sm">
            {locationError}
          </div>
        )}



        {internalShowAdvancedSearch && (
          <div className="border-t pt-4">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold text-foreground">{t('detailedSearch', language)}</h3>
              <div className="flex items-center gap-2">
                {/* 強制的に閉じるボタン（常時表示） */}
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handleAdvancedSearchToggle(false)}
                  title={language === 'ja' ? '詳細検索メニューを閉じる' : 'Close detailed search'}
                >
                  {language === 'ja' ? '閉じる' : 'Close'}
                </Button>
                {hasActiveFilters && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={clearFilters}
                    title={language === 'ja' ? '選択したフィルターをすべてクリア' : 'Clear all selected filters'}
                  >
                    <X className="h-4 w-4" />
                    {t('clearFilters', language)}
                  </Button>
                )}
              </div>
            </div>

            <div className="space-y-2 border rounded-lg">
              {/* メディアのみ */}
              <CollapsibleSection
                title={language === 'ja' ? 'メディア' : 'Media'}
                selectedCount={(filters.hasPhotos ? 1 : 0) + (filters.hasVideos ? 1 : 0)}
              >
                <div className="space-y-2 pt-2">
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="has-photos"
                      checked={filters.hasPhotos}
                      onCheckedChange={(checked) => onFiltersChange({ ...filters, hasPhotos: !!checked })}
                    />
                    <Label htmlFor="has-photos" className="text-sm">{t('withPhotos', language)}</Label>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="has-videos"
                      checked={filters.hasVideos}
                      onCheckedChange={(checked) => onFiltersChange({ ...filters, hasVideos: !!checked })}
                    />
                    <Label htmlFor="has-videos" className="text-sm">{t('withVideos', language)}</Label>
                  </div>
                </div>
              </CollapsibleSection>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

// React.memoを使用してコンポーネントを最適化
export const SearchForm = React.memo(SearchFormComponent, (prevProps, nextProps) => {
  // showAdvancedSearchが変更された場合は必ず再レンダリング
  if (prevProps.showAdvancedSearch !== nextProps.showAdvancedSearch) {
    return false; // 再レンダリング
  }
  
  // その他のpropsの変更をチェック
  const shouldUpdate = 
    prevProps.filters !== nextProps.filters ||
    prevProps.language !== nextProps.language ||
    prevProps.locationLoading !== nextProps.locationLoading ||
    prevProps.locationError !== nextProps.locationError;
  
  return !shouldUpdate; // shouldUpdateがtrueの場合は再レンダリング
});