<?php
include("header.php");

error_reporting(0);
ini_set('display_errors', 0);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

/* ----------------------------
   Date filter (new: from/to)
   Default: today
   ---------------------------- */
$from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : date('Y-m-d');
$to   = isset($_GET['to'])   && $_GET['to'] !== ''   ? $_GET['to']   : date('Y-m-d');

/* ----------------------------
   Pagination settings
   ---------------------------- */
$per_page = 10;

$page_users = isset($_GET['page_users']) ? max(1, intval($_GET['page_users'])) : 1;
$offset_users = ($page_users - 1) * $per_page;

$page_vendors = isset($_GET['page_vendors']) ? max(1, intval($_GET['page_vendors'])) : 1;
$offset_vendors = ($page_vendors - 1) * $per_page;

/* ----------------------------
   Search WHERE conditions
   ---------------------------- */
$where = "";
$where_vendor = "";

if ($search !== "") {
    $safe = mysqli_real_escape_string($conn, $search);
    $where = "WHERE (u.name LIKE '%$safe%' OR u.phone LIKE '%$safe%' OR u.employee_id LIKE '%$safe%')";
    $where_vendor = "WHERE (name LIKE '%$safe%' OR dealer_code LIKE '%$safe%')";
}

/* ----------------------------
   SITE OFFICE FILTER SECTION
   ---------------------------- */
$filter_office = $_GET['office'] ?? '';

$query = "SELECT * FROM employees WHERE status='Active' 
          AND (name LIKE '%$search%' 
          OR employee_id LIKE '%$search%' 
          OR role LIKE '%$search%')";

if (!empty($filter_office)) {
    $query .= " AND office = '$filter_office'";
}

$result = $conn->query($query);
$employees = $result->fetch_all(MYSQLI_ASSOC);

/* =========================================================
   ==================== CSV DOWNLOAD ========================
   ========================================================= */
if (isset($_GET['download_csv'])) {

    if (ob_get_level()) ob_end_clean();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export.csv"');

    $output = fopen("php://output", "w");

    $tab = $_GET['tab'] ?? 'users';


    /* =====================================================
       =============== EMPLOYEE TAB CSV EXPORT =============
       ===================================================== */
    if ($tab === 'users') {

        // Search filter for employee visit summary
        $whereEmp = "";
        if (!empty($search)) {
            $safe = mysqli_real_escape_string($conn, $search);
            $whereEmp = " AND (u.name LIKE '%$safe%' OR u.phone LIKE '%$safe%' OR u.id LIKE '%$safe%')";
        }

        // Query: NO LIMIT — download all filtered employee visit records
        $csv_sql = "
            SELECT 
                u.name,
                u.employee_id,
                u.phone,
                DATE(v.created_at) AS visit_date,
                COUNT(CASE WHEN v.visit_type = 'vendor' THEN 1 END) AS vendor_visits,
                COUNT(CASE WHEN v.visit_type = 'subdealer' THEN 1 END) AS subdealer_visits,
                COUNT(CASE WHEN v.visit_type = 'ace' THEN 1 END)       AS ace_visits,
                COUNT(CASE WHEN v.visit_type = 'customer' THEN 1 END) AS customer_visits,
                COUNT(CASE WHEN v.visit_type = 'other' THEN 1 END) AS other_visits,
                COALESCE(SUM(v.distance_from_prev_km),0) AS total_km
            FROM employees u
            JOIN visits v ON u.id = v.user_id
                AND DATE(v.created_at) BETWEEN '$from' AND '$to'
            LEFT JOIN vendors ON v.vendor_id = vendors.vendor_id
            $whereEmp
            GROUP BY u.id, DATE(v.created_at)
            HAVING (
                    vendor_visits +
                    subdealer_visits +
                    ace_visits +
                    customer_visits +
                    other_visits
                ) > 0

            ORDER BY visit_date DESC, u.id ASC
        ";

        // CSV Header
        fputcsv($output, [
            "Date",
            "Name",
            "Employee ID",
            "Phone",
            "Vendor Visits",
            "Sub Dealer Visits",
            "ACE Visits",
            "Customer Visits",
            "Other Visits",
            "Total KM"
        ]);


        $csv_run = $conn->query($csv_sql);

        while ($row = $csv_run->fetch_assoc()) {
            fputcsv($output, [
                $row["visit_date"],
                $row["name"],
                $row["employee_id"],
                $row["phone"],
                $row["vendor_visits"],
                $row["subdealer_visits"],
                $row["ace_visits"],
                $row["customer_visits"],
                $row["other_visits"],
                $row["total_km"]
            ]);


        }
    }



    /* =====================================================
       =============== DEALER TAB CSV EXPORT ===============
       ===================================================== */
    if ($tab === 'vendors') {

        $whereVen = "";
        if (!empty($search)) {
            $safe = mysqli_real_escape_string($conn, $search);
            $whereVen = "WHERE (name LIKE '%$safe%' OR dealer_code LIKE '%$safe%')";
        }

        // Dealer CSV query — all filtered rows, NO limit
        $csv_sql = "SELECT * FROM vendors $whereVen ORDER BY vendor_id DESC";

        // CSV Header
        fputcsv($output, [
            "Dealer Code",
            "Name",
            "Radius",
            "Address",
            "Visit Price",
            "Latitude",
            "Longitude"
        ]);

        $csv_run = $conn->query($csv_sql);

        while ($v = $csv_run->fetch_assoc()) {
            fputcsv($output, [
                $v["dealer_code"],
                $v["name"],
                $v["area"],
                $v["address"],
                $v["visit_price"],
                $v["lat"],
                $v["lng"]
            ]);
        }
    }

    fclose($output);
    exit;
}

?>


<style>
/* ensure google places dropdown appears above modal */
.pac-container { z-index: 2147483647 !important; }
.modal { z-index:1055 !important; }
.modal-backdrop { z-index:1040 !important; }

