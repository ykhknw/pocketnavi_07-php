// Main JavaScript for PocketNavi

let map;
let markers = [];

// 地図の初期化
function initMap(center = [35.6762, 139.6503], buildings = []) {
    if (map) {
        map.remove();
    }
    
    map = L.map('map').setView(center, 12);
    
    // タイルレイヤーの追加
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // ズームコントロールの位置調整
    map.zoomControl.setPosition('bottomleft');
    
    // マーカーの追加
    addMarkers(buildings);
    
    // 全マーカーを表示する範囲に調整
    if (buildings.length > 0) {
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
        if (building.lat && building.lng) {
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
    
    let architectsHtml = '';
    if (building.architects && building.architects.length > 0) {
        const architectNames = building.architects.map(architect => 
            lang === 'ja' ? architect.architectJa : architect.architectEn
        );
        architectsHtml = `
            <div style="margin-top: 8px;">
                <strong>${lang === 'ja' ? '建築家' : 'Architect'}:</strong><br>
                ${architectNames.join(', ')}
            </div>
        `;
    }
    
    return `
        <div style="padding: 8px; min-width: 200px;">
            <h3 style="font-weight: bold; font-size: 16px; margin-bottom: 8px;">
                <a href="building.php?slug=${building.slug}&lang=${lang}" 
                   style="color: #1e40af; text-decoration: none;">
                    ${title}
                </a>
            </h3>
            ${location ? `<div style="margin-bottom: 8px;"><strong>${lang === 'ja' ? '所在地' : 'Location'}:</strong> ${location}</div>` : ''}
            ${architectsHtml}
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
                
                // 現在地マーカーを追加
                addCurrentLocationMarker(lat, lng);
                
                // 現在地を中心に地図を移動
                map.setView([lat, lng], 15);
                
                btn.innerHTML = originalText;
                btn.disabled = false;
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
    const buildings = Array.from(buildingCards).map(card => {
        const buildingId = card.dataset.buildingId;
        // 実際のアプリでは、サーバーから建築物データを取得
        return {
            id: buildingId,
            lat: parseFloat(card.dataset.lat) || 0,
            lng: parseFloat(card.dataset.lng) || 0,
            title: card.querySelector('.card-title a').textContent,
            slug: card.querySelector('.card-title a').href.split('slug=')[1]?.split('&')[0] || ''
        };
    });
    
    // 地図の初期化
    if (document.getElementById('map')) {
        initMap([35.6762, 139.6503], buildings);
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
