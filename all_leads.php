<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------- Database Connection ----------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "crm";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userRole  = $_SESSION['user_role'] ?? "user";
$userName  = $_SESSION['user_name'] ?? "User";

// ---------- Ensure user is logged in ----------
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// ---------- Handle Delete ----------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM leads WHERE id=$id")) {
        $_SESSION['msg'] = "Lead deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Error deleting lead!";
        $_SESSION['msg_type'] = "error";
    }
    header("Location: all_leads.php");
    exit;
}

// ---------- Handle Add / Update ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $id          = $_POST['id'] ?? "";
    $name        = $_POST['name'];
    $email       = $_POST['email'];
    $phone       = $_POST['phone'];
    $visa        = $_POST['visa'];
    $status      = $_POST['status'];
    $generatedBy = $_POST['generated_by_team'] ?? '';
    $leadType    = $_POST['leadType'];

    if ($id) {
        $sql = "UPDATE leads SET 
                    name='$name', email='$email', phone='$phone', visa='$visa',
                    status='$status', generatedBy='$generatedBy', leadType='$leadType'
                WHERE id=$id";
        $msg = "Lead updated successfully!";
    } else {
        $sql = "INSERT INTO leads (name,email,phone,visa,status,generatedBy,leadType,created_at) 
                VALUES ('$name','$email','$phone','$visa','$status','$generatedBy','$leadType',NOW())";
        $msg = "Lead added successfully!";
    }

    if ($conn->query($sql)) {
        $_SESSION['msg'] = $msg;
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Error: " . $conn->error;
        $_SESSION['msg_type'] = "error";
    }

    header("Location: all_leads.php");
    exit;
}

// ---------- Fetch dropdown values ----------
$statusResult = $conn->query("SELECT * FROM lead_status ORDER BY name ASC");
$typeResult   = $conn->query("SELECT * FROM lead_type ORDER BY name ASC");
$typevisa     = $conn->query("SELECT * FROM visa_type ORDER BY name ASC");
$teamMembers  = $conn->query("SELECT * FROM team_members ORDER BY name ASC");

// ---------- Fetch Leads ----------
$leads = [];
$result = $conn->query("SELECT * FROM leads ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) $leads[] = $row;
}
$totalLeads = count($leads);

