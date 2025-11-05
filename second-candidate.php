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
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ---------- Fetch session info ----------
$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? 'user'; // default role
$userId   = $_SESSION['user_id'];

$id = $_GET['id'] ?? 0;

// Fetch employee info
$query = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// Fetch payments for this employee
$payStmt = $conn->prepare("SELECT * FROM payments WHERE customer_name=? ORDER BY payment_date DESC");
$payStmt->bind_param("s", $employee['name']);
$payStmt->execute();
$paymentsResult = $payStmt->get_result();
$payments = [];
while($row = $paymentsResult->fetch_assoc()){
    $payments[] = $row;
}

// Fetch notes for this employee (from DB)
$noteStmt = $conn->prepare("SELECT * FROM employee_notes WHERE employee_id=? ORDER BY created_at DESC");
$noteStmt->bind_param("i", $id);
$noteStmt->execute();
$noteResult = $noteStmt->get_result();
$employeeNotes = [];
while($row = $noteResult->fetch_assoc()){
    $employeeNotes[] = $row;
}

// ---------- Generate initials ----------
$parts = explode(' ', trim($userName));
$initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<title>Candidate Profile - <?= htmlspecialchars($employee['name']) ?></title>
<style>
* { box-sizing: border-box; }
body { margin:0; font-family:"Poppins",sans-serif; background:#f5f7fa; color:#333; }
header{background: linear-gradient(135deg, #96a7cb, #1e3c72); padding:10px 25px; display:flex; justify-content:space-between; align-items:center; height:70px; position:fixed; top:0; left:0; width:100%; z-index:1000; box-shadow:0 4px 12px rgba(0,0,0,0.2);}
.header-left img.logo{height:55px; width:auto; border-radius:12px;}
.header-right{display:flex; align-items:center; gap:18px;}
.switch{position:relative; display:inline-block; width:60px; height:32px;}
.switch input{display:none;}
.slider{position:absolute; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px;}
.slider::before{content:"☀️"; position:absolute; height:26px; width:26px; left:3px; bottom:3px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; transition:transform 0.4s,content 0.4s;}
input:checked+.slider{background-color:#ccc;}
input:checked+.slider::before{transform:translateX(28px) rotate(360deg); content:"🌙";}
/* Profile */
.profile{display:flex;align-items:center;gap:10px;position:relative;cursor:pointer}
.profile span{color:#fff;font-weight:600}
.profile-img{width:38px;height:38px;border-radius:50%;background:#fff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:700}
.dropdown-menu{ position:absolute; top:50px; right:0; background:#fff; color:#000; padding:8px 12px; border-radius:6px; box-shadow:0 6px 12px rgba(0,0,0,.15);}
.dropdown-menu.hidden{display:none;}
.dropdown-menu a{ text-decoration:none; color:#000; font-weight:500; }

.sidebar{width:230px; margin-top:0; background:#fff; border-right:1px solid #ddd; padding:20px 0; position:fixed; top:80px; bottom:0;}
.sidebar button{width:100%; margin:5px 0;font-family:"Poppins",sans-serif; text-align:left; font-weight:500; border:none; font-size:15px; background-color:transparent; color:#1a1a1a; cursor:pointer; padding:10px 25px; border-left:4px solid transparent; transition:all 0.3s ease;}
.sidebar button:hover{background:#e0e0e0; color:#000;}
.sidebar button.active{background:#d6d6d6; color:#000; font-weight:600;}
.container { flex:1; margin-left:230px; margin-top:80px; padding:20px; background-color:white; height:calc(100vh - 70px); overflow-y:auto;}
.title-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem; }
.title-row h2 { font-size:2rem; margin:0; font-weight:700; color:#222; }
.btn-group { display:flex; gap:1rem; }
.btn-group button {  background: linear-gradient(135deg, #87b6c6, #cfe9f6);
    color: #001a40; border:none; padding:0.6rem 1.2rem; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:0.4rem; transition:0.3s; }
.btn-group button:hover { background:#0056b3; }
.info-section { display:flex; justify-content:space-between; background:#f8f9fa; padding:1rem 2rem; border-radius:8px; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
.info-item { flex:1 1 200px; font-size:1rem; color:#555; }
.info-item strong { display:block; margin-bottom:0.3rem; color:#222; }
section h3 { margin-top:0; margin-bottom:1rem; font-weight:700; color:#222; border-bottom:2px solid #007bff; padding-bottom:0.3rem; width:max-content; }
table { width:100%; border-collapse:collapse; margin-bottom:2rem; font-size:0.95rem; }
th, td { padding:12px 15px; border-bottom:1px solid #e1e4e8; text-align:left; }
th { background:#f1f3f5; font-weight:700; color:#555; text-transform:uppercase; }
tbody tr:hover { background-color:#f0f8ff; }
.status { padding:5px 10px; border-radius:20px; font-size:0.8rem; font-weight:600; display:inline-block; color:white; text-transform:capitalize; }
.notes-section { background:#f8f9fa; border-radius:8px; padding:1rem 1.5rem; }
#notes-list { max-height:180px; overflow-y:auto; margin-bottom:1rem; }
.note { display:flex; margin-bottom:12px; }
.note img { border-radius:50%; margin-right:1rem; width:36px; height:36px; }
.note-body { background:white; padding:10px 15px; border-radius:8px; flex:1; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
.note-body strong { font-weight:700; color:#007bff; }
.note-body p { margin:4px 0; font-size:0.9rem; }
.note-meta { font-size:0.75rem; color:#777; }
textarea { width:100%; resize:vertical; font-size:1rem; padding:10px; border-radius:6px; border:1px solid #ddd; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
textarea:focus { outline:none; border-color:#007bff; box-shadow:0 0 4px rgba(0,123,255,0.5); }
.add-note-row { display:flex; justify-content:flex-end; margin-top:0.5rem; }
.add-note-row button { background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40; border:none;  padding:0.6rem 1.2rem; border-radius:6px; cursor:pointer; font-weight:600; transition:0.3s; }
.add-note-row button:hover { background-color:#0056b3; }

/* Dark mode */
body.dark .sidebar{background: linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{ background-color:transparent; color:white}  
body.dark .sidebar button.active{ background:#ababa5ff; color:#000; font-weight:600;}
body.dark .sidebar button:hover{ background:#ababa5ff; color:#000;}
body.dark { background: #0f172a; color: #e0e0e0;}
body.dark .container { background: #1e293b; color: #e2e8f0; box-shadow: 0 2px 10px rgba(255, 255, 255, 0.05);}
body.dark h2{color:#93c5fd;} body.dark h3{color:#93c5fd;}
body.dark .info-section { background: #1e293b; border:1px solid #334155; }
body.dark .info-item { color:#cbd5e1;} body.dark .info-item strong{color:#f8fafc;}
body.dark table { background:#1e293b; color:#e2e8f0; } body.dark th { background:#334155; color:#f8fafc;} body.dark td { border-color:#334155;} body.dark tr:hover { background:#334155;}
body.dark .notes-section { background:#1e293b; border:1px solid #334155;}
body.dark .note-body { background:#0f172a; color:#e2e8f0; box-shadow:0 1px 5px rgba(255,255,255,0.1);}
body.dark textarea { background:#0f172a; color:#e2e8f0; border:1px solid #334155;}
body.dark textarea:focus { border-color:#60a5fa; box-shadow:0 0 4px rgba(96,165,250,0.5);}
body.dark .btn-group button{ background: linear-gradient(135deg,#87b6c6,#cfe9f6); color:#001a40;}
body.dark .btn-group button:hover { background: linear-gradient(135deg,#587680ff,#97aab3ff); color:#0b1e3aff;}

.paid{background:#dcfce7;color:#166534;}
.due{background:#fef9c3;color:#92400e;}
.overdue{background:#fee2e2;color:#991b1b;}
@media (max-width:700px){ .info-section { flex-direction:column; gap:1rem; } .btn-group { flex-wrap:wrap; gap:0.5rem; justify-content:flex-start; } .btn-group button { flex:1 1 auto; justify-content:center; } .title-row { flex-direction:column; align-items:flex-start; gap:1rem; } }
</style>
</head>
<body>

<header>
  <div class="header-left"><img src="nova.png" alt="nova staff" class="logo"></div>
  <div class="header-right">
    <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>

    <div class="profile" id="profileMenu">
      <span id="profileName"><?= htmlspecialchars($userName) ?>&nbsp;(<?= ucfirst($userRole) ?>)</span>
      <div class="profile-img"><?= $initials ?></div>
      <div class="dropdown-menu hidden" id="logoutDropdown">
        <a href="login.php">Logout</a>
      </div>
    </div>
  </div>
</header>

<div class="sidebar">
  <button onclick="location.href='index.php'">Dashboard</button>
  <button onclick="location.href='all_leads.php'">All Leads</button>
  <button onclick="location.href='Candidate.php'">Candidate</button>
  <button onclick="location.href='daily_report.php'">Daily Report</button>
  <button onclick="location.href='target.php'">Targets</button>
  <br><br><br><br>
  <?php if ($userRole === 'admin'): ?>
    <button class="active" onclick="location.href='employees.php'">Employees</button>
    <button onclick="location.href='payment.php'">Payment</button>
    <button onclick="location.href='setting.php'">⚙ Settings</button>
  <?php endif; ?>
</div>

<div class="container">

  <div class="title-row">
    <h2><?= htmlspecialchars($employee['name']) ?></h2>
 
  </div>

  <div class="info-section">
    <div class="info-item"><strong>Email</strong> <?= htmlspecialchars($employee['email']) ?></div>
    <div class="info-item"><strong>Phone</strong> <?= htmlspecialchars($employee['mobile']) ?></div>
    <div class="info-item"><strong>Address</strong> <?= htmlspecialchars($employee['address']) ?></div>
  </div>

  <section>
    <h3>Installment Payments</h3>
    <table>
      <thead><tr><th>Date</th><th>Description</th><th>Salary</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($payments as $payment):
          $statusClass = $payment['status']=='paid' ? 'paid' : ($payment['status']=='pending' ? 'due' : 'overdue');
        ?>
        <tr>
          <td><?= htmlspecialchars($payment['payment_date']) ?></td>
          <td><?= htmlspecialchars($payment['installments']) ?></td>
          <td>$<?= number_format($payment['total_payment'],2) ?></td>
          <td><span class="status <?= $statusClass ?>"><?= ucfirst($payment['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section class="notes-section">
    <h3>Notes</h3>
    <div id="notes-list">
      <?php foreach($employeeNotes as $note): ?>
        <div class="note">
          <img src="https://i.pravatar.cc/36?img=2" alt="Avatar" />
          <div class="note-body">
            <strong><?= htmlspecialchars($userName) ?></strong>
            <p><?= htmlspecialchars($note['note']) ?></p>
            <p class="note-meta"><?= htmlspecialchars($note['created_at']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <textarea id="noteInput" rows="3" placeholder="Add a new note..."></textarea>
    <div class="add-note-row">
      <button onclick="addNote()">Add Note</button>
    </div>
  </section>
</div>

<script>
// 🌙 Dark Mode
const themeToggle = document.getElementById("themeToggle");
const savedTheme = localStorage.getItem("darkMode") === "true";
if(savedTheme) document.body.classList.add("dark");
themeToggle.checked = savedTheme;
themeToggle.addEventListener("change", () => { 
  document.body.classList.toggle("dark"); 
  localStorage.setItem("darkMode", document.body.classList.contains("dark")); 
});

// 👤 Profile Logout Dropdown
const profileMenu = document.getElementById('profileMenu');
const logoutDropdown = document.getElementById('logoutDropdown');
profileMenu.addEventListener('click', e => {
  e.stopPropagation();
  logoutDropdown.classList.toggle('hidden');
});
document.addEventListener('click', () => {
  logoutDropdown.classList.add('hidden');
});

// 📝 Notes System
function addNoteToUI(content, time) {
  const notesList = document.getElementById('notes-list');
  const noteHTML = `
    <div class="note">
      <img src="https://i.pravatar.cc/36?img=3" alt="Avatar" />
      <div class="note-body">
        <strong>You</strong>
        <p>${content}</p>
        <p class="note-meta">${time}</p>
      </div>
    </div>`;
  notesList.insertAdjacentHTML('afterbegin', noteHTML);
}

function addNote() {
  const input = document.getElementById('noteInput');
  const content = input.value.trim();
  const employeeId = <?= (int)$id ?>;
  if (!content) return alert('Note cannot be empty.');

  // Save to DB
  fetch('save_note.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ employee_id: employeeId, note: content })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      addNoteToUI(content, 'Just now');
      input.value = '';
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('⚠️ Something went wrong while saving note.');
  });
}
</script>

</body>
</html>
