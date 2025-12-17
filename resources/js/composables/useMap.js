// src/composables/useMap.js
import { ref, onMounted } from 'vue';
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import "leaflet.markercluster";
import { toast } from 'vue3-toastify';

// --- BIẾN TOÀN CỤC VÀ HẰNG SỐ ---
let map = null;
let markers = ref({}); 
let markerClusterGroup = null;

const defaultCenter = [21.0285, 105.8542]; // Tọa độ Hà Nội
const defaultZoom = 13;

// Định nghĩa các icon
function createMarkerIcon(iconUrl) {
  return new L.Icon({
    iconUrl,
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  });
}

const redIcon = createMarkerIcon('https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png');
const defaultMarkerIcon = createMarkerIcon('https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png');

// Định nghĩa các icon SVG dùng trong popup
const clockIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`;
const phoneIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle;"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>`;
const mapPinIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>`;

// --- MARKER MANAGEMENT FUNCTIONS ---

export const clearAllMarkers = () => {
    if (map && markerClusterGroup) {
        markerClusterGroup.clearLayers();
        markers.value = {}; 
    }
};

const setupMarkerClusterGroup = () => {
    markerClusterGroup = L.markerClusterGroup({
        iconCreateFunction: function (cluster) {
            const childCount = cluster.getChildCount();
            let bgColor, textColor, borderColor;

            if (childCount < 5) {
                bgColor = '#22c55e'; 
                borderColor = '#16a34a';
                textColor = '#ffffff';
            } else if (childCount < 10) {
                bgColor = '#3b82f6'; 
                borderColor = '#1d4ed8';
                textColor = '#ffffff';
            } else if (childCount < 20) {
                bgColor = '#fb923c'; 
                borderColor = '#ea580c';
                textColor = '#ffffff';
            } else {
                bgColor = '#f43f5e'; 
                borderColor = '#dc2626';
                textColor = '#ffffff';
            }

            return new L.DivIcon({
                html: `
                    <div style="
                        background: ${bgColor};
                        border: 4px solid ${borderColor};
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: ${textColor};
                        font-weight: 700;
                        font-size: 17px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.4), 0 2px 4px rgba(0,0,0,0.3);
                    ">
                        ${childCount}
                    </div>
                `,
                className: 'custom-cluster-icon',
                iconSize: L.point(50, 50)
            });
        },
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: true,
        zoomToBoundsOnClick: true,
        maxClusterRadius: 80,
        disableClusteringAtZoom: 16
    });
    map.addLayer(markerClusterGroup);
};

// --- INITIALIZATION & CONTROL FUNCTIONS ---

function resetMap() {
    if (map && map._currentLocationMarker) {
        map.removeLayer(map._currentLocationMarker);
        map._currentLocationMarker = null;
    }
    if(map) {
        map.setView(defaultCenter, defaultZoom);
    }
}

function setupCustomControls() {
    if (!map) return;

    // Control: Reset Map
    const resetControl = L.control({ position: window.innerWidth <= 1024 ? 'topright' : 'bottomright' });
    resetControl.onAdd = function () {
        const btn = L.DomUtil.create('button', 'leaflet-bar leaflet-control leaflet-control-custom');
        btn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px; margin: auto;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        `;
        btn.title = 'Đặt lại bản đồ';
        btn.style.cssText = 'background-color: white; width: 48px; height: 48px; cursor: pointer; font-size: 18px; line-height: 30px; text-align: center; margin: 10px;';
        L.DomEvent.disableClickPropagation(btn);
        btn.onclick = function () { resetMap(); };
        return btn;
    };
    resetControl.addTo(map);

    // Control: Current Location
    const currentLocation = L.control({ position: window.innerWidth <= 1024 ? 'topright' : 'bottomright' });
    currentLocation.onAdd = function () {
        const btn = L.DomUtil.create('button', 'leaflet-bar leaflet-control leaflet-control-custom');
        btn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px; margin: auto;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
        `;
        btn.title = 'Vị trí hiện tại của tôi';
        btn.style.cssText = 'background-color: white; width: 48px; height: 48px; cursor: pointer; font-size: 18px; line-height: 30px; text-align: center; margin: 10px; margin-bottom: 0;';
        L.DomEvent.disableClickPropagation(btn);
        btn.onclick = function () {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    const latLng = [position.coords.latitude, position.coords.longitude];
                    map.setView(latLng, 16);

                    if (map._currentLocationMarker) {
                        map.removeLayer(map._currentLocationMarker);
                    }
                    map._currentLocationMarker = L.marker(latLng, { icon: redIcon })
                        .addTo(map)
                        .bindPopup("Vị trí hiện tại của bạn")
                        .openPopup();
                }, function () {
                    toast.error('Không thể lấy vị trí hiện tại');
                });
            } else {
                toast.error('Trình duyệt không hỗ trợ định vị địa lý');
            }
        };
        return btn;
    };
    currentLocation.addTo(map);

    // Xử lý thay đổi vị trí controls khi resize màn hình
    window.addEventListener('resize', () => {
        const newPosition = window.innerWidth <= 1024 ? 'topright' : 'bottomright';
        resetControl.setPosition(newPosition);
        currentLocation.setPosition(newPosition);
    });
}

/**
 * Khởi tạo bản đồ Leaflet.
 * @param {Function} initialLoadCallback - Callback để tải dữ liệu ban đầu sau khi map sẵn sàng.
 */
export const initMap = (initialLoadCallback) => {
    onMounted(() => {
        // 1. Định nghĩa Base Layers
        const defaults = L.tileLayer('https://api.maptiler.com/maps/outdoor-v2/{z}/{x}/{y}.png?key=cVxgYKHPCe98W6oTrqUQ');
        const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
        const satellite = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}');
        const topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png');

        const baseLayers = {
            "Bản đồ mặc định": defaults,
            "Bản đồ giao thông": streets,
            "Bản đồ vệ tinh": satellite,
            "Bản đồ địa hình": topo
        };

        const bounds = L.latLngBounds(
            [8.0, 102.0],
            [23.5, 109.5]
        );

        // 2. Khởi tạo Map
        map = L.map('map', {
            center: defaultCenter,
            zoom: defaultZoom,
            layers: [defaults],
            maxBounds: bounds,
            maxBoundsViscosity: 1.0,
            minZoom: 9,
            maxZoom: 18,
            attributionControl: false
        });

        // 3. Thêm Layer Control
        L.control.layers(baseLayers).addTo(map);

        // 4. Thiết lập Marker Cluster Group
        setupMarkerClusterGroup();

        // 5. Thiết lập Controls tùy chỉnh (Reset & Current Location)
        setupCustomControls();

        // 6. Tải nội dung ban đầu (qua callback)
        if (initialLoadCallback) {
            initialLoadCallback();
        }
    });
};

/**
 * Hàm Focus Marker chung
 * @param {string} itemId - ID của item
 */
export const focusItem = (itemId) => {
    if (!map) return;
    
    const marker = markers.value[itemId];
    if (marker) {
        map.setView(marker.getLatLng(), 17, {
            animate: true,
            duration: 0.8,
            easeLinearity: 0.5
        });

        setTimeout(() => {
            if (markerClusterGroup && markerClusterGroup.hasLayer(marker)) {
                markerClusterGroup.zoomToShowLayer(marker, () => {
                    marker.openPopup();
                });
            } else {
                marker.openPopup();
            }
        }, 900);
    }
};

// --- HÀM ADD MARKER ---

export const addCourtMarkers = (courtsData, toHourMinute, defaultImage, onMarkerClick) => {
    courtsData.forEach(c => {
        if (!c.latitude || !c.longitude || isNaN(c.latitude) || isNaN(c.longitude)) return;

        const popupContent = `
            <div style="min-width: 220px; font-family: system-ui; margin-top: 20px;">
                <img src="${c.image || defaultImage}" alt="Court Image" style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; margin-bottom: 10px;" onerror="this.onerror=null;this.src='${defaultImage}'" />
                <h3 style="margin: 0 0 10px 0; font-weight: 600; font-size: 16px; color: #1f2937;">${c.name}</h3>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <p style="margin: 0;display:flex; justify-content:start; align-items:center; gap:6px; font-size: 14px; color: #4b5563;">
                        <span style="color: #4392E0; font-weight: 500;">${clockIcon}</span> 
                        Giờ Mở cửa: ${toHourMinute(c.opening_time)} - ${toHourMinute(c.closing_time)}
                    </p>
                    <p style="margin: 0;display:flex; justify-content:start; align-items:center; gap:6px; font-size: 14px; color: #4b5563;">
                        <span style="color: #4392E0; font-weight: 500;">${phoneIcon}</span> 
                        ${c.phone}
                    </p>
                    <p style="margin: 0;display:flex; justify-content:start; align-items:baseline; gap:6px; font-size: 14px; color: #4b5563; line-height: 1.4;">
                        <span style="color: #4392E0; font-weight: 500;">${mapPinIcon}</span> 
                        ${c.address}
                    </p>
                </div>
            </div>
        `;
        
        const m = L.marker([c.latitude, c.longitude], { icon: defaultMarkerIcon }).bindPopup(popupContent, { maxWidth: 300 });

        markers.value[c.id] = m;
        markerClusterGroup.addLayer(m);
        
        if (onMarkerClick) {
            m.on('click', () => { onMarkerClick(c); });
        }
    });
};

export const addUserMarkers = (usersData, defaultImage, maleIcon, femaleIcon, getVisibilityText, getUserRating, router, onMarkerClick) => {
    // Helper function cho icon giới tính
    const getGenderIconHtml = (gender) => {
        if (gender == 1) return `<img src="${maleIcon}" alt="male" style="width: 16px; height: 16px;"/>`;
        if (gender == 2) return `<img src="${femaleIcon}" alt="female" style="width: 16px; height: 16px;"/>`;
        return '';
    };

    // Helper function cho badge trạng thái hiển thị
    const getVisibilityBadgeHtml = (visibility) => {
        let bgColor, textColor;
        if (visibility === 'open') {
            bgColor = '#dcfce7'; 
            textColor = '#15803d'; 
        } else if (visibility === 'friend-only') {
            bgColor = '#fef9c3'; 
            textColor = '#a16207'; 
        } else if (visibility === 'private') {
            bgColor = '#fee2e2'; 
            textColor = '#b91c1c'; 
        } else {
            bgColor = '#e5e7eb'; 
            textColor = '#4b5563'; 
        }

        return `
            <span style="
                padding: 2px 8px; 
                border-radius: 4px; 
                font-size: 10px; 
                font-weight: 500; 
                text-transform: capitalize; 
                white-space: nowrap; 
                background-color: ${bgColor}; 
                color: ${textColor};
            ">
                ${getVisibilityText(visibility)}
            </span>
        `;
    };

    usersData.forEach(user => {
        if (!user.latitude || !user.longitude || isNaN(user.latitude) || isNaN(user.longitude)) return;

        const rating = getUserRating(user);
        const genderIconHtml = getGenderIconHtml(user.gender);
        const visibilityBadgeHtml = getVisibilityBadgeHtml(user.visibility);
        const genderText = user.gender_text || 'Khác';
        const ageGroup = user.age_group ? ' • ' + user.age_group : '';

        const popupContent = `
            <div id="user-popup-${user.id}" data-user-id="${user.id}" style="
                min-width: 250px; max-width: 300px; font-family: system-ui; padding: 0; margin: 0; cursor: pointer;
            ">
                <div class="popup-header" style="
                    display: flex; 
                    align-items: center; 
                    padding: 15px; 
                    border-bottom: 1px solid #e0e0e0;
                    border-radius: 6px 6px 0 0;
                ">
                    <div style="position: relative; flex-shrink: 0; margin-right: 15px;">
                        <img src="${user.avatar_url || defaultImage}" alt="Avatar" style="
                            width: 60px; 
                            height: 60px; 
                            object-fit: cover; 
                            border-radius: 50%;
                            border: 2px solid #ffffff; 
                            box-shadow: 0 0 0 2px #4392E0; 
                        " onerror="this.onerror=null;this.src='${defaultImage}'" />
                        <div style="
                            position: absolute; 
                            bottom: -3px; 
                            right: -3px; 
                            background-color: #f59e0b; 
                            color: white; 
                            border-radius: 10px; 
                            padding: 2px 6px;
                            font-size: 11px; 
                            line-height: 14px;
                            display: flex; 
                            align-items: center; 
                            justify-content: center;
                            font-weight: 700;
                            box-shadow: 0 1px 4px rgba(0,0,0,0.4);
                            width: 20px;
                            height: 20px;
                        ">
                            ${rating}
                        </div>
                    </div>
                    
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <h3 style="margin: 0; font-weight: 700; font-size: 16px; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${user.full_name}">
                                ${user.full_name}
                            </h3>
                            ${visibilityBadgeHtml}
                        </div>
                        <p style="margin: 3px 0 0 0; font-size: 13px; color: #6b7280;">Người chơi Pickleball</p>
                    </div>
                </div>

                <div class="popup-body" style="padding: 15px; background-color: white;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: #4b5563;">
                            <span style="color: #4392E0; flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px;">
                                ${genderIconHtml}
                            </span>
                            <span>${genderText}${ageGroup}</span>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 8px; font-size: 14px; color: #4b5563;">
                            <span style="color: #4392E0; flex-shrink: 0; width: 20px; height: 20px;">
                                ${mapPinIcon} 
                            </span>
                            <p style="margin: 0; line-height: 1.4; color: #374151;" title="${user.address}">
                                ${user.address}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const m = L.marker([user.latitude, user.longitude], { icon: defaultMarkerIcon }).bindPopup(popupContent, { maxWidth: 350 });

        markers.value[user.id] = m;
        markerClusterGroup.addLayer(m);
        
        if (onMarkerClick) {
            m.on('click', () => { onMarkerClick(user); });
        }
        
        m.on('popupopen', (e) => {
            const popupContainer = document.getElementById(`user-popup-${user.id}`);
            if (popupContainer) {
                popupContainer.addEventListener('click', (event) => {
                    const closeButton = e.popup.getElement().querySelector('.leaflet-popup-close-button');
                    if (closeButton && closeButton.contains(event.target)) {
                        return;
                    }
                    router.push({ path: `/profile/${user.id}` });
                    event.stopPropagation();
                });
            }
        });
    });
};

export const addMatchMarkers = (matchesData, onMarkerClick) => {
    matchesData.forEach(match => {
        if (!match.latitude || !match.longitude || isNaN(match.latitude) || isNaN(match.longitude)) return;

        const m = L.marker([match.latitude, match.longitude], { icon: defaultMarkerIcon })
            .bindPopup(`Thông tin trận đấu: ${match.name}`);

        markers.value[match.id] = m;
        markerClusterGroup.addLayer(m);
        
        if (onMarkerClick) {
            m.on('click', () => { onMarkerClick(match); });
        }
    });
};

export { markers };