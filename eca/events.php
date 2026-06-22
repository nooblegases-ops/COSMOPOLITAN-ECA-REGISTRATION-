<?php
require_once __DIR__ . '/includes/db.php';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        club_id INT NOT NULL,
        event_name VARCHAR(120) NOT NULL,
        event_date DATE NOT NULL,
        start_time TIME NULL,
        location VARCHAR(255) NOT NULL,
        latitude DECIMAL(10, 7) NULL,
        longitude DECIMAL(10, 7) NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_events_club
            FOREIGN KEY (club_id) REFERENCES clubs(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB
");

$eventColumns = $pdo->query('SHOW COLUMNS FROM events')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('latitude', $eventColumns, true)) {
    $pdo->exec('ALTER TABLE events ADD latitude DECIMAL(10, 7) NULL AFTER location');
}
if (!in_array('longitude', $eventColumns, true)) {
    $pdo->exec('ALTER TABLE events ADD longitude DECIMAL(10, 7) NULL AFTER latitude');
}
$locationColumn = $pdo->query("SHOW COLUMNS FROM events LIKE 'location'")->fetch();
if ($locationColumn && preg_match('/varchar\((\d+)\)/i', $locationColumn['Type'], $matches) && (int) $matches[1] < 255) {
    $pdo->exec('ALTER TABLE events MODIFY location VARCHAR(255) NOT NULL');
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM events WHERE id = :id');
    $stmt->execute(['id' => $id]);
    redirect_with_alert('events.php', 'success', 'Event deleted successfully.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubId = (int) ($_POST['club_id'] ?? 0);
    $eventName = trim($_POST['event_name'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($clubId <= 0 || $eventName === '' || $eventDate === '' || $location === '') {
        redirect_with_alert('events.php', 'error', 'Club, event name, date, and location are required.');
    }

    $stmt = $pdo->prepare('
        INSERT INTO events (club_id, event_name, event_date, start_time, location, latitude, longitude, description)
        VALUES (:club_id, :event_name, :event_date, :start_time, :location, :latitude, :longitude, :description)
    ');
    $stmt->execute([
        'club_id' => $clubId,
        'event_name' => $eventName,
        'event_date' => $eventDate,
        'start_time' => $startTime !== '' ? $startTime : null,
        'location' => $location,
        'latitude' => $latitude !== '' ? $latitude : null,
        'longitude' => $longitude !== '' ? $longitude : null,
        'description' => $description !== '' ? $description : null,
    ]);

    redirect_with_alert('events.php', 'success', 'Event created successfully.');
}

function google_maps_search_url(string $location): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($location);
}

function google_maps_embed_url(string $location): string
{
    return 'https://www.google.com/maps?q=' . rawurlencode($location) . '&output=embed';
}

function event_has_coordinates(array $event): bool
{
    return $event['latitude'] !== null && $event['longitude'] !== null;
}

function event_map_query(array $event): string
{
    return event_has_coordinates($event)
        ? $event['latitude'] . ',' . $event['longitude']
        : $event['location'];
}

$clubs = $pdo->query('SELECT id, club_name FROM clubs ORDER BY club_name')->fetchAll();
$events = $pdo->query('
    SELECT e.*, c.club_name
    FROM events e
    INNER JOIN clubs c ON c.id = e.club_id
    ORDER BY e.event_date DESC, e.start_time DESC, e.created_at DESC
')->fetchAll();

$pageTitle = 'Events';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
require_once __DIR__ . '/includes/header.php';
?>
<div class="stack">
    <div class="card">
        <h2 class="section-title">New Event</h2>
        <form method="post">
            <div class="form-grid">
                <div>
                    <label for="club_id">Club</label>
                    <select id="club_id" name="club_id" required>
                        <option value="">Select club</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= (int) $club['id'] ?>"><?= e($club['club_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="event_name">Event Name</label>
                    <input id="event_name" name="event_name" maxlength="120" required>
                </div>
                <div>
                    <label for="event_date">Date</label>
                    <input id="event_date" name="event_date" type="date" required>
                </div>
                <div>
                    <label for="start_time">Start Time</label>
                    <input id="start_time" name="start_time" type="time">
                </div>
                <div>
                    <label for="location">Location</label>
                    <input id="location" name="location" maxlength="255" placeholder="Click to search and mark location" autocomplete="off" readonly required>
                    <input id="latitude" name="latitude" type="hidden">
                    <input id="longitude" name="longitude" type="hidden">
                </div>
            </div>
            <div class="mt-4">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus"></i> Save Event</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 class="section-title">Event List</h2>
        <div class="event-card-grid">
            <?php foreach ($events as $event): ?>
                <article class="event-card">
                    <div class="event-card-content">
                        <div>
                            <p class="eyebrow"><?= e($event['club_name']) ?></p>
                            <h3><?= e($event['event_name']) ?></h3>
                        </div>
                        <div class="event-meta">
                            <span><i class="fa-solid fa-calendar-day"></i> <?= e($event['event_date']) ?></span>
                            <span><i class="fa-solid fa-clock"></i> <?= e($event['start_time'] ? substr($event['start_time'], 0, 5) : '-') ?></span>
                        </div>
                        <?php if (!empty($event['description'])): ?>
                            <p class="event-description"><?= e($event['description']) ?></p>
                        <?php endif; ?>
                        <div class="event-location">
                            <strong><i class="fa-solid fa-location-dot"></i> <?= e($event['location']) ?></strong>
                            <div class="actions">
                                <a class="btn btn-light" href="<?= e(google_maps_search_url(event_map_query($event))) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open in Google Maps</a>
                                <a class="btn btn-light confirm-delete" href="events.php?delete=<?= (int) $event['id'] ?>"><i class="fa-solid fa-trash"></i> Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php if (event_has_coordinates($event)): ?>
                        <div
                            class="map-preview map-preview-large saved-event-map"
                            data-lat="<?= e($event['latitude']) ?>"
                            data-lng="<?= e($event['longitude']) ?>"
                            data-label="<?= e($event['location']) ?>"></div>
                    <?php else: ?>
                        <iframe
                            class="map-preview map-preview-large"
                            src="<?= e(google_maps_embed_url($event['location'])) ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Map for <?= e($event['location']) ?>"></iframe>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if (!$events): ?>
                <p class="empty-text">No events created yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="map-picker-modal" id="mapPickerModal" aria-hidden="true">
    <div class="map-picker-dialog">
        <div class="crop-header">
            <div>
                <p class="eyebrow">Event Location</p>
                <h2>Choose and Mark Location</h2>
            </div>
            <button class="btn btn-light" id="closeMapPicker" type="button"><i class="fa-solid fa-xmark"></i> Close</button>
        </div>
        <div class="map-picker-search-row">
            <input id="mapSearchInput" placeholder="Search location">
            <button class="btn btn-secondary" id="previewMapLocation" type="button"><i class="fa-solid fa-magnifying-glass"></i> Preview</button>
        </div>
        <p class="map-picker-hint"><i class="fa-solid fa-location-dot"></i> Search a place or click anywhere on the map to move the marker.</p>
        <div class="map-picker-frame">
            <div id="eventMapPicker" class="map-picker-canvas" aria-label="Location picker map"></div>
            <span class="map-pin-label" id="mapPinLabel">Cosmopolitan College</span>
        </div>
        <div class="actions">
            <button class="btn btn-primary" id="useMapLocation" type="button"><i class="fa-solid fa-location-dot"></i> Mark This Location</button>
            <a class="btn btn-light" id="openMapSearch" href="<?= e(google_maps_search_url('Cosmopolitan College')) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open Google Maps</a>
        </div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    (function () {
        const locationInput = document.getElementById('location');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const modal = document.getElementById('mapPickerModal');
        const closeButton = document.getElementById('closeMapPicker');
        const useButton = document.getElementById('useMapLocation');
        const previewButton = document.getElementById('previewMapLocation');
        const searchInput = document.getElementById('mapSearchInput');
        const mapElement = document.getElementById('eventMapPicker');
        const openMapSearch = document.getElementById('openMapSearch');
        const mapPinLabel = document.getElementById('mapPinLabel');
        const defaultPosition = [4.9031, 114.9398];
        let pickerMap = null;
        let pickerMarker = null;
        let selectedLat = null;
        let selectedLng = null;
        let selectedLabel = '';
        let selectedSearchQuery = '';

        if (!locationInput || !latitudeInput || !longitudeInput || !modal || !closeButton || !useButton || !previewButton || !searchInput || !mapElement || !openMapSearch || !mapPinLabel || typeof L === 'undefined') {
            return;
        }

        const locationIcon = L.divIcon({
            className: 'event-location-marker',
            html: '<i class="fa-solid fa-location-dot" aria-hidden="true"></i>',
            iconSize: [34, 42],
            iconAnchor: [17, 40]
        });

        const fallbackPlaceName = function (lat, lng) {
            return 'Marked location (' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ')';
        };

        const closeModal = function () {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        };

        const sameText = function (first, second) {
            return first.trim().toLowerCase() === second.trim().toLowerCase();
        };

        document.querySelectorAll('.saved-event-map').forEach(function (mapNode) {
            const lat = Number(mapNode.dataset.lat);
            const lng = Number(mapNode.dataset.lng);
            const label = mapNode.dataset.label || 'Marked location';

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const savedMap = L.map(mapNode, {
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                zoomControl: false
            }).setView([lat, lng], 17);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(savedMap);

            L.marker([lat, lng], {
                icon: locationIcon
            }).addTo(savedMap).bindPopup(label);
        });

        const setSelectedLocation = function (lat, lng, label, moveMap, sourceQuery) {
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            selectedLat = lat;
            selectedLng = lng;
            selectedLabel = label || fallbackPlaceName(lat, lng);
            selectedSearchQuery = sourceQuery || selectedLabel;
            mapPinLabel.textContent = selectedLabel;

            if (pickerMarker) {
                pickerMarker.setLatLng([lat, lng]);
            }

            if (pickerMap && moveMap) {
                pickerMap.setView([lat, lng], 17);
            }

            const encodedQuery = encodeURIComponent(lat + ',' + lng);
            openMapSearch.href = 'https://www.google.com/maps/search/?api=1&query=' + encodedQuery;
        };

        const resolvePlaceName = async function (lat, lng) {
            try {
                const response = await fetch('https://nominatim.openstreetmap.org/reverse?format=json&zoom=18&addressdetails=1&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng));
                if (!response.ok) {
                    throw new Error('Place lookup failed.');
                }

                const result = await response.json();
                return result.display_name || fallbackPlaceName(lat, lng);
            } catch (error) {
                return fallbackPlaceName(lat, lng);
            }
        };

        const setSelectedLocationFromPoint = async function (lat, lng, moveMap) {
            setSelectedLocation(lat, lng, 'Finding place name...', moveMap);
            searchInput.value = 'Finding place name...';
            const placeName = await resolvePlaceName(lat, lng);
            setSelectedLocation(lat, lng, placeName, false);
            searchInput.value = placeName;
        };

        const initPickerMap = function () {
            if (pickerMap) {
                setTimeout(function () {
                    pickerMap.invalidateSize();
                }, 80);
                return;
            }

            pickerMap = L.map(mapElement).setView(defaultPosition, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(pickerMap);

            pickerMarker = L.marker(defaultPosition, {
                draggable: true,
                icon: locationIcon
            }).addTo(pickerMap);

            pickerMap.on('click', function (event) {
                setSelectedLocationFromPoint(event.latlng.lat, event.latlng.lng, false);
            });

            pickerMarker.on('dragend', function () {
                const position = pickerMarker.getLatLng();
                setSelectedLocationFromPoint(position.lat, position.lng, false);
            });

            setSelectedLocation(defaultPosition[0], defaultPosition[1], 'Cosmopolitan College', true);
            setTimeout(function () {
                pickerMap.invalidateSize();
            }, 80);
        };

        const updateMapPreview = async function (showErrors) {
            const shouldShowErrors = showErrors !== false;
            const query = searchInput.value.trim() || 'Cosmopolitan College';
            const encodedQuery = encodeURIComponent(query);
            openMapSearch.href = 'https://www.google.com/maps/search/?api=1&query=' + encodedQuery;

            try {
                const response = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodedQuery);
                if (!response.ok) {
                    throw new Error('Map search failed.');
                }

                const results = await response.json();

                if (!results.length) {
                    if (shouldShowErrors) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Location not found',
                            text: 'Try a more specific location name.',
                            confirmButtonColor: '#202959'
                        });
                    }
                    return false;
                }

                const result = results[0];
                const placeName = result.display_name || query;
                setSelectedLocation(Number(result.lat), Number(result.lon), placeName, true, query);
                searchInput.value = placeName;
                return true;
            } catch (error) {
                if (shouldShowErrors) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Map search failed',
                        text: 'Please check your internet connection and try again.',
                        confirmButtonColor: '#202959'
                    });
                }
                return false;
            }
        };

        const commitSelectedLocation = function () {
            if (selectedLat === null || selectedLng === null) {
                return false;
            }

            const placeName = selectedLabel && selectedLabel !== 'Finding place name...'
                ? selectedLabel
                : fallbackPlaceName(selectedLat, selectedLng);

            locationInput.value = placeName;
            latitudeInput.value = selectedLat.toFixed(7);
            longitudeInput.value = selectedLng.toFixed(7);
            mapPinLabel.textContent = placeName;

            return true;
        };

        const openModal = function () {
            if (modal.classList.contains('active')) {
                return;
            }

            searchInput.value = locationInput.value || '';
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            initPickerMap();

            if (latitudeInput.value && longitudeInput.value) {
                setSelectedLocation(Number(latitudeInput.value), Number(longitudeInput.value), locationInput.value || 'Marked location', true);
            } else if (searchInput.value.trim() !== '') {
                updateMapPreview(false);
            }

            setTimeout(function () {
                searchInput.focus();
            }, 80);
        };

        locationInput.addEventListener('click', openModal);
        locationInput.addEventListener('focus', openModal);
        previewButton.addEventListener('click', function () {
            updateMapPreview(true);
        });
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                updateMapPreview(true);
            }
        });

        closeButton.addEventListener('click', function () {
            closeModal();
        });

        useButton.addEventListener('click', async function () {
            const typedQuery = searchInput.value.trim();

            if (typedQuery !== '' && !sameText(typedQuery, selectedLabel) && !sameText(typedQuery, selectedSearchQuery)) {
                const foundLocation = await updateMapPreview(true);

                if (!foundLocation) {
                    return;
                }
            }

            if (selectedLat !== null && selectedLng !== null) {
                if (selectedLabel === 'Finding place name...' || selectedLabel.startsWith('Marked location (')) {
                    selectedLabel = await resolvePlaceName(selectedLat, selectedLng);
                }

                commitSelectedLocation();
            }

            closeModal();
        });
    })();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
