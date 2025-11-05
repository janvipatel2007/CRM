<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username  = $_SESSION['user_name'] ?? 'User';
$userRole  = $_SESSION['user_role'] ?? 'user';
$displayRole = ucfirst($userRole);

// initials
$nameParts = explode(' ', trim($username));
$initials = '';
foreach ($nameParts as $part) $initials .= strtoupper($part[0]);
if (strlen($initials) > 2) $initials = substr($initials, 0, 2);

$conn = new mysqli("localhost", "root", "", "crm");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ---------- Fetch data ----------
$salesData = $conn->query("SELECT * FROM month_target WHERE category='Sales'");
$leadData = $conn->query("SELECT * FROM month_target WHERE category='Leads'");
$recruitData = $conn->query("SELECT * FROM month_target WHERE category='Recruiter'");

// ---------- Totals ----------
$totalSales = $conn->query("SELECT SUM(closure_sales) AS total_closure, SUM(revenue) AS total_revenue FROM month_target WHERE category='Sales'")->fetch_assoc();
$leadStats = $conn->query("SELECT SUM(closure_lead) AS total_closure, SUM(cold_leads) AS cold, SUM(hot_leads) AS hot FROM month_target WHERE category='Leads'")->fetch_assoc();
$recruitStats = $conn->query("SELECT SUM(interviews) AS interviews, SUM(placements) AS placements, SUM(applications) AS applications FROM month_target WHERE category='Recruiter'")->fetch_assoc();

// ---------- Counts ----------
$countSales = intval($conn->query("SELECT COUNT(employee) AS total FROM month_target WHERE category='Sales'")->fetch_assoc()['total'] ?? 0);
$countLeads = intval($conn->query("SELECT COUNT(employee) AS total FROM month_target WHERE category='Leads'")->fetch_assoc()['total'] ?? 0);
$countRecruiter = intval($conn->query("SELECT COUNT(employee) AS total FROM month_target WHERE category='Recruiter'")->fetch_assoc()['total'] ?? 0);


// ---------- Fetch Data ----------
$salesData = $conn->query("SELECT * FROM month_target WHERE category='Sales'");
$leadData = $conn->query("SELECT * FROM month_target WHERE category='Leads'");
$recruitData = $conn->query("SELECT * FROM month_target WHERE category='Recruiter'");

