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
$userId = $_SESSION['user_id'] ?? 0;
$displayRole = ucfirst($userRole);

// initials
$nameParts = explode(' ', trim($username));
$initials = '';
foreach ($nameParts as $part) $initials .= strtoupper($part[0]);
if (strlen($initials) > 2) $initials = substr($initials, 0, 2);

$conn = new mysqli("localhost", "root", "", "crm");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ---------- Handle Report Save via AJAX ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    // Normalize and sanitize incoming values
    $date         = $conn->real_escape_string($_POST['date'] ?? date('Y-m-d'));
    $dept         = $conn->real_escape_string($_POST['dept'] ?? '');
    $qualified    = (int)($_POST['qualified'] ?? 0);
    $connected    = (int)($_POST['connected'] ?? 0);
    $meetings     = (int)($_POST['meetings'] ?? 0);
    $hot          = (int)($_POST['hot'] ?? 0);
    $cold         = (int)($_POST['cold'] ?? 0);
    $interview    = (int)($_POST['interview'] ?? 0);
    $applications = (int)($_POST['applications'] ?? 0);
    $offer        = (int)($_POST['offer'] ?? 0);
    $notes        = $conn->real_escape_string($_POST['notes'] ?? '');

    // Use prepared statement to insert
    $stmt = $conn->prepare("INSERT INTO daily_report
            (report_date, department, qualified, connected, meetings, hot, cold, interview, applications, offer, notes, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "ssiiiiiiiisi",
            $date, $dept, $qualified, $connected, $meetings, $hot, $cold, $interview, $applications, $offer, $notes, $userId
        );
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }

    $conn->close();
    exit;
}

// ---------- Apply Date Range Filter ----------
$startDate = $_GET['start'] ?? '';
$endDate   = $_GET['end'] ?? '';

$filterCondition = "";
$filterLabel = "Today";

if ($startDate && $endDate) {
    $s = date('Y-m-d', strtotime($startDate));
    $e = date('Y-m-d', strtotime($endDate));

    if ($userRole === 'admin') {
        $filterCondition = "WHERE report_date BETWEEN '$s' AND '$e'";
    } else {
        $filterCondition = "WHERE report_date BETWEEN '$s' AND '$e' AND user_id=$userId";
    }

    $filterLabel = date("M d, Y", strtotime($s)) . " → " . date("M d, Y", strtotime($e));
} else {
    if ($userRole === 'admin') {
        $filterCondition = "WHERE report_date = CURDATE()";
    } else {
   $filterCondition = "WHERE report_date = CURDATE() AND user_id = '$userId'";

    }
    $filterLabel = "Today";
}

// ---------- Fetch Departmental Report Counts ----------
$departments = [
    'Sales' => ['total'=>0,'qualified'=>0,'connected'=>0,'meetings'=>0,'hot'=>0],
    'Lead generation' => ['total'=>0,'qualified'=>0,'connected'=>0,'meetings'=>0,'hot'=>0,'cold'=>0],
    'Recruiter' => ['total'=>0,'qualified'=>0,'connected'=>0,'meetings'=>0,'hot'=>0,'interview'=>0,'applications'=>0,'offer'=>0]
];

$countSql = "
    SELECT department,
           COUNT(*) AS total,
           SUM(qualified) AS qualified,
           SUM(connected) AS connected,
           SUM(meetings) AS meetings,
           SUM(hot) AS hot,
           SUM(cold) AS cold,
           SUM(interview) AS interview,
           SUM(applications) AS applications,
           SUM(offer) AS offer
    FROM daily_report
    $filterCondition
    GROUP BY department
";

$countRes = $conn->query($countSql);
if($countRes && $countRes->num_rows > 0){
    while($row = $countRes->fetch_assoc()){
        $dept = $row['department'];
        if(isset($departments[$dept])){
            foreach($departments[$dept] as $k => $v){
                if(isset($row[$k])) $departments[$dept][$k] = (int)$row[$k];
            }
            $departments[$dept]['total'] = (int)($row['total'] ?? 0);
        }
    }
}

$todaySales     = $departments['Sales'];
$todayLeads     = $departments['Lead generation'];
$todayRecruiter = $departments['Recruiter'];

$todayReportsResult = $conn->query("SELECT COUNT(*) AS cnt FROM daily_report $filterCondition");
$todayReports = 0;
if ($todayReportsResult && $rowC = $todayReportsResult->fetch_assoc()) {
    $todayReports = (int)$rowC['cnt'];
}

