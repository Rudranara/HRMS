<?php
include("header.php");
ini_set("display_errors", 1);
error_reporting(E_ALL);

if (isset($_GET['from']) && isset($_GET['to'])) {
    $from = $_GET['from'];
    $to   = $_GET['to'];
} else {
    $from = date("Y-m-d");
    $to   = date("Y-m-d");
}

$user_id = $_SESSION['employee_id'];

/* =================================
      PAGINATION SETTINGS
================================= */
$limit = 10;   // rows per page
$page  = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

/* =================================
      GET TOTAL ROW COUNT
================================= */
// $count_q = "
//     SELECT COUNT(*) AS total 
//     FROM journey_start 
//     WHERE user_id = '$user_id'
//       AND DATE(start_time) BETWEEN '$from' AND '$to'
// ";

$count_q = "
    SELECT COUNT(*) AS total
    FROM (
        SELECT DATE(start_time)
        FROM journey_start
        WHERE user_id = '$user_id'
        AND DATE(start_time) BETWEEN '$from' AND '$to'
        GROUP BY DATE(start_time)
    ) AS x
";


$count_res = mysqli_query($conn, $count_q);
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $limit);

/* =================================
      MAIN SQL WITH PAGINATION
================================= */
$sql = "
    SELECT 
        DATE(js.start_time) AS journey_date,
        MIN(js.start_time) AS start_time,
        MAX(js.end_time) AS end_time,
        COALESCE(SUM(v.distance_from_prev_km),0) AS total_km,
        COUNT(v.visit_id) AS total_visits,
        COALESCE(SUM(vendors.visit_price),0) AS vendor_amount,
        SUM(CASE WHEN v.visit_type='customer' THEN 100 ELSE 0 END) AS customer_amount
    FROM journey_start js
    LEFT JOIN visits v 
        ON js.user_id = v.user_id 
       AND DATE(js.start_time) = DATE(v.arrival_time)
    LEFT JOIN vendors ON v.vendor_id = vendors.vendor_id
    WHERE js.user_id = '$user_id'
      AND DATE(js.start_time) BETWEEN '$from' AND '$to'
    GROUP BY DATE(js.start_time)
    ORDER BY journey_date DESC
    LIMIT $limit OFFSET $offset
";
$result = mysqli_query($conn, $sql);
?>