// ---------- Toast Message ----------
$msg = $_SESSION['msg'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM - Leads</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;transition:all 0.3s ease;}
body{background: linear-gradient(135deg,#dfe9f3,#ffffff);color:#001a40;overflow-x:hidden;overflow-y:hidden;}
body.dark{background:#0f172a;color:#e0e0e0;}
header{background: linear-gradient(135deg, #96a7cb, #1e3c72); padding:10px 25px; display:flex; justify-content:space-between; align-items:center; height:70px; position:fixed; top:0; left:0; width:100%; z-index:1000; box-shadow:0 4px 12px rgba(0,0,0,0.2);}
.header-left img.logo{height:55px; width:auto; border-radius:12px;}
.header-right{display:flex; align-items:center; gap:18px;}
.switch{position:relative; display:inline-block; width:60px; height:32px;}
.switch input{display:none;}
.slider{position:absolute; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px;}
.slider::before{content:"☀️"; position:absolute; height:26px; width:26px; left:3px; bottom:3px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; transition:transform 0.4s,content 0.4s;}
input:checked+.slider{background-color:#ccc;}
input:checked+.slider::before{transform:translateX(28px) rotate(360deg); content:"🌙";}

/* Profile Dropdown */
.profile{position:relative;display:flex;align-items:center;gap:10px;cursor:pointer;}
.profile span{font-weight:600;color:#fff;}
.profile-img{width:38px;height:38px;border-radius:50%;background:#fff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:bold;box-shadow:0 4px 15px rgba(0,0,0,0.3);}
.dropdown-menu{position:absolute;top:48px;right:0;background:#fff;color:#1e3c72;border:1px solid #ddd;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.2);padding:8px 0;min-width:120px;z-index:200;}
.dropdown-menu a{display:block;padding:8px 16px;text-decoration:none;color:inherit;font-weight:500;}
.dropdown-menu a:hover{background:#f3f4f6;}
.hidden{display:none;}
body.dark .dropdown-menu{background:#1e293b;color:#e0e0e0;border-color:#475569;}
body.dark .dropdown-menu a:hover{background:#334155;}
.sidebar{width:230px; margin-top:0; background:#fff; border-right:1px solid #ddd; padding:20px 0; position:fixed; top:80px; bottom:0;}
.sidebar button{width:100%; margin:5px 0; text-align:left; font-weight:500; border:none; font-size:15px; background-color:transparent; color:#1a1a1a; cursor:pointer; padding:10px 25px; border-left:4px solid transparent; transition:all 0.3s ease;}
.sidebar button:hover{background:#e0e0e0; color:#000;}
.sidebar button.active{background:#d6d6d6; color:#000; font-weight:600;}
.content{flex:1; margin-left:230px; margin-top:80px;   padding: 0 20px 20px; background-color:white; height:calc(100vh - 80px); overflow-y:auto;}
/* Hide top section smoothly when scrolling */
.top-controls {
  display:flex;
  flex-direction:column;
  gap:12px;
  transition:transform 0.3s ease, opacity 0.3s ease;
}

.hidden-top {
  transform: translateY(-80%);
  opacity: 0;
  pointer-events: none;
}
h1{font-size:32px;margin-bottom:0;color:#1e3c72;}
.form{display:flex;gap:10px;align-items:center;justify-content:flex-start;padding:12px 15px;border-radius:14px;background:#f5f5f5;border:1px solid #d0d7de;flex-wrap:wrap;}
.form input,.form select{padding:12px 10px;border-radius:10px;border:1px solid #87b6c6;flex:1; color: #001a40;}
.btn{padding:12px 20px;border-radius:10px;border:none;font-weight:600;cursor:pointer;background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}
.filter-bar{display:flex;gap:10px;align-items:center;justify-content:flex-start;padding:12px 15px;border-radius:14px;background:#f5f5f5;border:1px solid #d0d7de;flex-wrap:wrap;}
.filter-bar select,.filter-bar input{padding:10px;border-radius:8px;border:1px solid #87b6c6; width: 300px;  color: #001a40;}
.total-leads-box{padding:10px 20px;background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;font-weight:700;border-radius:10px;width:160px;margin-bottom:10px;}
.table-container { max-height: 500px; overflow-y:auto; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.table-container table { width:100%; border-collapse:collapse; }
.table-container th, .table-container td { padding:12px; text-align:center; font-size:14px;color: #001a40; border: none; border-bottom:1px solid #c8c2c2;}
.table-container th { background-color: #cfdbe7; color: #1e3c72; font-weight:600; position:sticky; top:0; z-index:2; }
.table-container tr:hover { background: rgba(123, 123, 123, 0.1); }
.edit-btn, .delete-btn{ display:block; padding:5px 10px; font-weight:600; text-decoration:none; }
.dropdown{position:relative; display:inline-block;}
.dot-btn{background:transparent; border:none; font-size:18px; cursor:pointer; font-weight:bold;}
.dropdown-content{ display:none; position:absolute; right:0; background:#fff; min-width:100px; box-shadow:0 4px 8px rgba(0,0,0,0.2); border-radius:8px; z-index:100;}
.dropdown-content a{ color:#001a40; padding:8px 12px; display:block; text-decoration:none; font-weight:500;}
.dropdown-content a:hover{ background:#e0e0e0;}


body.dark .sidebar{background: linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{ background-color:transparent; color:white}  
body.dark .sidebar button.active{ background:#ababa5ff; color:#000; font-weight:600;}
body.dark .sidebar button:hover{ background:#ababa5ff; color:#000;}
body.dark .content { background:#1e293b; color:#e2e8f0; }
body.dark h1 { color:#93c5fd; }
body.dark .form, body.dark .filter-bar { background:#1e293b; border:1px solid #374151; }
body.dark .form input, body.dark .form select, body.dark .filter-bar input, body.dark .filter-bar select { background:#0f172a; border:1px solid #475569; color:#e2e8f0; }
body.dark .table-container table { background:#1f2937; color:#e5e7eb; }
body.dark .table-container th { background:#374151; color:#f9fafb;  }
body.dark .table-container td { border-bottom:1px solid #374151; color:#e2e8f0; }
body.dark .table-container tr:nth-child(odd){ background:#1e293b; }
body.dark .table-container tr:nth-child(even){ background:#273548; }
body.dark .table-container tr:hover { background:#334155; }
body.dark .dropdown-content{ background:#1f2937;}
body.dark .dropdown-content a{ color:#e2e8f0;}
body.dark .dropdown-content a:hover{ background:#334155;}
/* Dark mode: table names white */
body.dark .table-container td a {
    color: #ffffff !important;
}

/* 3-dot menu */
.actions {
  position: relative;
}

.menu-btn {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #001a40; /* Light mode */
}

.menu-content {
  display: none;
  position: absolute;
  right: 0;
  top: 25px;
  background: #ffffff;
  min-width: 120px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  border-radius: 8px;
  z-index: 100;
  overflow: hidden;
}

.menu-content a {
  display: block;
  padding: 8px 12px;
  text-decoration: none;
  color: #001a40; /* Light mode */
  font-weight: 500;
}

.menu-content a:hover {
  background: #e0e0e0;
}

/* Dark mode */
body.dark .dot-btn { color: #e0e0e0; }
body.dark .menu-content { background: #1f2937; }
body.dark .menu-content a { color: #e0e0e0; }
body.dark .menu-content a:hover { background: #334155; }



/* Toast Message */
/* Toast Message (Centered) */
.toast {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-0%, -50%);  /* ✅ Center horizontally & vertically */
  background: #ffffffff;
  color: black;
  padding: 14px 28px;
  border-radius: 10px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
  font-weight: 600;
  z-index: 2000;
  opacity: 0;
  animation: fadeInOut 3.5s ease forwards;
  text-align: center;
  font-size: 16px;
}
.toast.error {
  background: #efefefff;
  color:black;
}

/* Animation */
@keyframes fadeInOut {
  0% { opacity: 0; transform: translate(-50%, -60%); }
  10% { opacity: 1; transform: translate(-50%, -50%); }
  90% { opacity: 1; transform: translate(-50%, -50%); }
  100% { opacity: 0; transform: translate(-50%, -40%); }
}

/* Scroll to Top Arrow */
#scrollTopBtn {
  position: fixed;
  bottom: 40px;
  right: 35px;
  background: linear-gradient(135deg, #1e3c72, #2a5298);
  color: white;
  border: none;
  border-radius: 50%;
  width: 45px;
  height: 45px;
  font-size: 20px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s;
  z-index: 999;
}
#scrollTopBtn.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}
#scrollTopBtn:hover { transform: scale(1.1); background: linear-gradient(135deg, #2a5298, #1e3c72); }

</style>
</head>
<body>
<?php if (!empty($msg)): ?>
  <div id="toast" class="toast <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<header>
  <div class="header-left"><img src="nova.png" alt="nova staff" class="logo"></div>
  <div class="header-right">
    <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>

    <!-- ✅ Profile with dropdown -->
    <div class="profile" id="profileMenu">
      <span id="profileName"><?= htmlspecialchars($userName) ?>&nbsp;(<?= ucfirst($userRole) ?>)</span>
      <?php
  // Generate initials (first letter of first + last word)
  $nameParts = explode(" ", trim($userName));
  $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
?>
<div class="profile-img" id="profileInitials"><?= htmlspecialchars($initials) ?></div>

      <div class="dropdown-menu hidden" id="logoutDropdown">
        <a href="login.php">Logout</a>
      </div>
    </div>
  </div>
</header>

<div class="sidebar">
  <button onclick="location.href='index.php'">Dashboard</button>
  <button class="active" onclick="location.href='all_leads.php'">All Leads</button>
  <button onclick="location.href='Candidate.php'">Candidate</button>
  <button onclick="location.href='daily_report.php'">Daily Report</button>
  <button onclick="location.href='target.php'">Targets</button><br><br><br><br>
  <?php if($userRole === 'admin'): ?>
    <button onclick="location.href='employees.php'">Employees</button>
    <button onclick="location.href='payment.php'">Payment</button>
    <button onclick="location.href='setting.php'">⚙ Settings</button>
  <?php endif; ?>
</div>

<div class="content">
  <div class="top-controls">
    <h1>Leads</h1>

    <?php 
      $editData = null;
      if(isset($_GET['edit'])){
        $id=intval($_GET['edit']);
        $res=$conn->query("SELECT * FROM leads WHERE id=$id");
        $editData=$res->fetch_assoc();
      }
    ?>

    <form method="post" class="form">
      <input type="hidden" name="id" value="<?= $_GET['edit'] ?? '' ?>">
      <input type="text" name="name" placeholder="Enter Name" value="<?= $editData['name'] ?? '' ?>" required>
      <input type="email" name="email" placeholder="Enter Email" value="<?= $editData['email'] ?? '' ?>" required>
      <input type="text" name="phone" placeholder="+1 212 456 7890" value="<?= $editData['phone'] ?? '' ?>" required>


      <select name="visa" required>
        <option value="">Select Visa</option>
    <?php
    $visaResult = $conn->query("SELECT * FROM lead_visa ORDER BY name ASC");
    while($v = $visaResult->fetch_assoc()){
        echo "<option value='{$v['name']}'>{$v['name']}</option>";
    }
    ?>
      </select>

      <!-- ✅ Status dropdown (from settings.php) -->
      <select name="status" required>
        <option value="">Select Status</option>
        <?php 
          $statusResult2 = $conn->query("SELECT * FROM lead_status ORDER BY name ASC");
          while ($row = $statusResult2->fetch_assoc()): ?>
            <option value="<?= $row['name']; ?>" <?= ($editData['status']??'')==$row['name']?"selected":"" ?>>
              <?= htmlspecialchars($row['name']); ?>
            </option>
        <?php endwhile; ?>
      </select>

      <div class="col-md-6">
        <select name="generated_by_team" class="form-select">
          <option value="">Generated By</option>
          <?php if ($teamMembers && $teamMembers->num_rows > 0): ?>
            <?php while ($tm = $teamMembers->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($tm['name']) ?>" <?= ($tm['name'] == $userName) ? 'selected' : '' ?>>
                <?= htmlspecialchars($tm['name']) ?>
              </option>
            <?php endwhile; ?>
          <?php else: ?>
            <option>No team members found</option>
          <?php endif; ?>
        </select>
      </div>

      <!-- ✅ Lead Type dropdown (from settings.php) -->
      <select name="leadType" required>
        <option value="">Select Type</option>
        <?php 
          $typeResult2 = $conn->query("SELECT * FROM lead_type ORDER BY name ASC");
          while ($row = $typeResult2->fetch_assoc()): ?>
            <option value="<?= $row['name']; ?>" <?= ($editData['leadType']??'')==$row['name']?"selected":"" ?>>
              <?= htmlspecialchars($row['name']); ?>
            </option>
        <?php endwhile; ?>
      </select>

      <button type="submit" class="btn"><?= isset($_GET['edit']) ? "Update Lead" : "Add Lead" ?></button>
    </form>

    <div class="filter-bar">
      <input type="text" id="searchBox" placeholder="Search by Name, Email, Phone...">
     <select name="visa_id" required>
  <option value="">All Visa</option>
  <?php
  $visaResult = $conn->query("SELECT * FROM lead_visa ORDER BY name ASC");
  while($v = $visaResult->fetch_assoc()){
    $sel = (($editData['visa_id'] ?? '') == $v['id']) ? 'selected' : '';
    echo "<option value='{$v['id']}' $sel>{$v['name']}</option>";
  }
  ?>
</select>
      <select id="filterStatus">
        <option value="">All Status</option>
        <?php 
          $statusResult3 = $conn->query("SELECT * FROM lead_status ORDER BY name ASC");
          while ($r = $statusResult3->fetch_assoc()){ echo "<option>".$r['name']."</option>"; } 
        ?>
      </select>
      <select id="filterType">
        <option value="">All Lead Type</option>
        <?php 
          $typeResult3 = $conn->query("SELECT * FROM lead_type ORDER BY name ASC");
          while ($r = $typeResult3->fetch_assoc()){ echo "<option>".$r['name']."</option>"; } 
        ?>
      </select>
    </div>

    <div class="total-leads-box">
      Total Leads: <span id="totalLeadsCount"><?= $totalLeads ?></span>
    </div>
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>NAME</th><th>EMAIL</th><th>PHONE</th><th>VISA</th><th>STATUS</th><th>GENERATED BY</th><th>LEAD TYPE</th><th>ACTIONS</th>
        </tr>
      </thead>
      <tbody id="leadList">
      <?php if (empty($leads)): ?>
          <tr><td colspan="9" class="text-center text-muted">No record found</td></tr>
      <?php else: foreach ($leads as $l): ?>
          <tr>
              <td><?= $l['id'] ?></td>
              <td><a href="notes.php?lead_id=<?= $l['id'] ?>" style="color:#1e3c72;text-decoration:underline;font-weight:600;"><?= htmlspecialchars($l['name']) ?></a></td>
              <td><?= htmlspecialchars($l['email']) ?></td>
              <td><?= htmlspecialchars($l['phone']) ?></td>
              <td><?= htmlspecialchars($l['visa']) ?></td>
              <td><?= htmlspecialchars($l['status']) ?></td>
              <td><?= htmlspecialchars($l['generatedBy']) ?></td>
              <td><?= htmlspecialchars($l['leadType']) ?></td>
              <td class="actions">
                  <div class="dropdown">
                      <button class="dot-btn">⋮</button>
                      <div class="dropdown-content">
                          <a href="?edit=<?= $l['id'] ?>">Edit</a>
                          <a href="?delete=<?= $l['id'] ?>" onclick="return confirm('Delete this lead?')">Delete</a>
                      </div>
                  </div>
              </td>
          </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<button id="scrollTopBtn" title="Back to Top">↑</button>

<script>
// ----- PROFILE MENU -----
const profileMenu = document.getElementById("profileMenu");
const logoutDropdown = document.getElementById("logoutDropdown");

if (profileMenu && logoutDropdown) {
  profileMenu.addEventListener("click", () => {
    logoutDropdown.classList.toggle("hidden");
  });

  window.addEventListener("click", (e) => {
    if (!profileMenu.contains(e.target) && !logoutDropdown.contains(e.target)) {
      logoutDropdown.classList.add("hidden");
    }
  });
}

// ----- DARK MODE TOGGLE -----
const themeToggle=document.getElementById("themeToggle");
if(localStorage.getItem("darkMode")==="true"){document.body.classList.add("dark");themeToggle.checked=true;}
themeToggle.addEventListener("change",()=>{document.body.classList.toggle("dark");localStorage.setItem("darkMode",document.body.classList.contains("dark"));});


// ----- 3-DOT DROPDOWN (⋮) FOR EACH ROW -----
document.querySelectorAll(".dot-btn").forEach((btn) => {
  btn.addEventListener("click", (e) => {
    e.stopPropagation();
    const dropdown = btn.nextElementSibling;

    // Close all other dropdowns
    document.querySelectorAll(".dropdown-content").forEach((menu) => {
      if (menu !== dropdown) menu.style.display = "none";
    });

    // Toggle this one
    dropdown.style.display =
      dropdown.style.display === "block" ? "none" : "block";
  });
});

// ----- CLOSE DROPDOWN WHEN CLICKED OUTSIDE -----
window.addEventListener("click", () => {
  document.querySelectorAll(".dropdown-content").forEach((menu) => {
    menu.style.display = "none";
  });
});

// ----- OPTIONAL: Close dropdown when scrolling -----
window.addEventListener("scroll", () => {
  document.querySelectorAll(".dropdown-content").forEach((menu) => {
    menu.style.display = "none";
  });
});

// ===== AUTO FORMAT PHONE INPUT (+1 XXX XXX XXXX) =====
document.addEventListener('input', function(e) {
  if (e.target.name === 'phone') {
    let x = e.target.value.replace(/\D/g, '').substring(0, 11); // digits only, limit 11
    if (!x.startsWith('1')) {
      x = '1' + x; // ensure it always starts with +1
    }
    let formatted = '+1';
    if (x.length > 1) formatted += ' ' + x.substring(1, 4);
    if (x.length > 4) formatted += ' ' + x.substring(4, 7);
    if (x.length > 7) formatted += ' ' + x.substring(7, 11);
    e.target.value = formatted.trim();
  }
});


// ===== Auto-hide Toast =====
const toast = document.getElementById("toast");
if (toast) setTimeout(() => toast.style.display = "none", 3500);

// ===== Scroll Hide Top Controls =====
const content = document.querySelector('.content');
const topControls = document.querySelector('.top-controls');
let lastScrollTop = 0;
if (content && topControls) {
  content.addEventListener('scroll', () => {
    const scrollTop = content.scrollTop;
    if (scrollTop > lastScrollTop + 20) topControls.classList.add('hidden-top');
    else if (scrollTop < lastScrollTop - 20 || scrollTop < 50) topControls.classList.remove('hidden-top');
    lastScrollTop = scrollTop;
  });
}

// ===== Scroll-to-Top Button =====
const scrollBtn = document.getElementById("scrollTopBtn");
content.addEventListener("scroll", () => {
  if (content.scrollTop > 200) scrollBtn.classList.add("show");
  else scrollBtn.classList.remove("show");
});
scrollBtn.addEventListener("click", () => {
  content.scrollTo({ top: 0, behavior: "smooth" });
});

// ===== SEARCH & FILTER FUNCTIONALITY =====
const searchBox = document.getElementById("searchBox");
const filterStatus = document.getElementById("filterStatus");
const filterType = document.getElementById("filterType");
const leadList = document.getElementById("leadList");

function filterLeads() {
  const search = searchBox.value.toLowerCase();
  const status = filterStatus.value.toLowerCase();
  const type = filterType.value.toLowerCase();

  document.querySelectorAll("#leadList tr").forEach(row => {
    const name = row.children[1]?.textContent.toLowerCase() || "";
    const email = row.children[2]?.textContent.toLowerCase() || "";
    const phone = row.children[3]?.textContent.toLowerCase() || "";
    const leadStatus = row.children[5]?.textContent.toLowerCase() || "";
    const leadType = row.children[7]?.textContent.toLowerCase() || "";

    const matchesSearch = name.includes(search) || email.includes(search) || phone.includes(search);
    const matchesStatus = !status || leadStatus === status;
    const matchesType = !type || leadType === type;

    if (matchesSearch && matchesStatus && matchesType) row.style.display = "";
    else row.style.display = "none";
  });
}

if (searchBox && filterStatus && filterType) {
  searchBox.addEventListener("input", filterLeads);
  filterStatus.addEventListener("change", filterLeads);
  filterType.addEventListener("change", filterLeads);
}


</script>
</body>
</html>
