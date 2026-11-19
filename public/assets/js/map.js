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
        showNearest(withDistance);

        const reachable = withDistance.filter(function (s) { return s.distance <= rangeKm; });
        const countEl = document.getElementById('reachableCount');
        if (countEl) countEl.textContent = reachable.length + ' estaciones a menos de ' + rangeKm + ' km';
    }

    function getUserLocation(showErrors) {
        return new Promise(function (resolve) {
            if (!navigator.geolocation) {
                if (showErrors && window.Swal) {
                    Swal.fire({ icon: 'info', title: 'Geolocalización no disponible', text: 'Tu navegador no permite obtener la ubicación.' });
                }
                resolve(null);
                return;
            }
            navigator.geolocation.getCurrentPosition(function (position) {
                userLocation = { lat: position.coords.latitude, lng: position.coords.longitude };
                if (userMarker) map.removeLayer(userMarker);
                userMarker = L.marker([userLocation.lat, userLocation.lng], { icon: pinIcon('user') })
                    .bindPopup('<strong>Tu ubicación</strong>')
                    .addTo(map);
                map.setView([userLocation.lat, userLocation.lng], 15);
                refreshForLocation();
                resolve(userLocation);
            }, function () {
                if (showErrors && window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'No pudimos obtener tu ubicación', text: 'Revisa los permisos del navegador e inténtalo de nuevo.' });
                }
                resolve(null);
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    }

    function showRoutePanel(distanceKm, timeMinutes, name) {
        const panel = document.getElementById('routePanel');
        const body = document.getElementById('routePanelBody');
        if (!panel || !body) return;
        panel.classList.remove('d-none');
        body.innerHTML = '<div class="route-summary">' +
            '<div class="fw-bold"><i class="bi bi-geo-alt me-1"></i>' + name + '</div>' +
            '<div><span class="text-muted">Distancia:</span> <strong>' + distanceKm.toFixed(1) + ' km</strong></div>' +
            '<div><span class="text-muted">Tiempo estimado:</span> <strong>' + Math.round(timeMinutes) + ' min</strong></div>' +
            '</div>';
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    window.ebikeRoute = function (lat, lng, name) {
        const draw = function (origin) {
            if (routingControl) {
                map.removeControl(routingControl);
                routingControl = null;
            }
            routingControl = L.Routing.control({
                waypoints: [L.latLng(origin.lat, origin.lng), L.latLng(lat, lng)],
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                show: false,
                addWaypoints: false,
                routeWhileDragging: false,
                fitSelectedRoutes: true,
                createMarker: function () { return null; },
                lineOptions: { styles: [{ color: '#0b6e4f', weight: 5, opacity: .85 }] }
            }).addTo(map);

            routingControl.on('routesfound', function (event) {
                const route = event.routes[0];
                showRoutePanel(route.summary.totalDistance / 1000, route.summary.totalTime / 60, name);
            });
            routingControl.on('routingerror', function () {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'info',
                        title: 'No se pudo calcular la ruta',
                        html: 'El servicio de rutas no está disponible. Puedes abrir la ruta en Google Maps:<br><a target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng + '">Abrir Google Maps</a>'
                    });
                }
            });
        };

        if (userLocation) {
            draw(userLocation);
        } else {
            getUserLocation(true).then(function (origin) {
                if (origin) draw(origin);
            });
        }
    };

    // Cargar datos
    if (stationsUrl) {
        fetch(stationsUrl)
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                stations = payload.data || [];
                addRentalMarkers();
                renderList(stations);
                if (stations.length) {
                    const bounds = L.latLngBounds(stations.map(function (s) { return [s.latitud, s.longitud]; }));
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
                }
            })
            .catch(function () {
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar las estaciones de alquiler.' });
            });
    }

    if (chargersUrl) {
        fetch(chargersUrl)
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                chargers = (payload.data || []).filter(function (item) {
                    return item.latitud !== null && item.longitud !== null;
                });
                addChargerMarkers();
                if (!isFull) return;
                const toggle = document.getElementById('filterCargadores');
                if (toggle && toggle.checked) chargerLayer.addTo(map);
            })
            .catch(function () { /* el mapa de alquiler sigue funcionando */ });
    }

    if (isFull) {
        const filterRental = document.getElementById('filterEstaciones');
        const filterCharger = document.getElementById('filterCargadores');
        const search = document.getElementById('stationSearch');
        const locateBtn = document.getElementById('locateBtn');
        const resetBtn = document.getElementById('resetView');
        const rangeInput = document.getElementById('rangeKm');
        const rangeLabel = document.getElementById('rangeLabel');

        if (filterRental) {
            filterRental.addEventListener('change', function () {
                if (filterRental.checked) rentalLayer.addTo(map); else map.removeLayer(rentalLayer);
            });
        }
        if (filterCharger) {
            filterCharger.addEventListener('change', function () {
                if (filterCharger.checked) chargerLayer.addTo(map); else map.removeLayer(chargerLayer);
            });
        }
        if (search) {
            search.addEventListener('input', function () {
                const term = search.value.toLowerCase().trim();
                const filtered = stations.filter(function (s) {
                    return !term || (s.nombre + ' ' + (s.direccion || '')).toLowerCase().includes(term);
                });
                renderList(filtered);
            });
        }
        if (locateBtn) locateBtn.addEventListener('click', function () { getUserLocation(true); });
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (userMarker) { map.removeLayer(userMarker); userMarker = null; }
                userLocation = null;
                if (routingControl) { map.removeControl(routingControl); routingControl = null; }
                const panel = document.getElementById('routePanel');
                if (panel) panel.classList.add('d-none');
                renderList(stations);
                if (stations.length) {
                    map.fitBounds(L.latLngBounds(stations.map(function (s) { return [s.latitud, s.longitud]; })), { padding: [40, 40], maxZoom: 16 });
                }
            });
        }
        if (rangeInput) {
            rangeInput.addEventListener('input', function () {
                rangeKm = parseFloat(rangeInput.value);
                if (rangeLabel) rangeLabel.textContent = rangeKm + ' km';
                refreshForLocation();
            });
        }
    }
});
