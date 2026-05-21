<?php
include("header.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

date_default_timezone_set('Asia/Kolkata');

/* CSRF */
require_once __DIR__ . '/worksheet/csrf.php';
$csrf = csrf_token();
?>

<script>
    window.CSRF_TOKEN = "<?= $csrf ?>";
    window.IS_ADMIN = true;
    window.CAN_MARK = true;
</script>

<link rel="stylesheet" href="/admin/worksheet/css/style.css">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
.worksheet-page {
    padding-bottom: 1.5rem;
}

.worksheet-shell,
.worksheet-modal .modal-content {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 24px;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
    background: rgba(255, 255, 255, 0.96);
}

.worksheet-shell {
    overflow: hidden;
}

.worksheet-body {
    padding: 1.35rem;
}

.worksheet-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
    padding: 1.15rem 1.25rem;
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
}

.worksheet-title {
    margin: 0;
    color: #111827;
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.worksheet-topbar-kicker {
    display: block;
    margin-bottom: 0.35rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.worksheet-filter-card {
    margin-bottom: 1.35rem;
    padding: 1.2rem 1.25rem;
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
}

.worksheet-filter-card .form-label {
    display: block;
    margin-bottom: 0.55rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.worksheet-filter-card .form-control,
.worksheet-filter-card .form-select,
.worksheet-filter-card select,
.worksheet-filter-card input[type="month"] {
    min-height: 50px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    color: #374151;
    box-shadow: none;
}

.worksheet-filter-card .form-control:focus,
.worksheet-filter-card .form-select:focus,
.worksheet-filter-card select:focus,
.worksheet-filter-card input[type="month"]:focus {
    border-color: #9aa7b8;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.18);
}

.worksheet-filter-row {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}

.worksheet-filter-field {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.worksheet-filter-field .form-control,
.worksheet-filter-field .form-select,
.worksheet-filter-field select,
.worksheet-filter-field input[type="month"] {
    width: 100%;
}

.worksheet-actions {
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
    gap: 0.75rem;
    flex-wrap: wrap;
    min-height: 50px;
}

.worksheet-btn-primary,
.worksheet-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 50px;
    padding: 0.82rem 1.3rem;
    border-radius: 14px;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.worksheet-btn-primary {
    border: 0;
    background: linear-gradient(135deg, #171717 0%, #2f2f2f 100%);
    box-shadow: 0 14px 30px rgba(17, 24, 39, 0.16);
}

.worksheet-btn-primary:hover,
.worksheet-btn-primary:focus {
    transform: translateY(-1px);
    box-shadow: 0 18px 34px rgba(17, 24, 39, 0.2);
}

.worksheet-btn-secondary {
    border: 1px solid #d6dde7;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    color: #475569;
}

.worksheet-btn-secondary:hover,
.worksheet-btn-secondary:focus {
    border-color: #c3ccd8;
    color: #334155;
    transform: translateY(-1px);
}

.worksheet-calendar-panel {
    padding: 1rem;
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    background: linear-gradient(180deg, #fbfcfd 0%, #f8fafc 100%);
}

.worksheet-page #report-calendar-root > .d-flex,
.worksheet-page #calendar-root > .d-flex {
    display: flex;
    flex-wrap: wrap;
    align-items: stretch;
    gap: 14px;
    width: 100%;
}

.worksheet-page .day-cell {
    flex: 0 0 calc((100% - 5 * 14px) / 6);
    max-width: calc((100% - 5 * 14px) / 6);
    min-width: 190px;
    min-height: 128px;
    padding: 0.95rem 0.95rem 0.8rem;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}

.worksheet-page .day-cell .date {
    color: #111827;
    font-size: 0.95rem;
    font-weight: 700;
}

.worksheet-page .day-cell .status {
    margin-top: auto !important;
    font-size: 0.82rem;
}

.worksheet-page .day-cell .btn.btn-light {
    min-width: 96px;
    min-height: 34px;
    border-radius: 12px;
    font-weight: 700;
    box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
}

.worksheet-page .day-cell .actions {
    top: 10px;
    right: 10px;
    gap: 7px;
}

.worksheet-page .day-cell .actions button {
    width: 30px;
    min-width: 30px;
    height: 30px;
    border-radius: 9px;
    border-width: 1px;
    box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
}

.worksheet-page .hour-box {
    border-radius: 14px;
    border: 1px solid rgba(226, 232, 240, 0.9);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.86) 0%, rgba(248, 250, 252, 0.92) 100%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.worksheet-page .calendar-legend {
    gap: 0.85rem;
    margin-bottom: 0.25rem;
}

.worksheet-page .legend-item {
    padding: 0.42rem 0.75rem;
    border: 1px solid #e7ecf3;
    border-radius: 999px;
    background: #fff;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
}

.worksheet-modal .modal-dialog {
    max-width: 920px;
}

#worksheetModal .modal-dialog {
    max-width: 1180px;
}

.worksheet-modal .modal-content {
    overflow: hidden;
}

#worksheetModal .modal-content {
    overflow: hidden;
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 26px;
    box-shadow: 0 26px 70px rgba(15, 23, 42, 0.2);
    background: #f8fafc;
}

.worksheet-modal .modal-header {
    padding: 1.2rem 1.3rem 1rem;
    border-bottom: 1px solid #e9eef5;
}

#worksheetModal .modal-header {
    padding: 1.2rem 1.5rem 1rem;
    border-bottom: 1px solid #e7edf4;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.worksheet-modal .modal-title {
    color: #111827;
    font-size: 1.2rem;
    font-weight: 800;
}

#worksheetModal .modal-title {
    color: #111827;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.worksheet-modal .modal-body {
    padding: 1.25rem 1.3rem;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
}

#worksheetModal .modal-body {
    padding: 1.35rem 1.5rem;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.worksheet-modal .modal-footer {
    padding: 1rem 1.3rem 1.25rem;
    border-top: 1px solid #e9eef5;
}

#worksheetModal .modal-footer {
    padding: 1rem 1.5rem 1.25rem;
    border-top: 1px solid #e7edf4;
    background: #ffffff;
    justify-content: flex-start;
}

