<?php
include("header.php");
$employee_id = $_SESSION['employee_id'] ?? 0;
if (!$employee_id) die("Unauthorized");

/* ======================================================
   ENSURE JOURNEY STARTED + GET JOURNEY ID
======================================================*/
$check = mysqli_query($conn,"
   SELECT id, status, start_time
    FROM journey_start
    WHERE user_id = '$employee_id'
      AND DATE(start_time) = CURDATE()
    ORDER BY id DESC
    LIMIT 1

");

$journey = mysqli_fetch_assoc($check);
if (!$journey || $journey['status'] !== 'started') {
    echo "<script>alert('Please start your journey first.');location='visit_details';</script>";
    exit;
}

$journey_id = (int)$journey['id']; // IMPORTANT

/* ======================================================
   FETCH VENDORS
======================================================*/
$vendors = mysqli_query($conn,"SELECT vendor_id,name FROM vendors ORDER BY name ASC");

/* ======================================================
   FETCH TIMELINE — JOURNEY BASED
======================================================*/
$timeline_q = mysqli_query($conn, "
    SELECT 
        t.visit_id,
        t.visit_type,
        t.vendor_id,
        t.customer_name,
        t.purpose,
        t.created_at,
        t.exit_time,
        t.lat,
        t.lng,
        v.name AS vendor_name
    FROM visits t
    LEFT JOIN vendors v ON t.vendor_id = v.vendor_id
    WHERE t.journey_id = '$journey_id'
    ORDER BY t.created_at ASC
");
?>

<style>
/* ===========================
   GLOBAL
=========================== */
body {
    background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
}

.journey-page {
    padding-top: 0.95rem !important;
    padding-left: 1rem !important;
    padding-right: 1rem !important;
    padding-bottom: 1.2rem !important;
}

.journey-header {
    margin-bottom: 1rem;
}

.journey-content-row {
    --bs-gutter-x: 0;
}

.journey-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.journey-subtitle {
    margin-top: 0.3rem;
    color: #64748b;
    font-size: 0.84rem;
    font-weight: 500;
}

.card {
    border-radius: 28px;
    box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(148, 163, 184, 0.14);
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
}

h6 { font-weight:700; }

.journey-form-card {
    overflow: hidden;
}

.journey-form-shell {
    padding: 1.25rem !important;
}

.journey-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #334155;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.journey-field-group {
    margin-top: 1rem;
}

.journey-camera-shell {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 22px;
    background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
    border: 1px solid #dbe4f0;
}

.journey-camera-title {
    display: block;
    margin-bottom: 0.6rem;
    color: #334155;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.journey-action-wrap {
    margin-top: 1rem;
}

/* ===========================
   TIMELINE
=========================== */
.timeline-container {
    white-space: nowrap;
    overflow-x: auto;
    padding: 12px 6px;
    scroll-behavior: smooth;
}
.timeline-container::-webkit-scrollbar { height:6px; }
.timeline-container::-webkit-scrollbar-thumb {
    background:#cbd5e1; border-radius:4px;
}

.timeline-item {
    display: inline-block;
    text-align: center;
    margin-right: 36px;
    min-width:150px;
    padding:10px;
    background:white;
    border-radius:10px;
    box-shadow:0 2px 6px rgba(0,0,0,0.06);
}

.timeline-dot-img {
    width: 34px;
    height: 34px;
    cursor: pointer;
    margin-bottom: 8px;
}

.timeline-line {
    display:inline-block;
    width:50px;
    height:3px;
    background:#e5e7eb;
    vertical-align: middle;
}

/* Badges */
.badge-start {
    background:#10b981;
    color:#fff;
    padding:5px 10px;
    border-radius:20px;
    margin-top:8px;
    font-size:12px;
}
.badge-end {
    background:#ef4444;
    color:#fff;
    padding:5px 10px;
    border-radius:20px;
    margin-top:8px;
    font-size:12px;
}

/* ===========================
   FORM
=========================== */
label { font-weight:600; font-size:14px; }

.form-control {
    min-height: 48px;
    border-radius: 16px;
    padding: 0.8rem 0.95rem;
    background: #fff;
    border: 1px solid #d9e2ec;
    box-shadow: none;
    color: #0f172a;
    font-size: 0.92rem;
    font-weight: 500;
}

.form-control:focus {
    border-color: #94a3b8;
    box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
}

#customerForm input {
    margin-bottom: 0.8rem;
}

.btn {
    min-height: 46px;
    padding: 0.78rem 1rem;
    border-radius: 16px;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    box-shadow: none !important;
}

