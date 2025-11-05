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

// ---------- Generate initials ----------
$initials = "";
if (!empty($userName)) {
    $nameParts = explode(' ', trim($userName));
    foreach ($nameParts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    $initials = substr($initials, 0, 2);
}

// ---------- Fetch deleted employees ----------
$deletedEmployees = $conn->query("SELECT * FROM deleted_employees ORDER BY deleted_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Deleted Employees</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;}
html,body{width:100%;height:100%;overflow-x:hidden;}
body{background:#ffffff;color:#001a40;zoom:100%;transition:background 0.3s,color 0.3s;}

/* -------- Header -------- */
header{
  background:linear-gradient(135deg,#96a7cb,#1e3c72);
  padding:10px 25px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  height:70px;
  position:fixed;
  top:0;left:0;width:100%;
  box-shadow:0 4px 12px rgba(0,0,0,0.2);
  z-index:1000;
}
.header-left img{height:55px;border-radius:10px;}
.header-right{display:flex;align-items:center;gap:20px;}

/* -------- Dark Mode Switch -------- */
.switch{position:relative;display:inline-block;width:60px;height:30px;}
.switch input{display:none;}
.slider{
  position:absolute;top:0;left:0;right:0;bottom:0;
  background:#ccc;border-radius:34px;cursor:pointer;
}
.slider::before{
  content:"☀️";
  position:absolute;
  height:24px;width:24px;
  left:3px;bottom:3px;
  background:white;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:15px;
  transition:transform 0.4s,content 0.4s;
}
input:checked + .slider::before{
  transform:translateX(28px);
  content:"🌙";
}

/* -------- Profile -------- */
.profile{display:flex;align-items:center;gap:10px;position:relative;cursor:pointer;}
.profile span{color:#fff;font-weight:600;}
.profile-img{
  width:40px;height:40px;border-radius:50%;
  background:#fff;color:#1e3c72;font-weight:700;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 2px 6px rgba(0,0,0,0.2);
}
.logout-box{
  position:absolute;top:55px;right:0;
  background:#fff;color:#000;
  border-radius:8px;box-shadow:0 6px 15px rgba(0,0,0,0.3);
  min-width:130px;display:none;z-index:999;
}
.logout-box a{
  display:block;padding:8px 15px;
  text-decoration:none;color:#000;font-weight:500;
}
.logout-box a:hover{background:#f3f3f3;border-radius:8px;}

/* -------- Sidebar -------- */
.sidebar{
  position:fixed;top:70px;left:0;
  width:230px;height:calc(100vh - 70px);
  background:#fff;border-right:1px solid #ddd;
  padding:20px 0;
}
.sidebar button{
  width:100%;padding:12px 25px;
  background:transparent;border:none;
  text-align:left;cursor:pointer;
  font-size:15px;font-weight:500;
  color:#1a1a1a;transition:0.3s;
}
.sidebar button:hover{background:#e0e0e0;}
.sidebar button.active{background:#d6d6d6;font-weight:600;color:#000;}

/* -------- Main Content -------- */
.main-content{
  margin-left:230px;margin-top:70px;
  padding:30px 40px;
  background:#ffffff;
  height:calc(100vh - 70px);
  overflow-y:auto;
}
.container{max-width:1150px;margin:auto;}

/* -------- Table -------- */
/* -------- Table -------- */
.table{width:100%; border-collapse:collapse;table-layout:fixed;}
.table th,.table td{
 padding:12px; text-align:center; font-size:14px;color: #001a40; border-bottom:1px solid #c8c2c2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.table th{
  background:#cfdbe7;color:#1e3c72;
  text-transform:uppercase;font-size:13px;letter-spacing:0.5px;
}
.table-hover tbody tr:hover td{background:#eaf1ff;}
.text-success{text-align:left;margin-top:10px;color:#1e3c72 !important;font-weight:700;}
.text-muted{color:#777 !important;text-align:left;font-size:15px;}
.back-btn{
  padding:10px 18px;
  background:linear-gradient(135deg,#87b6c6,#cfe9f6);
  color:#001a40;border-radius:10px;
  font-weight:500;margin-top:15px;width:200px;
}
.back-btn:hover{transform:scale(1.05);}

/* -------- Dark Mode -------- */
body.dark{background:#0f172a;color:#e0e0e0;}

body.dark .sidebar{background:#111827;border-color:#334155;}
body.dark .sidebar button{color:#e2e8f0;}
body.dark .sidebar button:hover{background:#374151;color:#fff;}
body.dark .sidebar button.active{background:#374151;color:#fff;}
body.dark .main-content{background:#0f172a;}
/* 🌙 Dark mode table styling fix for resigned.php */
body.dark table {
  background-color: #0f172a; /* deep navy tone */
  color: #e2e8f0;
  border-collapse: collapse;
  width: 100%;
}

body.dark th {
  background-color: #1e293b;
  color: #f8fafc;
  border-bottom: 2px solid #334155;
}

body.dark td {
  background-color: #1e293b;
  color: #f1f5f9;
  border-bottom: 1px solid #334155;
}

body.dark tr:nth-child(even) td {
  background-color: #273449;
}

body.dark tr:hover td {
  background-color: #334155;
}

body.dark thead {
  background-color: #1e293b;
  color: #fff;
}

body.dark .table-container {
  background-color: transparent;
}

body.dark .text-success{color:#93c5fd !important;}
body.dark .logout-box{background:#1f2937;color:#e5e7eb;border:1px solid #374151;}
body.dark .logout-box a{color:#e5e7eb;}
body.dark .logout-box a:hover{background:#374151;}
</style>
</head>
<body>

<header>
  <div class="header-left"><img src="nova.png" alt="NOVA STAFFS"></div>
  <div class="header-right">
    <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>
    <div class="profile" id="profileMenu">
      <span><?= htmlspecialchars($userName) ?> (<?= ucfirst($userRole) ?>)</span>
      <div class="profile-img" id="profileInitials"><?= $initials ?></div>
      <div class="logout-box" id="logoutDropdown">
        <a href="logout.php">Logout</a>
      </div>
    </div>
  </div>
</header>

<div class="sidebar">
  <button onclick="location.href='index.php'">Dashboard</button>
  <button onclick="location.href='all_leads.php'">All Leads</button>
  <button onclick="location.href='notes.php'">Notes</button>
  <button onclick="location.href='Candidate.php'">Candidate</button>
  <button onclick="location.href='daily_report.php'">Daily Report</button>
  <button onclick="location.href='target.php'">Targets</button><br><br><br>
  <?php if($userRole === 'admin'): ?>
    <button class="active" onclick="location.href='employees.php'">Employees</button>
    <button onclick="location.href='payment.php'">Payment</button>
    <button onclick="location.href='setting.php'">⚙ Settings</button>
  <?php endif; ?>
</div>

<div class="main-content">
  <div class="container">
    <h3 class="text-success">Resigned</h3>
    <p class="text-muted">Below is the list of deleted employees:</p>

    <table class="table table-hover">
      <thead>
        <tr>
          <th>Joining Date</th>
          <th>Employee Name</th>
          <th>Email</th>
          <th>Phone No</th>
          <th>Address</th>
          <th>Department</th>
          <th>Designation</th>
          <th>Salary</th>
          <th>Deleted At</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($deletedEmployees && $deletedEmployees->num_rows > 0): ?>
          <?php while($row = $deletedEmployees->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['join_date']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['mobile']) ?></td>
              <td><?= htmlspecialchars($row['address']) ?></td>
              <td><?= htmlspecialchars($row['department']) ?></td>
              <td><?= htmlspecialchars($row['designation']) ?></td>
              <td><?= htmlspecialchars($row['salary']) ?></td>
              <td><?= htmlspecialchars($row['deleted_at']) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="9" class="text-center text-muted">No deleted employees yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="text-center">
      <a href="employees.php" class="btn back-btn">⬅ Back to Employees</a>
    </div>
  </div>
</div>

<script>
// ----- Dark Mode -----
const themeToggle=document.getElementById("themeToggle");
if(localStorage.getItem("darkMode")==="true"){
  document.body.classList.add("dark");
  themeToggle.checked=true;
}
themeToggle.addEventListener("change",()=>{
  document.body.classList.toggle("dark");
  localStorage.setItem("darkMode",document.body.classList.contains("dark"));
});

// ----- Logout Dropdown -----
const profileMenu=document.getElementById("profileMenu");
const logoutDropdown=document.getElementById("logoutDropdown");
profileMenu.addEventListener("click",(e)=>{
  e.stopPropagation();
  logoutDropdown.style.display = logoutDropdown.style.display==="block"?"none":"block";
});
document.addEventListener("click",(e)=>{
  if(!profileMenu.contains(e.target)){
    logoutDropdown.style.display="none";
  }
});
</script>
</body>
</html>