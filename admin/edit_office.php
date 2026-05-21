<?php
include("header.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid request!'); window.location.href='manage_site';</script>";
    exit;
}
$office_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM offices WHERE id = ?");
$stmt->bind_param("i", $office_id);
$stmt->execute();
$result = $stmt->get_result();
$office = $result->fetch_assoc();

if (!$office) {
    echo "<script>alert('Office not found!'); window.location.href='manage_site';</script>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_office'])) {
    $edit_office_name = trim($_POST['edit_office_name']);
    $edit_state_name = trim($_POST['edit_state_name']);
    $edit_radius = trim($_POST['edit_radius']);

    $edit_mobile_number1 = trim($_POST['edit_mobile_number1']);
    $edit_mobile_number2 = trim($_POST['edit_mobile_number2']);
    $edit_expiry_date = trim($_POST['edit_expiry_date']);


    $edit_latitude = trim($_POST['edit_latitude']);
    $edit_longitude = trim($_POST['edit_longitude']);

    $stmt = $conn->prepare("UPDATE offices SET office_name = ?, state_name = ?, radius = ?,  mobile_number1 = ?,  mobile_number2 = ?,  expiry_date = ?, latitude = ?, longitude = ? WHERE id = ?");
    $stmt->bind_param("ssdsssddi", $edit_office_name, $edit_state_name, $edit_radius, $edit_mobile_number1, $edit_mobile_number2, $edit_expiry_date, $edit_latitude, $edit_longitude, $office_id);

    if ($stmt->execute()) {
        echo "<script>alert('Office updated successfully!'); window.location.href='manage_site';</script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to update office.</div>";
    }
}
?>
<style>
.edit-office-page {
    padding-bottom: 1.5rem;
}

.edit-office-topbar {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 24px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
    padding: 1.35rem 1.4rem;
    margin-bottom: 1.25rem;
}

.edit-office-topbar-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
    align-items: start;
}

.edit-office-form-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
    overflow: hidden;
}

.edit-office-form-body {
    padding: 1.2rem;
}

.edit-office-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.edit-office-title {
    margin: 0;
    color: #111827;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.edit-office-copy {
    margin: 0.28rem 0 0;
    color: #6b7280;
    font-size: 0.88rem;
}

.edit-office-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.1rem;
}

.edit-office-field {
    min-width: 0;
}

.edit-office-field-full {
    grid-column: 1 / -1;
}

.edit-office-field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.edit-office-field .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    background: #fff;
    color: #111827;
}

.edit-office-field .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.edit-office-field .form-control[readonly] {
    background: #f8fafc;
    color: #64748b;
}

#searchLocationEdit {
    width: 100%;
}

#editMap {
    height: 460px;
    border-radius: 18px;
    border: 1px solid #d8dee7;
    overflow: hidden;
    background: #f8fafc;
}

.edit-office-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 1.25rem;
}