body {
  font-family:'Plus Jakarta Sans','Inter',Arial,sans-serif;
  background:linear-gradient(180deg, #f5f7fa 0%, #eef2f6 100%);
    margin:0;
    padding:0;
}

.admin-home-shell {
  padding-bottom: 1.5rem;
}

.card {
  border-radius: 22px;
  border: 1px solid rgba(87, 96, 108, 0.12);
  box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
  background: #fff;
}

.control-card,
.search-card,
.table-card {
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 22px;
  box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
  background: #fff;
}

.control-card {
  padding: 0.95rem 1rem;
  height: 100%;
}

.search-card {
  padding: 1rem 1.15rem;
  margin-top: 0.25rem;
  margin-bottom: 1rem;
}

.table-card {
  overflow: hidden;
}

.section-label {
  display: block;
  margin-bottom: 0.45rem;
  color: #6b7280;
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.site-select {
  min-height: 44px;
}

.filters-wrapper {
  display:flex;
  gap:10px;
  align-items:center;
  justify-content:flex-end;
  flex-wrap:wrap;
}

.filters-form {
  display:flex;
  gap:10px;
  align-items:center;
  flex-wrap:wrap;
  justify-content:flex-end;
  width:auto;
  flex:0 1 auto;
}

.filter-box {
  height:44px !important;
  width:148px !important;
  min-width:148px !important;
  display:flex !important;
  align-items:center !important;
  justify-content:center !important;
  padding:0 14px !important;
  box-sizing:border-box !important;
  font-weight:600;
  border-radius:14px !important;
  border:1px solid #d7dde6 !important;
  background:#fff;
  color:#111827;
  box-shadow:none !important;
}

.filter-box:focus {
  border-color:#1e3a5f !important;
  box-shadow:0 0 0 0.18rem rgba(30, 58, 95, 0.12) !important;
}

.btn.filter-box {
  border:none !important;
}

.btn-admin-primary,
.save,
.modal-footer .btn-primary {
  background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
  color:#fff !important;
  border:none !important;
  box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.btn-admin-primary:hover,
.save:hover,
.modal-footer .btn-primary:hover {
  color:#fff !important;
  background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
}

.btn-admin-secondary,
.btn-outline-primary,
.modal-footer .btn-secondary {
  background:#16324f !important;
  color:#fff !important;
  border:1px solid #16324f !important;
  box-shadow:none !important;
}

.btn-admin-secondary:hover,
.btn-outline-primary:hover,
.modal-footer .btn-secondary:hover {
  background:#10263c !important;
  border-color:#10263c !important;
  color:#fff !important;
}

.btn-danger {
  background:#fbe6e5 !important;
  color:#c24141 !important;
  border:1px solid #f4c9c7 !important;
  box-shadow:none !important;
}

.btn-danger:hover {
  background:#f7d8d6 !important;
  color:#a93232 !important;
  border-color:#efb8b4 !important;
}

/* Custom modal */
.modal-custom {
    display:none;
    position:fixed;
    top:0; left:0; right:0; bottom:0;
    background:rgba(0,0,0,0.45);
    align-items:center;
    justify-content:center;
    z-index:1050;
}

.modal-custom .modal-content {
    background:#fff;
  padding:22px;
  border-radius:22px;
    margin:40px auto;
    width:520px;
    max-width:95%;
  border:1px solid rgba(87, 96, 108, 0.12);
  box-shadow:0 22px 46px rgba(15, 23, 42, 0.2);
}

.save {
    padding:10px;
  border-radius:14px;
    margin-top:12px;
    width:100%;
}

.close {
    background:#e5e7eb;
    color:#111827;
    padding:8px;
  border-radius:14px;
    border:none;
    margin-top:8px;
    width:100%;
}

.form-control {
  padding:10px 14px;
  border:1px solid #d5dce5;
  border-radius:14px;
    width:100%;
    box-sizing:border-box;
  min-height:44px;
  box-shadow:none;
}

.form-control:focus,
.form-select:focus {
  border-color:#1e3a5f;
  box-shadow:0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.modal-content.small { width:450px; }

.modal .modal-content {
  border:none;
  border-radius:22px;
  box-shadow:0 24px 52px rgba(15, 23, 42, 0.18);
}

.modal .modal-header,
.modal .modal-footer {
  border-color:#eef2f7;
  padding:1rem 1.25rem;
}

.modal .modal-body {
  padding:1.25rem;
}

.modal .form-label,
.modal-custom label {
  font-size:0.78rem;
  font-weight:700;
  letter-spacing:0.05em;
  text-transform:uppercase;
  color:#6b7280;
  margin-bottom:0.45rem;
}

/* Google Map */
#map { width:100%; height:320px; border-radius:16px; }
#search-box { width:100%; }

/* Pagination */
.pagination { margin:12px 0; }
.page-item.disabled .page-link { pointer-events:none; opacity:0.6; }

.page-link {
  border:none;
  color:#374151;
  background:#f3f5f8;
  min-width:40px;
  height:40px;
  display:flex;
  align-items:center;
  justify-content:center;
  border-radius:12px !important;
  margin-left:6px;
  font-weight:600;
}

.page-item.active .page-link {
  background:#161616;
  color:#fff;
}

.filter-box[type="date"],
.filter-box input[type="date"] {
  padding: 0 14px !important;
  height: 44px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    line-height: normal !important;
}



/* Chrome internal text alignment fix */
.filter-box::-webkit-datetime-edit {
    padding:0 !important;
    margin:0 !important;
  line-height:44px !important;
}

/* Align calendar icon vertically */
.filter-box::-webkit-calendar-picker-indicator {
    margin:0 !important;
    padding:0 !important;
    transform:translateY(1px);
}

/* Buttons same height */
.filter-box.btn {
    padding-top:0 !important;
    padding-bottom:0 !important;
}

.search-input-group {
  display:flex;
  gap:0.85rem;
  align-items:center;
}

.top-control-row {
  align-items:stretch;
}

.search-input-group .form-control {
  background:#f9fafb;
}

.table-toolbar {
  display:flex;
  align-items:center;
  gap:0.75rem;
  padding:1.2rem 1.3rem 0;
  flex-wrap:wrap;
}

.table-toolbar-nav {
  display:flex;
  align-items:center;
  gap:0.75rem;
  flex-wrap:wrap;
}

.table-toolbar-action {
  margin-left:auto;
}

.table-toolbar-action .btn {
  min-height:42px;
  padding:0.72rem 1.15rem;
  border-radius:14px;
  font-weight:700;
  box-shadow:0 12px 24px rgba(17, 24, 39, 0.14);
}

.tab-toggle {
  border-radius:999px !important;
  padding:0.72rem 1.1rem !important;
  font-size:0.86rem;
  font-weight:700 !important;
  letter-spacing:0.02em;
}

.tab-toggle.active {
  background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
  color:#fff !important;
  border-color:transparent !important;
  box-shadow:0 14px 24px rgba(17, 24, 39, 0.18);
}

.table-card .card-body {
  padding:0 0 1rem;
}

.table-card .table-responsive {
  padding:0 1.2rem 1.25rem;
}

.table {
  margin-bottom:0;
}

.table thead th {
  border-bottom:1px solid #e8edf3;
  color:#6b7280;
  font-size:0.78rem;
  font-weight:700;
  letter-spacing:0.05em;
  text-transform:uppercase;
  padding:1rem 0.95rem;
  white-space:nowrap;
}

.table tbody td {
  padding:1rem 0.95rem;
  border-bottom:1px solid #eef2f7;
  color:#1f2937;
  vertical-align:middle;
}

.table tbody tr:last-child td {
  border-bottom:none;
}

.table tbody tr:hover {
  background:#fbfcfe;
}

.table .btn {
  border-radius:12px;
  font-weight:700;
  min-width:auto;
}

.table-empty {
  padding:2rem 1rem !important;
  color:#c24141 !important;
  font-weight:600;
}

/* MOBILE RESPONSIVE */
@media (max-width:767px) {
    .filters-wrapper {
        flex-direction:column;
        align-items:stretch;
        width:100%;
    }

    .filter-box {
        width:100% !important;
        min-width:100% !important;
    }

    .filters-form,
    .search-input-group,
    .table-toolbar,
    .table-toolbar-nav {
      flex-direction:column;
      align-items:stretch;
    }

    .table-toolbar-action {
      margin-left:0;
      width:100%;
    }

    .table-toolbar-action .btn {
      width:100%;
    }

    .tab-toggle {
      width:100%;
    }
}

@media (max-width: 767px) {
    .filters-form input[type="date"] {
        margin-bottom: 14px !important;  /* gap between FROM and TO */
    }
}


/* MISC */
.avatar {
  width:42px;
  height:40px;
  border-radius:12px;
  object-fit:cover;
  background:#f3f5f8;
  padding:4px;
}
.text-xs { font-size:13px; }
.text-sm { font-size:15px; }

/* Lift ONLY the date input boxes */
.filters-form input[type="date"] {
    margin-top: -14px !important;
}

.compact-button {
  width:auto !important;
  min-width:132px !important;
  padding:0 18px !important;
}

#sd-map,
#ace-map {
  border-radius:16px;
}

</style>



<div class="container-fluid py-4 admin-home-shell">
  <div class="row top-control-row">
    <div class="col-12 mb-4 d-flex">
      <div class="control-card w-100">
        <span class="section-label">Date Controls</span>
        <div class="filters-wrapper d-flex justify-content-end align-items-center flex-wrap gap-2">

          <!-- DATE FILTER FORM -->
          <form method="GET" class="filters-form d-flex gap-2 align-items-center flex-wrap">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">

            <input type="date" name="from" 
                 class="form-control form-control-sm filter-box"
                 value="<?= htmlspecialchars($from) ?>" required>

            <input type="date" name="to"
                 class="form-control form-control-sm filter-box"
                 value="<?= htmlspecialchars($to) ?>" required>

            <button type="submit" class="btn btn-admin-primary btn-sm filter-box">Filter</button>
          </form>

          <!-- CSV BUTTON -->
              <a href="?download_csv=1&tab=<?= $active_tab ?>&search=<?= urlencode($search) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"
                class="btn btn-admin-secondary btn-sm filter-box compact-button">
             <i class="bi bi-cloud-arrow-down-fill"></i> Download CSV
          </a>

                    

          <!-- ADD VENDOR -->
          <button class="btn btn-admin-primary btn-sm filter-box compact-button" onclick="openVendorModal()">Add Dealer</button>
        </div>
            </div>
        </div>





    <div class="col-12">
      <!-- RESTORED ORIGINAL SEARCH BAR ABOVE THE TABLE -->
      <div class="search-card">
        <form method="GET" class="mb-0 mt-0">
          <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
          <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
          <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">

          <div class="search-input-group">
            <input type="text" name="search" class="form-control"
                 placeholder="Search by Name, Phone or ID"
                 value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-admin-primary btn-sm mb-0 px-4">Search</button>
          </div>
        </form>
      </div>
    </div>



        <div class="col-12">
      <div class="card table-card mb-4">
                <div class="card-body px-3 pt-3 pb-2">
                    <div class="table-responsive p-0">

                        <!-- tabs (visual only) -->
            <div class="table-toolbar">
              <div class="table-toolbar-nav">
                <button class="btn btn-sm btn-outline-primary tab-toggle <?= $active_tab == 'users' ? 'active' : '' ?>"
                                    onclick="window.location='admin_home?tab=users<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>'">
                                Employees
                </button>

                <button class="btn btn-sm btn-outline-primary tab-toggle <?= $active_tab == 'vendors' ? 'active' : '' ?>"
                                    onclick="window.location='admin_home?tab=vendors<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>'">
                                Dealers
                </button>

                <button class="btn btn-sm btn-outline-primary tab-toggle <?= $active_tab == 'subdealers' ? 'active' : '' ?>"
                             onclick="window.location='admin_home?tab=subdealers<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>'">
                             Sub Dealers
                </button>

                <button class="btn btn-sm btn-outline-primary tab-toggle <?= $active_tab == 'aces' ? 'active' : '' ?>"
                             onclick="window.location='admin_home?tab=aces<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>'">
                             ACE
                </button>
              </div>

              <?php if ($active_tab == 'subdealers'): ?>
                <div class="table-toolbar-action">
                  <button class="btn btn-admin-primary btn-sm" onclick="openSubDealerModal()">Add Sub Dealer</button>
                </div>
              <?php elseif ($active_tab == 'aces'): ?>
                <div class="table-toolbar-action">
                  <button class="btn btn-admin-primary btn-sm" onclick="openAceModal()">Add ACE</button>
                </div>
              <?php endif; ?>

            </div>

                        <!-- ============================
                             USERS TAB -> Daily Attendance Summary
                             ============================ -->
                        <div id="users" class="tab-pane <?= $active_tab == 'users' ? 'active' : '' ?>" style="<?= $active_tab == 'users' ? '' : 'display:none;' ?>">

                            <?php
                            


                            $count_users_sql = " SELECT COUNT(*) AS c FROM ( SELECT u.id, DATE(v.created_at) AS visit_date FROM employees u JOIN visits v ON u.id = v.user_id AND DATE(v.created_at) BETWEEN '$from' AND '$to' LEFT JOIN vendors ON v.vendor_id = vendors.vendor_id $where GROUP BY u.id, DATE(v.created_at) 

                                HAVING COUNT(CASE WHEN v.visit_type='vendor'    THEN 1 END) > 0
                                     OR COUNT(CASE WHEN v.visit_type='subdealer' THEN 1 END) > 0
                                     OR COUNT(CASE WHEN v.visit_type='ace'       THEN 1 END) > 0
                                     OR COUNT(CASE WHEN v.visit_type='customer'  THEN 1 END) > 0
                                     OR COUNT(CASE WHEN v.visit_type='other'     THEN 1 END) > 0
                                    ) AS x ";
                            


                           
  


                            $cnt_res = mysqli_query($conn, $count_users_sql);
                            $cnt_row = mysqli_fetch_assoc($cnt_res);
                            $total_users = intval($cnt_row['c']);
                            $total_pages_users = max(1, ceil($total_users / $per_page));

                            // main paginated query: one row per employee per day
                            $sql = "
                                SELECT 
                                    u.id,
                                    u.employee_id,
                                    u.name,
                                    u.phone,
                                    u.price_km,
                                    v.journey_id,
                                    DATE(v.created_at) AS visit_date,

                                    /* ================= VISIT COUNTS ================= */
                                    COUNT(CASE WHEN v.visit_type = 'vendor'    THEN 1 END) AS vendor_visits,
                                    COUNT(CASE WHEN v.visit_type = 'customer'  THEN 1 END) AS customer_visits,
                                    COUNT(CASE WHEN v.visit_type = 'other'     THEN 1 END) AS other_visits,
                                    COUNT(CASE WHEN v.visit_type = 'subdealer' THEN 1 END) AS subdealer_visits,
                                    COUNT(CASE WHEN v.visit_type = 'ace'       THEN 1 END) AS ace_visits,

                                    /* ================= KM ================= */
                                    CASE
                                        WHEN js.status = 'ended'
                                         AND DATE(js.start_time) = DATE(js.end_time)
                                            THEN js.total_km
                                        ELSE
                                            COALESCE(SUM(v.distance_from_prev_km), 0)
                                    END AS total_km,

                                    /* ================= AMOUNTS ================= */
                                    COALESCE(
                                        SUM(CASE WHEN v.visit_type = 'vendor' THEN ven.visit_price ELSE 0 END),
                                        0
                                    ) AS vendor_amount,

                                    COALESCE(
                                        SUM(CASE WHEN v.visit_type = 'subdealer' THEN sd.visit_price ELSE 0 END),
                                        0
                                    ) AS subdealer_amount,

                                    COALESCE(
                                        SUM(CASE WHEN v.visit_type = 'ace' THEN a.visit_price ELSE 0 END),
                                        0
                                    ) AS ace_amount,

                                    SUM(
                                        CASE 
                                            WHEN v.visit_type = 'customer' THEN 100
                                            WHEN v.visit_type = 'other'    THEN 50
                                            ELSE 0
                                        END
                                    ) AS customer_amount

                                FROM employees u

                                JOIN visits v 
                                    ON u.id = v.user_id
                                   AND DATE(v.created_at) BETWEEN '$from' AND '$to'

                                LEFT JOIN journey_start js
                                    ON js.id = v.journey_id

                                /* ===== PRICE SOURCE TABLES ===== */
                                LEFT JOIN vendors ven
                                    ON v.vendor_id = ven.vendor_id
                                   AND v.visit_type = 'vendor'

                                LEFT JOIN sub_dealers sd
                                    ON v.sub_dealer_id = sd.sub_dealer_id
                                   AND v.visit_type = 'subdealer'

                                LEFT JOIN aces a
                                    ON v.ace_id = a.ace_id
                                   AND v.visit_type = 'ace'

                                $where

                                GROUP BY u.id, DATE(v.created_at), js.id

                                HAVING
                                (
                                    vendor_visits +
                                    customer_visits +
                                    other_visits +
                                    subdealer_visits +
                                    ace_visits
                                ) > 0

                                ORDER BY visit_date DESC, u.id ASC

                                LIMIT $per_page OFFSET $offset_users



                            ";

                            $result = mysqli_query($conn, $sql);
                            ?>

                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Name / ID</th>
                                        <th>Phone</th>
                                        <th>Visits</th>
                                        <th>Total KM</th>
                                        <th>Amount </th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                if ($result && mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        $vendor_visits = intval($row['vendor_visits']);
                                        $subdealer_visits = intval($row['subdealer_visits']);
                                        $ace_visits       = intval($row['ace_visits']);
                                        $customer_visits = intval($row['customer_visits']);
                                        $other_visits = (int)$row['other_visits'];
                                        $km = (float)$row['total_km'];
                                        $vendor_amount = (float)$row['vendor_amount'];
                                        $subdealer_amount = (float)$row['subdealer_amount'];
                                        $ace_amount       = (float)$row['ace_amount'];
                                        $customer_amount = (float)$row['customer_amount'];
                                        // add KM pay
                                        $km_pay = $km * (float)$row['price_km'];
                                        $total_amount = $vendor_amount + $subdealer_amount + $ace_amount + $customer_amount + $km_pay;
                                        $total_visits = $vendor_visits + $subdealer_visits + $ace_visits + $customer_visits + $other_visits;
                    
                                        $date_for_link = $row['visit_date'];
                                        $emp_id = $row['id'];

                                        // prepare user json for edit button
                                        $user_json_arr = [
                                            'id' => $row['id'],
                                            'name' => $row['name'],
                                            'phone' => $row['phone'],
                                            'price_km' => $row['price_km']
                                        ];
                                        $user_json = htmlspecialchars(json_encode($user_json_arr, JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');

                                        echo "<tr>
                                                <td>" . htmlspecialchars(date("d M Y", strtotime($row['visit_date']))) . "</td>
                                                <td>
                                                  <div class='d-flex px-2 py-1'>
                                                    <div><img src='assets/img/logos/user.png' class='avatar me-3' alt='user'></div>
                                                    <div class='d-flex flex-column justify-content-center'>
                                                      <h6 class='mb-0 text-sm'>".htmlspecialchars($row['name'])."</h6>
                                                      <p class='text-xs text-secondary mb-0'>".htmlspecialchars($row['employee_id'])."</p>
                                                    </div>
                                                  </div>
                                                </td>
                                                <td><p class='text-xs mb-0'>".htmlspecialchars($row['phone'])."</p></td>
                                                <td><p class='text-xs mb-0'>{$total_visits}</p></td>
                                                <td><p class='text-xs mb-0'>".number_format($km,2)." KM</p></td>
                                                <td><p class='text-xs mb-0'>₹".number_format($total_amount,2)."</p></td>
                                                <td class='align-middle'>
                                                  <a href='visit_details?id={$emp_id}&date={$date_for_link}' class='btn btn-admin-secondary btn-sm'><i class='bi bi-eye-fill'></i></a>
                                                  <button class='btn btn-admin-primary btn-sm' onclick='editUser(JSON.parse(this.dataset.user))' data-user=\"{$user_json}\" style='margin-left:6px;'><i class='bi bi-pencil-square'></i></button>
                                                  <a href='delete_journey?journey_id=".$row['journey_id']."'
                                                     class='btn btn-danger btn-sm'
                                                     style='margin-left:6px;'
                                                     onclick=\"return confirm('Delete this journey?')\">
                                                    <i class='bi bi-trash-fill'></i>
                                                  </a>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center table-empty'>No records found for the selected date range.</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>

                            <!-- Users pagination -->
                            <?php if ($total_pages_users > 1): ?>
                                <nav aria-label="Users pagination">
                                    <ul class="pagination justify-content-end">
                                        <?php
                                        // base query params for pagination (preserve filters & tab & search & from/to)
                                        $base_q = [];
                                        if ($search !== '') $base_q['search'] = $search;
                                        $base_q['tab'] = 'users';
                                        $base_q['from'] = $from;
                                        $base_q['to'] = $to;

                                        // previous
                                        $prev_page = $page_users - 1;
                                        $prev_attrs = $base_q;
                                        $prev_attrs['page_users'] = $prev_page;
                                        $prev_url = '?' . http_build_query($prev_attrs);
                                        ?>
                                        <li class="page-item <?= $page_users <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page_users <= 1 ? '#' : $prev_url ?>">Prev</a>
                                        </li>

                                        <?php
                                        // page numbers
                                        $start = max(1, $page_users - 3);
                                        $end = min($total_pages_users, $page_users + 3);
                                        for ($p = $start; $p <= $end; $p++):
                                            $attrs = $base_q;
                                            $attrs['page_users'] = $p;
                                            $url = '?' . http_build_query($attrs);
                                        ?>
                                            <li class="page-item <?= $p == $page_users ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= $url ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php
                                        $next_page = $page_users + 1;
                                        $next_attrs = $base_q;
                                        $next_attrs['page_users'] = $next_page;
                                        $next_url = '?' . http_build_query($next_attrs);
                                        ?>
                                        <li class="page-item <?= $page_users >= $total_pages_users ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page_users >= $total_pages_users ? '#' : $next_url ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                        </div> <!-- end users tab -->

                        <!-- ============================
                             VENDORS TAB (paginated) - unchanged logic
                             ============================ -->
                        <div id="vendors" class="tab-pane <?= $active_tab == 'vendors' ? 'active' : '' ?>" style="<?= $active_tab == 'vendors' ? '' : 'display:none;' ?>">
                            <?php
                            // count vendors
                            $count_v_sql = "SELECT COUNT(*) AS c FROM vendors $where_vendor";
                            $cv_res = mysqli_query($conn, $count_v_sql);
                            $cv_row = mysqli_fetch_assoc($cv_res);
                            $total_vendors = intval($cv_row['c']);
                            $total_pages_vendors = max(1, ceil($total_vendors / $per_page));

                            // main paginated query for vendors
                            $vquery = mysqli_query($conn, "SELECT * FROM vendors $where_vendor ORDER BY vendor_id DESC LIMIT " . intval($per_page) . " OFFSET " . intval($offset_vendors));
                            ?>

                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr><th>DL Code</th><th>Name</th><th>Radius</th><th>Address</th><th>Visit Price</th><th>Lat</th><th>Lng</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                <?php
                                    if ($vquery && mysqli_num_rows($vquery) > 0) {

                                        

                                        while($v = mysqli_fetch_assoc($vquery)) {
                                          $vendor_json = htmlspecialchars(json_encode($v, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');

                                          echo "<tr>
                                                  <td>".htmlspecialchars($v['dealer_code'])."</td>
                                                  <td>".htmlspecialchars($v['name'])."</td>
                                                  <td>".htmlspecialchars($v['area'])."</td>
                                                  <td>".htmlspecialchars($v['address'])."</td>
                                                  <td>₹".number_format($v['visit_price'],2)."</td>
                                                  <td>{$v['lat']}</td>
                                                  <td>{$v['lng']}</td>
                                                  <td class='align-middle'>
                                                    <button class='btn btn-admin-primary btn-sm' onclick='editVendor(JSON.parse(this.dataset.vendor))' data-vendor='{$vendor_json}'>Edit</button>
                                                    <a href='delete_vendor?id={$v['vendor_id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Delete this vendor?\")' style='margin-left:6px;'>Delete</a>
                                                  </td>
                                                </tr>";

                                               
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center table-empty'>No vendors found.</td></tr>";
                                    }
                                ?>
                                </tbody>
                            </table>

                            <!-- Vendors pagination -->
                            <?php if ($total_pages_vendors > 1): ?>
                                <nav aria-label="Vendors pagination">
                                    <ul class="pagination justify-content-end">
                                        <?php
                                        $base_qv = [];
                                        if ($search !== '') $base_qv['search'] = $search;
                                        $base_qv['tab'] = 'vendors';
                                        $base_qv['from'] = $from;
                                        $base_qv['to'] = $to;
                                        $prev_v = $page_vendors - 1;
                                        $prev_v_attrs = $base_qv;
                                        $prev_v_attrs['page_vendors'] = $prev_v;
                                        $prev_v_url = '?' . http_build_query($prev_v_attrs);
                                        ?>
                                        <li class="page-item <?= $page_vendors <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page_vendors <= 1 ? '#' : $prev_v_url ?>">Prev</a>
                                        </li>

                                        <?php
                                        $start_v = max(1, $page_vendors - 3);
                                        $end_v = min($total_pages_vendors, $page_vendors + 3);
                                        for ($p = $start_v; $p <= $end_v; $p++):
                                            $attrs_v = $base_qv;
                                            $attrs_v['page_vendors'] = $p;
                                            $urlv = '?' . http_build_query($attrs_v);
                                        ?>
                                            <li class="page-item <?= $p == $page_vendors ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= $urlv ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php
                                        $next_v = $page_vendors + 1;
                                        $next_v_attrs = $base_qv;
                                        $next_v_attrs['page_vendors'] = $next_v;
                                        $next_v_url = '?' . http_build_query($next_v_attrs);
                                        ?>
                                        <li class="page-item <?= $page_vendors >= $total_pages_vendors ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page_vendors >= $total_pages_vendors ? '#' : $next_v_url ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                        </div> <!-- end vendors tab -->


                        <!-- ============================
                             SUB DEALERS TAB
                             ============================ -->
                        <div id="subdealers"
                                 class="tab-pane <?= $active_tab == 'subdealers' ? 'active' : '' ?>"
                                 style="<?= $active_tab == 'subdealers' ? '' : 'display:none;' ?>">

                            <?php
                            // SEARCH FILTER (same pattern as vendors)
                            $where_sd = "";
                            if ($search !== "") {
                                $safe = mysqli_real_escape_string($conn, $search);
                                $where_sd = "WHERE 
                                    sub_dealer_code LIKE '%$safe%' 
                                    OR name LIKE '%$safe%' 
                                    OR phone LIKE '%$safe%' 
                                    OR area LIKE '%$safe%'";
                            }

                            // QUERY
                            $sd_q = mysqli_query(
                                $conn,
                                "SELECT * FROM sub_dealers $where_sd ORDER BY sub_dealer_id DESC"
                            );
                            ?>

                            <table class="table align-items-center mb-0">
                            <thead>
                            <tr>
                              <th>ID</th>
                              <th>Name</th>
                              <th>Phone</th>
                              <th>Area</th>
                              <th>Price</th>
                              <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php if ($sd_q && mysqli_num_rows($sd_q) > 0): ?>
                            <?php while ($sd = mysqli_fetch_assoc($sd_q)):
                                $sd_json = htmlspecialchars(json_encode($sd), ENT_QUOTES);
                            ?>
                            <tr>
                              <td><?= htmlspecialchars($sd['sub_dealer_code']) ?></td>
                              <td><?= htmlspecialchars($sd['name']) ?></td>
                              <td><?= htmlspecialchars($sd['phone']) ?></td>
                              <td><?= htmlspecialchars($sd['area']) ?></td>
                              <td>₹<?= number_format((float)$sd['visit_price'], 2) ?></td>
                              <td>
                                <button class="btn btn-admin-primary btn-sm"
                                    onclick="editSubDealer(JSON.parse('<?= $sd_json ?>'))">
                                    Edit
                                </button>

                                <a href="delete_sub_dealer?id=<?= (int)$sd['sub_dealer_id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete Sub Dealer?')">
                                   Delete
                                </a>
                              </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                              <td colspan="6" class="text-center table-empty">
                                No Sub Dealers found
                              </td>
                            </tr>
                            <?php endif; ?>

                            </tbody>
                            </table>
                            </div>


                        <!-- SUB DEALERS TAB END -->

                        <!-- ACE start -->

                            <div id="aces"
                             class="tab-pane <?= $active_tab == 'aces' ? 'active' : '' ?>"
                             style="<?= $active_tab == 'aces' ? '' : 'display:none;' ?>">

                        <?php
                        $where_ace = "";
                        if ($search !== "") {
                            $safe = mysqli_real_escape_string($conn, $search);
                            $where_ace = "WHERE 
                                ace_code LIKE '%$safe%' 
                                OR name LIKE '%$safe%' 
                                OR phone LIKE '%$safe%' 
                                OR area LIKE '%$safe%'";
                        }

                        $ace_q = mysqli_query(
                            $conn,
                            "SELECT * FROM aces $where_ace ORDER BY ace_id DESC"
                        );
                        ?>

                        <table class="table align-items-center mb-0">
                        <thead>
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Phone</th>
                          <th>Area</th>
                          <th>Price</th>
                          <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php if ($ace_q && mysqli_num_rows($ace_q) > 0): ?>
                        <?php while ($a = mysqli_fetch_assoc($ace_q)):
                            $ace_json = htmlspecialchars(json_encode($a), ENT_QUOTES);
                        ?>
                        <tr>
                          <td><?= htmlspecialchars($a['ace_code']) ?></td>
                          <td><?= htmlspecialchars($a['name']) ?></td>
                          <td><?= htmlspecialchars($a['phone']) ?></td>
                          <td><?= htmlspecialchars($a['area']) ?></td>
                          <td>₹<?= number_format((float)$a['visit_price'], 2) ?></td>
                          <td>
                            <button class="btn btn-admin-primary btn-sm"
                                onclick="editAce(JSON.parse('<?= $ace_json ?>'))">
                                Edit
                            </button>

                            <a href="delete_ace?id=<?= (int)$a['ace_id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete ACE?')">
                               Delete
                            </a>
                          </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                          <td colspan="6" class="text-center table-empty">
                            No ACE found
                          </td>
                        </tr>
                        <?php endif; ?>

                        </tbody>
                        </table>
                        </div>
                        <!-- ACE end -->

                    </div> <!-- .table-responsive -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD / EDIT USER MODAL (kept original custom modal to avoid touching user flow) -->
<div class="modal-custom" id="userModal">
  <div class="modal-content">
    <h3 id="userModalTitle">Add / Edit User</h3>
    <form id="userForm" method="POST" action="save_user">
      <input type="hidden" name="id" id="id">
      <label>Name</label>
      <input type="text" name="name" id="uname" class="form-control" required>
      <label style="margin-top:8px;">Phone</label>
      <input type="text" name="phone" id="uphone" class="form-control" required>
      <label style="margin-top:8px;">Price per KM (₹)</label>
      <input type="number" step="0.01" name="price_km" id="uprice" class="form-control" required>
      <button class="save" type="submit">Save</button>
      <button class="close" type="button" onclick="closeUserModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- ADD / EDIT VENDOR MODAL -->
<div class="modal fade" id="vendorModal" tabindex="-1" aria-labelledby="vendorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="vendorModalLabel">Add / Edit Dealer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="vendorForm" method="POST" action="save_vendor">

          <input type="hidden" name="vendor_id" id="vendor_id">

          <div class="row g-2">

            <div class="col-md-6 mb-2">
              <label class="form-label">Dealer Name</label>
              <input type="text" name="name" id="vname" class="form-control" required>
            </div>

            <div class="col-md-6 mb-2">
              <label class="form-label">Dealer Code</label>
              <input type="text" name="dealer_code" id="vcode" class="form-control" required>
            </div>

          </div>

          <div class="row g-2">

            <div class="col-md-6 mb-2">
              <label class="form-label">Radius (in meters)</label>
              <input type="text" name="area" id="varea" class="form-control" required>
            </div>

            <div class="col-md-6 mb-2">
              <label class="form-label">ASO Name</label>
              <input type="text" name="aso_name" id="vaso" class="form-control" required>
            </div>

          </div>

          <div class="mb-2">
            <label class="form-label">Address</label>
            <textarea name="address" id="vaddress" class="form-control" required></textarea>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Visit Price (₹)</label>
              <input type="number" step="0.01" name="visit_price" id="vprice" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Search Location</label>
              <input type="text" id="search-box" class="form-control" placeholder="Search location...">
            </div>
          </div>

          <div id="map" class="mt-3"></div>

          <div class="row g-2 mt-2">
            <div class="col-md-6">
              <label class="form-label">Latitude</label>
              <input type="text" name="lat" id="vlat" class="form-control"  required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Longitude</label>
              <input type="text" name="lng" id="vlng" class="form-control"  required>
            </div>
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="vendorForm" class="btn btn-primary">Save</button>
      </div>

    </div>
  </div>
</div>


<!-- ADD / EDIT SUB DEALER MODAL -->
<div class="modal fade" id="subDealerModal" tabindex="-1" aria-labelledby="subDealerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- MODAL HEADER -->
      <div class="modal-header">
        <h5 class="modal-title" id="subDealerModalLabel">Add / Edit Sub Dealer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- MODAL BODY -->
      <div class="modal-body">
        <form method="POST" action="save_sub_dealer" id="subDealerForm">

          <input type="hidden" name="sub_dealer_id" id="sd_id">

          <div class="row g-2">

            <div class="col-md-6 mb-2">
              <label class="form-label">Sub Dealer ID</label>
              <input type="text" name="sub_dealer_code" id="sd_code"
                     class="form-control" required>
            </div>

            <div class="col-md-6 mb-2">
              <label class="form-label">Sub Dealer Name</label>
              <input type="text" name="name" id="sd_name"
                     class="form-control" required>
            </div>

          </div>

          <div class="row g-2">

            <div class="col-md-6 mb-2">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" id="sd_phone"
                     class="form-control" maxlength="10" required>
            </div>

            <div class="col-md-6 mb-2">
              <label class="form-label">Area</label>
              <input type="text" name="area" id="sd_area"
                     class="form-control">
            </div>

          </div>

          <div class="mb-2">
            <label class="form-label">Address</label>
            <textarea name="address" id="sd_address"
                      class="form-control"></textarea>
          </div>

          <div class="row g-2">

            <div class="col-md-6">
              <label class="form-label">Visit Price (₹)</label>
              <input type="number" step="0.01"
                     name="visit_price" id="sd_price"
                     class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Search Location</label>
              <input type="text" id="sd-search-box"
                     class="form-control"
                     placeholder="Search location...">
            </div>

          </div>

          <!-- MAP -->
          <div id="sd-map" class="mt-3" style="width:100%;height:320px;border-radius:6px;"></div>

          <div class="row g-2 mt-2">

            <div class="col-md-6">
              <label class="form-label">Latitude</label>
              <input type="text" name="lat" id="sd_lat"
                     class="form-control" >
            </div>

            <div class="col-md-6">
              <label class="form-label">Longitude</label>
              <input type="text" name="lng" id="sd_lng"
                     class="form-control" >
            </div>

          </div>

        </form>
      </div>

      <!-- MODAL FOOTER -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="submit" form="subDealerForm" class="btn btn-primary">
          Save
        </button>
      </div>

    </div>
  </div>
</div>


<!-- ADD / EDIT ACE MODAL -->
<div class="modal fade" id="aceModal" tabindex="-1" aria-labelledby="aceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- MODAL HEADER -->
      <div class="modal-header">
        <h5 class="modal-title" id="aceModalLabel">Add / Edit ACE</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- MODAL BODY -->
      <div class="modal-body">
        <form method="POST" action="save_ace" id="aceForm">

          <input type="hidden" name="ace_id" id="ace_id">

          <div class="row g-2">

            <div class="col-md-6 mb-2">
              <label class="form-label">ACE ID</label>
              <input type="text" name="ace_code" id="ace_code"
                     class="form-control" required>
            </div>

            <div class="col-md-6 mb-2">
              <label class="form-label">ACE Name</label>
              <input type="text" name="name" id="ace_name"
                     class="form-control" required>
            </div>

          </div>

          <div class="row g-2">

            <div class="col-md-6 mb-2">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" id="ace_phone"
                     class="form-control" maxlength="10" required>
            </div>

            <div class="col-md-6 mb-2">
              <label class="form-label">Area</label>
              <input type="text" name="area" id="ace_area"
                     class="form-control">
            </div>

          </div>

          <div class="mb-2">
            <label class="form-label">Address</label>
            <textarea name="address" id="ace_address"
                      class="form-control"></textarea>
          </div>

          <div class="row g-2">

            <div class="col-md-6">
              <label class="form-label">Visit Price (₹)</label>
              <input type="number" step="0.01"
                     name="visit_price" id="ace_price"
                     class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Search Location</label>
              <input type="text" id="ace-search-box"
                     class="form-control"
                     placeholder="Search location...">
            </div>

          </div>

          <!-- MAP -->
          <div id="ace-map" class="mt-3"
               style="width:100%;height:320px;border-radius:6px;"></div>

          <div class="row g-2 mt-2">

            <div class="col-md-6">
              <label class="form-label">Latitude</label>
              <input type="text" name="lat" id="ace_lat"
                     class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Longitude</label>
              <input type="text" name="lng" id="ace_lng"
                     class="form-control">
            </div>

          </div>

        </form>
      </div>

      <!-- MODAL FOOTER -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="submit" form="aceForm" class="btn btn-primary">
          Save
        </button>
      </div>

    </div>
  </div>
</div>



<script>
// Tabs
function showTab(tab) {
  document.querySelectorAll('.tab-pane').forEach(div => div.style.display = 'none');
  const el = document.getElementById(tab);
  if (el) el.style.display = 'block';
}

// Site select redirect
function redirectToSite() {
    var site = document.getElementById('site').value;
    if (site) window.location.href = site;
}

// USER modal helpers
function openUserModal() {
  document.getElementById('userForm').reset();
  document.getElementById('id').value = '';
  document.getElementById('userModalTitle').innerText = 'Add New Employee';
  document.getElementById('userForm').action = 'save_user';
  document.getElementById('userModal').style.display = 'flex';
}

function editUser(user) {
  document.getElementById('userModalTitle').innerText = 'Edit Employee';
  document.getElementById('id').value = user.id;
  document.getElementById('uname').value = user.name;
  document.getElementById('uphone').value = user.phone;
  document.getElementById('uprice').value = user.price_km;
  document.getElementById('userForm').action = 'update_user';
  document.getElementById('userModal').style.display = 'flex';
}

function closeUserModal() {
  document.getElementById('userModal').style.display = 'none';
}

// ---------------------------
// VENDOR MODAL HELPERS
// ---------------------------
let vendorModalInstance = null;

function openVendorModal() {
  const form = document.getElementById('vendorForm');
  form.reset();
  document.getElementById('vcode').value = '';
  document.getElementById('vaso').value = '';

  document.getElementById('vendor_id').value = '';
  document.getElementById('vendorForm').action = 'save_vendor';
  document.getElementById('vendorModalLabel').innerText = 'Add New Dealer';


  const modalEl = document.getElementById('vendorModal');
  vendorModalInstance = new bootstrap.Modal(modalEl);
  vendorModalInstance.show();

  modalEl.addEventListener('shown.bs.modal', function handler() {
    initMap(20.5937, 78.9629); // default India center
    modalEl.removeEventListener('shown.bs.modal', handler);
  });
}

function editVendor(vendor) {
  document.getElementById('vendorForm').action = 'save_vendor';

  document.getElementById('vendor_id').value = vendor.vendor_id || '';
  document.getElementById('vname').value = vendor.name || '';
  document.getElementById('vcode').value = vendor.dealer_code || '';
  document.getElementById('vaso').value = vendor.aso_name || '';

  document.getElementById('varea').value = vendor.area || '';
  document.getElementById('vaddress').value = vendor.address || '';
  document.getElementById('vprice').value = vendor.visit_price || '';
  document.getElementById('vlat').value = vendor.lat || '';
  document.getElementById('vlng').value = vendor.lng || '';

  document.getElementById('vendorModalLabel').innerText = 'Edit Dealer';

  const modalEl = document.getElementById('vendorModal');
  vendorModalInstance = new bootstrap.Modal(modalEl);
  vendorModalInstance.show();

  modalEl.addEventListener('shown.bs.modal', function handler() {
    initMap(parseFloat(vendor.lat), parseFloat(vendor.lng));
    modalEl.removeEventListener('shown.bs.modal', handler);
  });
}

function closeVendorModal() {
  if (vendorModalInstance) vendorModalInstance.hide();
}

// ---------------------------
// PERFECT ACCURACY MAP SYSTEM
// ---------------------------

let vendorMap, vendorMarker, vendorGeocoder;

function initMap(lat = 20.5937, lng = 78.9629) {

  lat = parseFloat(lat) || 20.5937;
  lng = parseFloat(lng) || 78.9629;

  const center = { lat, lng };

  // Always create fresh map for accuracy (no reuse)
  vendorMap = new google.maps.Map(document.getElementById("map"), {
    center: center,
    zoom: 16,
    gestureHandling: "greedy"
  });

  vendorMarker = new google.maps.Marker({
    map: vendorMap,
    draggable: true,
    position: center
  });

  vendorGeocoder = new google.maps.Geocoder();

  // Accurate Autocomplete (better than SearchBox)
  const input = document.getElementById("search-box");
  if (input) input.setAttribute("autocomplete", "off");

  const autocomplete = new google.maps.places.Autocomplete(input, {
    fields: ["geometry", "formatted_address"],
    types: ["establishment", "geocode"],
    strictBounds: false
  });

  autocomplete.bindTo("bounds", vendorMap);

  autocomplete.addListener("place_changed", function () {
    const place = autocomplete.getPlace();
    if (!place.geometry) return;

    vendorMap.setCenter(place.geometry.location);
    vendorMap.setZoom(18);

    vendorMarker.setPosition(place.geometry.location);

    // Exact Google-provided coordinates (8 decimal places)
    document.getElementById("vlat").value = place.geometry.location.lat().toFixed(8);
    document.getElementById("vlng").value = place.geometry.location.lng().toFixed(8);

    if (place.formatted_address) {
      document.getElementById("vaddress").value = place.formatted_address;
    }
  });

  // Manual marker drag
  vendorMarker.addListener("dragend", function (event) {
    const lat = event.latLng.lat().toFixed(8);
    const lng = event.latLng.lng().toFixed(8);

    document.getElementById("vlat").value = lat;
    document.getElementById("vlng").value = lng;

    reverseGeocode(event.latLng);
  });

  // Map click to set exact point
  vendorMap.addListener("click", function (event) {
    vendorMarker.setPosition(event.latLng);

    const lat = event.latLng.lat().toFixed(8);
    const lng = event.latLng.lng().toFixed(8);

    document.getElementById("vlat").value = lat;
    document.getElementById("vlng").value = lng;

    reverseGeocode(event.latLng);
  });
}

function reverseGeocode(latlng) {
  if (!vendorGeocoder) vendorGeocoder = new google.maps.Geocoder();
  vendorGeocoder.geocode({ location: latlng }, (results, status) => {
    if (status === "OK" && results[0]) {
      document.getElementById("vaddress").value = results[0].formatted_address;
    }
  });
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let activeTab = "<?= $active_tab ?>";
    showTab(activeTab);
});
</script>

<script>
let subDealerModalInstance = null;

function openSubDealerModal() {
    document.getElementById('subDealerForm').reset();
    document.getElementById('sd_id').value = '';
    document.getElementById('sd_lat').value = '';
    document.getElementById('sd_lng').value = '';

    const modalEl = document.getElementById('subDealerModal');
    subDealerModalInstance = new bootstrap.Modal(modalEl);
    subDealerModalInstance.show();

    modalEl.addEventListener('shown.bs.modal', function handler() {
        initSubDealerMap(20.5937, 78.9629); // India default
        modalEl.removeEventListener('shown.bs.modal', handler);
    });
}

function editSubDealer(d) {
    sd_id.value = d.sub_dealer_id || '';
    sd_code.value = d.sub_dealer_code || '';
    sd_name.value = d.name || '';
    sd_phone.value = d.phone || '';
    sd_area.value = d.area || '';
    sd_price.value = d.visit_price || '';
    sd_address.value = d.address || '';
    sd_lat.value = d.lat || '';
    sd_lng.value = d.lng || '';

    const modalEl = document.getElementById('subDealerModal');
    subDealerModalInstance = new bootstrap.Modal(modalEl);
    subDealerModalInstance.show();

    modalEl.addEventListener('shown.bs.modal', function handler() {
        initSubDealerMap(
            parseFloat(d.lat) || 20.5937,
            parseFloat(d.lng) || 78.9629
        );
        modalEl.removeEventListener('shown.bs.modal', handler);
    });
}


function initSubDealerMap(lat = 20.5937, lng = 78.9629) {

  const center = { lat: lat, lng: lng };

  const map = new google.maps.Map(document.getElementById("sd-map"), {
    center: center,
    zoom: 16,
    gestureHandling: "greedy"
  });

  const marker = new google.maps.Marker({
    map: map,
    position: center,
    draggable: true
  });

  const input = document.getElementById("sd-search-box");
  const autocomplete = new google.maps.places.Autocomplete(input);
  autocomplete.bindTo("bounds", map);

  autocomplete.addListener("place_changed", function () {
    const place = autocomplete.getPlace();
    if (!place.geometry) return;

    map.setCenter(place.geometry.location);
    map.setZoom(18);
    marker.setPosition(place.geometry.location);

    sd_lat.value = place.geometry.location.lat().toFixed(8);
    sd_lng.value = place.geometry.location.lng().toFixed(8);
    sd_address.value = place.formatted_address || '';
  });

  marker.addListener("dragend", function (e) {
    sd_lat.value = e.latLng.lat().toFixed(8);
    sd_lng.value = e.latLng.lng().toFixed(8);
  });
}

</script>


<script>
let aceModalInstance = null;
let aceMap = null;
let aceMarker = null;

function openAceModal() {
    document.getElementById('aceForm').reset();

    ace_id.value  = '';
    ace_lat.value = '';
    ace_lng.value = '';

    const modalEl = document.getElementById('aceModal');
    aceModalInstance = new bootstrap.Modal(modalEl);
    aceModalInstance.show();

    modalEl.addEventListener('shown.bs.modal', function handler() {
        initAceMap(20.5937, 78.9629); // India default
        modalEl.removeEventListener('shown.bs.modal', handler);
    });
}

function editAce(a) {
    ace_id.value      = a.ace_id || '';
    ace_code.value    = a.ace_code || '';
    ace_name.value    = a.name || '';
    ace_phone.value   = a.phone || '';
    ace_area.value    = a.area || '';
    ace_price.value   = a.visit_price || '';
    ace_address.value = a.address || '';
    ace_lat.value     = a.lat || '';
    ace_lng.value     = a.lng || '';

    const modalEl = document.getElementById('aceModal');
    aceModalInstance = new bootstrap.Modal(modalEl);
    aceModalInstance.show();

    modalEl.addEventListener('shown.bs.modal', function handler() {
        initAceMap(
            parseFloat(a.lat) || 20.5937,
            parseFloat(a.lng) || 78.9629
        );
        modalEl.removeEventListener('shown.bs.modal', handler);
    });
}

/* =========================
   ACE GOOGLE MAP (FINAL)
========================= */
function initAceMap(lat = 20.5937, lng = 78.9629) {

    const mapEl = document.getElementById("ace-map");

    // 🔥 CLEAR OLD MAP (VERY IMPORTANT)
    mapEl.innerHTML = '';

    const center = { lat, lng };

    aceMap = new google.maps.Map(mapEl, {
        center: center,
        zoom: 16,
        gestureHandling: "greedy"
    });

    aceMarker = new google.maps.Marker({
        map: aceMap,
        position: center,
        draggable: true
    });

    const input = document.getElementById("ace-search-box");
    const autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo("bounds", aceMap);

    autocomplete.addListener("place_changed", function () {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;

        aceMap.setCenter(place.geometry.location);
        aceMap.setZoom(18);
        aceMarker.setPosition(place.geometry.location);

        ace_lat.value = place.geometry.location.lat().toFixed(8);
        ace_lng.value = place.geometry.location.lng().toFixed(8);
        ace_address.value = place.formatted_address || '';
    });

    aceMarker.addListener("dragend", function (e) {
        ace_lat.value = e.latLng.lat().toFixed(8);
        ace_lng.value = e.latLng.lng().toFixed(8);
    });
}
</script>



<?php
// If search requested and we have vendor matches, open vendors tab automatically
$vendor_count_check = mysqli_query($conn, "SELECT COUNT(*) AS c FROM vendors $where_vendor");
$vendor_row = mysqli_fetch_assoc($vendor_count_check);

if ($search !== "" && $vendor_row['c'] > 0) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showTab('vendors');
        });
  </script>";
}


include("footer.php");
?>
