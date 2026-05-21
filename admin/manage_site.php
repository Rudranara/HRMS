<?php
include("header.php");
// Fetch offices
$stmt = $conn->prepare("SELECT * FROM offices");
$stmt->execute();
$result = $stmt->get_result();
// Handle Add Office
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_office'])) {
    $office_name = trim($_POST['office_name']);
    $state_name = trim($_POST['state_name']);
    $radius = trim($_POST['radius']);
    $mobile_number1 = trim($_POST['mobile_number1']);
    $mobile_number2 = trim($_POST['mobile_number2']);
    $expiry_date = trim($_POST['expiry_date']);
    
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);

    $stmt = $conn->prepare("INSERT INTO offices (office_name, state_name, radius, mobile_number1, mobile_number2, expiry_date, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddd", $office_name, $state_name, $radius, $mobile_number1, $mobile_number2, $expiry_date, $latitude, $longitude);

    if ($stmt->execute()) {
        echo "<script>alert('Office added successfully!'); window.location.href='manage_site';</script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to add office.</div>";
    }
}
// Handle Edit Office
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_office_id'])) {
    $edit_office_id = $_POST['edit_office_id'];
    $edit_office_name = trim($_POST['edit_office_name']);
    $edit_state_name = trim($_POST['edit_state_name']);
    $edit_radius = trim($_POST['edit_radius']);
    $edit_mobile_number1 = trim($_POST['edit_mobile_number1']);
    $edit_mobile_number2 = trim($_POST['edit_mobile_number2']);
    $edit_expiry_date = trim($_POST['edit_expiry_date']);
    $edit_latitude = trim($_POST['edit_latitude']);
    $edit_longitude = trim($_POST['edit_longitude']);
    $stmt = $conn->prepare("UPDATE offices SET office_name = ?, state_name = ?, radius = ?, mobile_number1 = ?, mobile_number2 = ?, expiry_date = ?, latitude = ?, longitude = ? WHERE id = ?");
    $stmt->bind_param("ssdsssddi", $edit_office_name, $edit_state_name, $edit_radius, $edit_mobile_number1, $edit_mobile_number2, $edit_expiry_date,  $edit_latitude, $edit_longitude, $edit_office_id);
    if ($stmt->execute()) {
        echo "<script>alert('Office updated successfully!'); window.location.href='manage_site';</script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to update office.</div>";
    }
}
// Handle Delete Office
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_office_id'])) {
    $delete_office_id = $_POST['delete_office_id'];
    $stmt = $conn->prepare("DELETE FROM offices WHERE id = ?");
    $stmt->bind_param("i", $delete_office_id);

    if ($stmt->execute()) {
        echo "<script>alert('Office deleted successfully!'); window.location.href='manage_site';</script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to delete office.</div>";
    }
}
?>
<style>
.manage-site-page {
    padding-bottom: 1.5rem;
}

.manage-site-topbar,
.manage-site-table-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.manage-site-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.manage-site-topbar-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
}

.manage-site-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-site-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.manage-site-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.manage-site-add-btn,
.manage-site-action-btn,
.manage-site-modal .btn,
.manage-site-modal .btn-close {
    border-radius: 14px;
}

.manage-site-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.manage-site-toolbar .btn,
.manage-site-toolbar a {
    min-height: 38px;
    padding: 0.52rem 0.95rem;
    border-radius: 14px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.manage-site-add-btn {
    border: none;
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
    text-decoration: none;
}

.manage-site-add-btn:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.manage-site-table-card {
    overflow: hidden;
}

.manage-site-table-card .card-body {
    padding: 0 0 1rem;
}

.manage-site-table-wrap {
    padding: 0 1.2rem 1.15rem;
    overflow-x: auto;
}

.manage-site-table {
    margin-bottom: 0;
}

.manage-site-table thead th {
    border-bottom: 1px solid #e8edf3;
    background: #f8fafc;
    color: #6b7280;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 1rem 0.95rem;
    white-space: nowrap;
}

.manage-site-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    font-size: 0.92rem;
    vertical-align: middle;
}

