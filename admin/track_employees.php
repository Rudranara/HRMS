<?php
include("header.php");

// Fetch all employees' punch-in locations who have not punched out
// $stmt = $conn->prepare("
//     SELECT 
//         e.id,
//         e.name,

//         /*  Priority: Visit location first */
//         COALESCE(
//             (
//                 SELECT CONCAT(v.lat, ',', v.lng)
//                 FROM visits v
//                 WHERE v.user_id = e.id
//                   AND DATE(v.created_at) = CURDATE()
//                 ORDER BY v.created_at DESC
//                 LIMIT 1
//             ),
//             a.current_location
//         ) AS current_location

//     FROM employees e

//     JOIN attendance a 
//         ON e.id = a.employee_id

//     WHERE a.punch_out_time IS NULL

//     ORDER BY a.punch_in_time DESC
// ");

// $stmt->execute();
// $result = $stmt->get_result();

// $employees = [];
// while ($row = $result->fetch_assoc()) {
//     $employees[] = $row;
// }
?>
<style>
    .track-employees-page {
        padding-top: 1.5rem;
        padding-bottom: 2.35rem;
    }

    .track-employees-header,
    .track-employees-map-card {
        border: 1px solid #e5eaf1;
        border-radius: 28px;
        background: #ffffff;
        box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
    }

    .track-employees-header {
        padding: 1.3rem 1.45rem;
        margin-bottom: 1rem;
    }

    .track-employees-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .track-employees-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.28rem;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .track-employees-subtitle {
        margin: 0.32rem 0 0;
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .track-employees-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0.72rem 1.15rem;
        border-radius: 14px;
        border: 1px solid #111827;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        color: #ffffff;
        box-shadow: 0 18px 32px rgba(15, 23, 42, 0.16);
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .track-employees-map-card {
        overflow: hidden;
    }

    .track-employees-map-card .card-header {
        padding: 1.15rem 1.4rem 0;
        border-bottom: 0;
        background: transparent;
    }

    .track-employees-map-card .card-header h6 {
        margin: 0;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .track-employees-map-card .card-body {
        padding: 1rem 1.35rem 1.35rem;
    }

    #map {
        height: 500px;
        width: 100%;
        border: 1px solid #e5eaf1;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
    }

    @media (max-width: 767.98px) {
        .track-employees-page {
            padding-top: 1.1rem;
        }

        .track-employees-header,
        .track-employees-map-card .card-header,
        .track-employees-map-card .card-body {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        #map {
            height: 420px;
        }
    }
</style>

<div class="container-fluid track-employees-page">
    <div class="row">
        <div class="col-12">
            <div class="track-employees-header">
                <div class="track-employees-header-row">
                    <div>
                        <h6 class="track-employees-title">Track Employee Locations</h6>
                        <p class="track-employees-subtitle">Monitor live employee positions on the map with automatic refresh updates.</p>
                    </div>
                    <a href="add_employee" class="btn track-employees-action mb-0">Add New Employee</a>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card mb-4 track-employees-map-card">
                <div class="card-header pb-0">
                    <h6>Employee Locations</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
<script>
    let map, markers = {};

    const stageLabels = {
        punch_in: 'Punch In',
        journey_start: 'Journey Start',
        visit: 'Visit',
        journey_end: 'Journey End',
        punch_out: 'Punch Out'
    };

    function initMap() {
        // Default center at Odisha
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 8,
            center: { lat: 20.9517, lng: 85.0985 }, // Default center (Odisha)
        });

        // Fetch and display locations initially
        fetchAndUpdateLocations();

        // Update locations every 10 seconds
        setInterval(fetchAndUpdateLocations, 10000);
    }

    function fetchAndUpdateLocations() {
        fetch('get_live_locations')
            .then(response => response.json())
            .then(data => {
                const activeIds = new Set();

                // Update markers on the map
                data.forEach(employee => {
                    const { id, name, current_location, tracking_stage, tracking_time } = employee;

                    if (current_location) {
                        activeIds.add(String(id));
                        const [lat, lng] = current_location.split(',').map(parseFloat);
                        const stageLabel = stageLabels[tracking_stage] || 'Live Location';
                        const timeLabel = tracking_time
                            ? new Date(tracking_time.replace(' ', 'T')).toLocaleString()
                            : 'N/A';
                        const icon = {
                            url: "https://cdn-icons-png.flaticon.com/512/3135/3135715.png",
                            scaledSize: new google.maps.Size(40, 40),
                        };

                        if (markers[id]) {
                            // Update existing marker position
                            markers[id].setPosition({ lat, lng });
                            markers[id].setIcon(icon);
                            markers[id].setTitle(`${name} - ${stageLabel}`);
                            if (markers[id].infoWindow) {
                                markers[id].infoWindow.setContent(
                                    `<div style="min-width: 180px;">
                                        <div style="font-size: 18px; font-weight: bold;">${name}</div>
                                        <div style="margin-top: 6px; font-size: 13px; color: #334155;">Stage: ${stageLabel}</div>
                                        <div style="margin-top: 4px; font-size: 12px; color: #64748b;">Updated: ${timeLabel}</div>
                                    </div>`
                                );
                            }
                        } else {
                            markers[id] = new google.maps.Marker({
                                position: { lat, lng },
                                map: map,
                                icon: icon,
                                title: `${name} - ${stageLabel}`,
                            });

                            // Add an info window
                            const infoWindow = new google.maps.InfoWindow({
                                content: `<div style="min-width: 180px;">
                                    <div style="font-size: 18px; font-weight: bold;">${name}</div>
                                    <div style="margin-top: 6px; font-size: 13px; color: #334155;">Stage: ${stageLabel}</div>
                                    <div style="margin-top: 4px; font-size: 12px; color: #64748b;">Updated: ${timeLabel}</div>
                                </div>`,
                            });

                            markers[id].infoWindow = infoWindow;

                            markers[id].addListener("click", () => {
                                infoWindow.open(map, markers[id]);
                            });
                        }
                    }
                });

                Object.keys(markers).forEach(id => {
                    if (!activeIds.has(String(id))) {
                        markers[id].setMap(null);
                        delete markers[id];
                    }
                });
            })
            .catch(console.error);
    }

    // Initialize the map
    window.onload = initMap;
</script>