.edit-office-actions .btn,
.edit-office-actions a {
    min-height: 38px;
    min-width: 118px;
    padding: 0.52rem 1.15rem;
    border-radius: 14px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.edit-office-btn-dark {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.edit-office-btn-dark:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.edit-office-btn-secondary {
    background: #16324f !important;
    color: #fff !important;
    border: 1px solid #16324f !important;
    box-shadow: none !important;
}

.edit-office-btn-secondary:hover {
    background: #10263c !important;
    border-color: #10263c !important;
    color: #fff !important;
}

@media (max-width: 991.98px) {
    .edit-office-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .edit-office-topbar {
        padding: 1rem 1.05rem;
    }

    .edit-office-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .edit-office-actions .btn,
    .edit-office-actions a {
        width: 100%;
    }
}
</style>

<div class="container-fluid py-4 edit-office-page">
    <div class="row">
        <div class="col-12">
            <div class="edit-office-topbar">
                <div class="edit-office-topbar-grid">
                    <div>
                        <span class="edit-office-section-label">Site Setup</span>
                        <h3 class="edit-office-title">Edit Office</h3>
                        <p class="edit-office-copy">Update office details, contact information, radius, and map location.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="edit-office-form-card">
                <div class="edit-office-form-body">
                    <form method="POST">
                        <div class="edit-office-form-grid">
                            <div class="edit-office-field">
                                <label>Site Name</label>
                                <input type="text" class="form-control" name="edit_office_name" value="<?= htmlspecialchars($office['office_name']) ?>" readonly required>
                            </div>

                            <div class="edit-office-field">
                                <label>State Name</label>
                                <input type="text" class="form-control" name="edit_state_name" value="<?= htmlspecialchars($office['state_name']) ?>" readonly required>
                            </div>

                            <div class="edit-office-field">
                                <label>Radius (meters)</label>
                                <input type="number" class="form-control" name="edit_radius" value="<?= htmlspecialchars($office['radius']) ?>" required>
                            </div>

                            <div class="edit-office-field">
                                <label>Mobile Number</label>
                                <input type="text" class="form-control" name="edit_mobile_number1" value="<?= htmlspecialchars($office['mobile_number1']) ?>">
                            </div>

                            <div class="edit-office-field">
                                <label>Alternate Number</label>
                                <input type="text" class="form-control" name="edit_mobile_number2" value="<?= htmlspecialchars($office['mobile_number2']) ?>">
                            </div>

                            <div class="edit-office-field">
                                <label>Expiry Date</label>
                                <input type="date" class="form-control" name="edit_expiry_date" value="<?= htmlspecialchars($office['expiry_date']) ?>">
                            </div>

                            <div class="edit-office-field edit-office-field-full">
                                <label>Search Location</label>
                                <input id="searchLocationEdit" type="text" class="form-control" placeholder="Search location">
                            </div>

                            <div class="edit-office-field edit-office-field-full">
                                <label>Select Location</label>
                                <div id="editMap"></div>
                            </div>

                            <div class="edit-office-field">
                                <label>Latitude</label>
                                <input type="text" class="form-control" name="edit_latitude" id="edit_latitude" value="<?= $office['latitude'] ?>" required>
                            </div>

                            <div class="edit-office-field">
                                <label>Longitude</label>
                                <input type="text" class="form-control" name="edit_longitude" id="edit_longitude" value="<?= $office['longitude'] ?>" required>
                            </div>
                        </div>

                        <div class="edit-office-actions">
                            <button type="submit" name="edit_office" class="btn edit-office-btn-dark mt-0">Save Changes</button>
                            <a href="manage_site" class="btn edit-office-btn-secondary mt-0">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Google Maps API & JavaScript -->
<script>
function initEditMap() {
    var defaultLat = <?= $office['latitude'] ?>;
    var defaultLng = <?= $office['longitude'] ?>;
    var map = new google.maps.Map(document.getElementById("editMap"), {
        center: { lat: defaultLat, lng: defaultLng },
        zoom: 15
    });

    var marker = new google.maps.Marker({
        position: { lat: defaultLat, lng: defaultLng },
        map: map,
        draggable: true
    });

    // Search box
    var searchBox = new google.maps.places.SearchBox(document.getElementById("searchLocationEdit"));
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(document.getElementById("searchLocationEdit"));

    // Update map and marker when searching
    searchBox.addListener("places_changed", function() {
        var places = searchBox.getPlaces();
        if (places.length == 0) return;

        var place = places[0];
        if (!place.geometry) return;

        map.setCenter(place.geometry.location);
        marker.setPosition(place.geometry.location);

        document.getElementById("edit_latitude").value = place.geometry.location.lat();
        document.getElementById("edit_longitude").value = place.geometry.location.lng();
    });

    // Update latitude and longitude fields when dragging marker
    marker.addListener("dragend", function(event) {
        document.getElementById("edit_latitude").value = event.latLng.lat();
        document.getElementById("edit_longitude").value = event.latLng.lng();
    });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCH2j-8_qFXr-AwOdr9sgaEa0jQQHp0YZU&libraries=places&callback=initEditMap" async defer></script>

<?php include("footer.php"); ?>
