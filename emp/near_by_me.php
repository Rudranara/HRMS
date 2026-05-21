<?php
include("header.php");

date_default_timezone_set('Asia/Kolkata');

// Fetch all employees except logged-in user
$self = $_SESSION['employee_id'];
$emp_q = mysqli_query($conn, "
    SELECT id, name 
    FROM employees 
    WHERE id != '$self' 
    ORDER BY name ASC
");

$selected_emp = isset($_GET['emp']) ? intval($_GET['emp']) : 0;
$timeline = [];
$phone = "";
?>
<style>

body {
    background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
}

.nearby-page {
    padding-top: 0.95rem !important;
    padding-bottom: 1.2rem !important;
}

.nearby-card {
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 28px;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
}

.nearby-card-shell {
    padding: 1.2rem !important;
}

.nearby-title {
    margin: 0 0 0.9rem;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.nearby-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.nearby-select {
    min-height: 48px;
    border-radius: 16px;
    border: 1px solid #d9e2ec;
    background: #ffffff;
    color: #0f172a;
    box-shadow: none;
    font-size: 0.92rem;
    font-weight: 600;
    padding: 0.8rem 0.95rem;
}

.nearby-select:focus {
    border-color: #94a3b8;
    box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
}

.nearby-phone {
    color: #0f172a;
    font-size: 1.02rem;
    font-weight: 700;
    letter-spacing: -0.01em;
}

.nearby-empty-card {
    color: #64748b;
    font-weight: 700;
}

/* ===== TIMELINE WRAPPER ===== */
.timeline-container {
    display: flex;
    align-items: flex-start;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 0.35rem 0 0.2rem;
    gap: 0.85rem;
    -webkit-overflow-scrolling: touch;
}

/* ===== TIMELINE CARD ===== */
.timeline-item {
    flex: 0 0 auto;
    min-width: 158px;
    max-width: 240px;
    min-height: 184px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    padding: 1rem 0.95rem;
    border-radius: 22px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    text-align: center;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
}

/* ICON IMAGE */
.timeline-item img {
    width: 42px;
    height: 42px;
    padding: 0.45rem;
    border-radius: 14px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    cursor: pointer;
}

/* ===== CONNECTING LINE (BETWEEN BOXES) ===== */
.timeline-line {
    flex: 0 0 auto;
    width: 40px;
    height: 4px;
    margin-top: 4.9rem;
    background: linear-gradient(90deg, #dbe7f5 0%, #cbd5e1 100%);
    border-radius: 999px;
}

/* ===== TEXT FIXES ===== */
.timeline-icon {
    margin-top: 0.6rem;
    font-size: 1.45rem;
}

.timeline-title {
    min-height: 2.4em;
    font-size: 0.88rem;
    font-weight: 700;
    margin-top: 0.5rem;
    color: #0f172a;
    line-height: 1.35;
    display: flex;
    align-items: center;
    justify-content: center;

    white-space: normal;
    word-break: break-word;
    overflow-wrap: break-word;
}

.timeline-time {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 600;
    margin-top: 0.32rem;
}

/* ===== BADGES ===== */
.badge-start {
    background: #e8f8ef;
    color: #15803d;
    border: 1px solid #bbf7d0;
    padding: 0.36rem 0.74rem;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    border-radius: 999px;
    margin-top: 0.65rem;
    display: inline-block;
}

.badge-end {
    background: #fff1f2;
    color: #dc2626;
    border: 1px solid #fecdd3;
    padding: 0.36rem 0.74rem;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    border-radius: 999px;
    margin-top: 0.65rem;
    display: inline-block;
}

@media (max-width: 767.98px) {
    .nearby-page {
        padding-top: 0.6rem !important;
        padding-left: 0.3rem !important;
        padding-right: 0.3rem !important;
        padding-bottom: 0.85rem !important;
    }

    .nearby-card {
        border-radius: 22px;
    }

    .nearby-card-shell {
        padding: 1rem !important;
    }

    .nearby-title {
        font-size: 0.94rem;
    }

    .nearby-label {
        font-size: 0.68rem;
    }

    .nearby-select {
        min-height: 44px;
        border-radius: 14px;
        font-size: 0.84rem;
        padding: 0.72rem 0.85rem;
    }

    .nearby-phone {
        font-size: 0.94rem;
    }

    .timeline-item {
        min-width: 140px;
        min-height: 172px;
        padding: 0.9rem 0.8rem;
        border-radius: 18px;
    }

    .timeline-line {
        width: 28px;
        margin-top: 4.45rem;
    }
}


</style>

<div class="container-fluid py-4 nearby-page">

    <div class="card p-3 mb-4 nearby-card">
        <div class="nearby-card-shell">
        <h6 class="nearby-title">👥 Nearby Employees</h6>

        <form method="GET">
            <label class="nearby-label">Select Employee</label>
            <select name="emp" class="form-control nearby-select" onchange="this.form.submit()">
                <option value="">Select Employee</option>
                <?php while($e = mysqli_fetch_assoc($emp_q)): ?>
                    <option value="<?= $e['id'] ?>" <?= ($selected_emp==$e['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($e['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
        </div>
    </div>

<?php
if ($selected_emp) {

    /* ===============================
       EMPLOYEE PHONE
    =============================== */
    $p = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT phone 
        FROM employees 
        WHERE id='$selected_emp'
    "));
    $phone = $p['phone'];

        echo "<div class='card p-3 mb-4 nearby-card'>
            <div class='nearby-card-shell'>
            <h6 class='nearby-title'>📞 Employee Phone</h6>
            <p class='nearby-phone'>$phone</p>
            </div>
          </div>";

    /* ===============================
       GET TODAY JOURNEY (LATEST)
    =============================== */
    $js = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT *
        FROM journey_start
        WHERE user_id='$selected_emp'
          AND DATE(start_time)=CURDATE()
        ORDER BY id DESC
        LIMIT 1
    "));

    if (!$js) {
        echo "<div class='card p-3 nearby-card nearby-empty-card'><div class='nearby-card-shell'>No journey started today.</div></div>";
    } else {

        $journey_id = (int)$js['id'];

        /* ===============================
           START POINT
        =============================== */
        $timeline[] = [
            "type"=>"start",
            "time"=>$js['start_time'],
            "lat"=>$js['start_lat'],
            "lng"=>$js['start_lng']
        ];

        /* ===============================
           VISITS (JOURNEY BASED)
        =============================== */
        $visits_q = mysqli_query($conn,"
                        SELECT 
                            t.*,

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


        while($row = mysqli_fetch_assoc($visits_q)) {
            $timeline[] = [
                "type"=>"visit",
                "visit_type"=>$row['visit_type'],
                "vendor_name"=>$row['vendor_name'],
                "subdealer_name" => $row['subdealer_name'],
                "ace_name"       => $row['ace_name'],
                "customer_name"=>$row['customer_name'],
                "time"=>$row['created_at'],
                "lat"=>$row['lat'],
                "lng"=>$row['lng']
            ];
        }

        /* ===============================
           END POINT (IF ENDED)
        =============================== */
        if (!empty($js['end_time'])) {
            $timeline[] = [
                "type"=>"end",
                "time"=>$js['end_time'],
                "lat"=>$js['end_lat'],
                "lng"=>$js['end_lng']
            ];
        }
?>

    <!-- TIMELINE -->
    <div class="card p-3 nearby-card">
        <div class="nearby-card-shell">
        <h6 class="nearby-title">📍 Timeline Today</h6>

        <div class="timeline-container">
            <?php if (count($timeline)==0): ?>
                <p>No data today</p>
            <?php else: ?>
                <?php foreach($timeline as $i => $t):
                    $time = date("h:i A", strtotime($t['time']));
                    $mapUrl = "https://www.google.com/maps?q={$t['lat']},{$t['lng']}";
                    $isLast = ($i == count($timeline)-1);
                ?>
                    <div class="timeline-item">
                        <img src="https://cdn-icons-png.flaticon.com/512/535/535239.png"
                             onclick="window.open('<?= $mapUrl ?>','_blank')">

                        <?php if ($t['type']=="start"): ?>
                            <div class="timeline-icon">🚀</div>
                            <div class="timeline-title">Start Journey</div>
                            <div class="timeline-time"><?= $time ?></div>
                            <span class="badge-start">START</span>

                        <?php elseif ($t['type']=="visit"): ?>

                            <?php if ($t['visit_type']=="vendor"): ?>
                                <div class="timeline-icon">🏬</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['vendor_name']) ?></div>
                            <?php elseif ($t['visit_type']=="subdealer"): ?>
                                <div class="timeline-icon">🏪</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['subdealer_name']) ?></div>

                            <?php elseif ($t['visit_type']=="ace"): ?>
                                <div class="timeline-icon">🧑‍🔧</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['ace_name']) ?></div>
                            <?php elseif ($t['visit_type']=="customer"): ?>
                                <div class="timeline-icon">👤</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['customer_name']) ?></div>
                            <?php else: ?>
                                <div class="timeline-icon">🧾</div>
                                <div class="timeline-title"><?= htmlspecialchars($t['customer_name']) ?></div>
                            <?php endif; ?>

                            <div class="timeline-time"><?= $time ?></div>

                        <?php else: ?>
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
            <?php endif; ?>
        </div>
        </div>
    </div>

<?php
    }
}
?>

</div>

<?php include("footer.php"); ?>
