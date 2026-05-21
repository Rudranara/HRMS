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
        js.id AS journey_id,
        DATE(js.start_time) AS journey_date,
        js.start_time,
        js.end_time,

        CASE
            WHEN js.status = 'ended'
             AND DATE(js.start_time) = DATE(js.end_time)
                THEN js.total_km
            ELSE
                COALESCE(SUM(v.distance_from_prev_km), 0)
        END AS total_km,
        COUNT(v.visit_id) AS total_visits,
        COALESCE(
            SUM(
                CASE
                    WHEN v.visit_type = 'vendor'     THEN vd.visit_price
                    WHEN v.visit_type = 'subdealer'  THEN sd.visit_price
                    WHEN v.visit_type = 'ace'        THEN a.visit_price
                    ELSE 0
                END
            ), 0
        ) AS vendor_amount,

        SUM(
            CASE 
                WHEN v.visit_type = 'customer' THEN 100
                WHEN v.visit_type = 'other' THEN 50
                ELSE 0
            END
        ) AS customer_amount

    FROM journey_start js

    LEFT JOIN visits v
        ON v.journey_id = js.id

    LEFT JOIN vendors vd
       ON v.vendor_id = vd.vendor_id
       AND v.visit_type = 'vendor'

    LEFT JOIN sub_dealers sd
       ON v.sub_dealer_id = sd.sub_dealer_id
      AND v.visit_type = 'subdealer'

    LEFT JOIN aces a
       ON v.ace_id = a.ace_id
      AND v.visit_type = 'ace'


    WHERE js.user_id = '$user_id'
      AND DATE(js.start_time) BETWEEN '$from' AND '$to'

    GROUP BY js.id
    ORDER BY js.start_time DESC
    LIMIT $limit OFFSET $offset
";



$result = mysqli_query($conn, $sql);
?>

<style>
    :root {
        --journey-shell-bg: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --journey-shell-border: rgba(148, 163, 184, 0.18);
        --journey-shell-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .journey-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .journey-filter-card,
    .journey-table-card {
        border: 1px solid var(--journey-shell-border);
        border-radius: 28px;
        background: var(--journey-shell-bg);
        box-shadow: var(--journey-shell-shadow);
        overflow: hidden;
    }

    .journey-filter-shell,
    .journey-table-shell {
        background: #ffffff;
    }

    .journey-filter-shell {
        padding: 1.2rem 1.25rem 1.25rem !important;
    }

    .journey-filter-row {
        margin-bottom: 0;
        align-items: flex-end;
    }

    .journey-field-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .journey-input {
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

    .journey-input:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .journey-filter-btn {
        min-height: 48px;
        margin-top: 0 !important;
        border-radius: 16px;
        border: 0;
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: 0 16px 28px rgba(18, 59, 118, 0.16);
    }

    .journey-table-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .journey-table {
        margin-bottom: 0;
        min-width: 840px;
    }

    .journey-table thead th {
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

    .journey-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .journey-table tbody tr:hover {
        background: #fbfdff;
    }

    .journey-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .journey-view-btn {
        min-height: 38px;
        padding: 0.65rem 0.95rem;
        border-radius: 14px;
        border: 0;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        color: #ffffff !important;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: none;
    }

    .journey-empty-state {
        padding: 1.4rem 1rem !important;
        color: #dc2626 !important;
        font-weight: 700;
    }

    .journey-pagination-wrap {
        padding: 1rem 1rem 1.1rem;
    }

    .journey-pagination-wrap .pagination {
        gap: 0.3rem;
        flex-wrap: wrap;
    }

    .journey-pagination-wrap .page-item .page-link {
        min-width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid #d9e2ec;
        color: #334155;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: none;
    }

    .journey-pagination-wrap .page-item.active .page-link {
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%);
        border-color: transparent;
        color: #ffffff;
    }

    .journey-pagination-wrap .page-item.disabled .page-link {
        background: #f8fafc;
        color: #94a3b8;
    }

    @media (max-width: 767.98px) {
        .journey-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .journey-filter-card,
        .journey-table-card {
            border-radius: 22px;
        }

        .journey-filter-shell {
            padding: 1rem !important;
        }

        .journey-filter-row {
            --bs-gutter-x: 0.65rem;
            --bs-gutter-y: 0.75rem;
        }

        .journey-filter-row .col-4,
        .journey-filter-row .col-sm-4,
        .journey-filter-row .col-md-4 {
            padding-left: calc(var(--bs-gutter-x) * 0.5);
            padding-right: calc(var(--bs-gutter-x) * 0.5);
        }

        .journey-field-label {
            font-size: 0.68rem;
        }

        .journey-input,
        .journey-filter-btn {
            min-height: 42px;
            border-radius: 14px;
            font-size: 0.74rem;
            padding: 0.7rem 0.78rem;
        }

        .journey-filter-btn {
            margin-top: 1.35rem !important;
        }

        .journey-table thead th,
        .journey-table tbody td {
            padding: 0.82rem 0.78rem;
        }

        .journey-view-btn {
            min-height: 34px;
            padding: 0.58rem 0.8rem;
            border-radius: 12px;
            font-size: 0.68rem;
        }

        .journey-pagination-wrap {
            padding-top: 0.9rem;
        }

        .journey-pagination-wrap .page-item .page-link {
            min-width: 36px;
            height: 36px;
            border-radius: 10px;
            font-size: 0.82rem;
        }
    }
</style>



<div class="container-fluid py-4 journey-page">


   
 <div class="row">
      <div class="col-12">
          <div class="card mb-4 journey-filter-card">
              <div class="card-body journey-filter-shell">
                  <form method="GET" class="row align-items-end journey-filter-row">
                    <div class="col-md-4 col-sm-4 col-4">
                        <label for="year" class="form-label journey-field-label">Start Date</label>
                        <input class="form-control journey-input" type="date" name="from" value="<?= $from ?>" required >
                    </div>
                    <div class="col-md-4 col-sm-4 col-4">
                        <label for="month" class="form-label journey-field-label">End Date</label>
                        <input class="form-control journey-input" type="date" name="to" value="<?= $to ?>" required>
                    </div>
                    <div class="col-md-4 col-sm-4 col-4">
                        <button class="form-control btn btn-sm journey-filter-btn" type="submit" >Filter</button>
                    </div>
                </form>
              </div>
          </div>
      </div>



        <!-- JOURNEY SUMMARY TABLE -->
 <div class="col-12">
            <div class="card mb-4 journey-table-card">
                <div class="card-body px-0 pt-0 pb-2 journey-table-shell">
                    <div class="table-responsive p-0 journey-table-wrap">
                         <table class="table align-items-center mb-0 journey-table">

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
                                        <a href='visit_record?id=$user_id&date={$r['journey_date']}' 
                                           class='btn btn-primary btn-sm journey-view-btn'>View</a>
                                    </td>
                                </tr>";
                            }

                        } else {
                            echo "<tr><td colspan='7' class='text-center text-danger journey-empty-state'>No journey records found.</td></tr>";
                        }
                        ?>


                    </tbody>
                         </table>

                
                    </div>
                        
                        <!-- PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                                                                        <nav class="mt-3 journey-pagination-wrap">
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



<?php include("footer.php"); ?>