.worksheet-modal .btn-secondary {
    min-height: 46px;
    padding: 0.75rem 1.2rem;
    border: 1px solid #d5dde8;
    border-radius: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    color: #475569;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

#worksheetModal #worksheetForm {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

#worksheetModal #saveAlert {
    margin: 0;
    border-radius: 14px;
    padding: 0.9rem 1rem;
    font-weight: 700;
}

#worksheetModal #hoursGrid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin: 0;
}

#worksheetModal #hoursGrid > .hour-box {
    width: auto;
    max-width: none;
    margin: 0;
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    border: 1px solid #dbe4ee;
    background: #ffffff;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
}

#worksheetModal #hoursGrid > .hour-box.lunch {
    opacity: 1;
    background: linear-gradient(180deg, #fffef9 0%, #fff8e8 100%);
    border-color: #efe1b6;
}

#worksheetModal #hoursGrid > .hour-box .form-label {
    display: flex;
    align-items: center;
    min-height: 52px;
    margin: 0;
    padding: 0.9rem 1rem 0.8rem;
    border-bottom: 1px solid #ebf0f5;
    color: #475569;
    font-size: 0.9rem;
    font-weight: 800;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

#worksheetModal #hoursGrid > .hour-box.lunch .form-label {
    background: linear-gradient(180deg, #fffdf4 0%, #fff6dc 100%);
}

#worksheetModal .editor {
    min-height: 188px;
    background: #ffffff;
}

#worksheetModal .ql-toolbar.ql-snow {
    padding: 0.7rem 0.8rem;
    border: 0;
    border-bottom: 1px solid #ebf0f5;
    background: #ffffff;
}

#worksheetModal .ql-container.ql-snow {
    min-height: 132px;
    border: 0;
    background: #ffffff;
}

#worksheetModal .ql-editor {
    min-height: 132px;
    padding: 0.9rem 1rem;
    color: #334155;
    font-size: 0.92rem;
    line-height: 1.55;
}

