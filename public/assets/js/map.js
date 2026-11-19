/**
 * Mapa E-Bike Jardín
 * Integra estaciones de alquiler (MySQL) y puntos de carga (dataset del proyecto).
 * Funciones: geolocalización, estación más cercana, alcance por autonomía y rutas.
 */
document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;

    const mode = mapEl.dataset.mode || 'full';
    const stationsUrl = mapEl.dataset.stationsUrl;
    const chargersUrl = mapEl.dataset.chargersUrl;
    const baseUrl = (window.APP && window.APP.baseUrl) || '';
    const isFull = mode === 'full';

    const map = L.map('map', {
        zoomControl: true,
        scrollWheelZoom: isFull
    }).setView([5.5986, -75.8199], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const rentalLayer = L.layerGroup().addTo(map);
    const chargerLayer = L.layerGroup();
    const markersById = new Map();
    let userMarker = null;
    let userLocation = null;
    let routingControl = null;
    let stations = [];
    let chargers = [];
    let rangeKm = parseFloat((document.getElementById('rangeKm') || {}).value || '20');

    function pinIcon(kind) {
        const icons = { rental: 'bi-bicycle', charger: 'bi-lightning-charge-fill', user: 'bi-person-fill' };
        return L.divIcon({
            className: '',
            html: '<div class="station-pin ' + (kind === 'charger' ? 'charger' : kind === 'user' ? 'user' : '') + '"><i class="bi ' + (icons[kind] || 'bi-geo-alt') + '"></i></div>',
            iconSize: [34, 34],
            iconAnchor: [17, 32],
            popupAnchor: [0, -30]
        });
    }

    function moneyText(value) {
        return '$ ' + Number(value).toLocaleString('es-CO') + ' COP';
    }

    function haversine(lat1, lon1, lat2, lon2) {
        const toRad = function (v) { return v * Math.PI / 180; };
        const R = 6371;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function stationPopup(station) {
        const rentUrl = baseUrl + '/alquileres/iniciar?estacion=' + station.id;
        return '<h4><i class="bi bi-bicycle me-1"></i>' + station.nombre + '</h4>' +
            '<div class="popup-meta">' + (station.direccion || '') + '</div>' +
            '<div class="mb-1"><strong>' + station.bicis_disponibles + '</strong> de ' + station.bicis_totales + ' bicis disponibles</div>' +
            '<div class="popup-meta">Energía estación: ' + Number(station.energia_disponible).toFixed(1) + '%</div>' +
            '<div class="d-flex gap-1 mt-2">' +
            '<button class="btn btn-brand btn-sm" onclick="ebikeRoute(' + station.latitud + ',' + station.longitud + ',\'' + String(station.nombre).replace(/'/g, "\\'") + '\')"><i class="bi bi-signpost-2 me-1"></i>Ruta</button>' +
            '<a class="btn btn-outline-brand btn-sm" href="' + rentUrl + '">Alquilar aquí</a>' +
            '</div>';
    }

    function chargerPopup(item) {
        return '<h4><i class="bi bi-ev-station me-1"></i>' + item.nombre + '</h4>' +
            '<div class="popup-meta">' + (item.direccion || '') + '</div>' +
            '<div class="mb-1">Estado: <strong>' + (item.estado || 'Sin dato') + '</strong></div>' +
            (item.potencia_kw ? '<div class="popup-meta">Potencia: ' + item.potencia_kw + ' kW</div>' : '') +
            (item.tipo_conector ? '<div class="popup-meta">Conector: ' + item.tipo_conector + '</div>' : '') +
            '<button class="btn btn-outline-brand btn-sm mt-2" onclick="ebikeRoute(' + item.latitud + ',' + item.longitud + ',\'' + String(item.nombre).replace(/'/g, "\\'") + '\')"><i class="bi bi-signpost-2 me-1"></i>Ruta</button>';
    }

    function addRentalMarkers() {
        rentalLayer.clearLayers();
        markersById.clear();
        stations.forEach(function (station) {
            const marker = L.marker([station.latitud, station.longitud], { icon: pinIcon('rental') })
                .bindPopup(stationPopup(station));
            marker.addTo(rentalLayer);
            marker.on('click', function () { highlightStation(station.id); });
            markersById.set(station.id, marker);
        });
    }

    function addChargerMarkers() {
        chargerLayer.clearLayers();
        chargers.forEach(function (item) {
            L.marker([Number(item.latitud), Number(item.longitud)], { icon: pinIcon('charger') })
                .bindPopup(chargerPopup(item))
                .addTo(chargerLayer);
        });
    }

    function renderList(list) {
        const container = document.getElementById('stationList');
        if (!container) return;
        if (!list.length) {
            container.innerHTML = '<div class="empty-state py-4"><i class="bi bi-geo"></i><p class="small mb-0">No se encontraron estaciones.</p></div>';
            return;
        }
        container.innerHTML = list.map(function (s) {
            return '<div class="station-item" data-station-id="' + s.id + '">' +
                '<h4><span>' + s.nombre + '</span><span class="chip">' + s.bicis_disponibles + ' libres</span></h4>' +
                '<p><i class="bi bi-geo-alt me-1"></i>' + (s.direccion || '') + '</p>' +
                '<div class="meta"><span><i class="bi bi-bicycle me-1"></i>' + s.bicis_totales + ' bicis</span>' +
                '<span><i class="bi bi-battery-charging me-1"></i>' + Number(s.energia_disponible).toFixed(0) + '%</span>' +
                (s.distance !== undefined ? '<span><i class="bi bi-signpost me-1"></i>' + s.distance.toFixed(1) + ' km</span>' : '') +
                '</div></div>';
        }).join('');

        container.querySelectorAll('[data-station-id]').forEach(function (el) {
            el.addEventListener('click', function () {
                const id = Number(el.dataset.stationId);
                const marker = markersById.get(id);
                if (marker) {
                    map.setView(marker.getLatLng(), 17);
                    marker.openPopup();
                    highlightStation(id);
                }
            });
        });
    }

    function highlightStation(id) {
        document.querySelectorAll('[data-station-id]').forEach(function (el) {
            el.classList.toggle('active', Number(el.dataset.stationId) === id);
        });
        const item = document.querySelector('[data-station-id="' + id + '"]');
        if (item && item.scrollIntoView) {
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    function showNearest(list) {
        const box = document.getElementById('nearestStation');
        if (!box) return;
        if (!userLocation) {
            box.innerHTML = '<p class="text-muted small mb-0"><i class="bi bi-crosshair me-1"></i>Activa tu ubicación para calcular la estación más cercana.</p>';
            return;
        }
        if (!list.length) {
            box.innerHTML = '<p class="text-muted small mb-0">No hay estaciones compatibles.</p>';
            return;
        }
        const nearest = list[0];
        const reachable = nearest.distance <= rangeKm;
        box.innerHTML = '<div class="d-flex justify-content-between align-items-start gap-2">' +
            '<div><div class="fw-bold">' + nearest.nombre + '</div>' +
            '<div class="small text-muted">' + nearest.direccion + '</div>' +
            '<div class="small mt-1"><i class="bi bi-signpost me-1"></i>' + nearest.distance.toFixed(1) + ' km · ' +
            (reachable ? '<span class="text-success fw-semibold">Alcanzable</span>' : '<span class="text-warning fw-semibold">Fuera de autonomía</span>') + '</div></div>' +
            '<button class="btn btn-sm btn-brand" onclick="ebikeRoute(' + nearest.latitud + ',' + nearest.longitud + ',\'' + String(nearest.nombre).replace(/'/g, "\\'") + '\')"><i class="bi bi-signpost-2"></i></button>' +
            '</div>';
    }

    function refreshForLocation() {
        if (!userLocation) return;
        const withDistance = stations.map(function (s) {
            return Object.assign({}, s, { distance: haversine(userLocation.lat, userLocation.lng, s.latitud, s.longitud) });
        }).sort(function (a, b) { return a.distance - b.distance; });

        renderList(withDistance);