#reachBtn, #restartBtn {
    width:100%;
    margin-top:12px;
}

#openCameraBtn {
    background: linear-gradient(135deg, #e5ecf4 0%, #d9e2ec 100%) !important;
    color: #1e293b !important;
    border: 1px solid #cbd5e1 !important;
}

#captureBtn {
    background: linear-gradient(135deg, #173b78 0%, #224f95 100%) !important;
    border: 0 !important;
}

#switchCameraBtn {
    background: linear-gradient(135deg, #fff4db 0%, #fde68a 100%) !important;
    color: #92400e !important;
    border: 1px solid #fcd34d !important;
}

#retakeBtn {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%) !important;
    color: #111827 !important;
    border: 1px solid #cbd5e1 !important;
}

#reachBtn {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
    border: 0 !important;
}

#msg {
    border-radius: 16px;
    padding: 0.9rem 1rem;
    border: 1px solid transparent;
    font-size: 0.88rem;
    font-weight: 600;
}

/* ===========================
   CAMERA
=========================== */
#cameraArea {
    display:none;
    margin-top: 0.8rem;
    text-align:center;
}

#video, #previewImage {
    width:100%;
    max-width:380px;
    border-radius: 20px;
    border: 1px solid #dbe4f0;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    background: #ffffff;
}

#captureBtn, #retakeBtn {
    width:100%;
    margin-top: 0.75rem;
}

#previewBox {
    margin-top: 0.8rem;
}

/* ===========================
   RESPONSIVE
=========================== */
@media (min-width:768px) {
    .timeline-item { min-width:180px; }
    .container-fluid { padding:20px 40px; }
}

@media (max-width: 767.98px) {
    .journey-page {
        padding-top: 0.6rem !important;
        padding-left: 0.9rem !important;
        padding-right: 0.9rem !important;
        padding-bottom: 0.85rem !important;
    }

    .journey-header {
        margin-bottom: 0.85rem;
    }

    .journey-title {
        font-size: 1rem;
    }

    .journey-subtitle {
        font-size: 0.76rem;
    }

    .journey-form-shell {
        padding: 1rem !important;
    }

    .journey-camera-shell {
        padding: 0.85rem;
        border-radius: 18px;
    }

    .form-control {
        min-height: 44px;
        padding: 0.72rem 0.85rem;
        border-radius: 14px;
        font-size: 0.86rem;
    }

    .btn {
        min-height: 42px;
        padding: 0.7rem 0.85rem;
        border-radius: 14px;
        font-size: 0.72rem;
    }

    #video, #previewImage {
        border-radius: 16px;
    }
}
</style>

