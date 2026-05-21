<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map with Search and Click</title>
    <style>
        #map {
            height: 400px;
            width: 100%;
        }
        input {
            margin-bottom: 10px;
            width: 100%;
            padding: 8px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <h2>Map Search and Click Example</h2>
    <input id="searchBox" type="text" placeholder="Search location...">
    <div id="map"></div>
    <input type="text" id="latitude" placeholder="Latitude" readonly>
    <input type="text" id="longitude" placeholder="Longitude" readonly>

    <script>
        function initMap() {
            const defaultLocation = { lat: 20.5937, lng: 78.9629 }; // India Center

            const map = new google.maps.Map(document.getElementById("map"), {
                center: defaultLocation,
                zoom: 5
            });

            const marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true
            });

            // Search box
            const searchBox = new google.maps.places.SearchBox(document.getElementById("searchBox"));

            // Bias the search results towards map viewport
            map.addListener("bounds_changed", () => {
                searchBox.setBounds(map.getBounds());
            });

            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();
                if (places.length === 0) return;

                const place = places[0];
                if (!place.geometry || !place.geometry.location) return;

                map.setCenter(place.geometry.location);
                map.setZoom(15);

                marker.setPosition(place.geometry.location);

                document.getElementById("latitude").value = place.geometry.location.lat();
                document.getElementById("longitude").value = place.geometry.location.lng();
            });

            // Click on map to get lat/lng
            map.addListener("click", (event) => {
                const clickedLocation = event.latLng;
                marker.setPosition(clickedLocation);

                document.getElementById("latitude").value = clickedLocation.lat();
                document.getElementById("longitude").value = clickedLocation.lng();
            });

            // Update lat/lng on marker drag
            marker.addListener("dragend", (event) => {
                document.getElementById("latitude").value = event.latLng.lat();
                document.getElementById("longitude").value = event.latLng.lng();
            });
        }
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC67iv2GBtMHo16Lyelrzlx-EqALXwv4X0&libraries=places&callback=initMap" async defer></script>
</body>
</html>
