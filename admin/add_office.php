<?php
include("header.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_office'])) {
    require("db_connection.php"); // Ensure DB connection is included
    $office_name = trim($_POST['office_name']);
    $state_name = trim($_POST['state_name']);
    $radius = trim($_POST['radius']);

    $mobile_number1 = trim($_POST['mobile_number1']);
    $mobile_number2 = trim($_POST['mobile_number2']);
    $expiry_date = trim($_POST['expiry_date']);

    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);

    $stmt = $conn->prepare("INSERT INTO offices (office_name, state_name, radius, mobile_number1, mobile_number2, expiry_date, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsssdd", $office_name, $state_name, $radius, $mobile_number1, $mobile_number2, $expiry_date,  $latitude, $longitude);

    if ($stmt->execute()) {
        echo "<script>alert('Office added successfully!'); window.location.href='manage_site';</script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to add office.</div>";
    }
}
?>

<style>
.add-office-page {
    padding-bottom: 1.5rem;
}

.add-office-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
    padding: 1rem 1.1rem;
    height: 100%;
}

.add-office-form-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
    overflow: hidden;
}

.add-office-form-body {
    padding: 1.2rem;
}

.add-office-topbar {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 24px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
    padding: 1.35rem 1.4rem;
    margin-bottom: 1.25rem;
}

.add-office-topbar-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
    align-items: start;
}

.add-office-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.add-office-title {
    margin: 0;
    color: #111827;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.add-office-copy {
    margin: 0.28rem 0 0;
    color: #6b7280;
    font-size: 0.88rem;
}

.add-office-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.add-office-toolbar .btn,
.add-office-toolbar a,
.add-office-actions .btn,
.add-office-actions a {
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

.add-office-btn-dark {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.add-office-btn-dark:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.add-office-btn-secondary {
    background: #16324f !important;
    color: #fff !important;
    border: 1px solid #16324f !important;
    box-shadow: none !important;
}

.add-office-btn-secondary:hover {
    background: #10263c !important;
    border-color: #10263c !important;
    color: #fff !important;
}

.add-office-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.1rem;
}

.add-office-field {
    min-width: 0;
}

.add-office-field-full {
    grid-column: 1 / -1;
}

.add-office-field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.add-office-field .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    background: #fff;
    color: #111827;
}

.add-office-field .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

#searchLocationAdd {
    width: 100%;
}

#addMap {
    height: 460px;
    border-radius: 18px;
    border: 1px solid #d8dee7;
    overflow: hidden;
    background: #f8fafc;
}

.add-office-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 1.25rem;
}

@media (max-width: 991.98px) {
    .add-office-topbar-grid,
    .add-office-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .add-office-topbar {
        padding: 1rem 1.05rem;
    }

    .add-office-toolbar,
    .add-office-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .add-office-toolbar .btn,
    .add-office-toolbar a,
    .add-office-actions .btn,
    .add-office-actions a {
        width: 100%;
    }
}
</style>

<div class="container-fluid py-4 add-office-page">
    <div class="row">
        <div class="col-12">
            <div class="add-office-topbar">
                <div class="add-office-topbar-grid">
                    <div>
                        <h3 class="add-office-title">Add New Office</h3>
                        <p class="add-office-copy">Create a new site record with contact details, radius, and map location.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="add-office-form-card">
                <div class="add-office-form-body">
                    <form method="POST">
                        <div class="add-office-form-grid">
                            <div class="add-office-field">
                                <label>Site Name</label>
                                <input type="text" class="form-control" name="office_name" required>
                            </div>

                            <div class="add-office-field">
                                <label>State Name</label>
                                <input type="text" class="form-control" name="state_name" required>
                            </div>

                            <div class="add-office-field">
                                <label>Radius (meters)</label>
                                <input type="number" class="form-control" name="radius" required>
                            </div>

                            <div class="add-office-field">
                                <label>Mobile Number</label>
                                <input type="text" class="form-control" name="mobile_number1">
                            </div>

                            <div class="add-office-field">
                                <label>Alternate Number</label>
                                <input type="text" class="form-control" name="mobile_number2">
                            </div>

                            <div class="add-office-field">
                                <label>Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date">
                            </div>

                            <div class="add-office-field add-office-field-full">
                                <label>Search Location</label>
                                <input id="searchLocationAdd" type="text" class="form-control" placeholder="Search location">
                            </div>

                            <div class="add-office-field add-office-field-full">
                                <label>Select Location</label>
                                <div id="addMap"></div>
                            </div>

                            <div class="add-office-field">
                                <label>Latitude</label>
                                <input type="text" class="form-control" name="latitude" id="latitude" required>
                            </div>

                            <div class="add-office-field">
                                <label>Longitude</label>
                                <input type="text" class="form-control" name="longitude" id="longitude" required>
                            </div>
                        </div>

                        <div class="add-office-actions">
                            <button type="submit" name="add_office" class="btn add-office-btn-dark mt-0">Add Office</button>
                            <a href="manage_site" class="btn add-office-btn-secondary mt-0">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function initAddMap() {
    let map = new google.maps.Map(document.getElementById("addMap"), {
        center: { lat: 20.5937, lng: 78.9629 },
        zoom: 5
    });

    let marker = new google.maps.Marker({
        map: map,
        draggable: true,
        position: { lat: 20.5937, lng: 78.9629 }
    });

    let searchBox = new google.maps.places.SearchBox(document.getElementById("searchLocationAdd"));
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(document.getElementById("searchLocationAdd"));

    google.maps.event.addListener(searchBox, 'places_changed', function() {
        let places = searchBox.getPlaces();
        if (places.length === 0) return;
        let place = places[0];
        map.setCenter(place.geometry.location);
        map.setZoom(15);
        marker.setPosition(place.geometry.location);
        document.getElementById("latitude").value = place.geometry.location.lat();
        document.getElementById("longitude").value = place.geometry.location.lng();
    });

    google.maps.event.addListener(marker, 'dragend', function(event) {
        document.getElementById("latitude").value = event.latLng.lat();
        document.getElementById("longitude").value = event.latLng.lng();
    });

    google.maps.event.addListener(map, 'click', function(event) {
        marker.setPosition(event.latLng);
        document.getElementById("latitude").value = event.latLng.lat();
        document.getElementById("longitude").value = event.latLng.lng();
    });
}
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCH2j-8_qFXr-AwOdr9sgaEa0jQQHp0YZU&libraries=places&callback=initAddMap" async defer></script>
<?php include("footer.php"); ?>