<div class="container-fluid py-4 journey-page">

    <div class="journey-header">
        <h6 class="journey-title">Record Visit</h6>
        <div class="journey-subtitle">Capture the visit details and optional photo without changing the current journey flow.</div>
    </div>

    <!-- TIMELINE -->
    <!-- <div class="row mb-3">
        <div class="col-12">
            <div class="card p-3">
                <h6><b> Today's Visits</b></h6>
                <div class="timeline-container">

                    <?php
                    $visits = [];
                    while ($row = mysqli_fetch_assoc($timeline_q)) { $visits[] = $row; }

                    if (count($visits) == 0) {
                        echo "<p class='text-center mt-3'>No visits today</p>";
                    } else {
                        $total = count($visits);
                        foreach ($visits as $i => $v) {

                            $isFirst = ($i == 0);
                            $isLast  = ($i == $total - 1);

                            if ($v['visit_type'] == "customer") {
                                $name = htmlspecialchars($v['customer_name']);
                                $icon = "👤";
                            } else {
                                $name = htmlspecialchars($v['vendor_name']);
                                $icon = "🏬";
                            }

                            $arrival = date("h:i A", strtotime($v['created_at']));
                            $exit = $v['exit_time'] ? date("h:i A", strtotime($v['exit_time'])) : "";
                            $mapUrl = "https://www.google.com/maps?q={$v['lat']},{$v['lng']}";
                    ?>

                    <div class="timeline-item">
                        <img src="https://cdn-icons-png.flaticon.com/512/535/535239.png"
                             class="timeline-dot-img"
                             onclick="window.open('<?= $mapUrl ?>','_blank')">

                        <div style="font-size:18px"><?= $icon ?></div>
                        <div style="font-size:14px; font-weight:600;"><?= $name ?></div>
                        <div style="font-size:12px; color:#6b7280;"><?= $arrival ?></div>

                        <?php if ($isFirst): ?><div class="badge-start">START</div><?php endif; ?>
                        <?php if ($isLast): ?><div class="badge-end"><?= $exit ? "STOP ($exit)" : "LAST" ?></div><?php endif; ?>
                    </div>

                    <?php if (!$isLast) echo "<div class='timeline-line'></div>"; } } ?>

                </div>
            </div>
        </div>
    </div> -->

    <!-- FORM -->
    <div class="row journey-content-row">
        <div class="col-12">
            <div class="card p-3 journey-form-card">
                <div class="journey-form-shell">
 
                <!-- Visit Type -->
                <label class="journey-section-label">Select Type</label>
                <select id="visitType" class="form-control" onchange="toggleForm()">
                    <option value="vendor">Dealer</option>
                    <option value="subdealer">Sub Dealer</option>
                    <option value="ace">ACE</option>
                    <option value="customer">Customer</option>
                    <option value="other">Other</option>
                </select>


                <!-- Vendor -->
                <div id="vendorForm" class="journey-field-group">
                    <label class="journey-section-label">Select Dealer</label>
                    <select id="vendor" class="form-control">
                        <?php
                        $vendors2 = mysqli_query($conn,"
                            SELECT vendor_id, name, address 
                            FROM vendors 
                            ORDER BY name ASC
                        ");

                        while ($vv = mysqli_fetch_assoc($vendors2)) {

                            // Address trim to 15 characters
                            $addr = $vv['address'];
                            $addr_short = strlen($addr) > 35 
                                ? substr($addr, 0, 35) . "..." 
                                : $addr;

                            echo "<option value='".intval($vv['vendor_id'])."'>"
                                . htmlspecialchars($vv['name'])
                                . " (" . htmlspecialchars($addr_short) . ")"
                                . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div id="subDealerForm" class="journey-field-group" style="display:none;">
                    <label class="journey-section-label">Select Sub Dealer</label>
                    <select id="subDealer" class="form-control">
                        <?php
                        $sds = mysqli_query($conn,"SELECT sub_dealer_id,name,address FROM sub_dealers ORDER BY name ASC");
                        while ($sd = mysqli_fetch_assoc($sds)) {
                            $addr = strlen($sd['address']) > 35
                                ? substr($sd['address'],0,35).'...'
                                : $sd['address'];

                            echo "<option value='{$sd['sub_dealer_id']}'>"
                                . htmlspecialchars($sd['name'])
                                . " (" . htmlspecialchars($addr) . ")</option>";
                        }
                        ?>
                    </select>
                </div>

                <div id="aceFormVisit" class="journey-field-group" style="display:none;">
                    <label class="journey-section-label">Select ACE</label>
                    <select id="aceVisit" class="form-control">
                        <?php
                        $aces = mysqli_query($conn,"SELECT ace_id,name,address FROM aces ORDER BY name ASC");
                        while ($a = mysqli_fetch_assoc($aces)) {
                            $addr = strlen($a['address']) > 35
                                ? substr($a['address'],0,35).'...'
                                : $a['address'];

                            echo "<option value='{$a['ace_id']}'>"
                                . htmlspecialchars($a['name'])
                                . " (" . htmlspecialchars($addr) . ")</option>";
                        }
                        ?>
                    </select>
                </div>


                <!-- Customer -->
                <div id="customerForm" style="display:none;" class="journey-field-group">
                    <label class="journey-section-label">Customer Name</label>
                    <input type="text" id="custName" class="form-control">

                    <label class="journey-section-label">Customer Mobile Number</label>
                    <input type="text" id="custMobile" class="form-control" maxlength="10">

                    <label class="journey-section-label">Customer Address</label>
                    <input type="text" id="custAddress" class="form-control">

                    <label class="journey-section-label">Purpose of Visit</label>
                    <input type="text" id="custPurpose" class="form-control">
                </div>

                <!-- Other -->
                <div id="otherForm" style="display:none;" class="journey-field-group">
                    <label class="journey-section-label">Visitor Name</label>
                    <input type="text" id="otherName" class="form-control">

                    <label class="journey-section-label">Visitor Address</label>
                    <input type="text" id="otherAddress" class="form-control">

                    <label class="journey-section-label">Reason of Visit</label>
                    <input type="text" id="otherReason" class="form-control">
                </div>

            <div id="msg" class="alert mt-3" style="display:none;"></div>
                <!-- CAMERA -->
                <div class="journey-camera-shell">
                    <label class="journey-camera-title">Take Photo (Optional)</label>
                    <button id="openCameraBtn" class="btn btn-secondary btn-sm" style="width:100%;">📷 Open Camera</button>

                    <div id="cameraArea">
                        <video id="video" autoplay playsinline></video>
                        <button id="captureBtn" class="btn btn-primary btn-sm">Capture</button>
                    </div>

                    <div id="previewBox" style="display:none;">
                        <img id="previewImage">
                    </div>
                    <button id="switchCameraBtn"
                            class="btn btn-warning btn-sm"
                            style="width:100%; margin-top:6px; display:none;">
                        🔄 Switch Camera
                    </button>

                    <button id="retakeBtn" class="btn btn-dark btn-sm" style="display:none;">🔁 Retake</button>
                </div>

                <!-- Reach Button -->
                <div class="journey-action-wrap">
                    <button id="reachBtn"
                            class="btn bg-gradient-info btn-sm">
                        I Am Reached
                    </button>
                </div>



               

               

                </div>
            </div>
        </div>
    </div>

</div>


<script>
/* ------------------------------
   Toggle Vendor/Customer Form
-------------------------------*/
function toggleForm() {
    let type = document.getElementById("visitType").value;
    document.getElementById("vendorForm").style.display = (type === "vendor") ? "block" : "none";
    document.getElementById("subDealerForm").style.display  = (type === "subdealer")  ? "block" : "none";
    document.getElementById("aceFormVisit").style.display   = (type === "ace")        ? "block" : "none";
    document.getElementById("customerForm").style.display = (type === "customer") ? "block" : "none";
    document.getElementById("otherForm").style.display    = (type === "other")    ? "block" : "none";
}



/* ------------------------------
   Camera Capture (UPDATED)
-------------------------------*/
let stream = null;
let capturedBlob = null;
let currentCamera = "user"; // user = front, environment = back

const openCameraBtn = document.getElementById('openCameraBtn');
const cameraArea = document.getElementById('cameraArea');
const captureBtn = document.getElementById('captureBtn');
const video = document.getElementById('video');
const previewBox = document.getElementById('previewBox');
const previewImage = document.getElementById('previewImage');
const retakeBtn = document.getElementById('retakeBtn');
const switchCameraBtn = document.getElementById('switchCameraBtn');

/* ------------------------------
   Open Camera (FRONT by default)
-------------------------------*/
openCameraBtn.onclick = async () => {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: currentCamera }
        });

        video.srcObject = stream;
        cameraArea.style.display = "block";
        openCameraBtn.style.display = "none";
        switchCameraBtn.style.display = "inline-block";

    } catch {
        alert("Camera not accessible");
    }
};

