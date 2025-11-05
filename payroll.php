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

// ---------- Fetch employee payment info ----------
$payments = [];
$res = $conn->query("SELECT name, salary, bank_account, ifsc, bank_name, payment_mode, join_date FROM employees");
if($res->num_rows > 0){
    while($row = $res->fetch_assoc()){
        $payments[] = $row;
    }
}

// ---------- Handle Logout ----------
if(isset($_GET['logout'])){
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payroll Filter</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; transition:all 0.3s ease; }
body{ background:linear-gradient(135deg,#dfe9f3,#ffffff); color:#001a40; overflow-x:hidden; }
body.dark{ background:#0f172a; color:#e0e0e0; }

header{ background:linear-gradient(135deg,#96a7cb,#1e3c72); padding:10px 25px; display:flex; justify-content:space-between; align-items:center; height:70px; position:fixed; top:0; width:100%; z-index:1000; box-shadow:0 4px 12px rgba(0,0,0,0.2); }
.header-left img.logo{ height:55px; width:auto; border-radius:12px; }
.header-right{ display:flex; align-items:center; gap:18px; }
.switch{ position:relative; display:inline-block; width:60px; height:32px; }
.switch input{ display:none; }
.slider{ position:absolute; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px; }
.slider::before{ content:"☀️"; position:absolute; height:26px; width:26px; left:3px; bottom:3px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; transition:transform 0.4s,content 0.4s; }
input:checked+.slider::before{ transform:translateX(28px) rotate(360deg); content:"🌙"; }

.profile{ display:flex; align-items:center; gap:10px; position:relative; cursor:pointer; }
.profile span{ font-weight:600; color:#fff; }
.profile-img{ width:38px; height:38px; border-radius:50%; background:#fff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-weight:bold; box-shadow:0 4px 15px rgba(0,0,0,0.3); }
.dropdown-menu{ position:absolute; top:50px; right:0; background:#fff; color:#000; padding:8px 12px; border-radius:6px; box-shadow:0 6px 12px rgba(0,0,0,.15);}
.dropdown-menu.hidden{display:none;}
.dropdown-menu a{ text-decoration:none; color:#000; font-weight:500; }

.sidebar{ width:230px; background:#fff; border-right:1px solid #ddd; padding:20px 0; position:fixed; top:80px; bottom:0; }
.sidebar button{ width:100%; margin:5px 0; text-align:left; font-weight:500; border:none; font-size:15px; background:transparent; color:#1a1a1a; cursor:pointer; padding:10px 25px; border-left:4px solid transparent; transition:all 0.3s ease; }
.sidebar button:hover{ background:#e0e0e0; color:#000; }
.sidebar button.active{ background:#d6d6d6; color:#000; font-weight:600;}

.container{ flex:1; margin-left:230px; margin-top:80px; padding:20px; background-color:white; height:calc(100vh - 70px); overflow-y:auto; }
h1{ font-size:28px; margin-bottom:5px; }
.subtitle{ color:#666; margin-bottom:20px; }
.filters{ display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
select, input[type="text"]{ padding:10px; font-size:14px; border-radius:6px; border:1px solid #ccc; }
table{ width:100%; border-collapse:collapse; margin-top:20px; }
th, td{ padding:14px; border-bottom:1px solid #eee; text-align:left; }
th{ background-color:#f6f8fa; font-weight:600; }
.footer{ display:flex; justify-content:space-between; flex-wrap:wrap; margin-top:20px; padding-top:20px; border-top:1px solid #ccc; }
.download-btn{ padding:10px 16px; background:#007bff; color:white; border:none; border-radius:6px; cursor:pointer; }
.download-btn:hover{ background:#0056b3; }

/* Dark Mode */
body.dark .sidebar{background: linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{ background-color:transparent; color:white}  
body.dark .sidebar button.active{ background:#ababa5ff; color:#000; font-weight:600;}
body.dark .sidebar button:hover{ background:#ababa5ff; color:#000;}
body.dark h1{ color:#93c5fd; }
body.dark p{ color: #a1a1a1ff; }
body.dark .container { background: #1e293b; color: #f1f5f9; }
body.dark  table { background:#1f2937; color:#e5e7eb; }
body.dark  th { background:#374151; color:#f9fafb;  }
body.dark  td { border-bottom:1px solid #374151; color:#e2e8f0; }
body.dark td a{color:#e2e8f0 !important;}
body.dark  tr:nth-child(odd){ background:#1e293b; }
body.dark  tr:nth-child(even){ background:#273548; }
body.dark  tr:hover { background:#334155; }

body.dark select, body.dark input[type="text"] { background: rgba(51,65,85,0.9); color: #f1f5f9; border: 1px solid #475569; }
body.dark .footer { border-top: 1px solid #475569; }
body.dark .download-btn { background:linear-gradient(135deg,#87b6c6,#cfe9f6); color:#001a40; }
body.dark .download-btn:hover { background: linear-gradient(135deg, #587680ff, #97aab3ff); color: #0b1e3aff; }

@media (max-width:700px){ .filters{ flex-direction:column; } .container{ margin-left:0; } }
</style>
</head>
<body>

<header>
  <div class="header-left"><img src="nova.png" alt="nova staff" class="logo"></div>
  <div class="header-right">
    <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>

    <div class="profile" id="profileMenu">
      <span id="profileName"><?= htmlspecialchars($userName) ?>&nbsp;(<?= ucfirst($userRole) ?>)</span>
      <div class="profile-img">
        <?php
          $parts = explode(' ', trim($userName));
          $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
          echo $initials;
        ?>
      </div>
      <div class="dropdown-menu hidden" id="logoutDropdown">
          <a href="?logout=1">Logout</a>
      </div>
    </div>
  </div>
</header>

<div class="sidebar">
  <button onclick="location.href='index.php'"> Dashboard</button>
  <button onclick="location.href='all_leads.php'"> All Leads</button>
  <button onclick="location.href='Candidate.php'"> Candidate</button>
  <button onclick="location.href='daily_report.php'"> Daily Report</button>
  <button onclick="location.href='target.php'"> Targets</button><br><br><br><br>

  <?php if($userRole === 'admin'): ?>
    <button onclick="location.href='employees.php'">Employees</button>
    <button class="active" onclick="location.href='payment.php'">Payment</button>
    <button onclick="location.href='setting.php'">⚙ Settings</button>
  <?php endif; ?>
</div>

<div class="container" id="payroll-section">
  <h1>Payroll</h1>
  <p class="subtitle">Manage and filter employee payroll</p>

  <div class="filters">
    <select id="month-filter"><option value="all">All Months</option></select>
    <select id="year-filter"><option value="all">All Years</option></select>
    <input type="text" id="search-input" placeholder="Search by employee name...">
  </div>

  <table>
    <thead>
      <tr>
        <th>Employee Name</th>
        <th>Account Number</th>
        <th>IFSC Code</th>
        <th>Bank Name</th>
        <th>Payment Mode</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody id="payroll-body">
      <?php foreach($payments as $emp):
        $month = $emp['join_date'] ? date('F', strtotime($emp['join_date'])) : '';
        $year = $emp['join_date'] ? date('Y', strtotime($emp['join_date'])) : '';
      ?>
      <tr data-month="<?php echo $month; ?>" data-year="<?php echo $year; ?>">
        <td><?php echo htmlspecialchars($emp['name']); ?></td>
        <td><?php echo htmlspecialchars($emp['bank_account']); ?></td>
        <td><?php echo htmlspecialchars($emp['ifsc']); ?></td>
        <td><?php echo htmlspecialchars($emp['bank_name']); ?></td>
        <td><?php echo htmlspecialchars($emp['payment_mode']); ?></td>
        <td class="amount">$<?php echo number_format((float)$emp['salary'], 2); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer">
    <div><strong>Total Amount:</strong> <span id="total-amount">$0.00</span></div>
    <button class="download-btn" onclick="downloadPDF()">⬇ Download PDF</button>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
const themeToggle = document.getElementById("themeToggle");
const savedTheme = localStorage.getItem("darkMode") === "true";
if(savedTheme) document.body.classList.add("dark");
themeToggle.checked = savedTheme;
themeToggle.addEventListener("change", () => { document.body.classList.toggle("dark"); localStorage.setItem("darkMode", document.body.classList.contains("dark")); });

const profileMenu = document.getElementById('profileMenu');
const logoutDropdown = document.getElementById('logoutDropdown');
profileMenu.addEventListener('click', e => { e.stopPropagation(); logoutDropdown.classList.toggle('hidden'); });
document.addEventListener('click', () => { logoutDropdown.classList.add('hidden'); });

function filterTable() {
  const monthFilter = document.getElementById('month-filter').value;
  const yearFilter = document.getElementById('year-filter').value;
  const searchQuery = document.getElementById('search-input').value.toLowerCase();
  let total = 0;
  document.querySelectorAll('#payroll-body tr').forEach(row => {
    const month = row.getAttribute('data-month');
    const year = row.getAttribute('data-year');
    const name = row.children[0].textContent.toLowerCase();
    const amount = parseFloat(row.querySelector('.amount').textContent.replace(/[^0-9.-]+/g,"")) || 0;
    const show = (monthFilter==='all'||month===monthFilter) &&
                 (yearFilter==='all'||year===yearFilter) &&
                 name.includes(searchQuery);
    row.style.display = show ? '' : 'none';
    if(show) total += amount;
  });
  document.getElementById('total-amount').textContent = `$${total.toLocaleString(undefined,{minimumFractionDigits:2})}`;
}

document.getElementById('month-filter').addEventListener('change', filterTable);
document.getElementById('year-filter').addEventListener('change', filterTable);
document.getElementById('search-input').addEventListener('input', filterTable);

function downloadPDF(){
  html2pdf().set({ margin:0.5, filename:'Filtered_Payroll.pdf', image:{type:'jpeg',quality:0.98}, html2canvas:{scale:2}, jsPDF:{unit:'in',format:'letter',orientation:'portrait'}}).from(document.getElementById('payroll-section')).save();
}

window.addEventListener('DOMContentLoaded', ()=>{
  const months = new Set();
  const years = new Set();
  document.querySelectorAll('#payroll-body tr').forEach(r=>{
    months.add(r.getAttribute('data-month'));
    years.add(r.getAttribute('data-year'));
  });
  const monthSelect = document.getElementById('month-filter');
  months.forEach(m => { if(m) monthSelect.innerHTML += `<option>${m}</option>`; });
  const yearSelect = document.getElementById('year-filter');
  years.forEach(y => { if(y) yearSelect.innerHTML += `<option>${y}</option>`; });
  filterTable();
});
</script>

</body>
</html>
