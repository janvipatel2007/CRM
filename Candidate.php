<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------- Connect to DB ----------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "crm";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$userRole  = $_SESSION['user_role'] ?? "user"; 
$username  = $_SESSION['user_name'] ?? "User";

// ---------- Ensure user is logged in ----------
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// ---------- Get logged-in user info ----------
$userName = $_SESSION['user_name'] ?? "Guest";
$userRole = $_SESSION['user_role'] ?? "user";

$teamMembers = [];
$empQuery = $conn->query("SELECT name FROM team_members ORDER BY name ASC");
if ($empQuery && $empQuery->num_rows > 0) {
    while ($r = $empQuery->fetch_assoc()) {
        $teamMembers[] = $r['name'];
    }
}

// ---- Handle Add Candidate ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate']) && !isset($_POST['edit_id'])) {
    $name       = $_POST['name'] ?? '';
    $enrolledBy = $_POST['enrolledBy'] ?? 'Not Selected';
    $date       = $_POST['date'] ?? NULL;
    $plan       = $_POST['plan'] ?? '-';
    $amount     = $_POST['amount'] ?? 0;
    $recruiter  = $_POST['recruiter'] ?? '-';

    $stmt = $conn->prepare("INSERT INTO candidates (name, enrolled_by, enrollment_date, plan, amount_paid, recruiter, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssssds", $name, $enrolledBy, $date, $plan, $amount, $recruiter);
    $stmt->execute();
    $stmt->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ---- Handle Edit Candidate ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = $_POST['edit_id'];
    $name       = $_POST['name'] ?? '';
    $enrolledBy = $_POST['enrolledBy'] ?? 'Not Selected';
    $date       = $_POST['date'] ?? NULL;
    $plan       = $_POST['plan'] ?? '-';
    $amount     = $_POST['amount'] ?? 0;
    $recruiter  = $_POST['recruiter'] ?? '-';

    $stmt = $conn->prepare("UPDATE candidates SET name=?, enrolled_by=?, enrollment_date=?, plan=?, amount_paid=?, recruiter=? WHERE id=?");
    $stmt->bind_param("ssssdsi", $name, $enrolledBy, $date, $plan, $amount, $recruiter, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ---- Handle Delete / Toggle Status ----
if(isset($_POST['delete_id'])){
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("UPDATE candidates SET status='inactive' WHERE id=?");
    $stmt->bind_param("i",$id);
    echo $stmt->execute() ? "success" : "error";
    $stmt->close();
    exit;
}

if(isset($_POST['toggle_status'])){
    $id = intval($_POST['id']);
    $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
    $stmt = $conn->prepare("UPDATE candidates SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ---- Fetch All Candidates ----
$candidates = [];
$result = $conn->query("SELECT * FROM candidates ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $candidates[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<title>Candidate Dashboard</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;transition:all 0.3s ease;}
html{scroll-behavior:smooth;}
body{    background: linear-gradient(135deg, #dfe9f3, #ffffff); color:#001a40;overflow-x:hidden;overflow-y:auto;}
body.dark{background:#0f172a;color:#e0e0e0;}
header{background:linear-gradient(135deg,#96a7cb,#1e3c72);padding:10px 25px;display:flex;justify-content:space-between;align-items:center;height:70px;position:fixed;top:0;left:0;width:100%;z-index:1000;}
.header-left img.logo{height:55px;width:auto;border-radius:12px;}
.header-right{display:flex;align-items:center;gap:18px;}
.switch{position:relative;display:inline-block;width:60px;height:32px;}
.switch input{display:none;}
.slider{position:absolute;top:0;left:0;right:0;bottom:0;background-color:#ccc;border-radius:34px;}
.slider::before{content:"☀️";position:absolute;height:26px;width:26px;left:3px;bottom:3px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;transition:transform 0.4s,content 0.4s;}
input:checked+.slider{background-color:#ccc;}
input:checked+.slider::before{transform:translateX(28px) rotate(360deg);content:"🌙";}

/* Profile Dropdown */
.profile{position:relative;display:flex;align-items:center;gap:10px;cursor:pointer;}
.profile span{font-weight:600;color:#fff;}
.profile-img{width:38px;height:38px;border-radius:50%;background:#fff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:bold;box-shadow:0 4px 15px rgba(0,0,0,0.3);}
.dropdown-menu{ position:absolute; top:50px; right:0; background:#fff; color:#000; padding:8px 12px; border-radius:6px; box-shadow:0 6px 12px rgba(0,0,0,.15);}
.dropdown-menu.hidden{display:none;}
.dropdown-menu a{ text-decoration:none; color:#000; font-weight:500; }

.hidden{display:none;}

.sidebar{width:230px;background:#fff;border-right:1px solid #ddd;padding:20px 0;position:fixed;top:80px;bottom:0;}
.sidebar button{width:100%;margin:5px 0;text-align:left;font-weight:500;border:none;font-size:15px;background-color:transparent;color:#1a1a1a;cursor:pointer;padding:10px 25px;border-left:4px solid transparent;}
.sidebar button:hover{background:#e0e0e0;color:#000;}
.sidebar button.active{background:#d6d6d6;color:#000;font-weight:600;}
body.dark .sidebar{background:linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{color:white;}
body.dark .sidebar button.active{background:#ababa5ff;color:#000;font-weight:600;}
body.dark .sidebar button:hover{background:#ababa5ff;color:#000;}
.container{ margin-left:230px; margin-top:80px; padding:20px; min-height: calc(100vh - 90px); background:white;}
h2{margin-bottom:20px;}
#addCandidateBtn{padding:12px 20px;margin-bottom:15px;background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;border:none;border-radius:8px;cursor:pointer;float:right;}
#addCandidateBtn:hover{background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}
.tabs{display:flex;border-bottom:2px solid #ddd;margin-bottom:20px;}
.tab{padding:10px 20px;cursor:pointer;border-bottom:3px solid transparent;}
.tab.active{color:#1976d2;border-color:#1976d2;font-weight:bold;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{text-align:left;padding:14px 12px;border-bottom:1px solid #eee;font-size:14px;}
th{background:#f9f9f9;}
.action-btn{cursor:pointer;font-size:16px;background:transparent;border:none;margin-left:5px;}

/* Overlay & Popup */
.overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.2);backdrop-filter:blur(10px);opacity:0;visibility:hidden;transition:0.3s;z-index:100;}
.overlay.active{opacity:1;visibility:visible;backdrop-filter:blur(6px);}
.popup{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(0.9);width:500px;max-width:95%;background:#fff;border-radius:20px;box-shadow:0 15px 40px rgba(0,0,0,0.4);padding:30px;opacity:0;visibility:hidden;transition:opacity 0.3s ease,transform 0.3s ease;z-index:101;}
.popup.active{opacity:1;visibility:visible;transform:translate(-50%,-50%) scale(1);}
.close-btn{position:absolute;top:15px;right:20px;font-size:26px;color:#333;cursor:pointer;font-weight:bold;}
.close-btn:hover{color:#1976d2;}
#addCandidateForm input,#addCandidateForm select{padding:10px;border-radius:6px;border:1px solid #ccc;margin-bottom:12px;width:100%;font-size:14px;}
#addCandidateForm button{padding:10px 15px;background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;border:none;border-radius:6px;cursor:pointer;}
#addCandidateForm button:hover{background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}

/* Dark Mode */
body.dark .container{background:#1e293b;color:#e2e8f0;}
body.dark table{background:#1e293b;border-color:#334155;}
body.dark th{background:#334155;color:#e2e8f0;}
body.dark td{border-bottom:1px solid #334155;color:#f1f5f9;}
body.dark .tab{color:#cbd5e1;}
body.dark .tab.active{color:#60a5fa;border-color:#60a5fa;}
body.dark h2{color:#93c5fd;}
body.dark .popup{background:#1e293b;color:#f1f5f9;}
body.dark .close-btn{color:#f1f5f9;}
body.dark .close-btn:hover{color:#60a5fa;}
body.dark #addCandidateForm input,body.dark #addCandidateForm select{background:#0f172a;color:#e2e8f0;border:1px solid #334155;}

/* Side Panel with stronger blur */
.side-panel {
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(12px);
  position: fixed;
  top: 70px;
  right: -700px;
  width: 500px;
  height: 80%;
  box-shadow: -4px 0 12px rgba(0,0,0,0.2);
  padding: 20px;
  transition: right 0.3s ease, backdrop-filter 0.3s ease;
  z-index: 105;
  overflow-y: auto;
  border-left: 1px solid #ddd;
  border-radius: 0 0 0 12px;
}

/* Shift all text content inside the panel */
.side-panel h2{

  font-size: 20px;
  font-family: 'Poppins',sans-serif;
}
.side-panel p {
  font-size: 20px;
 font-family:'Poppins',sans-serif;
}
.side{
  margin-top: 90px;
}
.side-panel.active {
  right: 0;
  backdrop-filter: blur(24px);
}

.side-panel .close-btn {
  position: absolute;
  top: 10px;
  right: 15px;
  font-size: 20px;
  cursor: pointer;
}

.side-panel .panel-btn {
  width: 100%;
  padding: 10px;
  margin-top: 10px;
  border: none;
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
  border-radius: 6px;
  cursor: pointer;
  font-family:'Poppins',sans-serif;
  font-size: 18px;
}



body.dark .side-panel {
  background: #1e293b;
  color: #f1f5f9;
  border-color: #334155;
}

</style>
</head>
<body>

<header>
  <div class="header-left"><img src="nova.png" alt="nova staff" class="logo"></div>
  <div class="header-right">
    <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>

    <!-- ✅ Profile with dropdown -->
    <div class="profile" id="profileMenu">
      <span id="profileName"><?= htmlspecialchars($userName) ?>&nbsp;(<?= ucfirst($userRole) ?>)</span>
      <div class="profile-img" id="profileInitials"><?= substr($userName,0,1) ?></div>

      <div class="dropdown-menu hidden" id="logoutDropdown">
        <a href="login.php">Logout</a>
      </div>
    </div>
  </div>
</header>
<div class="sidebar">
  <button onclick="location.href='index.php'">Dashboard</button>
  <button onclick="location.href='all_leads.php'">All Leads</button>
  <button class="active" onclick="location.href='Candidate.php'">Candidate</button>
  <button onclick="location.href='daily_report.php'">Daily Report</button>
  <button onclick="location.href='target.php'">Targets</button><br><br><br><br>

  <?php if($userRole === 'admin'): ?>
    <button onclick="location.href='employees.php'">Employees</button>
    <button onclick="location.href='payment.php'">Payment</button>
    <button onclick="location.href='setting.php'">⚙ Settings</button>
  <?php endif; ?>
</div>


<div class="container">
  <h2>My Applications</h2>
  <p>Thank you for your application!</p>
  <button id="addCandidateBtn">Add Candidate</button>

  <div class="tabs">
    <div class="tab active" data-tab="active">Active (<span id="activeCount">0</span>)</div>
    <div class="tab" data-tab="inactive">Inactive (<span id="inactiveCount">0</span>)</div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Candidate Name</th>
        <th>Enrolled By</th>
        <th>Enrollment Date</th>
        <th>Plan</th>
        <th>Amount Paid</th>
        <th>Recruiter</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="candidateTableBody">
      <?php foreach ($candidates as $c): ?>
        <tr data-status="<?= htmlspecialchars($c['status']) ?>" data-id="<?= $c['id'] ?>">
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?= htmlspecialchars($c['enrolled_by']) ?></td>
          <td><?= htmlspecialchars($c['enrollment_date']) ?></td>
          <td><?= htmlspecialchars($c['plan']) ?></td>
          <td><?= htmlspecialchars($c['amount_paid']) ?></td>
          <td><?= htmlspecialchars($c['recruiter']) ?></td>
          <td>
            <div class="action-btn">
            <button class="action-dots">⋮</button></div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ADD CANDIDATE POPUP -->
<div class="overlay" id="formOverlay"></div>
<div class="popup" id="addFormPopup">
  <span class="close-btn" id="closeFormBtn">&times;</span>
  <h2>Add Candidate</h2>
  <form id="addCandidateForm" method="POST">
    <input type="hidden" name="add_candidate" value="1">
    <input type="text" name="name" placeholder="Candidate Name" required>
<select name="enrolledBy" required>
  <option value="">Enrolled By</option>
  <?php foreach ($teamMembers as $member) { ?>
      <option value="<?= htmlspecialchars($member) ?>"><?= htmlspecialchars($member) ?></option>
  <?php } ?>
</select>
    <input type="date" name="date" placeholder="Enrollment Date">
    <input type="text" name="plan" placeholder="Plan">
    <input type="number" name="amount" placeholder="Amount Paid">
    <input type="text" name="recruiter" placeholder="Recruiter">
    <button type="submit">Add Candidate</button>
  </form>
</div>

<!-- SIDE PANEL -->
<div class="side-panel" id="sidePanel">
  <span class="close-btn" id="closePanel">&times;</span>
  <div class="side">
  <h2 id="panelName"></h2>
  <p><strong>Enrolled By:</strong> <span id="panelEnrolledBy"></span></p>
  <p><strong>Enrollment Date:</strong> <span id="panelDate"></span></p>
  <p><strong>Plan:</strong> <span id="panelPlan"></span></p>
  <p><strong>Amount Paid:</strong> <span id="panelAmount"></span></p>
  <p><strong>Recruiter:</strong> <span id="panelRecruiter"></span></p>
  </div>
</div>

<script>
// Theme Toggle
const themeToggle=document.getElementById("themeToggle");
if(localStorage.getItem("darkMode")==="true"){document.body.classList.add("dark");themeToggle.checked=true;}
themeToggle.addEventListener("change",()=>{document.body.classList.toggle("dark");localStorage.setItem("darkMode",document.body.classList.contains("dark"));});

// ✅ Profile Dropdown Script
const profileMenu = document.getElementById('profileMenu');
const logoutDropdown = document.getElementById('logoutDropdown');
profileMenu.addEventListener('click', e => {
  e.stopPropagation();
  logoutDropdown.classList.toggle('hidden');
});
document.addEventListener('click', e => {
  if (!profileMenu.contains(e.target)) logoutDropdown.classList.add('hidden');
});

// Popup
const addCandidateBtn=document.getElementById('addCandidateBtn');
const addFormPopup=document.getElementById('addFormPopup');
const formOverlay=document.getElementById('formOverlay');
const closeFormBtn=document.getElementById('closeFormBtn');
addCandidateBtn.addEventListener('click',()=>{addFormPopup.classList.add('active');formOverlay.classList.add('active');});
function closeFormPopup(){addFormPopup.classList.remove('active');formOverlay.classList.remove('active');}
closeFormBtn.addEventListener('click',closeFormPopup);
formOverlay.addEventListener('click',closeFormPopup);

const profileName = document.getElementById('profileName').innerText.trim();
const initials = profileName
  .split(' ')
  .map(word => word.charAt(0).toUpperCase())
  .join('');
document.getElementById('profileInitials').innerText = initials || 'U'; // 'U' as fallback

// Tabs filter
const tabs=document.querySelectorAll('.tab');
tabs.forEach(tab=>{
  tab.addEventListener('click',()=>{
    tabs.forEach(t=>t.classList.remove('active'));
    tab.classList.add('active');
    const type=tab.dataset.tab;
    document.querySelectorAll('#candidateTableBody tr')
      .forEach(r=>r.style.display=(r.dataset.status===type?"":"none"));
  });
});
document.querySelector('.tab.active').click();

// Count Active & Inactive rows
function updateCounts() {
  const rows = document.querySelectorAll('#candidateTableBody tr');
  let activeCount = 0, inactiveCount = 0;
  rows.forEach(r => {
    if (r.dataset.status === 'active') activeCount++;
    else if (r.dataset.status === 'inactive') inactiveCount++;
  });
  document.getElementById('activeCount').textContent = activeCount;
  document.getElementById('inactiveCount').textContent = inactiveCount;
}

// SIDE PANEL
const sidePanel = document.getElementById('sidePanel');
const closePanel = document.getElementById('closePanel');
let currentCandidateId = null;

document.querySelectorAll('.action-dots').forEach((btn)=>{
  btn.addEventListener('click', (e)=>{
    e.stopPropagation();
    const row = btn.closest('tr');
    currentCandidateId = row.dataset.id;
    const status = row.dataset.status;
    
    document.getElementById('panelName').textContent = row.children[0].textContent;
    document.getElementById('panelEnrolledBy').textContent = row.children[1].textContent;
    document.getElementById('panelDate').textContent = row.children[2].textContent;
    document.getElementById('panelPlan').textContent = row.children[3].textContent;
    document.getElementById('panelAmount').textContent = row.children[4].textContent;
    document.getElementById('panelRecruiter').textContent = row.children[5].textContent;

    // Show delete button only if active
    if(status === 'active') {
      if(!document.getElementById('panelDeleteBtn')){
        const btnEl = document.createElement('button');
        btnEl.textContent = 'Delete Candidate';
        btnEl.id = 'panelDeleteBtn';
        btnEl.className = 'panel-btn';
        btnEl.onclick = deleteCandidatePanel;
        sidePanel.appendChild(btnEl);
      }
    } else {
      const delBtn = document.getElementById('panelDeleteBtn');
      if(delBtn) delBtn.remove();
    }

    // Show panel and add blur to overlay
    sidePanel.classList.add('active');
    formOverlay.classList.add('active');
  });
});

closePanel.addEventListener('click', ()=>{sidePanel.classList.remove('active');formOverlay.classList.remove('active');});
document.addEventListener('click',()=>{sidePanel.classList.remove('active');formOverlay.classList.remove('active');});
sidePanel.addEventListener('click', e=>e.stopPropagation());

// Delete Candidate
function deleteCandidatePanel(){
  if(!currentCandidateId) return;
  if(!confirm("Are you sure you want to delete this candidate?")) return;
  
  fetch('', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `delete_id=${currentCandidateId}`
  })
  .then(res=>res.text())
  .then(data=>{
    if(data==="success"){
      document.querySelector(`tr[data-id='${currentCandidateId}']`).dataset.status = 'inactive';
      updateCounts();
      sidePanel.classList.remove('active');
      formOverlay.classList.remove('active');
      document.querySelector('.tab.active').click();
    } else {
      alert('Failed to delete.');
    }
  });
}

updateCounts();
</script>

</body>
</html>