/* ------------------------------
   🔄 Switch Camera (Front ⇄ Back)
-------------------------------*/
switchCameraBtn.onclick = async () => {

    if (stream) stream.getTracks().forEach(t => t.stop());

    currentCamera = currentCamera === "user" ? "environment" : "user";

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: currentCamera } }
        });

        video.srcObject = stream;

    } catch {
        alert("Unable to switch camera");
    }
};

/* ------------------------------
   Capture Photo
-------------------------------*/
captureBtn.onclick = () => {

    const canvas = document.createElement("canvas");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);

    compressJourneyPhoto(canvas, 10 * 1024).then(blob => {
        if (!blob) {
            alert("Unable to process photo. Please try again.");
            return;
        }

        capturedBlob = blob;
        previewImage.src = URL.createObjectURL(blob);
        previewBox.style.display = "block";
        retakeBtn.style.display = "inline-block";
    });

    if (stream) stream.getTracks().forEach(t => t.stop());

    cameraArea.style.display = "none";
    switchCameraBtn.style.display = "none";
};

function canvasToJpegBlob(canvas, quality) {
    return new Promise(resolve => {
        canvas.toBlob(resolve, "image/jpeg", quality);
    });
}

async function compressJourneyPhoto(sourceCanvas, targetBytes) {
    const sizeSteps = [960, 820, 720, 640, 560, 480, 420, 360, 320, 280, 240];
    const qualitySteps = [0.55, 0.4, 0.3, 0.22, 0.16, 0.12, 0.08, 0.05];

    for (const maxDimension of sizeSteps) {
        const ratio = Math.min(1, maxDimension / Math.max(sourceCanvas.width, sourceCanvas.height));
        const width = Math.max(1, Math.round(sourceCanvas.width * ratio));
        const height = Math.max(1, Math.round(sourceCanvas.height * ratio));

        const resizedCanvas = document.createElement("canvas");
        resizedCanvas.width = width;
        resizedCanvas.height = height;
        resizedCanvas.getContext("2d").drawImage(sourceCanvas, 0, 0, width, height);

        for (const quality of qualitySteps) {
            const blob = await canvasToJpegBlob(resizedCanvas, quality);
            if (blob && blob.size <= targetBytes) {
                return blob;
            }
        }
    }

    const fallbackCanvas = document.createElement("canvas");
    fallbackCanvas.width = 220;
    fallbackCanvas.height = Math.max(1, Math.round(sourceCanvas.height * (220 / sourceCanvas.width)));
    fallbackCanvas.getContext("2d").drawImage(sourceCanvas, 0, 0, fallbackCanvas.width, fallbackCanvas.height);
    return canvasToJpegBlob(fallbackCanvas, 0.05);
}

