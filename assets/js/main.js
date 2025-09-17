// Main JavaScript for PocketNavi

let map;
let markers = [];

// 地図の初期化
function initMap(center = [35.6762, 139.6503], buildings = []) {
    if (map) {
        map.remove();
    }
    
    map = L.map('map').setView(center, 15);
    
    // タイルレイヤーの追加
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // ズームコントロールの位置調整
    map.zoomControl.setPosition('bottomleft');
    
    // マーカーの追加
    addMarkers(buildings);
    
    // 全マーカーを表示する範囲に調整（複数マーカーの場合のみ）
    if (buildings.length > 1) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
}

// マーカーの追加
function addMarkers(buildings) {
    // 既存のマーカーを削除
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    
    buildings.forEach((building, index) => {
        if (building.lat && building.lng && building.lat !== 0 && building.lng !== 0) {
            const isDetailView = buildings.length === 1;
            
            let icon;
            if (isDetailView) {
                // 詳細ページ用の赤いマーカー
                icon = L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });
            } else {
                // 一覧ページ用の数字付きマーカー
                icon = L.divIcon({
                    html: `<div style="background-color: #2563eb; color: white; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">${index + 1}</div>`,
                    className: 'custom-marker',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                });
            }
            
            const marker = L.marker([building.lat, building.lng], { icon })
                .bindPopup(createPopupContent(building), {
                    closeButton: true,
                    autoClose: false,
                    closeOnClick: false
                });
            
            markers.push(marker);
            map.addLayer(marker);
        }
    });
}

// ポップアップコンテンツの作成
function createPopupContent(building) {
    const lang = document.documentElement.lang || 'ja';
    const title = lang === 'ja' ? building.title : building.titleEn;
    const location = lang === 'ja' ? building.location : building.locationEn;
    
    return `
        <div style="padding: 8px; min-width: 200px;">
            <h3 style="font-weight: bold; font-size: 16px; margin-bottom: 8px;">
                <a href="building.php?slug=${building.slug}&lang=${lang}" 
                   style="color: #1e40af; text-decoration: none;">
                    ${title}
                </a>
            </h3>
            ${location ? `<div style="margin-bottom: 8px;"><strong>${lang === 'ja' ? '所在地' : 'Location'}:</strong> ${location}</div>` : ''}
        </div>
    `;
}

// 現在地の取得
function getCurrentLocation() {
    const btn = document.getElementById('getLocationBtn');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>取得中...';
    btn.disabled = true;
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // 現在地検索のURLにリダイレクト
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('lat', lat);
                currentUrl.searchParams.set('lng', lng);
                currentUrl.searchParams.set('radius', '5'); // デフォルト5km
                currentUrl.searchParams.delete('q'); // キーワード検索をクリア
                
                window.location.href = currentUrl.toString();
            },
            function(error) {
                alert('位置情報の取得に失敗しました: ' + error.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        );
    } else {
        alert('このブラウザは位置情報をサポートしていません。');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// 現在地マーカーの追加
function addCurrentLocationMarker(lat, lng) {
    // 既存の現在地マーカーを削除
    markers.forEach(marker => {
        if (marker.options.isLocationMarker) {
            map.removeLayer(marker);
            const index = markers.indexOf(marker);
            if (index > -1) {
                markers.splice(index, 1);
            }
        }
    });
    
    const locationIcon = L.divIcon({
        html: '<div style="background-color: #ef4444; border-radius: 50%; width: 16px; height: 16px; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); animation: pulse 2s infinite;"></div>',
        className: 'location-marker',
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    });
    
    const locationMarker = L.marker([lat, lng], { 
        icon: locationIcon,
        isLocationMarker: true
    }).bindPopup('<div style="padding: 8px;"><strong>現在地</strong></div>');
    
    markers.push(locationMarker);
    map.addLayer(locationMarker);
}

// 地図上で建物を表示
function showOnMap(lat, lng) {
    if (map) {
        map.setView([lat, lng], 15);
    }
}

// 動画を開く
function openVideo(url) {
    window.open(url, '_blank');
}

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    // 建築物データの取得
    const buildingCards = document.querySelectorAll('.building-card');
    console.log('Found building cards:', buildingCards.length); // デバッグ用
    
    const buildings = Array.from(buildingCards).map((card, index) => {
        console.log(`Card ${index}:`, {
            lat: card.dataset.lat,
            lng: card.dataset.lng,
            title: card.dataset.title,
            titleEn: card.dataset['title-en']
        }); // デバッグ用
        
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        
        // 座標が有効な場合のみ建築物データに含める
        if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
            return {
                building_id: card.dataset.buildingId,
                lat: lat,
                lng: lng,
                title: card.dataset.title,
                titleEn: card.dataset['title-en'], // ハイフンを含む属性名は角括弧でアクセス
                location: card.dataset.location,
                locationEn: card.dataset['location-en'], // ハイフンを含む属性名は角括弧でアクセス
                slug: card.dataset.slug
            };
        }
        return null;
    }).filter(building => building !== null);
    
    console.log('Buildings for map:', buildings); // デバッグ用
    
    // 地図の初期化
    if (document.getElementById('map')) {
        if (buildings.length > 0) {
            // 建築物がある場合は、最初の建築物を中心に設定
            const centerLat = buildings[0].lat;
            const centerLng = buildings[0].lng;
            initMap([centerLat, centerLng], buildings);
        } else {
            // 建築物がない場合は東京を中心に設定
            initMap([35.6762, 139.6503], []);
        }
    }
    
    // 検索フォームの送信
    const searchForm = document.querySelector('form[method="GET"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const query = this.querySelector('input[name="q"]').value.trim();
            if (!query) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // カードのクリックイベント
    buildingCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('a') && !e.target.closest('button')) {
                const link = this.querySelector('.card-title a');
                if (link) {
                    window.location.href = link.href;
                }
            }
        });
    });
});

// パルスアニメーションのCSS追加
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.7; }
        100% { transform: scale(1); opacity: 1; }
    }
`;
document.head.appendChild(style);

