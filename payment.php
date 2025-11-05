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

// ---------- Handle Add Payment ----------
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_payment'])){
    $name = $_POST['customer_name'];
    $amount = $_POST['total_payment'];
    $installments = $_POST['installments'];
    $status = $_POST['status'];
    $date = $_POST['payment_date'];
    $method = $_POST['payment_method'];
    $enrolled = $_POST['enrolled_by'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO payments 
        (customer_name,total_payment,installments,status,payment_date,payment_method,enrolled_by,notes)
        VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sdssssss",$name,$amount,$installments,$status,$date,$method,$enrolled,$notes);
    $stmt->execute();
    $stmt->close();
    header("Location: payment.php");
    exit;
}

// ---------- Fetch all payments ----------
$result = $conn->query("SELECT * FROM payments ORDER BY payment_date DESC");
$payments = [];
if($result->num_rows>0){
    while($row = $result->fetch_assoc()){
        $payments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Payments Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ====== General ====== */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;transition:all 0.3s ease;}
body{background: linear-gradient(135deg,#dfe9f3,#ffffff);color:#001a40;overflow-x:hidden;}
header{background: linear-gradient(135deg,#96a7cb,#1e3c72);padding:10px 25px;display:flex;justify-content:space-between;align-items:center;height:70px;position:fixed;top:0;width:100%;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,0.2);}
.header-left img.logo{height:55px;width:auto;border-radius:12px;}
.header-right{display:flex;align-items:center;gap:18px;}
.profile{display:flex;align-items:center;gap:10px;position:relative;}
.profile span{font-weight:600;color:#fff;}
.profile-img{width:38px;height:38px;border-radius:50%;background:#fff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:bold;box-shadow:0 4px 15px rgba(0,0,0,0.3);cursor:pointer;}
.dropdown-menu{
  position:absolute;
  top:50px;
  right:0;
  background:#fff;
  color:#000;
  padding:8px 12px;
  border-radius:6px;
  box-shadow:0 6px 12px rgba(0,0,0,.15);
  display: none; /* hide by default */
}
.dropdown-menu.show{display:block;}
.dropdown-menu a{ text-decoration:none; color:#000; font-weight:500; }

.switch{position:relative; display:inline-block; width:60px; height:32px;}
.switch input{display:none;}
.slider{position:absolute; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px;}
.slider::before{content:"☀️"; position:absolute; height:26px; width:26px; left:3px; bottom:3px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; transition:transform 0.4s,content 0.4s;}
input:checked+.slider{background-color:#ccc;}
input:checked+.slider::before{transform:translateX(28px) rotate(360deg); content:"🌙";}

/* ===== Sidebar ===== */
.sidebar{width:230px;background:#fff;border-right:1px solid #ddd;padding:20px 0;position:fixed;top:80px;bottom:0;}
.sidebar button{width:100%;margin:5px 0;text-align:left;font-weight:500;border:none;font-size:15px;background-color:transparent;color:#1a1a1a;cursor:pointer;padding:10px 25px;border-left:4px solid transparent;}
.sidebar button:hover{background:#e0e0e0;color:#000;}
.sidebar button.active{background:#d6d6d6;color:#000;font-weight:600;}

/* ===== Main Content ===== */
.main-content{margin-left:230px;margin-top:80px;padding:20px;background:white;min-height:100vh;}
h1{font-size:28px;font-weight:700;margin-bottom:20px;}
.card{background:white;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);padding:25px;margin-bottom:20px;}
.income h2{font-size:34px;color:#000;margin:6px 0;}
.income p{color:#000;font-weight:500;margin:0;}

.actions{display:flex;gap:10px;margin-bottom:15px;align-items:center;flex-wrap:wrap;}
.actions input{flex:1 1 200px;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;}
.btn{border:none;padding:10px 16px;border-radius:8px;font-weight:600;cursor:pointer;font-size:14px;white-space:nowrap;transition:all 0.3s ease;}
.btn.export{background:#fff;border:1px solid #d1d5db;}
.btn.add{background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}
.btn.add:hover{background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;transform:scale(1.05);}

table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;}
th,td{padding:14px 18px;border-bottom:1px solid #e5e7eb;text-align:left;}
th{background:#f9fafb;color:#6b7280;font-size:14px;text-transform:uppercase;}
td{font-size:15px;}
.status{padding:4px 10px;border-radius:12px;font-weight:500;font-size:13px;}
.paid{background:#dcfce7;color:#166534;}
.pending{background:#fef9c3;color:#92400e;}
.overdue{background:#fee2e2;color:#991b1b;}
a.view{color:#2563eb;font-weight:500;text-decoration:none;cursor:pointer;}
a.view:hover{text-decoration:underline;}

/* ===== Modal ===== */
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);justify-content:center;align-items:center;z-index:1000;animation:fadeIn 0.3s ease-in-out;}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
.modal-content{background: rgba(255,255,255,0.98);backdrop-filter:blur(14px);border-radius:20px;padding:35px 40px;width:700px;max-width:95%;box-shadow:0 20px 50px rgba(0,0,0,0.3);transform:translateY(-30px);opacity:0;animation:slideIn 0.5s forwards;}
@keyframes slideIn{to{transform:translateY(0);opacity:1;}}
.modal-content h2{text-align:center;color:#2563eb;margin-bottom:25px;font-size:26px;}
.form-group{position:relative;margin-bottom:22px;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:16px 14px;border:1px solid #d1d5db;border-radius:14px;background: rgba(255,255,255,0.8);font-size:16px;transition:all 0.3s ease;}
.form-group label{position:absolute;top:50%;left:14px;transform:translateY(-50%);color:#6b7280;pointer-events:none;transition:all 0.2s ease;background: rgba(255,255,255,0.98);padding:0 6px;}
.form-group input:focus + label,.form-group input:not(:placeholder-shown) + label,
.form-group select:focus + label,.form-group select:not([value=""]):valid + label,
.form-group textarea:focus + label,.form-group textarea:not(:placeholder-shown) + label{top:-10px;font-size:13px;color:#2563eb;}
.modal-content button{padding:14px;border-radius:14px;font-weight:600;font-size:15px;margin-top:10px;cursor:pointer;transition:all 0.3s ease;}
#saveBtn{background: linear-gradient(135deg,#2563eb,#1e40af);color:white;box-shadow:0 6px 15px rgba(37,99,235,0.3);width:48%;margin-right:4%;}
#saveBtn:hover{transform:scale(1.03);box-shadow:0 8px 20px rgba(37,99,235,0.4);}
.close-btn{background:#e5e7eb;color:#111;width:48%;}
.close-btn:hover{background:#d1d5db;}
.form-row{display:flex;gap:16px;flex-wrap:wrap;}
.form-row .form-group{flex:1;min-width:140px;}
.form-group.full-width{width:100%;}

/* Dark Mode Styles */
body.dark {background:#0f172a;color:#f1f5f9;}
body.dark .sidebar{background: linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{ background-color:transparent; color:white}  
body.dark .sidebar button.active{ background:#ababa5ff; color:#000; font-weight:600;}
body.dark .sidebar button:hover{ background:#ababa5ff; color:#000;}
body.dark .main-content {background: #1e293b;color: #f1f5f9;}
body.dark .card{background: #0f172a;}
body.dark p{color:#fff;}
body.dark h2{color:#fff;}

body.dark .actions input{background: #0f172a;color: #e2e8f0;border:1px solid #334155;}
body.dark  table { background:#1f2937; color:#e5e7eb; }
body.dark  th { background:#374151; color:#f9fafb;  }
body.dark  td { border-bottom:1px solid #374151; color:#e2e8f0; }
body.dark td a{color:#e2e8f0 !important;}
body.dark  tr:nth-child(odd){ background:#1e293b; }
body.dark  tr:nth-child(even){ background:#273548; }
body.dark  tr:hover { background:#334155; }

body.dark .status.paid {background:#064e3b;color:#d1fae5;}
body.dark .status.pending {background:#78350f;color:#fef3c7;}
body.dark .status.overdue {background:#7f1d1d;color:#fee2e2;}
body.dark .modal-content {background: rgba(30,41,59,0.95);color:#f1f5f9;}
body.dark .form-group input, body.dark .form-group select, body.dark .form-group textarea {background: rgba(51,65,85,0.9); color:#f1f5f9; border:1px solid #475569;}
body.dark .form-group label {background-color:transparent;color:#f1f5f9;}
body.dark #saveBtn {background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}
body.dark .close-btn {background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}
body.dark .btn.export {background: #1e293b;border:1px solid #475569;color:#f1f5f9;}
body.dark .btn.add {background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}


@media(max-width:700px){.modal-content{width:90%;padding:25px 20px;}.form-row{flex-direction:column;}.modal-content button{width:100%;margin-right:0;}}
</style>
</head>
<body>

<header>
  <div class="header-left"><img src="nova.png" alt="nova staff" class="logo"></div>
  <div class="header-right">
    <!-- Dark/Light Mode Toggle -->
    <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>
    <div class="profile">
      <span id="profileName"><?= htmlspecialchars($userName) ?>&nbsp;(<?= ucfirst($userRole) ?>)</span>
      <div class="profile-img" id="profileInitials">
        <?php
          $parts = explode(' ', trim($userName));
          $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
          echo $initials;
        ?>
      </div>
      <!-- Logout Dropdown -->
      <div class="dropdown-menu" id="logoutDropdown">
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

<div class="main-content">
  <h1>Payments Dashboard</h1>
  <div class="card income">
    <p>This Month’s Income</p>
    <h2 id="totalIncome">$<?php
      $month_total = 0;
      $now = date('Y-m');
      foreach($payments as $p){
          if(substr($p['payment_date'],0,7)==$now){
              $month_total += $p['total_payment'];
          }
      }
      echo number_format($month_total,2);
    ?></h2>
    <p>+5.6% from last month</p>
  </div>

  <div class="actions">
    <input type="text" id="searchInput" placeholder="Search customers...">
    <button class="btn export" onclick="exportCSV()">⬇ Export Data</button>
    <button class="btn add" id="openModal">+ Add Payment</button>
  </div>

  <table id="paymentTable">
    <thead>
      <tr>
        <th>Customer Name</th>
        <th>Total Payment</th>
        <th>Installments</th>
        <th>Status</th>
        <th>Date</th>
        <th>Payment Method</th>
        <th>Enrolled By</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($payments as $p): 
        $statusClass = $p['status']=='paid'?'paid':($p['status']=='pending'?'pending':'overdue');
      ?>
      <tr data-notes="<?=htmlspecialchars($p['notes'])?>">
        <td><?=htmlspecialchars($p['customer_name'])?></td>
        <td>$<?=number_format($p['total_payment'],2)?></td>
        <td><?=htmlspecialchars($p['installments'])?></td>
        <td><span class="status <?=$statusClass?>"><?=ucfirst($p['status'])?></span></td>
        <td><?=htmlspecialchars($p['payment_date'])?></td>
        <td><?=htmlspecialchars($p['payment_method'])?></td>
        <td><?=htmlspecialchars($p['enrolled_by'])?></td>
        <td><a href="#" class="view">View</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Payment Modal -->
<div class="modal" id="paymentModal">
  <div class="modal-content">
    <h2>Add Payment</h2>
    <form method="POST" id="addPaymentForm">
      <input type="hidden" name="add_payment" value="1">
      <div class="form-group full-width">
        <input type="text" name="customer_name" placeholder=" " required>
        <label>Customer Name</label>
      </div>
      <div class="form-row">
        <div class="form-group">
          <input type="number" name="total_payment" placeholder=" " required>
          <label>Total Payment</label>
        </div>
        <div class="form-group">
          <input type="text" name="installments" placeholder=" " required>
          <label>Installments</label>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <select name="status" required>
            <option value="" disabled selected>Status</option>
            <option value="paid">Paid</option>
            <option value="pending">Pending</option>
            <option value="overdue">Overdue</option>
          </select>
          <label>Status</label>
        </div>
        <div class="form-group">
          <select name="payment_method" required>
            <option value="" disabled selected>Payment Method</option>
            <option value="Credit Card">Credit Card</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash">Cash</option>
            <option value="Other">Other</option>
          </select>
          <label>Payment Method</label>
        </div>
        <div class="form-group">
          <select name="enrolled_by" required>
            <option value="" disabled selected>Enrolled By</option>
            <option value="Admin">Admin</option>
            <option value="Manager">Manager</option>
            <option value="Team Lead">Team Lead</option>
            <option value="HR">HR</option>
          </select>
          <label>Enrolled By</label>
        </div>
      </div>
      <div class="form-group full-width">
        <input type="date" name="payment_date" placeholder=" " required>
        <label>Payment Date</label>
      </div>
      <div class="form-group full-width">
        <textarea name="notes" rows="3" placeholder=" "></textarea>
        <label>Notes (optional)</label>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" id="saveBtn">Add Payment</button>
        <button type="button" class="close-btn" id="closeModal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- View Modal -->
<div class="modal" id="viewModal">
  <div class="modal-content">
    <h2>Payment Details</h2>
    <p><strong>Customer Name:</strong> <span id="viewName"></span></p>
    <p><strong>Total Payment:</strong> <span id="viewAmount"></span></p>
    <p><strong>Installments:</strong> <span id="viewInstallments"></span></p>
    <p><strong>Status:</strong> <span id="viewStatus"></span></p>
    <p><strong>Date:</strong> <span id="viewDate"></span></p>
    <p><strong>Payment Method:</strong> <span id="viewPaymentMethod"></span></p>
    <p><strong>Enrolled By:</strong> <span id="viewEnrolledBy"></span></p>
    <p><strong>Notes:</strong> <span id="viewNotes"></span></p>
    <button class="close-btn" id="closeViewModal">Close</button>
  </div>
</div>

<script>
  // Dark/Light toggle
  const themeToggle = document.getElementById("themeToggle");
  const savedTheme = localStorage.getItem("darkMode") === "true";
  if(savedTheme) document.body.classList.add("dark");
  themeToggle.checked = savedTheme;
  themeToggle.addEventListener("change", () => {
      document.body.classList.toggle("dark");
      localStorage.setItem("darkMode", document.body.classList.contains("dark"));
  });

  // Logout dropdown
  const profileImg = document.getElementById('profileInitials');
  const logoutDropdown = document.getElementById('logoutDropdown');
  profileImg.addEventListener('click', e=>{
      e.stopPropagation();
      logoutDropdown.classList.toggle('show');
  });
  document.addEventListener('click', ()=>logoutDropdown.classList.remove('show'));

  // Payment Modal
  const paymentModal=document.getElementById('paymentModal');
  const openModalBtn=document.getElementById('openModal');
  const closeModalBtn=document.getElementById('closeModal');
  const viewModal=document.getElementById('viewModal');
  const closeViewModalBtn=document.getElementById('closeViewModal');
  const searchInput=document.getElementById('searchInput');

  openModalBtn.addEventListener('click',()=>paymentModal.style.display='flex');
  closeModalBtn.addEventListener('click',()=>paymentModal.style.display='none');
  closeViewModalBtn.addEventListener('click',()=>viewModal.style.display='none');

  searchInput.addEventListener('input',()=> {
    const filter=searchInput.value.toLowerCase();
    document.querySelectorAll('#paymentTable tbody tr').forEach(tr=>{
      tr.style.display=tr.cells[0].textContent.toLowerCase().includes(filter)?'':'none';
    });
  });

  document.querySelectorAll('a.view').forEach(link=>{
    link.addEventListener('click', e=>{
      e.preventDefault();
      const tr=link.closest('tr');
      document.getElementById('viewName').textContent=tr.cells[0].textContent;
      document.getElementById('viewAmount').textContent=tr.cells[1].textContent;
      document.getElementById('viewInstallments').textContent=tr.cells[2].textContent;
      document.getElementById('viewStatus').textContent=tr.cells[3].textContent;
      document.getElementById('viewDate').textContent=tr.cells[4].textContent;
      document.getElementById('viewPaymentMethod').textContent=tr.cells[5].textContent;
      document.getElementById('viewEnrolledBy').textContent=tr.cells[6].textContent;
      document.getElementById('viewNotes').textContent=tr.dataset.notes || 'None';
      viewModal.style.display='flex';
    });
  });

  window.onclick=function(e){
    if(e.target===paymentModal) paymentModal.style.display='none';
    if(e.target===viewModal) viewModal.style.display='none';
  };

  function exportCSV(){
    const rows=[];
    const headers=Array.from(document.querySelectorAll('#paymentTable th')).map(th=>th.textContent);
    rows.push(headers);
    document.querySelectorAll('#paymentTable tbody tr').forEach(tr=>{
      rows.push(Array.from(tr.cells).map(td=>td.textContent));
    });
    let csvContent="data:text/csv;charset=utf-8,"+rows.map(e=>e.join(",")).join("\n");
    const encodedUri=encodeURI(csvContent);
    const link=document.createElement("a");
    link.setAttribute("href",encodedUri);
    link.setAttribute("download","payments.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
</script>

</body>
</html>
