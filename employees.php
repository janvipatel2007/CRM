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

// ---------- Ensure user is logged in ----------
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ---------- Get logged-in user info ----------
$userName = $_SESSION['user_name'] ?? "Guest";
$userRole = $_SESSION['user_role'] ?? "user";

// ---------- Generate initials ----------
$initials = '';
if (!empty($userName)) {
    $nameParts = explode(' ', trim($userName));
    foreach ($nameParts as $part) {
        $initials .= strtoupper($part[0]);
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // ---------- Get employee details ----------
    $result = $conn->query("SELECT name, email, mobile, address, department, designation, salary, join_date 
                            FROM employees WHERE id=$delete_id");

    if (!$result) {
        die("SELECT Query Failed: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        $emp = $result->fetch_assoc();

        // ---------- Prepare insert for deleted_employees ----------
        $stmt = $conn->prepare("INSERT INTO deleted_employees 
            (name, email, mobile, address, department, designation, salary, join_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare Failed: " . $conn->error);
        }

        $stmt->bind_param(
            "ssssssss",
            $emp['name'],
            $emp['email'],
            $emp['mobile'],
            $emp['address'],
            $emp['department'],
            $emp['designation'],
            $emp['salary'],
            $emp['join_date']
        );
        $stmt->execute();
        $stmt->close();

        // ---------- Delete original employee ----------
        if (!$conn->query("DELETE FROM employees WHERE id=$delete_id")) {
            die("Delete Failed: " . $conn->error);
        }

        // ---------- Redirect ----------
        header("Location: resigned.php");
        exit;
    } else {
        echo "<script>alert('Employee not found!');</script>";
    }
}



// ---------- Fetch all employees ----------
$sql = "SELECT id, name, email, mobile, address, department, designation, salary, join_date FROM employees ORDER BY id DESC";
$result = $conn->query($sql);



?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Employees</title>
<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif;transition:all 0.3s ease; }
body{ background: linear-gradient(135deg,#dfe9f3,#ffffff); color:#001a40; overflow-x:hidden; overflow-y:auto;}
body.dark{ background:#0f172a; color:#e0e0e0; }

header{ background: linear-gradient(135deg, #96a7cb, #1e3c72); padding:10px 25px; display:flex; justify-content:space-between; align-items:center; height:70px; position:fixed; top:0; left:0; width:100%; z-index:1000; box-shadow:0 4px 12px rgba(0,0,0,0.2); }
.header-left img.logo{ height:55px; width:auto; border-radius:12px; }
.header-right{ display:flex; align-items:center; gap:18px; }

.switch{ position:relative; display:inline-block; width:60px; height:32px; }
.switch input{ display:none; }
.slider{ position:absolute; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px; }
.slider::before{ content:"☀️"; position:absolute; height:26px; width:26px; left:3px; bottom:3px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; transition:transform 0.4s,content 0.4s; }
input:checked+.slider{ background-color:#ccc; }
input:checked+.slider::before{ transform:translateX(28px) rotate(360deg); content:"🌙"; }

.profile{display:flex;align-items:center;gap:10px;position:relative;cursor:pointer}
.profile span{color:#fff;font-weight:600}
.profile-img{width:38px;height:38px;border-radius:50%;background:#fff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:700}
.dropdown-menu{ position:absolute; top:50px; right:0; background:#fff; color:#000; padding:8px 12px; border-radius:6px; box-shadow:0 6px 12px rgba(0,0,0,.15);}
.dropdown-menu.hidden{display:none;}
.dropdown-menu a{ text-decoration:none; color:#000; font-weight:500; }

.sidebar{ width:230px; background:#fff; border-right:1px solid #ddd; padding:20px 0; position:fixed; top:80px; bottom:0; box-shadow:none; }
.sidebar button{ width:100%; margin:5px 0; text-align:left; font-weight:500; border:none; font-size:15px; background-color:transparent; color:#1a1a1a; cursor:pointer; padding:10px 25px; border-left:4px solid transparent; transition:all 0.3s ease; }
.sidebar button:hover{ background:#e0e0e0; color:#000; }
.sidebar button.active{ background:#d6d6d6; color:#000; font-weight:600; }

.main-content{ margin-left:230px; margin-top:80px; padding:20px; min-height: calc(100vh - 90px); background:white; }
.main-inner{ max-width:1300px; margin:auto; }
h1{ font-size:32px; margin-bottom:15px; color:#1e3c72; }

.header-bar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
.search-box{ position:relative; flex:1; max-width:300px; }
.search-box input{ width:100%; padding:10px 14px 10px 34px; border:1px solid #d1d5db; border-radius:8px; font-size:15px; }
.search-box::before{ content:"🔍"; position:absolute; left:10px; top:8px; font-size:18px; color:#9ca3af; }
.header-buttons{ display:flex; gap:10px; }
.btn{ border:none; padding:10px 16px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; text-decoration:none; }
.btn.add{ background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40; }
.btn.payroll{background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40; }
.btn.payments{ background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40; }

.card{ background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.1); overflow:hidden; }
table{ width:100%; border-collapse:collapse; }
th,td{ text-align:left; padding:15px 20px; border-bottom:1px solid #e5e7eb; }
th{ text-transform:uppercase; font-size:13px; color:#6b7280; background:#f9fafb; }
td{ font-size:15px; }
.employee-info{ display:flex; flex-direction:column; }

.actions{ position: relative; text-align:center; }
.action-wrapper{ position: relative; display:inline-block; }
.action-btn{ border:none; background:none; font-size:20px; cursor:pointer; color:#6b7280; padding:5px; }
.action-btn:hover{ color:#2563eb; }
.actions-menu {
  display: none;
  position: fixed;
  background: #fff;
  min-width: 100px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  border-radius: 8px;
  z-index: 10000;
}
.actions-menu button{ border:none; background:none; text-align:left; padding:10px 15px; font-size:14px; cursor:pointer; width:100%; color:#374151; }
.actions-menu button:hover{ background:#f3f4f6; color:#2563eb; }

body.dark { background: #0f172a; color: #e0e0e0; }
body.dark .sidebar { background: linear-gradient(145deg, #1f2937, #111827); border-color: #334155; }
body.dark .sidebar button { color: #e2e8f0; }
body.dark .sidebar button.active { background: #ababa5ff; color: #000; font-weight: 600; }
body.dark .sidebar button:hover { background: #ababa5ff; color: #000; }
body.dark .main-content { background: #1e293b; color: #e2e8f0; }
body.dark  table { background:#1f2937; color:#e5e7eb; }
body.dark  th { background:#374151; color:#f9fafb;  }
body.dark  td { border-bottom:1px solid #374151; color:#e2e8f0; }
body.dark td a{color:#e2e8f0 !important;}
body.dark  tr:nth-child(odd){ background:#1e293b; }
body.dark  tr:nth-child(even){ background:#273548; }
body.dark  tr:hover { background:#334155; }
body.dark .search-box input{    background: #0f172a;
    color: #e2e8f0;
    border: 1px solid #334155;
}



body.dark h1{color: #93c5fd !important;}

</style>
</head>
<body>

<header>
  <div class="header-left"><img src="nova.png" alt="nova staff" class="logo"></div>
  <div class="header-right">
    <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>
    <div class="profile" id="profileMenu">
      <span id="profileName"><?= htmlspecialchars($userName) ?>&nbsp;(<?= ucfirst($userRole) ?>)</span>
      <div class="profile-img" id="profileInitials"><?= $initials ?></div>
      <div class="dropdown-menu hidden" id="logoutDropdown">
        <a href="login.php">Logout</a>
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
  <button onclick="location.href='target.php'">Targets</button>
  <br><br><br><br>
  <?php if($userRole === 'admin'): ?>
    <button class="active" onclick="location.href='employees.php'">Employees</button>
    <button onclick="location.href='payment.php'">Payment</button>
    <button onclick="location.href='setting.php'">⚙ Settings</button>
  <?php endif; ?>
</div>

<div class="main-content">
  <div class="main-inner">
    <h1>Employees</h1>
    <div class="header-bar">
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search employees..." onkeyup="searchEmployee()">
      </div>
      <div class="header-buttons">
        <a href="add_employee.php" class="btn add">+ Add Employee</a>
        <a href="payroll.php" class="btn payroll">💰 Payroll</a>
        <a href="payment.php" class="btn payments">💳 Payments</a>
        <a href="resigned.php" class="btn payments">resigned</a>
      </div>
    </div>

    <div class="card">
      <table id="employeeTable">
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
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <tr>
                <td><div class="employee-info"><strong><?= htmlspecialchars($row['join_date']); ?></strong></div></td>
                <td><a href="second-candidate.php?id=<?= $row['id']; ?>" style="color:#2563eb; font-weight:600; text-decoration:none;"><?= htmlspecialchars($row['name']); ?></a></td>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= htmlspecialchars($row['mobile']); ?></td>
                <td><?= htmlspecialchars($row['address']); ?></td>
                <td><?= htmlspecialchars($row['department']); ?></td>
                <td><?= htmlspecialchars($row['designation']); ?></td>
                <td><?= htmlspecialchars($row['salary']); ?></td>
                <td class="actions">
                  <div class="action-wrapper">
                    <button class="action-btn">⋮</button>
                    <div class="actions-menu">
                      <button onclick="editEmployee(<?= $row['id']; ?>)">✏️ Edit</button>
                      <button onclick="deleteEmployee(<?= $row['id']; ?>)">🗑️ Delete</button>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="9" style="text-align:center; padding:20px;">No employees found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
// ===== Dark mode =====
const themeToggle = document.getElementById("themeToggle");
const savedTheme = localStorage.getItem("darkMode") === "true";
if(savedTheme) document.body.classList.add("dark");
themeToggle.checked = savedTheme;
themeToggle.addEventListener("change", () => { 
  document.body.classList.toggle("dark"); 
  localStorage.setItem("darkMode", document.body.classList.contains("dark")); 
});

// ===== Search =====
function searchEmployee(){
  const input = document.getElementById("searchInput").value.toLowerCase();
  const rows = document.querySelectorAll("#employeeTable tbody tr");
  rows.forEach(r=>{
    const name = r.cells[1].innerText.toLowerCase();
    r.style.display = name.includes(input) ? "" : "none";
  });
}

// ===== Profile dropdown =====
const profileMenu = document.getElementById('profileMenu');
const logoutDropdown = document.getElementById('logoutDropdown');
profileMenu.addEventListener('click', e => {
  e.stopPropagation();
  logoutDropdown.classList.toggle('hidden');
});
document.addEventListener('click', () => {
  logoutDropdown.classList.add('hidden');
});

// ===== Actions =====
function editEmployee(id){ window.location.href = 'add_employee.php?edit=' + id; }
function deleteEmployee(id){ if(confirm("Are you sure you want to delete this employee?")){ window.location.href = 'employees.php?delete_id=' + id; } }

document.querySelectorAll('.action-btn').forEach((btn) => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    document.querySelectorAll('.actions-menu').forEach(menu => { if(menu!==btn.nextElementSibling) menu.style.display='none'; });
    const menu = btn.nextElementSibling;
    const rect = menu.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    if(rect.right > viewportWidth){ menu.classList.add('left'); } else { menu.classList.remove('left'); }
    menu.style.display = (menu.style.display==='flex') ? 'none' : 'flex';
    menu.style.flexDirection='column';
  });
});
document.addEventListener('click', () => { document.querySelectorAll('.actions-menu').forEach(menu => { menu.style.display='none'; }); });
</script>
</body>
</html>