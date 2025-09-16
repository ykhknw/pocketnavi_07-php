import React from 'react';
import Map from '../Map';
import { SearchHistoryComponent } from '../SearchHistory';
import { Building, SearchHistory } from '../../types';

interface SidebarProps {
  buildings: Building[];
  selectedBuilding: Building | null;
  onBuildingSelect: (building: Building) => void;
  currentLocation: { lat: number; lng: number } | null;
  language: 'ja' | 'en';
  startIndex: number;
  onSearchAround: (lat: number, lng: number) => void;
  popularSearches: SearchHistory[];
  popularSearchesLoading?: boolean;
  popularSearchesError?: string | null;
  onSearchClick: (query: string) => void;
  onFilterSearchClick?: (filters: Partial<SearchHistory['filters']>) => void;
  showAdminPanel?: boolean;
}

function SidebarComponent({
  buildings,
  selectedBuilding,
  onBuildingSelect,
  currentLocation,
  language,
  startIndex,
  onSearchAround,
  popularSearches,
  popularSearchesLoading = false,
  popularSearchesError = null,
  onSearchClick,
  onFilterSearchClick,
  showAdminPanel = false
}: SidebarProps) {
  return (
    <div className="lg:col-span-1 space-y-6 lg:pl-4 pt-6">
      <div style={{ zIndex: showAdminPanel ? 1 : 'auto' }}>
        <Map
          buildings={buildings}
          selectedBuilding={selectedBuilding}
          onBuildingSelect={onBuildingSelect}
          currentLocation={currentLocation}
          language={language}
          startIndex={startIndex}
          onSearchAround={onSearchAround}
        />
      </div>
      
      <SearchHistoryComponent
        popularSearches={popularSearches}
        popularSearchesLoading={popularSearchesLoading}
        popularSearchesError={popularSearchesError}
        language={language}
        onSearchClick={onSearchClick}
        onFilterSearchClick={onFilterSearchClick}
      />
    </div>
  );
}

// Props比較関数
const arePropsEqual = (prevProps: SidebarProps, nextProps: SidebarProps): boolean => {
  return (
    (prevProps.buildings?.length ?? 0) === (nextProps.buildings?.length ?? 0) &&
    (prevProps.buildings?.every((building, index) => 
      building.id === nextProps.buildings?.[index]?.id
    ) ?? true) &&
    prevProps.selectedBuilding?.id === nextProps.selectedBuilding?.id &&
    prevProps.currentLocation?.lat === nextProps.currentLocation?.lat &&
    prevProps.currentLocation?.lng === nextProps.currentLocation?.lng &&
    prevProps.language === nextProps.language &&
    prevProps.startIndex === nextProps.startIndex &&
    (prevProps.popularSearches?.length ?? 0) === (nextProps.popularSearches?.length ?? 0) &&
    prevProps.onBuildingSelect === nextProps.onBuildingSelect &&
    prevProps.onSearchAround === nextProps.onSearchAround &&
    prevProps.onSearchClick === nextProps.onSearchClick &&
    prevProps.showAdminPanel === nextProps.showAdminPanel
  );
};

export default React.memo(SidebarComponent, arePropsEqual); 