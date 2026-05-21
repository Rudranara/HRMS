<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
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

<!-- Google Maps API with Places Library -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCH2j-8_qFXr-AwOdr9sgaEa0jQQHp0YZU&libraries=places&callback=initMap" async defer></script>

<!-- Search Box Inputs -->
<input id="searchLocationAdd" type="text" placeholder="Search location" style="width: 300px; margin: 10px;">
<input id="searchLocationEdit" type="text" placeholder="Search location" style="width: 300px; margin: 10px;">

</body>
</html>