#worksheetModal #autosaveStatus {
    color: #64748b !important;
    font-size: 0.82rem;
    font-style: normal;
    font-weight: 700;
}

#worksheetModal #saveDraft,
#worksheetModal #submitSheet {
    display: none !important;
}

#worksheetModal .modal-footer > div:last-child:empty {
    display: none;
}

#worksheetModal #saveDraft,
#worksheetModal #submitSheet {
    min-height: 46px;
    padding: 0.75rem 1.2rem;
    border-radius: 14px;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

#worksheetModal #saveDraft {
    border: 1px solid #d6dde7;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    color: #475569;
}

#worksheetModal #submitSheet {
    border: 0;
    background: linear-gradient(135deg, #171717 0%, #2f2f2f 100%);
    box-shadow: 0 14px 28px rgba(17, 24, 39, 0.18);
}

#worksheetModal .btn-close {
    box-shadow: none;
}

@media (max-width: 991.98px) {
    .worksheet-topbar {
        padding: 1rem 1.05rem;
    }

    .worksheet-body,
    .worksheet-filter-card,
    .worksheet-calendar-panel {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    #worksheetModal .modal-dialog {
        max-width: calc(100vw - 2rem);
        margin: 1rem auto;
    }

    #worksheetModal #hoursGrid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .worksheet-topbar {
        display: block;
    }

    .worksheet-title {
        font-size: 1.35rem;
    }

    .worksheet-actions {
        align-items: stretch;
    }

    .worksheet-btn-primary,
    .worksheet-btn-secondary {
        width: 100%;
    }

    .worksheet-calendar-panel {
        padding: 0.85rem;
    }

    .worksheet-page .day-cell {
        flex-basis: calc(50% - 14px);
        min-width: 0;
        min-height: 118px;
    }

    #worksheetModal .modal-header,
    #worksheetModal .modal-body,
    #worksheetModal .modal-footer {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    #worksheetModal .modal-footer {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.85rem;
    }

    #worksheetModal .modal-footer > div:last-child {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        width: 100%;
    }

    #worksheetModal #hoursGrid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575.98px) {
    .worksheet-page .day-cell {
        flex-basis: 100%;
        min-height: 108px;
    }
}
</style>

<!-- ===== PAGE FRAME (SAME AS EMP & OTHER CALENDARS) ===== -->
<div class="container-fluid py-4 worksheet-page">
    <div class="card worksheet-shell">
        <div class="card-body worksheet-body">

            <div class="worksheet-topbar">
                <div>
                    <span class="worksheet-topbar-kicker">Admin Worksheet</span>
                    <h3 class="worksheet-title">Workreport</h3>
                </div>
            </div>

            <div class="worksheet-filter-card">
            <div class="row align-items-end worksheet-filter-row">
                <div class="col-lg-4 col-md-5 worksheet-filter-field">
                    <label class="form-label">Employee</label>
                    <select id="reportUser" class="form-control"></select>
                </div>

                <div class="col-lg-3 col-md-4 worksheet-filter-field">
                    <label class="form-label">Month</label>
                    <input
                        type="month"
                        id="reportMonth"
                        class="form-control"
                        value="<?= date('Y-m') ?>"
                    >
                </div>

                <div class="col-lg-5 col-md-3 worksheet-filter-field">
                    <div class="worksheet-actions">
                    <button id="loadReport" class="btn btn-primary worksheet-btn-primary">Load</button>
                    <button id="monthlySummary" class="btn btn-outline-secondary worksheet-btn-secondary">
                        Monthly Summary
                    </button>
                    </div>
                </div>
            </div>
            </div>

            <div class="worksheet-calendar-panel">
                <div id="report-calendar-root"></div>
            </div>

        </div>
    </div>
</div>

<!-- ================= DETAIL MODAL ================= -->
<div class="modal fade worksheet-modal" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="reportModalBody"></div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<!-- ADMIN JS -->
<script src="/admin/worksheet/js/report_calendar.js"></script>
<script src="/admin/worksheet/js/report.js"></script>

<?php include("footer.php"); ?>