// ---------- Totals ----------
$totalSales = $conn->query("
    SELECT 
        SUM(closure_sales) AS total_closure, 
        SUM(revenue) AS total_revenue 
    FROM month_target 
    WHERE category='Sales'
")->fetch_assoc();

$leadStats = $conn->query("
    SELECT 
        SUM(closure_lead) AS total_closure, 
        SUM(cold_leads) AS cold, 
        SUM(hot_leads) AS hot 
    FROM month_target 
    WHERE category='Leads'
")->fetch_assoc();

$recruitStats = $conn->query("
    SELECT 
        SUM(interviews) AS interviews, 
        SUM(placements) AS placements, 
        SUM(applications) AS applications 
    FROM month_target 
    WHERE category='Recruiter'
")->fetch_assoc();

// ---------- Counts ----------
$countSales = intval($conn->query("SELECT COUNT(employee) AS total FROM month_target WHERE category='Sales'")->fetch_assoc()['total'] ?? 0);
$countLeads = intval($conn->query("SELECT COUNT(employee) AS total FROM month_target WHERE category='Leads'")->fetch_assoc()['total'] ?? 0);
$countRecruiter = intval($conn->query("SELECT COUNT(employee) AS total FROM month_target WHERE category='Recruiter'")->fetch_assoc()['total'] ?? 0);



?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Targets Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:"Poppins",sans-serif;transition:all .25s ease}
html,body{height:100%}
body{background:linear-gradient(135deg,#dfe9f3,#ffffff);color:#001a40;overflow:hidden}
body.dark{background:#0f172a;color:#e0e0e0}
header{background:linear-gradient(135deg,#96a7cb,#1e3c72);height:70px;display:flex;align-items:center;justify-content:space-between;padding:10px 22px;position:fixed;left:0;top:0;right:0;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.header-left .logo{height:55px;border-radius:10px}
.header-right{display:flex;align-items:center;gap:14px}
.switch{position:relative;width:60px;height:32px;display:inline-block}
.switch input{display:none}
.slider{position:absolute;inset:0;background:#cfcfcf;border-radius:999px}
.slider::before{content:"☀️";position:absolute;left:4px;bottom:3px;height:26px;width:26px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;transition:transform .4s}
.switch input:checked + .slider::before{transform:translateX(28px) rotate(360deg);content:"🌙"}
.profile{display:flex;align-items:center;gap:10px;position:relative;cursor:pointer}
.profile span{color:#fff;font-weight:600}
.profile-img{width:38px;height:38px;border-radius:50%;background:#fff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:700;box-shadow:0 4px 15px rgba(0,0,0,.18)}
.dropdown-menu{ position:absolute; top:50px; right:0; background:#fff; color:#000; padding:8px 12px; border-radius:6px; box-shadow:0 6px 12px rgba(0,0,0,.15);}
.dropdown-menu.hidden{display:none;}
.sidebar{ width:230px;margin-top:10px; background:#fff; border-right:1px solid #ddd; padding:0; position:fixed; top:70px; bottom:0; left:0; }
.sidebar button{ width:100%; margin:5px 0; text-align:left; font-weight:500; border:none; font-size:15px; background-color:transparent; color:#1a1a1a; cursor:pointer; padding:10px 25px; border-left:4px solid transparent; transition:all 0.3s ease; }
.sidebar button:hover{ background:#e0e0e0; color:#000; }
.sidebar button.active{ background:#d6d6d6; color:#000; font-weight:600; }
.dashboard-container{display:grid;grid-template-columns:230px 1fr;grid-template-rows:70px 1fr;height:100vh}
.main-content{grid-column:2;grid-row:2;padding:0;overflow:auto;height:calc(100vh - 70px)}
.bottom-grid{display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;margin:20px}
.left-panel{background:#fff;padding:20px;box-shadow:0 4px 14px rgba(0,0,0,0.08);border-radius:10px}
.bottom-grid .left-panel h2 {margin: 0 0 6px 0;font-size: 1.125rem; /* 18px */font-weight: 600;color: #0f172a; /* dark */letter-spacing: -0.2px;}
.bottom-grid .left-panel p {  margin: 0 0 14px 0;color: #6b7280; /* muted grey */ font-size: 0.95rem;line-height: 1.45;}
.section {margin-bottom: 25px;padding: 15px 18px;border-radius: 10px;background: #f8fafc;box-shadow: 0 2px 8px rgba(0,0,0,0.05);cursor: pointer;transition: all 0.3s ease;}
.section:hover {transform: translateY(-2px);box-shadow: 0 4px 12px rgba(0,0,0,0.1);}
.section.active {background: #e8ecff;box-shadow: 0 4px 14px rgba(0,0,0,0.15);transform: scale(1.02);}
.section.active .table-container {max-height: 400px;opacity: 1;padding: 10px;}
.stats {display:flex;gap:15px;margin-top:8px;padding-left:10px;font-size:11px;}
.stats span {background:#fff;padding:6px 14px;border-radius:5px;box-shadow:0 1px 3px rgba(0,0,0,0.08);}
.table-container {background:#fff;margin-top:10px;border-radius:6px;overflow:hidden;max-height:0;opacity:0;transition:all 0.4s ease;box-shadow:0 2px 4px rgba(0,0,0,0.08);}
table {width:100%;border-collapse:collapse;font-size:11px;}
th,td {text-align:left;padding:8px;border-bottom:1px solid #ddd;}
th {background:#f1f3f5;}
.target-performance-section{margin:20px;padding:20px;background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.08);}
.target-performance-cards{display:flex;flex-wrap:wrap;gap:20px;margin-top:12px;}
.target-card{flex:1 1 200px;min-width:200px;background:#f8fafc;border-radius:10px;padding:15px;box-shadow:0 2px 6px rgba(0,0,0,0.06);background:linear-gradient(135deg,#dbeafe,#eff6ff);}
.target-card h3{margin-bottom:4px;font-size:16px;}
.target-value{font-size:22px;font-weight:700;color:#2563eb;}
.target-sub{font-size:12px;color:#64748b;}

/* 🌙 DARK MODE THEME (Black & Gray) */
body.dark {
  background: #0d1117;
  color: #e5e7eb;
}

/* Header */
body.dark .sidebar{background: linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{ background-color:transparent; color:white}  
body.dark .sidebar button.active{ background:#ababa5ff; color:#000; font-weight:600;}
body.dark .sidebar button:hover{ background:#ababa5ff; color:#000;}
body.dark h2{  color: #93c5fd !important;}
/* Cards and Panels */
body.dark .left-panel,
body.dark .target-performance-section,
body.dark .section {
  background: #1e293b;
  color: #e2e8f0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}

/* Tables */
body.dark table {
  background: #0f172a;
  color: #f1f5f9;
}
body.dark th {
  background: #1e293b;
  color: #f8fafc;
}
body.dark td {
  border-bottom: 1px solid #334155;
}

/* Cards */
body.dark .target-card {
  background: #111827;
  color: #a0a1a4ff;
}
body.dark .target-card .target-value{color: white !important ;}
body.dark .target-card .target-sub{color:#86878aff ;}
body.dark p{color: #9ea5b3ff !important;}


/* 🌙 Enhanced dark mode for tables and stats */
body.dark .section {
  background: #111827;
  border: 1px solid #1f2937;
  box-shadow: 0 0 10px rgba(0,0,0,0.6);
}

body.dark .table-container {
  background: #111827;
  border-radius: 8px;
  border: 1px solid #1f2937;
}

body.dark table {
  background: #535760ff;
  border-collapse: collapse;
  color: #e5e7eb;
}

body.dark th {
  background: #1f2937;
  color: #f9fafb;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

body.dark td {
  background: #0f172a;
  color: #cbd5e1;
  border-bottom: 1px solid #1e293b;
}

body.dark tr:hover td {
  background: #1e293b;
}

body.dark .stats span {
  background: #1e293b;
  color: #f1f5f9;
  border: 1px solid #334155;
}

</style>
</head>
<body>
<header>
  <div class="header-left"><img src="nova.png" alt="nova staff" class="logo"></div>
  <div class="header-right">
    <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>
    <div class="profile" id="profileMenu">
      <span id="profileName"><?= htmlspecialchars($username) ?>&nbsp;(<?= ucfirst($userRole) ?>)</span>
      <div class="profile-img" id="profileInitials"><?= $initials ?></div>
      <div class="dropdown-menu hidden" id="logoutDropdown"><a href="login.php">Logout</a></div>
    </div>
  </div>
</header>

<div class="dashboard-container">
  <div class="sidebar">
    <button onclick="location.href='index.php'">Dashboard</button>
    <button onclick="location.href='all_leads.php'">All Leads</button>
    <button onclick="location.href='Candidate.php'">Candidate</button>
    <button onclick="location.href='daily_report.php'">Daily Report</button>
    <button class="active" onclick="location.href='target.php'">Targets</button><br><br><br><br>
    <?php if($userRole === 'admin'): ?>
      <button onclick="location.href='employees.php'">Employees</button>
      <button onclick="location.href='payment.php'">Payment</button>
      <button onclick="location.href='setting.php'">⚙ Settings</button>
    <?php endif; ?>
  </div>

  <main class="main-content">
   <section class="target-performance-section">
  <h2>Targets & Performance</h2>
  <div class="target-performance-cards">

    <div class="target-card">
      <h3>Sales</h3>
      <div class="target-value"><?= $countSales ?></div>
      <div class="target-sub">target: <?= $totalSales['total_revenue'] ?? 0 ?> | progress: <?= $totalSales['total_closure'] ?? 0 ?></div>
    </div>

    <div class="target-card">
      <h3>Leads</h3>
      <div class="target-value"><?= $countLeads ?></div>
      <div class="target-sub">target: <?= $leadStats['cold'] + $leadStats['hot'] ?? 0 ?> | progress: <?= $leadStats['total_closure'] ?? 0 ?></div>
    </div>

    <div class="target-card">
      <h3>Recruiter</h3>
      <div class="target-value"><?= $countRecruiter ?></div>
      <div class="target-sub">target: <?= $recruitStats['placements'] ?? 0 ?> | progress: <?= $recruitStats['interviews'] ?? 0 ?></div>
    </div>

  </div>
</section>

    <section class="bottom-grid">
      <div class="left-panel">
         <h2>Monthly Targets</h2>
          <p>See targets vs achieved values for each month. Progress shows how close the achieved value is to the target.</p>
        <!-- SALES -->
        <div class="section" data-chart="sales">
          <h5>SALES</h5>
          <div class="stats">
            <span>TOTAL CLOSURES: <?= $totalSales['total_closure'] ?? 0 ?></span>
            <span>REVENUE: ₹<?= $totalSales['total_revenue'] ?? 0 ?></span>
          </div>
          <div class="table-container">
            <table>
              <thead><tr><th>Employee</th><th>Closures</th><th>Revenue</th></tr></thead>
              <tbody>
                <?php while($row = $salesData->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['employee']) ?></td>
                    <td><?= $row['closure_sales'] ?></td>
                    <td>₹<?= $row['revenue'] ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- LEADS -->
        <div class="section" data-chart="leads">
          <h5>LEAD GENERATION</h5>
          <div class="stats">
            <span>TOTAL CLOSURE: <?= $leadStats['total_closure'] ?? 0 ?></span>
            <span>COLD LEADS: <?= $leadStats['cold'] ?? 0 ?></span>
            <span>HOT LEADS: <?= $leadStats['hot'] ?? 0 ?></span>
          </div>
          <div class="table-container">
            <table>
              <thead><tr><th>Employee</th><th>Closure</th><th>Cold Leads</th><th>Hot Leads</th></tr></thead>
              <tbody>
                <?php while($row = $leadData->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['employee']) ?></td>
                    <td><?= $row['closure_lead'] ?></td>
                    <td><?= $row['cold_leads'] ?></td>
                    <td><?= $row['hot_leads'] ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RECRUITER -->
        <div class="section" data-chart="recruiter">
          <h5>RECRUITERS</h5>
          <div class="stats">
            <span>TOTAL INTERVIEWS: <?= $recruitStats['interviews'] ?? 0 ?></span>
            <span>PLACEMENTS: <?= $recruitStats['placements'] ?? 0 ?></span>
            <span>APPLICATIONS: <?= $recruitStats['applications'] ?? 0 ?></span>
          </div>
          <div class="table-container">
            <table>
              <thead><tr><th>Employee</th><th>Interviews</th><th>Placements</th><th>Applications</th></tr></thead>
              <tbody>
                <?php while($row = $recruitData->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['employee']) ?></td>
                    <td><?= $row['interviews'] ?></td>
                    <td><?= $row['placements'] ?></td>
                    <td><?= $row['applications'] ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
      <div class="right">
        <canvas id="mainChart"></canvas>
      </div>
    </section>
  </main>
</div>

<script>

// ========== Theme Toggle ==========
const themeToggle = document.getElementById("themeToggle");

if (localStorage.getItem("darkMode") === "true") {
    document.body.classList.add("dark");
    themeToggle.checked = true;
}

themeToggle.addEventListener("change", () => {
    document.body.classList.toggle("dark");
    localStorage.setItem("darkMode", document.body.classList.contains("dark"));
});




const ctx=document.getElementById("mainChart");
const chartData={
  sales:{labels:["Closures","Revenue (₹ in 10k)"],data:[<?= $totalSales['total_closure'] ?? 0 ?>,<?= ($totalSales['total_revenue'] ?? 0)/10000 ?>],colors:["#8b5cf6","#06b6d4"]},
  leads:{labels:["Closures","Cold","Hot"],data:[<?= $leadStats['total_closure'] ?? 0 ?>,<?= $leadStats['cold'] ?? 0 ?>,<?= $leadStats['hot'] ?? 0 ?>],colors:["#3b82f6","#9ca3af","#22c55e"]},
  recruiter:{labels:["Interviews","Placements","Applications"],data:[<?= $recruitStats['interviews'] ?? 0 ?>,<?= $recruitStats['placements'] ?? 0 ?>,<?= $recruitStats['applications'] ?? 0 ?>],colors:["#14b8a6","#facc15","#ef4444"]}
};

let currentChart=new Chart(ctx,{type:"doughnut",data:{labels:chartData.sales.labels,datasets:[{data:chartData.sales.data,backgroundColor:chartData.sales.colors}]},options:{plugins:{legend:{position:"bottom"}},cutout:"65%"}});

document.querySelectorAll(".section").forEach(sec=>{
  sec.addEventListener("click",()=>{
    document.querySelectorAll(".section").forEach(s=>s.classList.remove("active"));
    sec.classList.add("active");
    const key=sec.getAttribute("data-chart");
    const d=chartData[key];
    currentChart.data.labels=d.labels;
    currentChart.data.datasets[0].data=d.data;
    currentChart.data.datasets[0].backgroundColor=d.colors;
    currentChart.update();
  });
});
</script>
</body>
</html>