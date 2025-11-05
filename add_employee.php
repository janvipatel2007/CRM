<?php
session_start();
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "crm";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$edit_id = $_GET['edit'] ?? null;
if($edit_id){
    $edit_id = intval($edit_id);
    $res = $conn->query("SELECT * FROM employees WHERE id=$edit_id");
    if($res->num_rows > 0){
        $emp = $res->fetch_assoc();
        $_POST = $emp; // prefill form
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = $_POST['name'] ?? '';
    $emp_id = $_POST['emp_id'] ?? '';
    $join_date = $_POST['join_date'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $address = $_POST['address'] ?? '';
    $department = $_POST['department'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $salary = $_POST['salary'] ?? '';
    $bank_account = $_POST['bank_account'] ?? '';
    $ifsc = $_POST['ifsc'] ?? '';
    $bank_name = $_POST['bank_name'] ?? '';
    $payment_mode = $_POST['payment_mode'] ?? '';

    if(isset($_GET['edit'])){
        $stmt = $conn->prepare("UPDATE employees SET name=?, emp_id=?, join_date=?, email=?, mobile=?, address=?, department=?, designation=?, salary=?, bank_account=?, ifsc=?, bank_name=?, payment_mode=? WHERE id=?");
        $stmt->bind_param("ssssssssdssssi",$name,$emp_id,$join_date,$email,$mobile,$address,$department,$designation,$salary,$bank_account,$ifsc,$bank_name,$payment_mode,$edit_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO employees (name, emp_id, join_date, email, mobile, address, department, designation, salary, bank_account, ifsc, bank_name, payment_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssdssss",$name,$emp_id,$join_date,$email,$mobile,$address,$department,$designation,$salary,$bank_account,$ifsc,$bank_name,$payment_mode);
    }

    if($stmt->execute()){
        echo "<script>alert('Employee saved successfully'); window.location.href='employees.php';</script>"; exit;
    } else {
        echo "<script>alert('Error: ".$stmt->error."');</script>";
    }
}
$userName = $_SESSION['user_name'] ?? "Guest";
$userRole = $_SESSION['user_role'] ?? "user"; // ✅ fixed variable name

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<title> <?php echo isset($edit_id) ? 'Edit Employee' : 'Add Employee'; ?> Add New Employee</title>
<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; transition:all 0.3s ease; }
body{ background: linear-gradient(135deg,#dfe9f3,#ffffff); color:#001a40; overflow-x:hidden; }
body.dark{ background:#0f172a; color:#e0e0e0; }

header { background: linear-gradient(135deg, #96a7cb, #1e3c72); padding: 10px 25px; display:flex; justify-content: space-between; align-items:center; height:70px; position:fixed; top:0; width:100%; z-index:1000; box-shadow:0 4px 12px rgba(0,0,0,0.2); }
.header-left img.logo{ height:55px; width:auto; border-radius:12px; }

.header-right{ display:flex; align-items:center; gap:18px; position:relative; }
.switch{ position:relative; display:inline-block; width:60px; height:32px; }
.switch input{ display:none; }
.slider{ position:absolute; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px; }
.slider::before{ content:"☀️"; position:absolute; height:26px; width:26px; left:3px; bottom:3px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; transition:transform 0.4s,content 0.4s; }
input:checked+.slider::before{ transform:translateX(28px) rotate(360deg); content:"🌙"; }

.profile{ display:flex; align-items:center; gap:10px; position:relative; cursor:pointer; }
.profile span{ font-weight:600; color:#fff; }
.profile-img{ width:38px; height:38px; border-radius:50%; background:#fff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-weight:bold; box-shadow:0 4px 15px rgba(0,0,0,0.3); }

.dropdown-menu {
  position:absolute;top:60px;right:0;background:white;border:1px solid #ddd;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);display:flex;
  flex-direction:column;padding:10px;min-width:120px;z-index:100;}
.dropdown-menu a {
  padding:8px 12px;border-radius:6px;text-decoration:none;color:#000;}
.dropdown-menu a:hover { background:#f2f2f2;}

body.dark .dropdown-menu { background:#1e293b; color:white; border-color:#334155; }
body.dark .dropdown-menu a { color:white; }
body.dark .dropdown-menu a:hover { background:#334155; }

.sidebar{ width:230px; background:#fff; border-right:1px solid #ddd; padding:20px 0; position:fixed; top:80px; bottom:0; }
.sidebar button{ width:100%; margin:5px 0; font-family:"Poppins",sans-serif;text-align:left; font-weight:500; border:none; font-size:15px; background-color:transparent; color:#1a1a1a; cursor:pointer; padding:10px 25px; border-left:4px solid transparent; transition:all 0.3s ease; }
.sidebar button:hover{ background:#e0e0e0; color:#000; }
.sidebar button.active{ background:#d6d6d6; color:#000; font-weight:600; border-radius:0; }

body.dark .sidebar{background: linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{ background-color:transparent; color:white}  
body.dark .sidebar button.active{background:#ababa5ff;color:#000;font-weight:600; border-radius:0;}
body.dark .sidebar button:hover{background:#ababa5ff;color:#000;}

h1 { font-size:1.8rem; font-weight:700; margin:0; }
p { color:#ccccc; margin-top:6px; margin-bottom:30px; font-size:0.95rem; }
body.dark h1 {color: #93c5fd;}

.container { display:flex; max-width:1450px; margin-left:230px; gap:40px; margin-top:80px; background-color:white; height:auto;}
.content { width:250px; display:flex; flex-direction:column; padding-left:15px; padding-top:20px; }
.steps { display:flex; flex-direction:column; gap:30px; margin-top:10px; position:relative; }
.step { display:flex; align-items:center; gap:15px; cursor:pointer; position:relative; transition:0.2s; }
.step:not(:last-child)::after { content:""; position:absolute; left:18px; top:36px; width:2px; height:30px; background:#ccc; }
.step.active::after { background:#1a1a1a; }
.step-circle { width:36px; height:36px; border-radius:50%; border:2px solid #1a1a1a; display:flex; justify-content:center; align-items:center; font-weight:600; transition:0.3s; }
.step-circle.active { background:#1a1a1a; color:white; }
.step-label { font-weight:600; font-size:1rem; color:#1a1a1a; transition:color 0.3s; }
.step-label.inactive { color:#888; }
.form-container { flex:1; background:#f9f9f9; padding:35px 45px;  box-shadow:0 2px 10px rgba(0,0,0,0.08); }
.form-row { display:flex; gap:25px; flex-wrap:wrap; }
.form-group { flex:1 1 45%; display:flex; flex-direction:column; margin-bottom:20px; }
label { font-weight:600; margin-bottom:8px; }
input, select { padding:12px 14px; border:1px solid #ccc; border-radius:6px; font-size:1rem; outline:none; transition:border-color 0.2s; background:#fff; }
input:focus, select:focus { border-color:#000; }
.buttons { display:flex; justify-content:flex-end; gap:15px; margin-top:25px; }
button { padding:12px 22px; border-radius:6px; font-weight:600; border:none; cursor:pointer; font-size:1rem; transition:0.2s; }
.cancel-btn { background:#fff; border:1.5px solid #000; color:#000; }
.cancel-btn:hover { background:#f5f5f5; }
.next-btn {  background: linear-gradient(135deg, #87b6c6, #cfe9f6); color: #001a40; }
.next-btn:hover { background: linear-gradient(135deg, #516e77ff, #67c4f3ff); color: #001a40;}
.hidden { display:none; }
/* 🌙 DARK MODE THEME STYLES */
body.dark {
  background-color: #0f172a;
  color: #e2e8f0;
}

/* Containers */
body.dark .container {
  background-color: #1e293b;
}
body.dark .form-container {
  background-color: #1e293b;
  box-shadow: 0 2px 10px rgba(255, 255, 255, 0.05);
}

/* Sidebar steps */
body.dark .step-circle {
  border-color: #94a3b8;
  color: #e2e8f0;
}
body.dark .step-circle.active {
  background: #e2e8f0;
  color: #0f172a;
}
body.dark .step-label {
  color: #e2e8f0;
}
body.dark .step-label.inactive {
  color: #94a3b8;
}
body.dark .step.active::after {
  background: #e2e8f0;
}
body.dark .step:not(:last-child)::after {
  background: #475569;
}

/* Form fields */
body.dark input,
body.dark select {
  background-color: #334155;
  color: #e2e8f0;
  border: 1px solid #475569;
}
body.dark input:focus,
body.dark select:focus {
  border-color: #e2e8f0;
}

/* Buttons */
body.dark .cancel-btn {
  background: transparent;
  border: 1.5px solid #e2e8f0;
  color: #e2e8f0;
}
body.dark .cancel-btn:hover {
  background: #334155;
}
body.dark .next-btn {
 background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
}
body.dark .next-btn:hover {
  background: #cbd5e1;
}

/* Headers, labels, text */
body.dark label {
  color: #e2e8f0;
}
body.dark .step-circle.active,
body.dark .step-label.active {
color:#001a40;
}

/* Scrollbar (optional aesthetic) */
body.dark ::-webkit-scrollbar {
  width: 10px;
}
body.dark ::-webkit-scrollbar-thumb {
  background-color: #475569;
  border-radius: 5px;
}
body.dark ::-webkit-scrollbar-track {
  background: #1e293b;
}

/* Default (light mode) calendar icon */
input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(0);
}

/* Dark mode calendar icon → white */
body.dark input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1);
}



@media (max-width:800px){ .container{ flex-direction:column; } .sidebar{ width:100%; } }
</style>
</head>
<body>
<header>
  <div class="header-left"><img src="nova.png" alt="nova staff" class="logo"></div>
  <div class="header-right">
    <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>
    <div class="profile" id="profile">
      <span id="profileName"><?= htmlspecialchars($userName) ?>&nbsp;(<?= ucfirst($userRole) ?>)</span>
      <div class="profile-img" id="profileInitials"></div>
      <div class="dropdown-menu hidden" id="profileDropdown">
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

<div class="container">
  <div class="content">
    <h1><?php echo isset($edit_id) ? 'Edit Employee' : 'Add Employee'; ?></h1>
    <p>Fill in the employee details to add them to the payroll system</p>
    <div class="steps">
      <div class="step active" onclick="showStep(1)">
        <div class="step-circle active" id="circle1">1</div>
        <div class="step-label" id="label1">Employee Details</div>
      </div>
      <div class="step" onclick="showStep(2)">
        <div class="step-circle" id="circle2">2</div>
        <div class="step-label inactive" id="label2">Payment Information</div>
      </div>
    </div>
  </div>

  <form class="form-container" id="employeeForm" method="POST">
    <!-- STEP 1 -->
    <div id="step1">
      <div class="form-row">
        <div class="form-group">
          <label>Employee Name</label>
          <input type="text" name="name" required placeholder="Enter full name">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Employee ID</label>
          <input type="text" name="emp_id" required placeholder="Enter employee ID">
        </div>
        <div class="form-group">
          <label>Date of Joining</label>
          <input type="date" name="join_date" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Work Email</label>
          <input type="email" name="email" required placeholder="employee@company.com">
        </div>
        <div class="form-group">
          <label>Mobile Number</label>
          <input type="tel" name="mobile" required placeholder="Enter mobile number">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Address</label>
          <input type="text" name="address" required placeholder="Enter address">
        </div>
        <div class="form-group">
          <label>Department</label>
          <select name="department" required>
            <option value="">Select department</option>
            <option>HR</option>
            <option>Sales</option>
            <option>Marketing</option>
            <option>Development</option>
            <option>Support</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Salary</label>
          <input type="number" step="0.01" name="salary" required placeholder="Enter salary">
        </div>
        <div class="form-group">
          <label>Designation</label>
          <select name="designation" required>
            <option value="">Select designation</option>
            <option>Manager</option>
            <option>Team Lead</option>
            <option>Developer</option>
            <option>Intern</option>
          </select>
        </div>
      </div>
      <div class="buttons">
        <button type="reset" class="cancel-btn">Cancel</button>
        <button type="button" class="next-btn" onclick="showStep(2)">Next</button>
      </div>
    </div>

    <!-- STEP 2 -->
    <div id="step2" class="hidden">
      <div class="form-row">
        <div class="form-group">
          <label>Bank Account Number</label>
          <input type="text" name="bank_account" required placeholder="Enter bank account number">
        </div>
        <div class="form-group">
          <label>IFSC Code</label>
          <input type="text" name="ifsc" required placeholder="Enter IFSC code">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Bank Name</label>
          <input type="text" name="bank_name" required placeholder="Enter bank name">
        </div>
        <div class="form-group">
          <label>Payment Mode</label>
          <select name="payment_mode" required>
            <option value="">Select payment mode</option>
            <option>Bank Transfer</option>
            <option>Cheque</option>
            <option>UPI</option>
          </select>
        </div>
      </div>
      <div class="buttons">
        <button type="button" class="cancel-btn" onclick="showStep(1)">Back</button>
        <button type="submit" class="next-btn"><?php echo isset($edit_id) ? 'Update Employee' : 'Add Employee'; ?></button>
      </div>
    </div>
  </form>
</div>

<script>

  const themeToggle = document.getElementById("themeToggle");
const savedTheme = localStorage.getItem("darkMode") === "true";
if(savedTheme) document.body.classList.add("dark");
themeToggle.checked = savedTheme;
themeToggle.addEventListener("change", () => { 
    document.body.classList.toggle("dark"); 
    localStorage.setItem("darkMode", document.body.classList.contains("dark")); 
});
function showStep(step){
  document.getElementById('step1').classList.toggle('hidden', step!==1);
  document.getElementById('step2').classList.toggle('hidden', step!==2);
  document.querySelectorAll('.step').forEach((el,idx)=>{
    el.classList.toggle('active', idx+1===step);
    el.querySelector('.step-circle').classList.toggle('active', idx+1===step);
    el.querySelector('.step-label').classList.toggle('inactive', idx+1!==step);
  });
}

// ✅ Profile initials
const profileName = document.getElementById('profileName').innerText.trim();
const initials = profileName.split(' ')[0][0].toUpperCase() + (profileName.split(' ')[1]?.[0]?.toUpperCase() || '');
document.getElementById('profileInitials').innerText = initials || 'U';

// ✅ Profile dropdown toggle
const profile = document.getElementById('profile');
const dropdown = document.getElementById('profileDropdown');
profile.addEventListener('click', e=>{
  e.stopPropagation();
  dropdown.classList.toggle('hidden');
});
document.addEventListener('click', e=>{
  if(!profile.contains(e.target)) dropdown.classList.add('hidden');
});
</script>
</body>
</html>
