<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

if (!isset($_SESSION['employee_id'])) {
    header('Location:/emp/index');
    exit;
}

date_default_timezone_set('Asia/Kolkata');

/* CSRF */
require_once __DIR__ . '/worksheet/csrf.php';
$csrf = csrf_token();

/* Load common layout */
include("header.php");
?>

<script>
    window.CSRF_TOKEN = "<?= $csrf ?>";
    window.IS_ADMIN = false;
    window.CAN_MARK = true;
</script>

<link rel="stylesheet" href="/emp/worksheet/css/style.css">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    .worksheet-page-shell {
        min-height: calc(100vh - 120px);
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem;
    }

    .worksheet-main-card {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 28px;
        background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .worksheet-main-body {
        padding: 1.3rem !important;
        background: transparent;
    }

    .worksheet-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .worksheet-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.14rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .worksheet-today-btn {
        min-height: 42px;
        padding: 0.68rem 1.15rem;
        border: 0;
        border-radius: 14px;
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .worksheet-calendar-panel {
        padding: 1rem;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.55);
    }

    #calendar-root > .d-flex {
        gap: 14px;
    }

    .day-cell {
        min-height: 114px;
        padding: 0.95rem 0.9rem 0.85rem;
        border-radius: 20px;
        border: 1px solid rgba(203, 213, 225, 0.9) !important;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .day-cell:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
    }

    .day-cell .date {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        min-height: 34px;
        padding: 0 0.45rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.72);
        color: #0f172a;
        font-size: 0.88rem;
        font-weight: 800;
        box-shadow: inset 0 0 0 1px rgba(226, 232, 240, 0.9);
    }

    .day-cell .actions {
        top: 10px;
        right: 10px;
        gap: 0.35rem;
    }

    .day-cell .actions button {
        border-radius: 9px;
        min-width: 30px;
        height: 30px;
        box-shadow: none;
    }

    .day-cell.today {
        border-color: rgba(18, 59, 118, 0.28) !important;
        box-shadow: 0 18px 34px rgba(18, 59, 118, 0.16);
    }

    .calendar-legend {
        margin-top: 1rem !important;
        margin-bottom: 0 !important;
        gap: 0.65rem;
    }

    .legend-item {
        padding: 0.45rem 0.72rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(226, 232, 240, 0.95);
        color: #475569;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 999px;
    }

    #worksheetModal .modal-dialog.modal-xl {
        max-width: min(1180px, calc(100vw - 2rem));
    }

    #worksheetModal .modal-content {
        max-height: calc(100vh - 3rem);
        overflow: hidden;
        background: #ffffff !important;
        border: 1px solid #dbe4f0;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    #worksheetModal .modal-header,
    #worksheetModal .modal-body {
        background: #ffffff !important;
    }

    #worksheetModal .modal-body {
        max-height: calc(100vh - 220px);
        overflow-y: auto;
        overflow-x: hidden;
        padding-bottom: 6.5rem;
        background: #f8fafc !important;
    }

    #worksheetModal .modal-footer {
        position: sticky;
        bottom: 0;
        background: #ffffff !important;
        backdrop-filter: blur(10px);
        z-index: 2;
        border-top: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 -8px 20px rgba(15, 23, 42, 0.08);
    }

    #worksheetModal .hour-box {
        margin-bottom: 14px;
        padding: 0;
        border-radius: 16px;
        background: #ffffff !important;
        border: 1px solid #dbe4f0;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    #worksheetModal #hoursGrid {
        row-gap: 14px;
    }

    #worksheetModal #hoursGrid .form-label {
        display: block;
        margin: 0;
        padding: 12px 14px 8px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        background: #ffffff;
        border-bottom: 1px solid #e5edf6;
    }

    #worksheetModal #hoursGrid .hour-box.lunch .form-label {
        background: #fff7e6;
        color: #9a6700;
    }

    #worksheetModal #hoursGrid .editor {
        padding: 10px 12px 12px;
        background: #ffffff !important;
    }

    #worksheetModal #hoursGrid .ql-toolbar.ql-snow {
        padding: 8px 10px;
        border: 0;
        border-bottom: 1px solid #e5edf6;
        border-radius: 0;
        background: #f8fafc !important;
    }

    #worksheetModal #hoursGrid .ql-container.ql-snow {
        min-height: 120px;
        border: 0;
        border-radius: 0;
        background: #ffffff !important;
    }

    #worksheetModal #hoursGrid .ql-editor {
        min-height: 110px;
        font-size: 15px;
        line-height: 1.7;
        color: #334155;
        background: #ffffff !important;
    }

    @media (max-width: 767.98px) {
        .worksheet-page-shell {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .worksheet-main-card {
            border-radius: 22px;
        }

        .worksheet-main-body {
            padding: 0.95rem !important;
        }

        .worksheet-header-row {
            margin-bottom: 0.9rem;
        }

        .worksheet-title {
            font-size: 0.98rem;
            line-height: 1.24;
        }

        .worksheet-today-btn {
            min-height: 36px;
            padding: 0.5rem 0.72rem;
            border-radius: 12px;
            font-size: 0.6rem;
            letter-spacing: 0.04em;
        }

        .worksheet-calendar-panel {
            padding: 0.72rem;
            border-radius: 18px;
        }

        #calendar-root > .d-flex {
            gap: 10px;
        }

        .day-cell {
            min-height: 96px;
            padding: 3.25rem 0.72rem 0.72rem;
            border-radius: 16px;
        }

        .day-cell .date {
            position: absolute;
            top: 0.72rem;
            left: 0.72rem;
            min-width: 30px;
            min-height: 30px;
            font-size: 0.78rem;
        }

        .day-cell .actions {
            top: 2.35rem;
            left: 0.72rem;
            right: auto;
        }

        .day-cell .actions button {
            min-width: 26px;
            height: 26px;
            font-size: 0.68rem;
        }

        .legend-item {
            padding: 0.38rem 0.62rem;
            font-size: 0.68rem;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
        }
    }
