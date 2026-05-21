

<?php
include("header.php");

error_reporting(0);
ini_set('display_errors', 0);

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$date    = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");

if (!$user_id) die('Missing user_id');

/* ===========================
   EMPLOYEE NAME
=========================== */
$emp = mysqli_query($conn, "SELECT name FROM employees WHERE id = $user_id");
$emp_row = mysqli_fetch_assoc($emp);
$employee_name = $emp_row['name'] ?? 'Employee';

/* ===========================
   GET JOURNEY
=========================== */
$journey_q = mysqli_query($conn, "
    SELECT id, start_time, start_lat, start_lng, end_time, end_lat, end_lng, end_km
    FROM journey_start
    WHERE user_id = '$user_id'
      AND DATE(start_time) <= '$date'
      AND (end_time IS NULL OR DATE(end_time) >= '$date')
    ORDER BY id DESC
    LIMIT 1
");

$journey = mysqli_fetch_assoc($journey_q);
if (!$journey) {
    echo "<div class='container-fluid py-4'><p class='empty'>No journey found.</p></div>";
    include("footer.php");
    exit;
}

$journey_id = $journey['id'];

/* ===========================
   BUILD TIMELINE
=========================== */
$timeline = [];

/* START */
$timeline[] = [
    "type" => "start",
    "time" => $journey['start_time'],
    "lat"  => $journey['start_lat'],
    "lng"  => $journey['start_lng'],
    "km"   => null
];

/* VISITS */
$visit_q = mysqli_query($conn, "
    SELECT 
        t.visit_type,
        t.customer_name,
        t.purpose,
        t.created_at AS time,
        t.arrival_time,
        t.exit_time,
        t.lat,
        t.lng,
        t.distance_from_prev_km,

        v.name  AS vendor_name,
        sd.name AS subdealer_name,
        a.name  AS ace_name

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

while ($v = mysqli_fetch_assoc($visit_q)) {
    $timeline[] = [
        "type" => "visit",
        "visit_type" => $v['visit_type'],
        "vendor_name" => $v['vendor_name'],
        "subdealer_name" => $v['subdealer_name'],
        "ace_name"       => $v['ace_name'],
        "customer_name" => $v['customer_name'],
        "purpose" => $v['purpose'],
        "time" => $v['time'],
        "arrival_time" => $v['arrival_time'],
        "exit_time" => $v['exit_time'],
        "lat"  => $v['lat'],
        "lng"  => $v['lng'],
        "km"   => (float)$v['distance_from_prev_km']
    ];
}

/* END */
if (!empty($journey['end_time'])) {
    $timeline[] = [
        "type" => "end",
        "time" => $journey['end_time'],
        "lat"  => $journey['end_lat'],
        "lng"  => $journey['end_lng'],
        "km"   => $journey['end_km']
    ];
}

foreach ($timeline as $index => &$item) {
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
body { background: linear-gradient(180deg, #f4f7fb 0%, #eef3f8 100%); }

.visit-details-page {
    padding-top: 0.95rem !important;
    padding-bottom: 1.2rem !important;
}

.visit-details-heading {
    margin-bottom: 1rem;
    color: #0f172a;
    font-size: 1.1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.visit-details-card {
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    padding: 1.15rem 1.2rem !important;
}

.section-title {
    font-size: 1rem;
    font-weight: 800;
    margin-bottom: 1rem;
    color: #1e293b;
    letter-spacing: -0.01em;
}

.timeline-container { white-space:nowrap; overflow-x:auto; padding:12px 0 4px; }
.timeline-item {
    display:inline-block; text-align:center; background:#fff;
    padding:16px 14px; min-width:164px;
    border-radius:18px; border: 1px solid #e2e8f0;
    box-shadow:0 14px 30px rgba(15, 23, 42, 0.08);
    vertical-align: top;
}
.timeline-item img { width:36px;height:36px;margin-bottom:6px;cursor:pointer; }


.timeline-connector {
    display: inline-flex;
    align-items: center;
    margin: 0 14px;
    vertical-align: top;
    padding-top: 72px;
}

.timeline-connector .line {
    width: 44px;
    height: 3px;
    background: #e2e8f0;
    border-radius: 999px;
}

.timeline-connector .km {
    margin: 0 8px;
    padding: 5px 11px;
    background: #e0f2fe;
    color: #0369a1;
    font-size: 12px;
    font-weight: 600;
    border-radius: 14px;
    white-space: nowrap;
}

.thumb {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
}

.timeline-icon { font-size:26px; }
.timeline-title { font-size:14px; font-weight:700; }
.timeline-time { font-size:12px; color:#64748b; }
.timeline-spend-label { font-size:11px; color:#64748b; margin-top:6px; }
.timeline-spend-value { font-size:12px; font-weight:400; color:#64748b; }

.badge-start { background:#059669;color:#fff;padding:4px 10px;border-radius:20px;font-size:11px; }
.badge-end { background:#dc2626;color:#fff;padding:4px 10px;border-radius:20px;font-size:11px; }

.visit-table-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}

.visit-records-table {
    margin-bottom: 0;
    min-width: 860px;
}

.visit-records-table thead th {
    padding: 1rem;
    border-bottom: 1px solid #eef2f7;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
}

.visit-records-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
    color: #1f2937;
    font-size: 0.88rem;
}

.visit-records-table tbody tr:hover {
    background: #fbfdff;
}

.visit-records-table tbody tr:last-child td {
    border-bottom: 0;
}

.empty {
    color: #64748b;
    font-weight: 600;
}

@media (max-width: 767.98px) {
    .visit-details-page {
        padding-top: 0.6rem !important;
        padding-left: 0.3rem !important;
        padding-right: 0.3rem !important;
        padding-bottom: 0.85rem !important;
    }

    .visit-details-heading {
        font-size: 0.96rem;
        line-height: 1.3;
    }

    .visit-details-card {
        padding: 1rem !important;
        border-radius: 20px;
    }

    .timeline-item {
        min-width: 148px;
        padding: 14px 12px;
    }

    .timeline-connector {
        margin: 0 10px;
        padding-top: 68px;
    }

    .visit-records-table thead th,
    .visit-records-table tbody td {
        padding: 0.82rem 0.78rem;
    }
}
</style>

<div class="container-fluid py-4 visit-details-page">
<h6 class="visit-details-heading">📋 Visit Details — <?= date("d M Y", strtotime($date)) ?></h6>

<div class="card visit-details-card mb-4">
<h6 class="section-title">📍 Journey Timeline</h6>

<div class="timeline-container">

<?php foreach ($timeline as $i => $t): ?>
<?php
$time = date("h:i A", strtotime($t['time']));
$mapUrl = "https://www.google.com/maps?q={$t['lat']},{$t['lng']}";
$isLast = ($i === array_key_last($timeline));
?>
<div class="timeline-item">
<img src="https://cdn-icons-png.flaticon.com/512/535/535239.png"
     onclick="window.open('<?= $mapUrl ?>','_blank')">

<?php if ($t['type']=='start'): ?>
    <div class="timeline-icon">🚀</div>
    <div class="timeline-title">Start Journey</div>
    <div class="timeline-time"><?= $time ?></div>
    <span class="badge-start">START</span>

<?php elseif ($t['type']=='visit'): ?>
    <?php if ($t['visit_type']=='vendor'): ?>
        <div class="timeline-icon">🏬</div>
        <div class="timeline-title"><?= htmlspecialchars($t['vendor_name']) ?></div>
    <?php elseif ($t['visit_type']=='subdealer'): ?>
        <div class="timeline-icon">🏪</div>
        <div class="timeline-title"><?= htmlspecialchars($t['subdealer_name']) ?></div>

    <?php elseif ($t['visit_type']=='ace'): ?>
        <div class="timeline-icon">🧑‍🔧</div>
        <div class="timeline-title"><?= htmlspecialchars($t['ace_name']) ?></div>
    <?php elseif ($t['visit_type']=='customer'): ?>
        <div class="timeline-icon">👤</div>
        <div class="timeline-title"><?= htmlspecialchars($t['customer_name']) ?></div>
    <?php else: ?>
        <div class="timeline-icon">🧾</div>
        <div class="timeline-title"><?= htmlspecialchars($t['customer_name']) ?></div>
        <div class="timeline-time"><?= htmlspecialchars($t['purpose']) ?></div>
    <?php endif; ?>
    <div class="timeline-time"><?= $time ?></div>
    <?php if (!empty($t['spent_time_label'])): ?>
        <div class="timeline-spend-label">Spend Time</div>
        <div class="timeline-spend-value"><?= htmlspecialchars($t['spent_time_label']) ?></div>
    <?php endif; ?>

<?php elseif ($t['type']=='end'): ?>
    <div class="timeline-icon">🏁</div>
    <div class="timeline-title">End Journey</div>
    <div class="timeline-time"><?= $time ?></div>
    <span class="badge-end">END</span>
<?php endif; ?>
</div>

<?php if (!$isLast && isset($timeline[$i+1]['km'])): ?>
    <div class="timeline-connector">
        <span class="line"></span>
        <span class="km"><?= number_format((float)$timeline[$i+1]['km'], 2) ?> KM</span>
        <span class="line"></span>
    </div>
<?php endif; ?>



<?php endforeach; ?>
</div>
</div>


<!-- TABLE -->
<div class="card visit-details-card">
<h6 class="section-title">📝 Visit Records</h6>

<div class="table-responsive visit-table-wrap">
<table class="table align-items-center mb-0 visit-records-table">
<thead>
<tr>
<th>Name</th>
<th>Address</th>
<th>Phone</th>
<th>Visit Type</th>
<th>Visited At</th>
<th>Photo</th>
</tr>
</thead>
<tbody>
<?php
$r = mysqli_query($conn, "
    SELECT 
        t.created_at,
        t.photo_path,
        t.visit_type,
        t.customer_name,
        t.customer_address,
        t.customer_mobile,

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


    WHERE t.journey_id = '$journey_id'
    ORDER BY t.created_at ASC

");

if (mysqli_num_rows($r) == 0) {
    echo "<tr><td colspan='5' class='empty'>No visits found.</td></tr>";
}

while ($row = mysqli_fetch_assoc($r)) {

    if ($row['visit_type']=="vendor") {
        $name = $row['vendor_name'];
        $address = $row['vendor_address'];
        $type = "Dealer";
    }
    elseif ($row['visit_type']=="subdealer") {
        $name = $row['subdealer_name'];
        $address = $row['subdealer_address'];
        $type = "Sub Dealer";
    }
    elseif ($row['visit_type']=="ace") {
        $name = $row['ace_name'];
        $address = $row['ace_address'];
        $type = "ACE";
    }
     else {
        $name = $row['customer_name'];
        $address = $row['customer_address'];
        $type = ucfirst($row['visit_type']);
    }

    $photo = $row['photo_path']
        ? "<a href='../emp/{$row['photo_path']}' target='_blank'><img class='thumb' src='../emp/{$row['photo_path']}'></a>"
        : "<span class='text-muted'>No Photo</span>";

    echo "
    <tr>
        <td>".htmlspecialchars($name)."</td>
        <td>".htmlspecialchars($address)."</td>
        <td>{$row['customer_mobile']}</td>
        <td>$type</td>
        <td>{$row['created_at']}</td>
        <td>$photo</td>
    </tr>";
}
?>
</tbody>
</table>
</div>
</div>


</div>

<?php include("footer.php"); ?>
