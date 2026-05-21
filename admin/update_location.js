navigator.geolocation.watchPosition(
    position => {
        const { latitude, longitude } = position.coords;

        // Send updated location to the server
        fetch('update_employee_location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                location: `${latitude},${longitude}`
            })
        }).catch(console.error);
    },
    error => console.error("Location error:", error),
    { enableHighAccuracy: true }
);
