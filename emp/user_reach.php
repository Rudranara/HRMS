<?php
// user_reach.php (B design applied)
// keep your includes / session logic as-is
include("header.php");

$user_id = $_SESSION['employee_id'];

$vendors = mysqli_query($conn, "SELECT vendor_id, name, lat, lng, area FROM vendors ORDER BY name ASC");

// Fetch user name
$q = mysqli_query($conn, "SELECT name FROM employees WHERE id = '$user_id'");
$res = mysqli_fetch_assoc($q);
$name = $res['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>User Journey Tracker</title>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCH2j-8_qFXr-AwOdr9sgaEa0jQQHp0YZU&libraries=geometry"></script>

<style>
/* ---------- B design (matches admin_home/manage_employee) ---------- */
:root{
  --bg:#f4f7fb;
  --card:#fff;
  --primary-start: #2563eb;
  --primary-end: #1e40af;
  --accent:#06b6d4;
  --muted:#64748b;
  --shadow:0 6px 20px rgba(15,23,42,0.08);
  --radius:12px;
  --text:#0f172a;
}

/* Page */
body{
  font-family: 'Inter', Arial, sans-serif;
  background:var(--bg);
  margin:0;
  color:var(--text);
}

/* Container similar to admin_home */
.container-fluid {
  max-width:1200px;
  margin:28px auto;
  padding:22px;
}

/* Card */
.card {
  background:var(--card);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  overflow:hidden;
  padding:20px;
}

/* Header row */
.header-row {
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  margin-bottom:16px;
}
.header-title {
  font-size:20px;
  color:#1e3a8a;
  font-weight:700;
}
.header-actions { display:flex; gap:8px; align-items:center; }

/* Buttons */
.btn {
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 12px;
  border-radius:8px;
  border:0;
  cursor:pointer;
  font-weight:600;
  color:#fff;
  text-decoration:none;
}
.bg-gradient-primary { background: linear-gradient(90deg,var(--primary-start),var(--primary-end)); }
.bg-gradient-info { background: linear-gradient(90deg,#06b6d4,#0891b2); }
.bg-gradient-dark { background:#111827; }
.btn-sm { font-size:13px; padding:6px 10px; border-radius:8px; }

/* Layout cards */
.section {
  margin-bottom:18px;
}

/* Titles inside cards */
.card h2 {
  margin:0 0 10px 0;
  color:#1e3a8a;
  font-size:18px;
  font-weight:700;
}

/* Inputs */
select, input[type="file"], input[type="text"], input[type="number"] {
  width:100%;
  padding:10px;
  border:1px solid #e5e7eb;
  border-radius:8px;
  box-sizing:border-box;
  font-size:14px;
}

/* Message boxes */
.msg {
  margin-top:12px;
  padding:12px;
  border-radius:8px;
  display:none;
  font-size:14px;
}
.msg.active { display:block; }
.msg.info { background:#dbeafe; color:#1e3a8a; }
.msg.success { background:#dcfce7; color:#15803d; }
.msg.warning { background:#fef3c7; color:#b45309; }
.msg.error { background:#fee2e2; color:#b91c1c; }

/* Preview image */
.preview-box { margin-top:12px; display:none; text-align:center; }
.preview-box img {
  width:100%;
  max-width:360px;
  border-radius:10px;
  box-shadow:0 6px 18px rgba(15,23,42,0.06);
}

/* Responsive */
@media(max-width:768px){
  .header-row { flex-direction:column; align-items:flex-start; gap:10px; }
  .card { padding:16px; }
}
</style>
</head>
<body>

<div class="container-fluid">
  <div class="header-row">
    <div class="header-title">👋 Welcome, <?php echo htmlspecialchars($name); ?></div>
    <div class="header-actions">
      
      
    </div>
  </div>

  <!-- Start Journey Card -->
  <div class="card section">
    <h2>🚀 Start Your Journey</h2>
    <p style="margin:0 0 12px 0; color:var(--muted)">Click below to mark your starting location.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <button id="startBtn" class="btn bg-gradient-primary btn-sm" onclick="startJourney()">Start Journey</button>
      <div id="startMsg" class="msg info" style="flex:1;min-width:200px;"></div>
    </div>
  </div>

  <!-- Mark Vendor Visit Card -->
  <div class="card section">
    <h2>📍 Mark Vendor Visit</h2>
    <p style="margin:0 0 12px 0; color:var(--muted)">Select the vendor you reached and upload a live photo as proof.</p>

    <div style="display:grid;grid-template-columns:1fr 220px;gap:12px;">
      <div>
        <label style="font-weight:600;color:#374151;">Select Vendor</label>
        <select id="vendor">
          <?php while($v = mysqli_fetch_assoc($vendors)) { ?>
            <option value="<?php echo $v['vendor_id']; ?>"
                    data-lat="<?php echo $v['lat']; ?>"
                    data-lng="<?php echo $v['lng']; ?>"
                    data-area="<?php echo $v['area']; ?>">
              <?php echo htmlspecialchars($v['name']); ?>
            </option>
          <?php } ?>
        </select>

        <label style="font-weight:600;color:#374151;margin-top:10px;">Take Live Photo</label>
        <input type="file" id="photo" accept="image/*" capture="environment">

        <div class="preview-box" id="previewBox">
          <img id="previewImage" alt="Preview">
        </div>

        <div style="margin-top:12px;">
          <button id="reachBtn" class="btn bg-gradient-primary btn-sm" onclick="recordVisit()">I Am Reached</button>
          <button id="retakeBtn" class="btn bg-gradient-info btn-sm" type="button" onclick="retakePhoto()" style="display:none;margin-left:8px;">🔁 Retake</button>
        </div>
        <div id="msg" class="msg info"></div>
      </div>

      <!-- Right column: vendor info / help -->
      <div style="background:#f8fafc;border-radius:8px;padding:12px;">
        <h3 style="margin:0 0 8px 0;color:#1e3a8a;font-size:16px;">Tips</h3>
        <ul style="margin:0 0 8px 18px;color:var(--muted);">
          <li>Enable GPS and allow location access.</li>
          <li>Use the camera option to take a live photo.</li>
          <li>Make sure you're within the vendor radius to record the visit.</li>
        </ul>
        <div style="color:var(--muted);font-size:13px;">Allowed radius handled dynamically per vendor.</div>
      </div>
    </div>
  </div>

  
</div>

<script>
// --- Start Journey ---
function startJourney() {
  const startMsg = document.getElementById("startMsg");
  startMsg.className = "msg info active";
  startMsg.textContent = "📡 Getting your GPS location...";

  navigator.geolocation.getCurrentPosition(function(pos) {
    const fd = new FormData();
    fd.append('start_lat', pos.coords.latitude);
    fd.append('start_lng', pos.coords.longitude);

    fetch("start_journey", { method: "POST", body: fd })
      .then(r => r.json())
      .then(j => {
        startMsg.className = "msg success active";
        startMsg.textContent = "✅ " + (j.message || "Journey started");
      })
      .catch(e => {
        startMsg.className = "msg error active";
        startMsg.textContent = "❌ Failed: " + e;
      });
  }, function(err) {
    startMsg.className = "msg error active";
    startMsg.textContent = "❌ GPS Error: " + err.message;
  }, {enableHighAccuracy:true});
}

// --- Photo preview and retake ---
const photoInput = document.getElementById('photo');
photoInput.addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('previewImage').src = e.target.result;
    document.getElementById('previewBox').style.display = 'block';
    document.getElementById('retakeBtn').style.display = 'inline-flex';
  }
  reader.readAsDataURL(file);
});

function retakePhoto() {
  document.getElementById('photo').value = '';
  document.getElementById('previewBox').style.display = 'none';
  document.getElementById('previewImage').src = '';
  document.getElementById('retakeBtn').style.display = 'none';
  document.getElementById('photo').click();
}

// --- Record Vendor Visit ---
function recordVisit() {
  const msg = document.getElementById('msg');
  msg.className = "msg info active";
  msg.textContent = '📡 Getting your GPS location...';

  const vendor = document.getElementById("vendor");
  const vendor_id = vendor.value;
  const vendorLat = parseFloat(vendor.options[vendor.selectedIndex].getAttribute("data-lat"));
  const vendorLng = parseFloat(vendor.options[vendor.selectedIndex].getAttribute("data-lng"));
  let vendorArea = parseFloat(vendor.options[vendor.selectedIndex].getAttribute("data-area")) || 100;

  if (vendorArea < 5) vendorArea *= 1000;

  navigator.geolocation.getCurrentPosition(function(position) {
    const userLat = position.coords.latitude;
    const userLng = position.coords.longitude;

    const a = new google.maps.LatLng(userLat, userLng);
    const b = new google.maps.LatLng(vendorLat, vendorLng);
    const meters = google.maps.geometry.spherical.computeDistanceBetween(a, b);
    const km = (meters / 1000).toFixed(3);

    if (meters > vendorArea) {
      msg.className = "msg warning active";
      msg.innerHTML = `⚠️ You are outside the vendor's allowed area.<br>Allowed Radius: ${vendorArea.toFixed(1)} m<br>Current Distance: ${meters.toFixed(1)} m`;
      return;
    }

    const fd = new FormData();
    fd.append('vendor_id', vendor_id);
    fd.append('lat', userLat);
    fd.append('lng', userLng);
    fd.append('distance', km);

    const fileInput = document.getElementById('photo');
    if (fileInput.files.length > 0) fd.append('photo', fileInput.files[0]);

    fetch('record_visit', { method: 'POST', body: fd })
      .then(async r => {
        const text = await r.text();
        try {
          const j = JSON.parse(text);
          if (j.status === 'recorded') {
            msg.className = "msg success active";
            msg.innerHTML = "✅ Visit recorded successfully!<br>Distance: " + j.distance_km + " km";
          } else if (j.status === 'already') {
            msg.className = "msg warning active";
            msg.innerHTML = "⚠️ You already recorded this vendor today.";
          } else {
            msg.className = "msg error active";
            msg.innerHTML = "❌ Error: " + (j.error || j.message || 'Unknown error');
          }
        } catch(e) {
          msg.className = "msg error active";
          msg.innerHTML = "⚠️ Invalid response: " + text;
        }
      })
      .catch(e => {
        msg.className = "msg error active";
        msg.textContent = "❌ Request failed: " + e;
      });
  }, function(err) {
    msg.className = "msg error active";
    msg.textContent = '❌ GPS error: ' + err.message;
  }, {enableHighAccuracy:true});
}
</script>

</body>
</html>




