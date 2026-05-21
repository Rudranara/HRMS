<?php
include("header.php");
if (!isset($_GET['employee_id'])) {
    echo "<div class='alert alert-danger'>Employee ID is missing!</div>";
    exit;
}

$employee_id = $_GET['employee_id'];

$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    echo "<div class='alert alert-danger'>Employee not found!</div>";
    exit;
}
?>
<style>
body {
    background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
}

.emp-profile-page {
    padding-top: 1.5rem;
    padding-bottom: 2.5rem;
}

.emp-profile-header,
.emp-profile-shell,
.profile-overview-card,
.profile-panel,
.profile-tab {
    border: 1px solid #e5eaf1;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 26px 60px rgba(15, 23, 42, 0.07);
}

.emp-profile-header {
    padding: 1.2rem 1.4rem;
    margin-bottom: 1rem;
}

.emp-profile-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.emp-profile-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.emp-profile-edit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 0.7rem 1.1rem;
    border-radius: 14px;
    border: 1px solid #111827;
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.16);
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
}

.emp-profile-shell {
    padding: 1rem;
}

.profile-layout {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}

.profile-top-row {
    margin-bottom: 1rem;
}

.profile-overview-card {
    display: grid;
    grid-template-columns: 180px minmax(0, 1fr) 150px;
    gap: 1rem;
    padding: 1rem;
    align-items: center;
}

.profile-media-card,
.profile-summary-card {
    border: 1px solid #e9eef5;
    border-radius: 20px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.profile-media-card {
    padding: 1rem;
}

.profile-img {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.9rem;
    height: 100%;
}

.profile-img img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 24px;
    border: 1px solid #dce5f0;
    background: #f8fafc;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.12);
}

.profile-img .file {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 40px;
    padding: 0.7rem 0.95rem;
    border: 0;
    border-radius: 12px;
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 800;
    pointer-events: none;
}

.profile-summary-card {
    padding: 1.15rem 1.2rem;
}

.profile-summary-top {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
}

.profile-head h5 {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.profile-head h6 {
    margin: 0.3rem 0 0;
    color: #2563eb;
    font-size: 0.9rem;
    font-weight: 700;
}

.profile-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    min-width: 112px;
    padding: 0.65rem 0.95rem;
    border: 1px solid #dbe4ee;
    border-radius: 14px;
    background: #f8fafc;
    color: #0f172a;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.profile-status-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
}

