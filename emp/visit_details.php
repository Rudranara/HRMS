<?php
session_start();
include("header.php");


$user_id = $_SESSION['employee_id'] ?? 0;
if (!$user_id) die('Missing user_id');

$q = mysqli_query($conn, "
    SELECT 1 
    FROM attendance 
    WHERE employee_id = '$user_id' 
      AND DATE(punch_in_time) = CURDATE()
    LIMIT 1
");

if (mysqli_num_rows($q) == 0) {
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>

<script>
Swal.fire({
    icon: 'warning',
    title: 'Punch In Required',
    text: 'You did not punch in for today'
}).then(() => {
    window.location.href = 'add_attendance';
});
</script>
<?php
exit;
}


// error_reporting(0);
// ini_set('display_errors', 0);


$locked = isset($_SESSION['journey_reached']);

$date = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");

/* ======================================================
   FETCH TODAY'S JOURNEY (JOURNEY-BASED)
======================================================*/
$check = mysqli_query($conn,"
    SELECT id, status, start_time, start_lat, start_lng, end_time, end_lat, end_lng
    FROM journey_start
    WHERE user_id = '$user_id'
      AND DATE(start_time) = CURDATE()
    ORDER BY id DESC
    LIMIT 1
");

$journey = mysqli_fetch_assoc($check);
$timeline = [];

$journey_id = $journey ? (int)$journey['id'] : 0;

/* ======================================================
   BUILD TIMELINE
======================================================*/
if ($journey && !empty($journey['start_time'])) {
    $timeline[] = [
        "type" => "start",
        "time" => $journey['start_time'],
        "lat"  => $journey['start_lat'],
        "lng"  => $journey['start_lng']
    ];
}

/* ---- VISITS (JOURNEY BASED) ---- */
if ($journey_id) {

    $visit_q = mysqli_query($conn,"
        SELECT 
            t.visit_type,
            t.created_at,
            t.arrival_time,
            t.exit_time,
            t.lat,
            t.lng,

            -- Names by visit type
            v.name  AS vendor_name,
            sd.name AS subdealer_name,
            a.name  AS ace_name,

            t.customer_name,
            t.purpose

        FROM visits t

        LEFT JOIN vendors v
               ON t.vendor_id = v.vendor_id
              AND t.visit_type = 'vendor'

        LEFT JOIN sub_dealers sd
               ON t.sub_dealer_id = sd.sub_dealer_id
              AND t.visit_type = 'subdealer'

        LEFT JOIN aces a
               ON t.ace_id = a.ace_id
              AND t.visit_type = 'ace'

        WHERE t.journey_id = '$journey_id'
        ORDER BY t.created_at ASC

    ");

    while ($row = mysqli_fetch_assoc($visit_q)) {
        $timeline[] = [
            "type" => "visit",
            "visit_type" => $row['visit_type'],
            "vendor_name" => $row['vendor_name'],
            "subdealer_name" => $row['subdealer_name'], 
            "ace_name"       => $row['ace_name'],
            "customer_name" => $row['customer_name'],
            "purpose" => $row['purpose'],
            "time" => $row['created_at'],
            "arrival_time" => $row['arrival_time'],
            "exit_time" => $row['exit_time'],
            "lat"  => $row['lat'],
            "lng"  => $row['lng']
        ];
    }
}

/* ---- END ---- */
if ($journey && !empty($journey['end_time'])) {
    $timeline[] = [
        "type" => "end",
        "time" => $journey['end_time'],
        "lat"  => $journey['end_lat'],
        "lng"  => $journey['end_lng']
    ];
}

foreach ($timeline as &$item) {
    $item['spent_time_label'] = null;

    if ($item['type'] !== 'visit' || empty($item['arrival_time']) || empty($item['exit_time'])) {
        continue;
    }

    $arrival_time = strtotime($item['arrival_time']);
    $exit_time = strtotime($item['exit_time']);

    if (!$arrival_time || !$exit_time || $exit_time <= $arrival_time) {
        continue;
    }

    $spent_minutes = (int) floor(($exit_time - $arrival_time) / 60);
    $hours = (int) floor($spent_minutes / 60);
    $minutes = $spent_minutes % 60;

    if ($hours > 0 && $minutes > 0) {
        $item['spent_time_label'] = $hours . ' hr ' . $minutes . ' min';
    } elseif ($hours > 0) {
        $item['spent_time_label'] = $hours . ' hr';
    } else {
        $item['spent_time_label'] = max($spent_minutes, 1) . ' min';
    }
}
unset($item);

?>

<style>
body {
    background: linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
}

.visit-details-page {
    padding-top: 0.95rem !important;
    padding-bottom: 1.2rem !important;
}

.visit-details-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.16rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.card {
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 26px;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    box-shadow: 0 22px 52px rgba(15, 23, 42, 0.08);
}

.visit-details-panel {
    padding: 1.25rem !important;
}

.section-title {
    margin-bottom: 0.9rem;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.timeline-container {
    white-space: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 0.4rem 0 0.2rem;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

.timeline-item {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    padding: 1rem 0.95rem;
    margin-right: 0.95rem;
    width: 158px;
    min-width: 158px;
    min-height: 188px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 22px;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
    vertical-align: top;
}

.timeline-item img {
    width: 42px;
    height: 42px;
    padding: 0.45rem;
    border-radius: 14px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    cursor: pointer;
}

.timeline-icon {
    margin-top: 0.7rem;
    font-size: 1.45rem;
    line-height: 1;
}

.timeline-title {
    margin-top: 0.55rem;
    color: #0f172a;
    font-size: 0.88rem;
    font-weight: 700;
    line-height: 1.35;
    white-space: normal;
    min-height: 2.4em;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-time {
    margin-top: 0.35rem;
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 600;
    min-height: 1.4em;
}

.timeline-line {
    width: 54px;
    height: 4px;
    border-radius: 999px;
    background: linear-gradient(90deg, #dbe7f5 0%, #cbd5e1 100%);
    display: inline-block;
    vertical-align: top;
    margin: 0 0.2rem;
    margin-top: 4.6rem;
}

.visit-name {
    font-weight: 700;
    font-size: 0.87rem;
}

.visit-meta {
    margin-top: 0.3rem;
    color: #64748b;
    font-size: 0.74rem;
    line-height: 1.45;
    white-space: normal;
}

.timeline-spend-label {
    margin-top: 0.3rem;
    color: #64748b;
    font-size: 0.74rem;
    line-height: 1.45;
}

.timeline-spend-value {
    color: #64748b;
    font-size: 0.74rem;
    line-height: 1.45;
}

.badge-start,
.badge-end {
    display: inline-block;
    margin-top: 0.65rem;
    padding: 0.38rem 0.74rem;
    font-size: 0.65rem;
    border-radius: 999px;
    font-weight: 800;
    letter-spacing: 0.08em;
}

.badge-start {
    background: #e8f8ef;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.badge-end {
    background: #fff1f2;
    color: #dc2626;
    border: 1px solid #fecdd3;
}

.timeline-container .btn {
    min-height: 40px;
    padding: 0.62rem 0.95rem;
    border-radius: 14px;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    box-shadow: none;
    margin-top: auto !important;
}

.timeline-container .btn.bg-gradient-primary {
    background: #e8f8ef !important;
    color: #15803d !important;
    border: 1px solid #bbf7d0 !important;
}

.timeline-container .btn.bg-gradient-info {
    background: #e8f0ff !important;
    color: #1d4ed8 !important;
    border: 1px solid #bfd4ff !important;
}

.timeline-container .btn.bg-gradient-dark {
    background: #eceff3 !important;
    color: #1f2937 !important;
    border: 1px solid #d3d9e2 !important;
}

.thumb {
    width: 62px;
    height: 62px;
    object-fit: cover;
    border-radius: 14px;
    border: 2px solid #e2e8f0;
    transition: 0.2s ease;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.thumb:hover {
    transform: scale(1.05);
    border-color: #94a3b8;
}

.visit-details-table-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}

.visit-details-table {
    margin-bottom: 0;
    min-width: 760px;
}

.visit-details-table thead th {
    padding: 0.95rem 1rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    color: #64748b;
    font-size: 0.71rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
}

.visit-details-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
    color: #1e293b;
    font-size: 0.86rem;
}

.visit-details-table tbody tr:hover {
    background: #fbfdff;
}

.visit-details-table tbody tr:last-child td {
    border-bottom: 0;
}

.empty {
    text-align: center;
    color: #94a3b8;
    font-weight: 700;
    padding: 1.2rem 0 !important;
}

.btn.secondary {
    background: #475569 !important;
    color: white !important;
    border-radius: 10px;
    padding: 8px 14px;
}

.jstartend-wrap {
    display: flex;
    justify-content: flex-end;
    width: 100%;
    gap: 10px;
}

.my-journey-btn.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .visit-details-page {
        padding-top: 0.6rem !important;
        padding-left: 0.3rem !important;
        padding-right: 0.3rem !important;
        padding-bottom: 0.8rem !important;
    }

    .visit-details-title {
        font-size: 1rem;
    }

    .visit-details-panel {
        padding: 1rem !important;
    }

    .timeline-item {
        width: 138px;
        min-width: 138px;
        min-height: 174px;
        padding: 0.9rem 0.8rem;
        border-radius: 18px;
    }

    .timeline-line {
        width: 40px;
        margin-top: 4.3rem;
    }

    .timeline-container .btn {
        min-height: 38px;
        padding: 0.56rem 0.8rem;
        font-size: 0.7rem;
    }

    .section-title {
        font-size: 0.92rem;
    }

    .visit-details-table thead th,
    .visit-details-table tbody td {
        padding: 0.82rem 0.8rem;
    }

    .thumb {
        width: 56px;
        height: 56px;
        border-radius: 12px;
    }
}


</style>

<div class="container-fluid py-4 visit-details-page">
  <div class="col-6 mb-4 d-flex align-items-center">
            <h6 class="mb-0 visit-details-title">Today Journey</h6>
        </div> 
    <div class="row">

        
       
       
   <!-- TIMELINE -->
<div class="col-12">
    <div class="card p-3 mb-4 visit-details-panel">
        <h6 class="section-title">📍 Today's Timeline</h6>

        <div class="timeline-container">

            <?php
            /* ===============================
               NO JOURNEY STARTED
            =============================== */
            if (!$journey || empty($journey['start_time'])):
            ?>
                <!-- START BUTTON ONLY -->
                <div class="timeline-item">
                    <div class="timeline-icon">🚀</div>
                    <div class="timeline-title">Start Journey</div>

                    <button class="btn bg-gradient-primary btn-sm mt-2"
                            onclick="startJourney()">
                        Start
                    </button>
                </div>

            <?php else: ?>

                <?php foreach ($timeline as $i => $t): ?>

                    <?php
                    $time = date("h:i A", strtotime($t['time']));
                    $mapUrl = "https://www.google.com/maps?q={$t['lat']},{$t['lng']}";
                    $isLast = ($i == count($timeline) - 1);
                    ?>

                    <div class="timeline-item">

                        <!-- MAP -->
                        <img src="https://cdn-icons-png.flaticon.com/512/535/535239.png"
                             onclick="window.open('<?= $mapUrl ?>','_blank')">

                        <?php if ($t['type'] == "start"): ?>

                            <div class="timeline-icon">🚀</div>
                            <div class="timeline-title">Start Journey</div>
                            <div class="timeline-time"><?= $time ?></div>
                            <span class="badge-start">START</span>

                        <?php elseif ($t['type'] == "visit"): ?>

                            <?php if ($t['visit_type'] == "vendor"): ?>
                                <div class="timeline-icon">🏬</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['vendor_name']) ?></div>
                            <?php elseif ($t['visit_type'] == "subdealer"): ?>
                                <div class="timeline-icon">🏪</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['subdealer_name']) ?></div>

                            <?php elseif ($t['visit_type'] == "ace"): ?>
                                <div class="timeline-icon">🧑‍🔧</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['ace_name']) ?></div>

                            <?php elseif ($t['visit_type'] == "customer"): ?>
                                <div class="timeline-icon">👤</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['customer_name']) ?></div>

                            <?php else: ?>
                                <div class="timeline-icon">📝</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['customer_name']) ?></div>
                                <div class="visit-meta"><?= htmlspecialchars($t['purpose']) ?></div>
                            <?php endif; ?>

                            <div class="timeline-time"><?= $time ?></div>
                            <?php if (!empty($t['spent_time_label'])): ?>
                                <div class="timeline-spend-label">Spend Time</div>
                                <div class="timeline-spend-value"><?= htmlspecialchars($t['spent_time_label']) ?></div>
                            <?php endif; ?>

                        <?php elseif ($t['type'] == "end"): ?>

                            <div class="timeline-icon">🏁</div>
                            <div class="timeline-title">End Journey</div>
                            <div class="timeline-time"><?= $time ?></div>
                            <span class="badge-end">END</span>

                        <?php endif; ?>

                    </div>

                    <?php if (!$isLast): ?>
                        <div class="timeline-line"></div>
                    <?php endif; ?>

                <?php endforeach; ?>

                <!-- ACTION BUTTONS AFTER START -->
                <?php if ($journey && $journey['status'] === 'started'): ?>

                    <div class="timeline-line"></div>

                        <?php if ($locked): ?>

                            <!-- 🔄 RESTART JOURNEY (ONLY WHEN LOCKED) -->
                            <div class="timeline-item" id="timelineRestart">
                                <div class="timeline-icon">🔄</div>
                                <div class="timeline-title">Restart Journey</div>

                                <button class="btn bg-gradient-info btn-sm mt-2"
                                        onclick="restartJourney()">
                                    Restart
                                </button>
                            </div>

                        <?php else: ?>

                            <!-- 🏬 DEALER POINT (ONLY WHEN NOT LOCKED) -->
                            <div class="timeline-item" id="timelineDealerPoint">
                                <div class="timeline-icon">🏬</div>
                                <div class="timeline-title">Dealer Point</div>

                                <a href="my_journey"
                                   class="btn bg-gradient-info btn-sm mt-2"
                                   onclick="return openDealerPoint();">
                                    Open
                                </a>
                            </div>

                        <?php endif; ?>

                        <div class="timeline-line"></div>

                        <!-- 🏁 END JOURNEY (ALWAYS WHEN STARTED) -->
                        <div class="timeline-item" id="timelineEndJourney">
                            <div class="timeline-icon">🏁</div>
                            <div class="timeline-title">End Journey</div>

                            <button class="btn bg-gradient-dark btn-sm mt-2"
                                    onclick="endJourney()">
                                End
                            </button>
                        </div>


                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</div>


        

    

        <!-- TABLE -->
        <div class="col-12">
            <div class="card p-3 visit-details-panel">
                <h6 class="section-title">📝 Visit Records</h6>

                <div class="table-responsive p-0 visit-details-table-wrap">
                    <table class="table align-items-center mb-0 visit-details-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Visit Type</th>
                                <th>Visited At</th>
                                <th>Photo</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Fetch visits for the table (same date)
                        $q = "
                            SELECT 
                                t.created_at,
                                t.photo_path,
                                t.visit_type,
                                t.customer_name,
                                t.customer_address,
                                t.purpose,

                                v.name  AS vendor_name,
                                v.address AS vendor_address,

                                sd.name AS subdealer_name,
                                sd.address AS subdealer_address,

                                a.name  AS ace_name,
                                a.address AS ace_address

                            FROM visits t

                            LEFT JOIN vendors v
                                   ON t.vendor_id = v.vendor_id
                                  AND t.visit_type = 'vendor'

                            LEFT JOIN sub_dealers sd
                                   ON t.sub_dealer_id = sd.sub_dealer_id
                                  AND t.visit_type = 'subdealer'

                            LEFT JOIN aces a
                                   ON t.ace_id = a.ace_id
                                  AND t.visit_type = 'ace'

                            WHERE t.user_id = '$user_id'
                              AND DATE(t.created_at) = '$date'
                            ORDER BY t.created_at ASC

                        ";

                    

                        $r = mysqli_query($conn, $q);

                        if (mysqli_num_rows($r) > 0) {
                            while ($row = mysqli_fetch_assoc($r)) {
                                
                                if ($row['visit_type'] == "vendor") {

                                        $name = $row['vendor_name'];
                                        $address = $row['vendor_address'];
                                        $type = "Dealer";

                                    }
                                    elseif ($row['visit_type'] == "subdealer") {
                                        $name = $row['subdealer_name'];
                                        $address = $row['subdealer_address'];
                                        $type = "Sub Dealer";
                                    }
                                    elseif ($row['visit_type'] == "ace") {
                                        $name = $row['ace_name'];
                                        $address = $row['ace_address'];
                                        $type = "ACE";
                                    }
                                    elseif ($row['visit_type'] == "customer") {

                                        $name = $row['customer_name'];
                                        $address = $row['customer_address'];
                                        $type = ucfirst($row['visit_type']);

                                    }
                                    elseif ($row['visit_type'] == "other") {

                                        $name = $row['customer_name']; 
                                        $address = $row['customer_address']; 
                                        $type = ucfirst($row['visit_type']);

                                    }
                                    else{
                                        echo "Invalid visit type!";
                                    }

                                $final_path = "../emp/" . $row['photo_path'];

                                $photo = $row['photo_path']
                                    ? "<a href='$final_path' target='_blank'><img class='thumb' src='$final_path'></a>"
                                    : "<span class='text-muted'>No photo</span>";

                                echo "
                                <tr>
                                    <td>".htmlspecialchars($name)."</td>
                                    <td>".htmlspecialchars($address)."</td>
                                    <td>$type</td>
                                    <td>{$row['created_at']}</td>
                                    <td>$photo</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='empty'>No visits found for this date.</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
<script>
async function fetchJsonSafe(url, opts = {}) {
  const res = await fetch(url, opts);
  const txt = await res.text();
  try { return JSON.parse(txt); }
  catch { return { __raw: txt }; }
}




let journeyStarting = false;

function startJourney() {

  if (journeyStarting) return;   //  block double click
  journeyStarting = true;

  navigator.geolocation.getCurrentPosition(async pos => {

    let fd = new FormData();
    fd.append("start_lat", pos.coords.latitude);
    fd.append("start_lng", pos.coords.longitude);

    const r = await fetchJsonSafe("start_journey1", {
      method: "POST",
      body: fd
    });

    if (r.message) {
        alert(r.message);
    }

    window.location.href = "visit_details";

  }, err => {
    journeyStarting = false; // re-enable if GPS fails
    alert("GPS Error: " + err.message);
  });
}



function endJourney() {

    // 1️⃣ Alert message (info)
    alert("⚠️ Please confirm before ending your journey.");

    // 2️⃣ Show popup modal
    document.getElementById("endJourneyModal").style.display = "flex";
}

function closeEndJourneyModal() {
    document.getElementById("endJourneyModal").style.display = "none";
}

function confirmEndJourney() {

    document.getElementById("endJourneyModal").style.display = "none";

    navigator.geolocation.getCurrentPosition(async pos => {

        let fd = new FormData();
        fd.append("end_lat", pos.coords.latitude);
        fd.append("end_lng", pos.coords.longitude);

        const r = await fetchJsonSafe("end_journey", {
            method: "POST",
            body: fd
        });

        alert("Journey Ended Successfully");
        location.reload();

    }, err => {
        alert("GPS Error: " + err.message);
    });
}






function restartJourney() {
    fetch("update_exit_time", { method: "POST" })
        .then(r => r.json())
        .then(j => {
            if (j.status === "success") {
                alert("Journey Restarted");
                location.reload();
            } else {
                alert("Failed to restart journey");
            }
        });
}


function openDealerPoint() {

    const dealerPointBtn = document.getElementById("dealerPointBtn");

    //  Disable button immediately
    dealerPointBtn.classList.add("disabled");
    dealerPointBtn.style.pointerEvents = "none";

    fetch("check_dealer_point")
        .then(res => res.json())
        .then(j => {

            if (j.status === "error") {
                alert(j.message);

                //  Re-enable button if blocked
                dealerPointBtn.classList.remove("disabled");
                dealerPointBtn.style.pointerEvents = "auto";
                return;
            }

            //  Allowed → redirect
            window.location.href = "my_journey";
        })
        .catch(() => {
            alert("Network error. Please try again.");

            //  Re-enable on error
            dealerPointBtn.classList.remove("disabled");
            dealerPointBtn.style.pointerEvents = "auto";
        });

    return false; //  stop default link navigation
}







document.addEventListener("DOMContentLoaded", () => {

    // 📱 Only mobile
    if (window.innerWidth > 768) return;

    // Priority order:
    // 1️⃣ Restart
    // 2️⃣ Dealer Point
    // 3️⃣ End Journey
    const targets = [
        document.getElementById("timelineRestart"),
        document.getElementById("timelineDealerPoint"),
        document.getElementById("timelineEndJourney")
    ];

    for (let el of targets) {
        if (el && el.offsetParent !== null) {
            setTimeout(() => {
                el.scrollIntoView({
                    behavior: "smooth",
                    inline: "center",
                    block: "nearest"
                });
            }, 400);
            break;
        }
    }
});


</script>



<?php include("footer.php"); ?>