</style>

<!-- ===== PAGE FRAME (MATCH ADMIN STYLE) ===== -->
<div class="container-fluid py-4 worksheet-page-shell">
    <div class="card worksheet-main-card">
        <div class="card-body worksheet-main-body">

            <div class="worksheet-header-row">
                <h3 class="worksheet-title">Your Worksheet</h3>
                <button id="todayBtn" class="btn btn-sm worksheet-today-btn">Today</button>
            </div>

            <div class="worksheet-calendar-panel">
                <div id="calendar-root"></div>

                <div class="calendar-legend mt-3 mb-3">
                    <span class="legend-item"><span class="legend-dot empty"></span> Empty</span>
                    <span class="legend-item"><span class="legend-dot partial"></span> Partial</span>
                    <span class="legend-item"><span class="legend-dot filled"></span> Filled</span>
                    <span class="legend-item"><span class="legend-dot holiday"></span> Holiday</span>
                    <span class="legend-item"><span class="legend-dot weekoff"></span> Weekoff</span>
                    <span class="legend-item"><span class="legend-dot leave"></span> Leave</span>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ================= PROFILE MODAL ================= -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div><strong>Email:</strong> <?= $_SESSION['employee_email'] ?? '' ?></div>
                <div class="mt-2"><strong>Role:</strong> Employee</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= WORKSHEET MODAL ================= -->
<div class="modal fade" id="worksheetModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Worksheet for <span id="modalDate"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="worksheetForm">
                    <div id="saveAlert" class="alert d-none" role="alert"></div>
                    <div class="row" id="hoursGrid"></div>
                </form>
            </div>

            <div class="modal-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small" id="autosaveStatus">Idle</div>
                <div>
                    <button type="button" id="saveDraft" class="btn btn-secondary">Save Draft</button>
                    <button type="button" id="submitSheet" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="/emp/worksheet/js/calendar.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pbtn = document.getElementById('profileBtn');
    const pmodalEl = document.getElementById('profileModal');

    if (pbtn && pmodalEl) {
        const pmodal = new bootstrap.Modal(pmodalEl);
        pbtn.addEventListener('click', function (e) {
            e.preventDefault();
            pmodal.show();
        });
    }
});
</script>

<?php include("footer.php"); ?>
