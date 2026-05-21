<?php
include("header.php");
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to edit your profile.</div>";
    exit;
}
$employee_id = $_SESSION['employee_id'];
// Fetch employee details
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
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
.emp-profile{
    padding: 3%;
    margin-top: 3%;
    margin-bottom: 3%;
    border-radius: 0.5rem;
    background: #fff;
}
.profile-img{
    text-align: center;
    height: 150px;
    width: 300px;
}
.profile-img img{
    width: 70%;
    height: 100%;
}
.profile-img .file {
    position: relative;
    overflow: hidden;
    margin-top: -20%;
    width: 70%;
    border: none;
    border-radius: 0;
    font-size: 15px;
    background: #212529b8;
}
.profile-img .file input {
    position: absolute;
    opacity: 0;
    right: 0;
    top: 0;
}
.profile-head h5{
    color: #333;
}
.profile-head h6{
    color: #0062cc;
}
.profile-edit-btn{
    border: none;
    border-radius: 1.5rem;
    width: 70%;
    padding: 2%;
    font-weight: 600;
    color: #6c757d;
    cursor: pointer;
}
.proile-rating{
    font-size: 12px;
    color: #818182;
    margin-top: 5%;
}
.proile-rating span{
    color: #495057;
    font-size: 15px;
    font-weight: 600;
}
.profile-head .nav-tabs{
    margin-bottom:5%;
}
.profile-head .nav-tabs .nav-link{
    font-weight:600;
    border: none;
}
.profile-head .nav-tabs .nav-link.active{
    border: none;
    border-bottom:2px solid #0062cc;
}
.profile-work{
    padding: 14%;
    margin-top: -15%;
}
.profile-work p{
    font-size: 12px;
    color: #818182;
    font-weight: 600;
    margin-top: 10%;
}
.profile-work a{
    text-decoration: none;
    color: #495057;
    font-weight: 600;
    font-size: 14px;
}
.profile-work ul{
    list-style: none;
}
.profile-tab label{
    font-weight: 600;
}
.profile-tab p{
    font-weight: 600;
    color: #0062cc;
}
    </style>
<div class="container-fluid py-4">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4">
            <div class="card-header pb-0 p-3">
                <div class="row">
                    <div class="col-6 d-flex align-items-center">
                        <h6 class="mb-0">Employee Details</h6>
                    </div>
                    <div class="col-6 text-end">
                        <a href="edit_profile?id=<?= $employee_id ?>" class="btn bg-gradient-dark mb-0">Edit Profile</a>
                    </div>
                </div>
            </div>
            <div class="card-body p-2">

            <div class="container emp-profile">
            <form method="post">
                <div class="row">
                    <div class="col-md-4">
                        <div class="profile-img">
                        <img src="<?= htmlspecialchars($employee['photo']) ? $employee['photo'] : 'assets/img/logos/user.png' ?>" alt="user1">
                            <div class="file btn btn-lg btn-primary">
                                <?= htmlspecialchars($employee['name']) ?>                             
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-head">
                                    <h5>
                                    <?= htmlspecialchars($employee['name']) ?>
                                    </h5>
                                    <h6>
                                    <?= htmlspecialchars($employee['designation']) ?>
                                    </h6>
                                    <p class="proile-rating">Office/Site : <span><?= htmlspecialchars($employee['office']) ?></span></p>
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">About</a>
                                </li>
                               
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="profile-edit-btn" ><?= htmlspecialchars($employee['status']) ?></button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="profile-work">
                            <p>Office Time</p>
                            <a href="">Punch In - <?= htmlspecialchars($employee['punchin_time']) ?></a><br/>
                            <a href="">Punch Out - <?= htmlspecialchars($employee['punchout_time']) ?></a><br/>
                            <a href="">Break Time - <?= htmlspecialchars($employee['break_time']) ?></a> <br/>
                            <a href="">Working Hours - <?= htmlspecialchars($employee['working_hours']) ?></a><br/>
                           
                            <p>Bank Details</p>
                            <a href="">Account No- <?= htmlspecialchars($employee['bank_account']) ?></a><br/>
                            <a href="">IFSC - <?= htmlspecialchars($employee['ifsc_code']) ?></a><br/>
                            <a href="">UAN - <?= htmlspecialchars($employee['epf_number']) ?></a><br/>
                            <a href="">ESIC - <?= htmlspecialchars($employee['esic']) ?></a><br/>
                           
                            <br/>
                           
                            <p>Salary Details</p>
                            <a href="">Salary Type- <?= htmlspecialchars($employee['salary_type']) ?></a><br/>
                            <a href="">Basic- <?= htmlspecialchars($employee['basic']) ?></a><br/>
                            <a href="">Gross Salary - <?= htmlspecialchars($employee['gross_salary']) ?></a><br/>
                            <a href="">Total Deductions- <?= htmlspecialchars($employee['total_deductions']) ?></a><br/>
                            <a href="">Net Salary - <?= htmlspecialchars($employee['net_salary']) ?></a><br/>
                            <p>Availlable Leaves</p>
                            <a href="">Sick Leave- <?= htmlspecialchars($employee['sick_leave']) ?></a><br/>
                            <a href="">Casual Leave- <?= htmlspecialchars($employee['casual_leave']) ?></a><br/>
                            <a href="">Paid Leave - <?= htmlspecialchars($employee['paid_leave']) ?></a><br/>
                            <a href="">Other Leave- <?= htmlspecialchars($employee['other_leave']) ?></a><br/>
                            <a href="">Total Leave - <?= htmlspecialchars($employee['total_leave']) ?></a><br/>
                          
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="tab-content profile-tab" id="myTabContent">
                            <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Employee Id</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['employee_id']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Name</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['name']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Email</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['email']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Phone</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['phone']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Profession</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['designation']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Role</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['role']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Date of Birth</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['dob']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Fathet's Name</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['father_name']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Emergency Contact</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['emergency_contact']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Date of Joining</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['date_of_joining']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Adhar</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['adhar_number']) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>PAN</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p><?= htmlspecialchars($employee['pan_number']) ?></p>
                                            </div>
                                        </div>

                            </div>
                            <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Experience</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p>Expert</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Hourly Rate</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p>10$/hr</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Total Projects</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p>230</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>English Level</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p>Expert</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Availability</label>
                                            </div>
                                            <div class="col-md-6">
                                                <p>6 months</p>
                                            </div>
                                        </div>
                                <div class="row">
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
        </div>
        </div>
    
<?php include ("footer.php") ?>