$sqlReports = "SELECT * FROM daily_report $filterCondition ORDER BY report_date DESC";
$resultReports = $conn->query($sqlReports);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Daily Reports Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

 <style>
    *{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; transition:all 0.3s ease; }
    html { scroll-behavior: smooth; }
    body { background: linear-gradient(135deg,#dfe9f3,#ffffff); color:#001a40; overflow-x: hidden; overflow-y: auto; }
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
.dropdown-menu{position:absolute;top:50px;right:0;background:#fff;color:#000;padding:8px 12px;border-radius:6px;box-shadow:0 6px 12px rgba(0,0,0,.15)}
.dropdown-menu.hidden{display:none}

    .sidebar{ width:230px; background:#fff; border-right:1px solid #ddd; padding:20px 0; position:fixed; top:80px; bottom:0; }
    .sidebar button{ width:100%; margin:5px 0; text-align:left; font-weight:500; border:none; font-size:15px; background-color:transparent; color:#1a1a1a; cursor:pointer; padding:10px 25px; border-left:4px solid transparent; transition:all 0.3s ease; }
    .sidebar button:hover{ background:#e0e0e0; color:#000; }
    .sidebar button.active{ background:#d6d6d6; color:#000; font-weight:600; }

    .container { flex:1; margin-left:230px; margin-top:80px; padding:20px; background-color:white; min-height: calc(100vh - 70px); overflow-y:auto; }

    .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; margin-top:10px; }
    .page-title h2 { font-size:22px; margin-bottom:6px; }
    .page-title p { font-size:14px; color:#6b7280; }

    .controls { display:flex; gap:12px; align-items:center; }
    .select-month { padding:8px 14px; border-radius:8px; border:1px solid #d1d5db; width:200px; height:35px; }
    .btn-primary { width:250px; height:40px;background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40; border:none; border-radius:8px; cursor:pointer; }
    .btn-primary:hover { background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40; }

    .cards-row { display:flex; gap:20px; margin-bottom:20px; }
    .card { flex:1; background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.08); display:flex; justify-content:space-between; align-items:center; }
    .card h4 { font-size:14px; color:#6b7280; margin-bottom:6px; }
    .card .value { font-size:22px; font-weight:bold; color: #000;}
    .emoji { font-size:24px; background:#f1f5f9; padding:12px; border-radius:12px; }

    .reports { flex:1; display:flex; flex-direction:column; background:#fff; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.08); overflow:hidden; }
    .reports-header { display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #e5e7eb; }
    .reports-header h3 { font-size:16px; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; padding:16px 20px; font-size:14px; }
    th { color:#6b7280; font-weight:500; border-bottom:1px solid #e5e7eb; }
    tr td { border-bottom:1px solid #f3f4f6; }
    .tagsales { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; color:#fff; background:#3b82f6; }
    .status { background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:500; display:inline-block; }

    .fade-in { animation:fadeIn 0.6s ease-in-out; }
    @keyframes fadeIn { from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);} }

    .modal { background:rgba(255,255,255,0.95); backdrop-filter:blur(12px); border-radius:16px; padding:25px 30px; width:480px; box-shadow:0 12px 30px rgba(0,0,0,0.15); }
    .overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.45); display:none; justify-content:center; align-items:center; z-index:1000; }
    .overlay.show { display:flex; }
    .modal-header { display:flex; justify-content:space-between; align-items:center; font-size:20px; font-weight:600; margin-bottom:20px; color:#111827; }
    .close-btn { background:none; border:none; font-size:24px; cursor:pointer; color:#6b7280; }
    label { font-weight:500; display:block; margin-bottom:6px; margin-top:10px; color:#374151; }
    input, select, textarea { width:100%; padding:10px; border-radius:10px; border:1px solid #d1d5db; font-size:14px; outline:none; }
    .metrics-grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:8px; }
    .btn { padding:10px 20px; border-radius:8px; border:none; font-size:14px; cursor:pointer; }
    .btn-cancel { background:#f3f4f6; color:#374151; }
    .btn-submit { background:#2563eb; color:#fff; font-weight:500; }
    .metric-input { display:flex; align-items:center; justify-content:space-between; border:1px solid #d1d5db; border-radius:10px; overflow:hidden; }
    .metric-input button { background:transparent; border:none; font-size:18px; padding:8px 12px; cursor:pointer; font-weight:600; }
    .metric-input input { border:none; text-align:center; width:60px; outline:none; font-size:14px; }

    .card.dept-sales { background:white; color:#0a357aff; }
    .card.dept-lead { background:white; color:#5b3d06ff; }
    .card.dept-recruiter { background:white; color:#59630eff; }

    .dept-badge { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; color:#fff; display:inline-block; }
    .dept-sales { background:#0a357a; } /* just badge color */
    .dept-lead { background:#b66f00; }
    .dept-recruiter { background:#2a7a2a; }

    textarea { resize:vertical; min-height:60px; }
  
    
    

body.dark {background: #060628ff !important; color: #fff;}
body.dark .sidebar{background: linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{ background-color:transparent; color:white}  
body.dark .sidebar button.active{background:#ababa5ff;color:#000;font-weight:600;}
body.dark .sidebar button:hover{background:#ababa5ff;color:#000;}
body.dark .container, body.dark .reports { background: linear-gradient(145deg,#1f2937,#111827);  color:#93c5fd;  }
body.dark .reports th, body.dark .reports td {color: #ccc;}
body.dark input[type="date"] {color: #fff; background-color: #111827; border: 1px solid #374151; }
body.dark input[type="date"]::-webkit-calendar-picker-indicator {filter: invert(1); /* full white icon */cursor: pointer;}
body.dark .card{ background:#111827; color:#fff;}
body.dark .select-month { background:#111827;color:#fff; border:1px solid #555; }
body.dark  body.dark .btn-submit { background:#111827; color:#fff; border:1px solid #555; }
body.dark th { color:#d1d5db; }
body.dark .tagsales{ color:#fff; }
body.dark .overlay { background:rgba(0,0,0,0.7); }
body.dark .modal { background:#1f2937; color:#f1f1f1; border:1px solid #444; }
body.dark .modal-header{color: #ccc;}
body.dark label { color:#ccc; }
body.dark input, body.dark select { background:#111827; color:#f9fafb; border:1px solid #555; }
body.dark .counter { background:#3a3c3e; border-color:#555; }
body.dark .counter button { color:#f9fafb; }
body.dark .btn-cancel { background:#111827;; color:#f1f1f1; }
body.dark .metric-input button:hover {background: #333;}
body.dark .metric-input button {color: #fff;}
body.dark .metric-input input {background: transparent;color: #fff;border: none;}
body.dark .card .value {
    color: white;
}

.note-cell {
  cursor: pointer;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 250px; /* Fixed width */
  vertical-align: top;
  transition: all 0.3s ease;
  position: relative;
}

.note-cell.expanded {
  white-space: normal; /* Allow wrapping */
  word-wrap: break-word;
  overflow: visible;
  text-overflow: clip;
  background: #f9fafb;
  border-radius: 8px;
  padding: 6px 15px;
  max-width: 250px; /* Keep width same */
  position: relative;
  z-index: 1;
}

body.dark .note-cell.expanded {
  background: #1f2937;
  color: #e5e7eb;
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
      <div class="dropdown-menu hidden" id="logoutDropdown">
        <a href="login.php">Logout</a>
      </div>
    </div>
  </div>
</header>

<div class="sidebar">
  <button onclick="location.href='index.php'"> Dashboard</button>
  <button onclick="location.href='all_leads.php'"> All Leads</button>
  <button onclick="location.href='Candidate.php'"> Candidate</button>
  <button class="active" onclick="location.href='daily_report.php'"> Daily Report</button>
  <button onclick="location.href='target.php'"> Targets</button><br><br><br><br>

  <?php if($userRole === 'admin'): ?>
    <button onclick="location.href='employees.php'">Employees</button>
    <button onclick="location.href='payment.php'">Payment</button>
    <button onclick="location.href='setting.php'">⚙ Settings</button>
  <?php endif; ?>
</div>


<div class="container">
  <div class="page-header">
    <div class="page-title">
      <h2>Your Daily Reports</h2>
      <p>Showing reports: <b><?= htmlspecialchars($filterLabel) ?></b></p>
    </div>
    <div class="controls">
      <input type="date" id="startDate" class="select-month" value="<?= htmlspecialchars($startDate) ?>">
      <input type="date" id="endDate" class="select-month" value="<?= htmlspecialchars($endDate) ?>">
      <button id="filterBtn" class="btn-primary">Filter Reports</button>
      <button id="addReportBtn" class="btn-primary">+ Add Today's Report</button>
    </div>
  </div>

  <!-- Cards -->
  <div class="cards-row">
    <div class="card dept-sales">
        <div>
            <h4>Sales</h4>
            <div class="value"><?= $todaySales['total'] ?? 0 ?></div>
            <div style="font-size:12px;color:#6b7280;">
              Qualified: <?= $todaySales['qualified'] ?? 0 ?>  Connected: <?= $todaySales['connected'] ?? 0 ?>  Meetings: <?= $todaySales['meetings'] ?? 0 ?>  Hot: <?= $todaySales['hot'] ?? 0 ?>
            </div>
        </div>
        <div class="emoji">📈</div>
    </div>

    <div class="card dept-lead">
        <div>
            <h4>Lead generation</h4>
            <div class="value"><?= $todayLeads['total'] ?? 0 ?></div>
            <div style="font-size:12px;color:#6b7280;">
              Qualified: <?= $todayLeads['qualified'] ?? 0 ?> |
              Connected: <?= $todayLeads['connected'] ?? 0 ?> |
              Cold: <?= $todayLeads['cold'] ?? 0 ?> |
              Hot: <?= $todayLeads['hot'] ?? 0 ?>
            </div>
        </div>
        <div class="emoji">👥</div>
    </div>

    <div class="card dept-recruiter">
        <div>
            <h4>Recruiter</h4>
            <div class="value"><?= $todayRecruiter['total'] ?? 0 ?></div>
            <div style="font-size:12px;color:#6b7280;">
              Interview: <?= $todayRecruiter['interview'] ?? 0 ?> |
              Application: <?= $todayRecruiter['applications'] ?? 0 ?> |
              Offer: <?= $todayRecruiter['offer'] ?? 0 ?>
            </div>
        </div>
        <div class="emoji">👨🏻‍💻</div>
    </div>
  </div>

  <!-- Reports Table -->
  <div class="reports">
    <div class="reports-header">
      <h3>Recent Reports</h3>
      <div style="font-size:14px;color:#6b7280;">Total: <?= $todayReports ?></div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Department</th>
          <th>Key Metrics</th>
          <th>Notes</th>
        </tr>
      </thead>
     <tbody>
<?php
if($resultReports && $resultReports->num_rows > 0){
    while($row = $resultReports->fetch_assoc()){
        echo "<tr class='fade-in'>";
        echo "<td>".htmlspecialchars(date('M d, Y', strtotime($row['report_date'])))."</td>";

        $deptName = $row['department'];
        $deptClass = '';
        if ($deptName === 'Sales') $deptClass = 'dept-sales';
        elseif ($deptName === 'Lead generation') $deptClass = 'dept-lead';
        elseif ($deptName === 'Recruiter') $deptClass = 'dept-recruiter';

        echo "<td><span class='dept-badge $deptClass'>".htmlspecialchars($deptName)."</span></td>";

        // Department-specific metrics
        $metricsDisplay = '-';
        $notes = trim($row['notes'] ?? '');

        if($deptName === 'Sales'){
            $metricsDisplay = "Qualified: ".(int)$row['qualified']." |
                               Connected: ".(int)$row['connected']." |
                               Meetings: ".(int)$row['meetings']." |
                               Hot: ".(int)$row['hot'];
        }
        elseif($deptName === 'Lead generation'){
            $metricsDisplay = "Qualified: ".(int)$row['qualified']." |
                               Connected: ".(int)$row['connected']." |
                               Cold: ".(int)$row['cold']." |
                               Hot: ".(int)$row['hot'];
        }
        elseif ($deptName === 'Recruiter') {
            $applications = (int)($row['applications'] ?? 0);
            $interview    = (int)($row['interview'] ?? 0);
            $offer        = (int)($row['offer'] ?? 0);

            $metricsDisplay = "Interview: $interview | Applications: $applications | Offer: $offer";
        }

echo "<td>$metricsDisplay</td>";

$fullNote  = htmlspecialchars($row['notes']);
$shortNote = htmlspecialchars(strlen($row['notes']) > 40 ? substr($row['notes'], 0, 40) . '...' : $row['notes']);

echo "<td class='note-cell' data-full=\"$fullNote\" data-short=\"$shortNote\" onclick='toggleNote(this)'>$shortNote</td>";

    }
} else {
    echo "<tr><td colspan='4'>No reports found</td></tr>";
}
?>
    </tbody>
    </table>
</div>

<!-- Modal -->
<div class="overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-header">
      Add New Report
      <button class="close-btn" id="closeModal">&times;</button>
    </div>
    <div class="modal-body">
      <label>Date</label>
      <input type="date" id="reportDate" value="<?= date('Y-m-d') ?>">

      <label>Department</label>
      <select id="department">
        <option>Sales</option>
        <option>Lead generation</option>
        <option>Recruiter</option>
      </select>

      <div class="metrics-grid" id="metricsGrid"></div>

      <div style="text-align:right;margin-top:20px;">
        <button class="btn btn-cancel" id="cancelBtn">Cancel</button>
        <button class="btn btn-submit" id="saveReport">Save Report</button>
      </div>
    </div>
  </div>
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


// ========== Profile Dropdown ==========
const profileMenu = document.getElementById("profileMenu");
const logoutDropdown = document.getElementById("logoutDropdown");

profileMenu.addEventListener("click", (e) => {
    e.stopPropagation(); // Prevent click bubbling
    logoutDropdown.classList.toggle("hidden");
});

// Close dropdown when clicking outside
document.addEventListener("click", (e) => {
    if (!profileMenu.contains(e.target)) {
        logoutDropdown.classList.add("hidden");
    }
});


// Modal and metrics logic
const overlay = document.getElementById('modalOverlay');
const addReportBtn = document.getElementById('addReportBtn');
const closeModalBtn = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelBtn');
const departmentSelect = document.getElementById('department');
const metricsGrid = document.getElementById('metricsGrid');
const reportDateInput = document.getElementById('reportDate');

function changeValue(id, delta) {
    const input = document.getElementById(id);
    if (!input) return;
    let value = parseInt(input.value || 0) + delta;
    input.value = Math.max(0, value);
}

function loadMetrics() {
    const dept = departmentSelect.value;
    let metrics = [];
    if (dept === 'Sales') metrics = ['Qualified','Connected','Meetings','Hot'];
    else if (dept === 'Lead generation') metrics = ['Qualified','Connected','Cold','Hot'];
    else metrics = ['Interview','Applications','Offer'];

    // Generate metrics inputs (two-column grid)
    metricsGrid.innerHTML = metrics.map(m => `
        <div>
            <label>${m}</label>
            <div class="metric-input">
                <button type="button" onclick="changeValue('${m.toLowerCase()}', -1)">−</button>
                <input type="number" id="${m.toLowerCase()}" value="0" min="0">
                <button type="button" onclick="changeValue('${m.toLowerCase()}', 1)">+</button>
            </div>
        </div>
    `).join('');

    // Add Notes box only for Recruiter
    if (dept === 'Recruiter') {
        metricsGrid.innerHTML += `
            <div style="grid-column: span 2;">
                <label>Notes</label>
                <textarea id="notes" rows="3" style="width:100%;border-radius:10px;border:1px solid #d1d5db;padding:10px;font-size:14px;resize:vertical;"></textarea>
            </div>
        `;
    }
}

// Open modal
addReportBtn.addEventListener('click', () => {
    overlay.classList.add('show');
    // default date to today
    const today = new Date().toISOString().split('T')[0];
    reportDateInput.value = today;
    loadMetrics();
});

// Close modal handlers
[closeModalBtn, cancelBtn].forEach(btn => btn.addEventListener('click', () => overlay.classList.remove('show')));

// Close clicking outside modal
overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.classList.remove('show');
});

// department change
departmentSelect.addEventListener('change', loadMetrics);

// Save report via AJAX (same endpoint)
document.getElementById('saveReport').addEventListener('click', () => {
    const data = {
        date: document.getElementById('reportDate').value,
        dept: departmentSelect.value
    };

    ['qualified','connected','meetings','hot','cold','interview','applications','offer'].forEach(metric => {
        const el = document.getElementById(metric);
        if (el) data[metric] = el.value || 0;
    });

    const notesEl = document.getElementById('notes');
    if (notesEl) data['notes'] = notesEl.value.trim();

    fetch('daily_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
    .then(res => res.text())
    .then(res => {
        if (res.includes('success')) {
            alert('Report saved successfully!');
            overlay.classList.remove('show');
            location.reload();
        } else {
            alert('Error: ' + res);
        }
    })
    .catch(err => alert('Error: ' + err));
});

// Filter reports
document.getElementById('filterBtn').addEventListener('click', () => {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    if (start && end) {
        location.href = `daily_report.php?start=${start}&end=${end}`;
    } else {
        alert('Select both start and end dates.');
    }
});



function toggleNote(cell) {
  const fullText = cell.getAttribute('data-full');
  const shortText = cell.getAttribute('data-short');

  // Collapse all other notes
  document.querySelectorAll('.note-cell.expanded').forEach(c => {
    if (c !== cell) {
      c.classList.remove('expanded');
      c.textContent = c.getAttribute('data-short');
    }
  });

  // Toggle clicked note
  if (cell.textContent === shortText) {
    cell.classList.add('expanded');
    cell.textContent = fullText;
  } else {
    cell.classList.remove('expanded');
    cell.textContent = shortText;
  }
}
</script>



</body>
</html>