.manage-site-table tbody tr:last-child td {
    border-bottom: none;
}

.manage-site-table tbody tr:hover {
    background: #fbfcfe;
}

.manage-site-name {
    margin: 0;
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 700;
}

.manage-site-state {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 96px;
    padding: 0.42rem 0.7rem;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #dbe3ed;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.manage-site-map-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 0.52rem 0.95rem;
    border-radius: 14px;
    background: #16324f;
    color: #fff;
    border: 1px solid #16324f;
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
}

.manage-site-map-link:hover {
    background: #10263c;
    border-color: #10263c;
    color: #fff;
}

.manage-site-actions {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 0.5rem;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.manage-site-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    min-height: 38px;
    padding: 0.45rem 0.85rem;
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    text-decoration: none;
    margin: 0 !important;
}

.manage-site-action-btn.btn-warning {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.manage-site-action-btn.btn-danger {
    background: #fbe6e5 !important;
    color: #c24141 !important;
    border: 1px solid #f4c9c7 !important;
    box-shadow: none !important;
}

.manage-site-action-btn.btn-danger:hover {
    background: #f7d8d6 !important;
    color: #a93232 !important;
}

.manage-site-empty {
    margin: 1rem 1.2rem 0;
    padding: 1rem 1.1rem;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    background: #f8fafc;
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 700;
}

.manage-site-modal .modal-dialog {
    max-width: 700px;
}

.manage-site-modal .modal-content {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 18px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.manage-site-modal .modal-header,
.manage-site-modal .modal-footer {
    border-color: #eef2f7;
    padding: 1rem 1.25rem;
}

.manage-site-modal .modal-body {
    padding: 1.25rem;
}

.manage-site-modal .modal-title,
.manage-site-modal h5 {
    margin: 0;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
}

.manage-site-modal label {
    display: block;
    margin: 0.85rem 0 0.4rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.manage-site-modal .form-control {
    min-height: 44px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.65rem 0.85rem;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
    font-size: 0.92rem;
}

.manage-site-modal .form-control:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.manage-site-modal .btn-primary {
    background: linear-gradient(135deg, #161616, #2d2d2d);
    color: #fff;
    border: none;
}

.manage-site-modal .btn-secondary {
    border: none;
    background: #f3f4f6;
    color: #334155;
}

#searchLocationAdd,
#searchLocationEdit {
    width: 100% !important;
    margin: 0.75rem 0 0 !important;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.7rem 0.85rem;
    background: #f8fafc;
    color: #0f172a;
    font-size: 0.92rem;
}

#addMap,
#editMap {
    margin-top: 0.2rem;
    border: 1px solid #d7deea;
    border-radius: 18px;
    overflow: hidden;
}

@media (max-width: 767.98px) {
    .manage-site-topbar-grid {
        grid-template-columns: 1fr;
    }

    .manage-site-table-wrap,
    .manage-site-modal .modal-header,
    .manage-site-modal .modal-body,
    .manage-site-modal .modal-footer {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .manage-site-toolbar,
    .manage-site-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .manage-site-toolbar .btn,
    .manage-site-toolbar a,
    .manage-site-actions > * {
        width: 100%;
    }
}
</style>

<div class="container-fluid manage-site-page">
    <div class="row">
        <div class="col-12">
            <div class="manage-site-topbar">
                <div class="manage-site-topbar-grid">
                    <div>
                        <span class="manage-site-section-label">Site Directory</span>
                        <h6 class="manage-site-title">Manage Site</h6>
                        <p class="manage-site-copy">View, edit, and manage all site records from one place.</p>
                    </div>
                    <div class="manage-site-toolbar">
                    <a href="add_office" class="btn manage-site-add-btn mb-0">Add New Site</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card manage-site-table-card mb-4">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive manage-site-table-wrap">
                        <?php if ($result->num_rows > 0): ?>
                            <table class="table manage-site-table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th>Site Name</th>
                                        <th>State Name</th>
                                        <th>Location</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($office = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <p class="manage-site-name"><?= htmlspecialchars($office['office_name']) ?></p>
                                            </td>
                                            <td>
                                                <span class="manage-site-state"><?= htmlspecialchars($office['state_name']) ?></span>
                                            </td>
                                            
                                            <td>
                                                <a class="manage-site-map-link" href="https://www.google.com/maps?q=<?= $office['latitude'] ?>,<?= $office['longitude'] ?>" target="_blank">
                                                    View on Map
                                                </a>
                                            </td>
                                            <td>
                                                <div class="manage-site-actions">
                                                    <a href="edit_office?id=<?= $office['id'] ?>" class="btn btn-warning btn-sm manage-site-action-btn">Edit</a>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="delete_office_id" value="<?= $office['id'] ?>">
                                                        <button class="btn btn-danger btn-sm manage-site-action-btn" onclick="return confirm('Are you sure you want to delete this office?');">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="manage-site-empty">No Site found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Add Office Modal -->
<div class="modal fade manage-site-modal" id="addOfficeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Site</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Site Name</label>
                    <input type="text" class="form-control" name="office_name" required>
                    <label>State Name</label>
                    <input type="text" class="form-control" name="state_name" required>
                    <label>Radius</label>
                    <input type="number" class="form-control" name="radius" required>


                    <label>Mobile Number</label>
                    <input type="number" class="form-control" name="mobile_number1" >
                    <label>Alternate Number</label>
                    <input type="number" class="form-control" name="mobile_number2" >
                    <label>Expiry Date</label>
                    <input type="date" class="form-control" name="expiry_date" >


                    <label>Select Location</label>
                    <div id="addMap" style="height: 300px;"></div>
                    <!-- Search Box Inputs -->
<input id="searchLocationAdd" type="text" placeholder="Search location" style="width: 300px; margin: 10px;">

                    <label>Latitude</label>
                    <input type="text" class="form-control" name="latitude" id="latitude">
                    <label>Longitude</label>
                    <input type="text" class="form-control" name="longitude" id="longitude">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_office" class="btn btn-primary">Add Site</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Office Modal -->
<!-- Edit Office Modal with Map -->
<div class="modal fade manage-site-modal" id="editOfficeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5>Edit Site</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_office_id" id="edit_office_id">
                    <label>Site Name</label>
                    <input type="text" class="form-control" name="edit_office_name" id="edit_office_name" required>
                    <label>State Name</label>
                    <input type="text" class="form-control" name="edit_state_name" id="edit_state_name" required>
                    <label>Radius</label>
                    <input type="number" class="form-control" name="edit_radius" id="edit_radius" required>

                    <label>Mobile Number</label>
                    <input type="number" class="form-control" name="edit_mobile_number1" id="edit_mobile_number1" required>
                    <label>Alternate Number</label>
                    <input type="number" class="form-control" name="edit_mobile_number2" id="edit_mobile_number2" required>
                    <label>Expiry Date</label>
                    <input type="date" class="form-control" name="edit_expiry_date" id="edit_expiry_date" required>


                    <label>Select Location</label>
                    <div id="editMap" style="height: 300px;"></div>
                    <input id="searchLocationEdit" type="text" placeholder="Search location" style="width: 300px; margin: 10px;">
                    <label>Latitude</label>
                    <input type="text" class="form-control" name="edit_latitude" id="edit_latitude" required>
                    <label>Langitude</label>
                    <input type="text" class="form-control" name="edit_longitude" id="edit_longitude" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function initMap() {
    // Initialize Add Location Map
    let map = new google.maps.Map(document.getElementById("addMap"), {
        center: { lat: 20.5937, lng: 78.9629 }, // Default India Center
        zoom: 5
    });

    let marker = new google.maps.Marker({
        map: map,
        draggable: true,
        position: { lat: 20.5937, lng: 78.9629 }
    });

    // Create Search Box and link to UI element
    let input = document.getElementById("searchLocationAdd");
    let searchBox = new google.maps.places.SearchBox(input);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    // Listen for search box selection
    searchBox.addListener("places_changed", function () {
        let places = searchBox.getPlaces();
        if (places.length === 0) return;

        let place = places[0];
        if (!place.geometry) return;

        map.setCenter(place.geometry.location);
        map.setZoom(15);
        marker.setPosition(place.geometry.location);

        document.getElementById("latitude").value = place.geometry.location.lat();
        document.getElementById("longitude").value = place.geometry.location.lng();
    });

    // Update lat/lng on marker drag
    google.maps.event.addListener(marker, 'dragend', function (event) {
        document.getElementById("latitude").value = event.latLng.lat();
        document.getElementById("longitude").value = event.latLng.lng();
    });

    // Update marker position on map click
    google.maps.event.addListener(map, 'click', function (event) {
        marker.setPosition(event.latLng);
        document.getElementById("latitude").value = event.latLng.lat();
        document.getElementById("longitude").value = event.latLng.lng();
    });
}

// Initialize Edit Location Map inside Modal
document.addEventListener('DOMContentLoaded', function () {
    const editOfficeModal = document.getElementById('editOfficeModal');
    let editMap, editMarker, editSearchBox;

    editOfficeModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const lat = parseFloat(button.getAttribute('data-lat')) || 20.5937;
        const lng = parseFloat(button.getAttribute('data-lng')) || 78.9629;

        document.getElementById('edit_office_id').value = button.getAttribute('data-id');
        document.getElementById('edit_office_name').value = button.getAttribute('data-name');
        document.getElementById('edit_state_name').value = button.getAttribute('data-state');
        document.getElementById('edit_radius').value = button.getAttribute('data-radius');

        document.getElementById('edit_mobile_number1').value = button.getAttribute('data-mobile_number1');
        document.getElementById('edit_mobile_number2').value = button.getAttribute('data-mobile_number2');
        document.getElementById('edit_expiry_date').value = button.getAttribute('data-expiry_date');


        document.getElementById('edit_latitude').value = lat;
        document.getElementById('edit_longitude').value = lng;

        // Initialize Edit Location Map
        editMap = new google.maps.Map(document.getElementById("editMap"), {
            center: { lat: lat, lng: lng },
            zoom: 10
        });

        editMarker = new google.maps.Marker({
            map: editMap,
            draggable: true,
            position: { lat: lat, lng: lng }
        });

        // Initialize Search Box for Edit Modal
        let input = document.getElementById("searchLocationEdit");
        let editSearchBox = new google.maps.places.SearchBox(input);
        editMap.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

        // Listen for search box selection
        editSearchBox.addListener("places_changed", function () {
            let places = editSearchBox.getPlaces();
            if (places.length === 0) return;

            let place = places[0];
            if (!place.geometry) return;

            editMap.setCenter(place.geometry.location);
            editMap.setZoom(15);
            editMarker.setPosition(place.geometry.location);

            document.getElementById("edit_latitude").value = place.geometry.location.lat();
            document.getElementById("edit_longitude").value = place.geometry.location.lng();
        });

        // Update lat/lng on marker drag
        google.maps.event.addListener(editMarker, 'dragend', function (event) {
            document.getElementById("edit_latitude").value = event.latLng.lat();
            document.getElementById("edit_longitude").value = event.latLng.lng();
        });

        // Update marker position on map click
        google.maps.event.addListener(editMap, 'click', function (event) {
            editMarker.setPosition(event.latLng);
            document.getElementById("edit_latitude").value = event.latLng.lat();
            document.getElementById("edit_longitude").value = event.latLng.lng();
        });
    });
});
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCH2j-8_qFXr-AwOdr9sgaEa0jQQHp0YZU&callback=initMap" async defer></script>
<?php include("footer.php"); ?>