<div class="container-fluid py-4">
    <div class="row">

      
        <div class="jaction-row">
            
            <!-- My Journey (same size as Start / End) -->
            <a href="my_journey" 
               class="btn bg-gradient-primary btn-sm my-journey-btn">
                My Journey
            </a>

            <div class="jstartend-wrap">
                <button id="startBtn"
                        class="btn bg-gradient-primary btn-sm btn-journey"
                        onclick="startJourney()">
                    Start Journey
                </button>

                <button id="endBtn"
                        class="btn bg-gradient-dark btn-sm btn-journey"
                        style="display:none;"
                        onclick="endJourney()">
                    End Journey
                </button>
            </div>

        </div>



        <!-- JOURNEY SUMMARY TABLE -->
        <div class="col-12">
            <div class="card visit-table-card mb-4">
                <div class="card-body px-3 pt-3 pb-2">

                    <?php
                    // ************** IMPORTANT NEW SQL *****************
                    // $sql = "
                    //     SELECT 
                    //         DATE(js.start_time) AS journey_date,
                    //         MIN(js.start_time) AS start_time,
                    //         MAX(js.end_time) AS end_time,

                    //         COALESCE(SUM(v.distance_from_prev_km),0) AS total_km,
                    //         COUNT(v.visit_id) AS total_visits,

                    //         COALESCE(SUM(vendors.visit_price),0) AS vendor_amount,
                    //         SUM(CASE WHEN v.visit_type='customer' THEN 100 ELSE 0 END) AS customer_amount

                    //     FROM journey_start js

                    //     LEFT JOIN visits v 
                    //         ON js.user_id = v.user_id AND DATE(js.start_time) = DATE(v.created_at)

                    //     LEFT JOIN vendors
                    //         ON v.vendor_id = vendors.vendor_id

                    //     WHERE js.user_id = '$user_id'
                    //       AND DATE(js.start_time) BETWEEN '$from' AND '$to'

                    //     GROUP BY DATE(js.start_time)
                    //     ORDER BY journey_date DESC
                    // ";

                    // $result = mysqli_query($conn, $sql);
                    ?>

                    <div class="table-responsive">
                    <table class="table align-items-center mb-0 visit-table">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Total KM</th>
                        <th>Visits</th>
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                        if (mysqli_num_rows($result) > 0) {

                            // Get employee KM price
                            $emp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_km FROM employees WHERE id='$user_id'"));
                            $price_km = floatval($emp['price_km']);

                            while ($r = mysqli_fetch_assoc($result)) {

                                $vendor_amount   = floatval($r['vendor_amount']);
                                $customer_amount = floatval($r['customer_amount']);
                                $km              = floatval($r['total_km']);
                                $km_amount       = $km * $price_km;

                                // FINAL correct amount
                                $totalAmount = $vendor_amount + $customer_amount + $km_amount;

                                echo "
                                <tr>
                                    <td>".date("d M Y", strtotime($r['journey_date']))."</td>
                                    <td>".($r['start_time'] ? date("h:i A", strtotime($r['start_time'])) : "-")."</td>
                                    <td>".($r['end_time'] ? date("h:i A", strtotime($r['end_time'])) : "-")."</td>
                                    <td>".number_format($km,2)." KM</td>
                                    <td>{$r['total_visits']}</td>
                                    <td>₹".number_format($totalAmount,2)."</td>
                                    <td>
                                        <a href='visit_details?id=$user_id&date={$r['journey_date']}' 
                                           class='btn btn-primary btn-sm'>View</a>
                                    </td>
                                </tr>";
                            }

                        } else {
                            echo "<tr><td colspan='7' class='text-center text-danger'>No journey records found.</td></tr>";
                        }
                        ?>


                    </tbody>
                    </table>
                    </div>
                        
                        <!-- PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                                    <nav class="mt-3">
                                      <ul class="pagination justify-content-center">

                                        <!-- Prev -->
                                        <li class="page-item <?= ($page <= 1 ? 'disabled' : '') ?>">
                                          <a class="page-link" 
                                             href="?from=<?= $from ?>&to=<?= $to ?>&page=<?= $page-1 ?>">Prev</a>
                                        </li>

                                        <!-- Page Numbers -->
                                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                          <li class="page-item <?= ($page == $p ? 'active' : '') ?>">
                                            <a class="page-link" 
                                               href="?from=<?= $from ?>&to=<?= $to ?>&page=<?= $p ?>"><?= $p ?></a>
                                          </li>
                                        <?php endfor; ?>

                                        <!-- Next -->
                                        <li class="page-item <?= ($page >= $total_pages ? 'disabled' : '') ?>">
                                          <a class="page-link"
                                             href="?from=<?= $from ?>&to=<?= $to ?>&page=<?= $page+1 ?>">Next</a>
                                        </li>

                                      </ul>
                                    </nav>
                        <?php endif; ?>
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

function checkJourneyStatus() {
  fetchJsonSafe('check_journey').then(j => {
    if (j.status === "started") {
        startBtn.style.display = "none";
        endBtn.style.display = "inline-block";
    } else {
        startBtn.style.display = "inline-block";
        endBtn.style.display = "none";
    }
  });
}

function startJourney() {
  navigator.geolocation.getCurrentPosition(async pos => {
    let fd = new FormData();
    fd.append("start_lat", pos.coords.latitude);
    fd.append("start_lng", pos.coords.longitude);
    const r = await fetchJsonSafe("start_journey1", { method:"POST", body:fd });
    alert(r.message);
    checkJourneyStatus();
  });
}

function endJourney() {
  navigator.geolocation.getCurrentPosition(async pos => {
    let fd = new FormData();
    fd.append("end_lat", pos.coords.latitude);
    fd.append("end_lng", pos.coords.longitude);
    const r = await fetchJsonSafe("end_journey", { method:"POST", body:fd });
    alert("Journey Ended");
    location.reload();
  });
}

document.addEventListener("DOMContentLoaded", checkJourneyStatus);
</script>

<?php include("footer.php"); ?>