.proile-rating {
    margin: 0.95rem 0 0;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.proile-rating span {
    color: #0f172a;
    font-size: 0.88rem;
    font-weight: 800;
    letter-spacing: normal;
    text-transform: none;
}

.profile-head .nav-tabs {
    display: inline-flex;
    gap: 0.5rem;
    margin: 1rem 0 0;
    padding: 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    background: #f8fafc;
}

.profile-head .nav-tabs .nav-link {
    margin: 0;
    padding: 0.58rem 0.95rem;
    border: 0;
    border-radius: 999px;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.profile-head .nav-tabs .nav-link.active {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
}

.profile-panel,
.profile-tab {
    padding: 1.2rem;
    height: 100%;
}

.profile-panel {
    min-height: 100%;
}

.profile-work {
    padding: 0;
    margin: 0;
}

.profile-section + .profile-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #edf2f7;
}

.profile-section-title {
    margin: 0 0 0.85rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.profile-work a {
    display: block;
    margin-bottom: 0.55rem;
    color: #0f172a;
    font-size: 0.84rem;
    font-weight: 600;
    line-height: 1.5;
    text-decoration: none;
}

.profile-detail-row {
    align-items: center;
    padding: 0.85rem 0;
    margin: 0;
    border-bottom: 1px solid #eef2f7;
}

.profile-detail-row:last-child {
    border-bottom: 0;
}

.profile-tab label {
    display: block;
    margin: 0;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.profile-tab p {
    margin: 0;
    color: #0f172a;
    font-size: 0.88rem;
    font-weight: 700;
}

@media (max-width: 991.98px) {
    .profile-overview-card {
        grid-template-columns: 1fr;
    }

    .profile-summary-top {
        align-items: flex-start;
    }

    .profile-status-wrap {
        justify-content: flex-start;
    }
}

@media (max-width: 767.98px) {
    .emp-profile-page {
        padding-top: 1.1rem;
    }

    .emp-profile-header,
    .emp-profile-shell,
    .profile-overview-card,
    .profile-panel,
    .profile-tab {
        padding-left: 0.9rem;
        padding-right: 0.9rem;
    }

    .profile-img img {
        width: 104px;
        height: 104px;
    }

    .profile-head h5 {
        font-size: 1.2rem;
    }
}
</style>

<div class="container-fluid emp-profile-page">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="emp-profile-header">
            <div class="emp-profile-header-row">
                <h6 class="emp-profile-title">Employee Details</h6>
                <a href="edit_employee?employee_id=<?= $employee['employee_id'] ?>" class="emp-profile-edit-btn">Edit Employee</a>
            </div>
        </div>

        <div class="emp-profile-shell">
            <form method="post">
                <div class="row profile-top-row">
                    <div class="col-12">
                        <div class="profile-overview-card">
                            <div class="profile-media-card">
                                <div class="profile-img">
                                    <img src="<?= htmlspecialchars($employee['photo']) ? $employee['photo'] : 'assets/img/logos/user.png' ?>" alt="user1">
                                    <div class="file btn btn-lg btn-primary">
                                        <?= htmlspecialchars($employee['name']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="profile-summary-card">
                                <div class="profile-head">
                                    <div class="profile-summary-top">
                                        <div>
                                            <h5><?= htmlspecialchars($employee['name']) ?></h5>
                                            <h6><?= htmlspecialchars($employee['designation']) ?></h6>
                                        </div>
                                    </div>
                                    <p class="proile-rating">Office/Site : <span><?= htmlspecialchars($employee['office']) ?></span></p>
                                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">About</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="profile-status-wrap">
                                <button type="button" class="profile-status-badge"><?= htmlspecialchars($employee['status']) ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row profile-layout">
                    <div class="col-lg-4">
                        <div class="profile-panel">
                                <div class="profile-work">
                                    <div class="profile-section">
                                        <p class="profile-section-title">Office Time</p>
                                        <a href="">Punch In - <?= htmlspecialchars($employee['punchin_time']) ?></a><br/>
                                        <a href="">Punch Out - <?= htmlspecialchars($employee['punchout_time']) ?></a><br/>
                                        <a href="">Break Time - <?= htmlspecialchars($employee['break_time']) ?></a><br/>
                                        <a href="">Working Hours - <?= htmlspecialchars($employee['working_hours']) ?></a><br/>
                                    </div>

                                    <div class="profile-section">
                                        <p class="profile-section-title">Bank Details</p>
                                        <a href="">Account No- <?= htmlspecialchars($employee['bank_account']) ?></a><br/>
                                        <a href="">IFSC - <?= htmlspecialchars($employee['ifsc_code']) ?></a><br/>
                                        <a href="">UAN - <?= htmlspecialchars($employee['epf_number']) ?></a><br/>
                                        <a href="">ESIC - <?= htmlspecialchars($employee['esic']) ?></a><br/>
                                    </div>

                                    <div class="profile-section">
                                        <p class="profile-section-title">Salary Details</p>
                                        <a href="">Salary Type- <?= htmlspecialchars($employee['salary_type']) ?></a><br/>
                                        <a href="">Basic- <?= htmlspecialchars($employee['basic']) ?></a><br/>
                                        <a href="">Gross Salary - <?= htmlspecialchars($employee['gross_salary']) ?></a><br/>
                                        <a href="">Total Deductions- <?= htmlspecialchars($employee['total_deductions']) ?></a><br/>
                                        <a href="">Net Salary - <?= htmlspecialchars($employee['net_salary']) ?></a><br/>
                                    </div>

                                    <div class="profile-section">
                                        <p class="profile-section-title">Availlable Leaves</p>
                                        <a href="">Sick Leave- <?= htmlspecialchars($employee['sick_leave']) ?></a><br/>
                                        <a href="">Casual Leave- <?= htmlspecialchars($employee['casual_leave']) ?></a><br/>
                                        <a href="">Paid Leave - <?= htmlspecialchars($employee['paid_leave']) ?></a><br/>
                                        <a href="">Other Leave- <?= htmlspecialchars($employee['other_leave']) ?></a><br/>
                                        <a href="">Total Leave - <?= htmlspecialchars($employee['total_leave']) ?></a><br/>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="tab-content profile-tab" id="myTabContent">
                            <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Employee Id</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['employee_id']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Name</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['name']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Email</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['email']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Phone</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['phone']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Profession</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['designation']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Role</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['role']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Date of Birth</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['dob']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Fathet's Name</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['father_name']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Emergency Contact</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['emergency_contact']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Date of Joining</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['date_of_joining']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Adhar</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['adhar_number']) ?></p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>PAN</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p><?= htmlspecialchars($employee['pan_number']) ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Experience</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p>Expert</p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Hourly Rate</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p>10$/hr</p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Total Projects</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p>230</p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>English Level</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p>Expert</p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-6">
                                        <label>Availability</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p>6 months</p>
                                    </div>
                                </div>
                                <div class="row profile-detail-row">
                                    <div class="col-md-12">
                                        <label>Your Bio</label><br/>
                                        <p>Your detail description</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include ("footer.php") ?>