/* ------------------------------
   Retake Photo (same camera)
-------------------------------*/
retakeBtn.onclick = async () => {

    previewBox.style.display = "none";
    retakeBtn.style.display = "none";

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: currentCamera } }
        });

        video.srcObject = stream;
        cameraArea.style.display = "block";
        switchCameraBtn.style.display = "inline-block";

    } catch {
        alert("Camera not accessible");
    }
};

/* ------------------------------
   Reach / Record Visit
-------------------------------*/
reachBtn.onclick = () => {

    msg.style.display = 'none';
    const type = document.getElementById("visitType").value;

    if (type === "vendor") {
        if (!document.getElementById("vendor").value) {
            alert("Select Dealer");
            return;
        }
    }
    else if (type === "customer") {
        if (!custName.value.trim() || !custMobile.value.trim() || !custAddress.value.trim() || !custPurpose.value.trim()) {
            alert("Enter customer name, address, mobile & purpose");
            return;
        }
    }
    else if (type === "other") {
        if (!otherName.value.trim() || !otherAddress.value.trim() || !otherReason.value.trim()) {
            alert("Enter visitor name, address & reason");
            return;
        }
    }

    else if (type === "subdealer") {
        if (!subDealer.value) {
            alert("Select Sub Dealer");
            return;
        }
    }

    else if (type === "ace") {
        if (!aceVisit.value) {
            alert("Select ACE");
            return;
        }
    }


    msg.style.display = 'block';
    msg.className = "alert alert-info mt-3";
    msg.innerHTML = "📡 Getting GPS...";

    navigator.geolocation.getCurrentPosition(pos => {

        let fd = new FormData();
        fd.append("visit_type", type);
        fd.append("lat", pos.coords.latitude);
        fd.append("lng", pos.coords.longitude);

        if (capturedBlob) fd.append("photo", capturedBlob, "photo.jpg");

        if (type === "vendor") {
            fd.append("vendor_id", document.getElementById("vendor").value);
        }
        else if (type === "customer") {
            fd.append("customer_name", custName.value.trim());
            fd.append("customer_mobile", custMobile.value.trim());
            fd.append("customer_address", custAddress.value.trim());
            fd.append("purpose", custPurpose.value.trim());
        }
        else if (type === "other") {
            fd.append("visitor_name", otherName.value.trim());
            fd.append("visitor_address", otherAddress.value.trim());
            fd.append("purpose", otherReason.value.trim());
        }
        else if (type === "subdealer") {
            fd.append("sub_dealer_id", subDealer.value);
        }

        else if (type === "ace") {
            fd.append("ace_id", aceVisit.value);
        }


        fetch("record_visit", { method: "POST", body: fd })
            .then(r => r.text())
            .then(txt => {
                let j;
                try { j = JSON.parse(txt); } 
                catch { throw new Error("Invalid response"); }

                if (j.status === "recorded") {

                        reachBtn.disabled = true;
                        msg.className = "alert alert-success mt-3";
                        msg.innerHTML = "✔ Visit recorded successfully<br>Redirecting in 30 seconds...";

                        setTimeout(() => {
                            window.location.href = "visit_details";
                        }, 30000); 
                    }

                else {
                    //  Vendor visited before / radius issue / any error
                    msg.className = "alert alert-danger mt-3";
                    msg.innerHTML = j.message;
                }
            })
            .catch(err => {
                msg.className = "alert alert-danger mt-3";
                msg.innerHTML = "Error: " + err.message;
            });

    }, err => {
        msg.className = "alert alert-danger mt-3";
        msg.innerHTML = "GPS Error: " + err.message;
    });
};




</script>


<?php include("footer.php"); ?>
