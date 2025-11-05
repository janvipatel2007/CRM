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
$nameParts = explode(' ', trim($username));
$initials = '';
foreach($nameParts as $part){ $initials .= strtoupper($part[0]); }
if(strlen($initials) > 2) $initials = substr($initials,0,2);

// ---------- Handle Add Task ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $task = $_POST['task'] ?? '';
    $task_date = $_POST['task_date'] ?? '';
    $user_name = $username;

    if($task){
        $stmt = $conn->prepare("INSERT INTO tasks (user_name, task, task_date) VALUES (?,?,?)");
        $stmt->bind_param("sss",$user_name,$task,$task_date);
        $stmt->execute();
        $lastId = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['status'=>'success','id'=>$lastId]);
        exit;
    }
}

// ---------- Handle Delete Task ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'] ?? '';
    if($task_id){
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id=?");
        $stmt->bind_param("i",$task_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status'=>'deleted']);
        exit;
    }
}

// ---------- Fetch all tasks ----------
$tasks_result = $conn->query("SELECT * FROM tasks WHERE user_name='$username' ORDER BY id DESC");
$tasks = [];
while($row=$tasks_result->fetch_assoc()){$tasks[]=$row;}
$tasks_json = json_encode($tasks);

// ---------- Fetch all leads ----------
$result = $conn->query("SELECT * FROM leads ORDER BY id DESC");
$leads = [];
while($row = $result->fetch_assoc()){$leads[]=$row;}
$leads_json = json_encode($leads);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
/* ====== CSS: Same as your previous code with dark mode ====== */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;transition:all 0.3s ease;}
html, body{width:100%;height:100%;overflow:hidden;}
body{background: linear-gradient(135deg,#dfe9f3,#ffffff); color:#001a40;}
body.dark{background:#0f172a;color:#e0e0e0;}
header{background: linear-gradient(135deg, #96a7cb, #1e3c72); padding:10px 25px; display:flex; justify-content:space-between; align-items:center; height:70px; position:fixed; top:0; left:0; width:100%; z-index:1000; box-shadow:0 4px 12px rgba(0,0,0,0.2);}
.header-left img.logo{ height:55px; width:auto; border-radius:12px; }
.header-right{ display:flex; align-items:center; gap:18px; }
.switch{ position:relative; display:inline-block; width:60px; height:32px; }
.switch input{ display:none; }
.slider{ position:absolute; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px; }
.slider::before{content:"☀️"; position:absolute; height:26px; width:26px; left:3px; bottom:3px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; transition:transform 0.4s,content 0.4s;}
input:checked+.slider::before{ transform:translateX(28px) rotate(360deg); content:"🌙"; }
.profile{display:flex;align-items:center;gap:10px;position:relative;cursor:pointer}
.profile span{color:#fff;font-weight:600}
.profile-img{width:38px;height:38px;border-radius:50%;background:#fff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:700}
.dropdown-menu{position:absolute;top:50px;right:0;background:#fff;color:#000;padding:8px 12px;border-radius:6px;box-shadow:0 6px 12px rgba(0,0,0,.15)}
.dropdown-menu.hidden{display:none}
.sidebar{width:230px; margin-top:0; background:#fff; border-right:1px solid #ddd; padding:20px 0; position:fixed; top:80px; bottom:0;}
.sidebar button{width:100%; margin:5px 0; text-align:left; font-weight:500; border:none; font-size:15px; background-color:transparent; color:#1a1a1a; cursor:pointer; padding:10px 25px; border-left:4px solid transparent; transition:all 0.3s ease;}
.sidebar button:hover{background:#e0e0e0; color:#000;}
.sidebar button.active{background:#d6d6d6; color:#000; font-weight:600;}
.main-content{position:absolute; left:230px; top:80px; right:0; bottom:0; padding:20px; background-color:white; overflow-y:auto;}
.cards{display:flex; justify-content:space-between; align-items:stretch; gap:20px; margin-bottom:10px;}
.card{flex:1; background:linear-gradient(135deg, #ffffff, #f1f5f9); border-radius:14px; box-shadow:0 3px 10px rgba(0,0,0,0.08); padding:20px; text-align:left; transition:transform 0.2s ease;}
.card:hover{transform:translateY(-4px);}
.card h3{ margin-bottom:12px; font-size:17px; font-weight:600; color:#1e3c72;}
.card ul{ list-style:none; padding:0; }
.card ul li{ display:flex; justify-content:space-between; cursor:pointer; margin:8px 0; font-weight:500;}
.value-box{ width:55px; text-align:center; border:2px solid #001a40; border-radius:8px; background:#f9fbfd; font-weight:600;}
.chart-card{background: linear-gradient(135deg, #ffffff, #f1f5f9); border-radius:14px; box-shadow:0 3px 10px rgba(0,0,0,0.08); padding:15px; display:flex; flex-direction:column; width:33%; height:250px; transition: transform 0.2s ease;}
.chart-card h3{margin-bottom:12px; font-size:17px; font-weight:600; color:#1e3c72;}
.chart-container{ display:flex; align-items:center; justify-content:space-between;}
.legend{flex:1; display:flex; flex-direction:column; justify-content:center; gap:14px; font-size:16px; font-weight:600; padding-left:20px;}
.legend-item{display:flex; align-items:center; gap:10px;}
.legend-color{width:16px; height:16px; border-radius:3px;}
.chart-box{flex:1; display:flex; align-items:center; justify-content:center;}
.chart-box canvas{ max-width:100%!important; max-height:220px!important;}
.charts{display:flex; gap:20px; justify-content:flex-start;}
.task-section{margin-top:10px; background: linear-gradient(145deg, #ffffff, #f1f5f9); padding:20px; border-radius:14px; box-shadow:0 3px 10px rgba(0,0,0,0.08); display:flex; flex-direction:column; width:650px; height:240px; overflow:hidden;}
.task-section h3{ font-size:18px; font-weight:600; color:#1e3c72; margin-bottom:15px;}
.task-input{ display:flex; gap:10px; margin-bottom:15px; flex-shrink:0; }
.task-input input{ flex:1; padding:10px; border:1px solid #ccc; border-radius:8px; font-size:14px;}
.task-input button{ padding:10px 15px; border:none; border-radius:8px; background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40; font-weight:600; cursor:pointer;}
.task-list{ list-style:none; padding:0; flex:1; max-height:250px; overflow-y:auto; border-top:1px solid #ddd; margin-top:10px;}
.task-list li{ background:#f9fbfd; margin:5px 0; padding:10px 12px; border-radius:8px; border:1px solid #ddd; display:flex; justify-content:space-between; align-items:center; font-size:15px; }
.delete-btn{ background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;border:none; border-radius:6px; padding:4px 8px; cursor:pointer; font-size:12px;}
body.dark .sidebar{background: linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{ background-color:transparent; color:white}  
body.dark .sidebar button.active{ background:#ababa5ff; color:#000; font-weight:600;}
body.dark .sidebar button:hover{ background:#ababa5ff; color:#000;}
body.dark .main-content{ background:#1e293b; color:#e0e0e0;}
body.dark .card, body.dark .chart-card, body.dark .task-section{ background:#273548; box-shadow:0 3px 10px rgba(0,0,0,0.3); color:#e0e0e0;}
body.dark h3{ color:#93c5fd; }
body.dark .value-box{ border-color:#e0e0e0; background:#334155; color:#e0e0e0; }
body.dark .task-list li{ background:#334155; border:1px solid #475569; color:#e0e0e0;}
body.dark .task-input input{ background:#1e293b; border:1px solid #475569; color:#e0e0e0;}
body.dark .task-input button{ background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}
body.dark .delete-btn{background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}
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
  <button class="active" onclick="location.href='index.php'"> Dashboard</button>
  <button onclick="location.href='all_leads.php'"> All Leads</button>
  <button onclick="location.href='Candidate.php'"> Candidate</button>
  <button onclick="location.href='daily_report.php'"> Daily Report</button>
  <button onclick="location.href='target.php'"> Targets</button><br><br><br><br>

  <?php if($userRole === 'admin'): ?>
    <button onclick="location.href='employees.php'">Employees</button>
    <button onclick="location.href='payment.php'">Payment</button>
    <button onclick="location.href='setting.php'">⚙ Settings</button>
  <?php endif; ?>
</div>

<div class="main-content">
  <div class="cards">
    <div class="card">
      <h3>LEADS</h3>
      <ul>
        <li data-filter="hot">Hot Leads <div class="value-box" id="hotCount"></div></li>
        <li data-filter="cold">Cold Leads <div class="value-box" id="coldCount"></div></li>
        <li data-filter="not interested">N.I <div class="value-box" id="niCount"></div></li>
        <li data-filter="dnr">DNR <div class="value-box" id="dnrCount"></div></li>
      </ul>
    </div>
    <div class="card">
      <h3>SALES</h3>
      <ul>
        <li data-filter="qualified">Qualified <div class="value-box" id="qualifiedCount"></div></li>
        <li data-filter="connected">Connected <div class="value-box" id="connectedCount"></div></li>
        <li data-filter="hot prospect">Hot Prospect <div class="value-box" id="hotProspectCount"></div></li>
      </ul>
    </div>
    <div class="chart-card">
      <h3>LEAD STATUS</h3>
      <div class="chart-container">
        <div class="legend">
          <div class="legend-item"><div class="legend-color" style="background:#ef4444"></div> Hot</div>
          <div class="legend-item"><div class="legend-color" style="background:#3b82f6"></div> Cold</div>
          <div class="legend-item"><div class="legend-color" style="background:#f59e0b"></div> N.I</div>
          <div class="legend-item"><div class="legend-color" style="background:#6b7280"></div> DNR</div>
        </div>
        <div class="chart-box"><canvas id="leadStatusChart"></canvas></div>
      </div>
    </div>
  </div>

  <div class="charts">
    <div class="chart-card">
      <h3>SALES STATUS</h3>
      <div class="chart-container">
        <div class="legend">
          <div class="legend-item"><div class="legend-color" style="background:#22c55e"></div> Qualified</div>
          <div class="legend-item"><div class="legend-color" style="background:#3b82f6"></div> Connected</div>
          <div class="legend-item"><div class="legend-color" style="background:#eab308"></div> Hot Prospect</div>
        </div>
        <div class="chart-box"><canvas id="salesChart"></canvas></div>
      </div>
    </div>

    <div class="task-section">
      <h3>🗒 TASKS</h3>
      <div class="task-input" style="position: relative; display:flex; align-items:center;">
        <input type="text" id="taskInput" placeholder="Enter your new task..." style="flex:1; padding-right: 100px;">
        <input type="date" id="taskDatePicker" style="position:absolute; right:140px; top:50%; transform:translateY(-50%); opacity:0; width:24px; cursor:pointer;">
        <div id="calendarIcon" style="position: absolute; right:140px; top: 50%; transform: translateY(-50%); display:flex; gap:6px; color:#4b5563; font-size:20px; cursor:pointer;">
          <i class="ri-calendar-2-line"></i>
        </div>
        <button id="addTaskBtn" style="margin-left:10px;">Add Task</button>
      </div>
      <ul class="task-list" id="taskList"></ul>
    </div>
  </div>
</div>

<script>
const themeToggle = document.getElementById("themeToggle");
if(localStorage.getItem("darkMode") === "true"){ document.body.classList.add("dark"); themeToggle.checked = true; }
themeToggle.addEventListener("change", () => { document.body.classList.toggle("dark"); localStorage.setItem("darkMode", document.body.classList.contains("dark")); });

const profileMenu=document.getElementById('profileMenu');
const logoutDropdown=document.getElementById('logoutDropdown');
profileMenu.addEventListener('click',e=>{e.stopPropagation();logoutDropdown.classList.toggle('hidden');});
document.addEventListener('click',e=>{if(!profileMenu.contains(e.target))logoutDropdown.classList.add('hidden');});

let leads = <?= $leads_json ?>;
let tasks = <?= $tasks_json ?>;

function renderLeads(){ 
  document.getElementById("hotCount").textContent = leads.filter(l => l.leadType && l.leadType.toLowerCase() === "hot").length;
  document.getElementById("coldCount").textContent = leads.filter(l => l.leadType && l.leadType.toLowerCase() === "cold").length;
  document.getElementById("niCount").textContent = leads.filter(l => l.status && (l.status.toLowerCase() === "not interested" || l.status.toLowerCase() === "n.i")).length;
  document.getElementById("dnrCount").textContent = leads.filter(l => l.status && l.status.toLowerCase().startsWith("dnr")).length;
  document.getElementById("qualifiedCount").textContent = leads.filter(l => l.status && l.status.toLowerCase() === "qualified").length;
  document.getElementById("connectedCount").textContent = leads.filter(l => l.status && l.status.toLowerCase() === "connected").length;
  document.getElementById("hotProspectCount").textContent = leads.filter(l => l.status && l.status.toLowerCase() === "hot prospect").length;
}

function loadCharts(){
  new Chart(document.getElementById("leadStatusChart"),{
    type:'doughnut',
    data:{
      labels:['Hot','Cold','N.I','DNR'],
      datasets:[{
        data:[
          leads.filter(l => l.leadType && l.leadType.toLowerCase() === "hot").length,
          leads.filter(l => l.leadType && l.leadType.toLowerCase() === "cold").length,
          leads.filter(l => l.status && (l.status.toLowerCase() === "not interested" || l.status.toLowerCase() === "n.i")).length,
          leads.filter(l => l.status && l.status.toLowerCase().startsWith("dnr")).length
        ],
        backgroundColor:['#ef4444','#3b82f6','#f59e0b','#9499a2ff']
      }]
    },
    options:{plugins:{legend:{display:false}}}
  });

  new Chart(document.getElementById("salesChart"),{
    type:'doughnut',
    data:{
      labels:['Qualified','Connected','Hot Prospect'],
      datasets:[{
        data:[
          leads.filter(l => l.status && l.status.toLowerCase() === "qualified").length,
          leads.filter(l => l.status && l.status.toLowerCase() === "connected").length,
          leads.filter(l => l.status && l.status.toLowerCase() === "hot prospect").length
        ],
        backgroundColor:['#22c55e','#3b82f6','#eab308']
      }]
    },
    options:{plugins:{legend:{display:false}}}
  });
}

// TASKS
const taskInput = document.getElementById("taskInput");
const taskDatePicker = document.getElementById("taskDatePicker");
const calendarIcon = document.getElementById("calendarIcon");
const taskList = document.getElementById("taskList");
const addTaskBtn = document.getElementById("addTaskBtn");
calendarIcon.addEventListener("click",()=>taskDatePicker.showPicker());

function renderTasks(){
  taskList.innerHTML='';
  tasks.forEach(t=>{
    const li = document.createElement('li');
    li.innerHTML = `<span>${t.task} - ${t.task_date}</span> <button class='delete-btn' data-id='${t.id}'>Delete</button>`;
    li.querySelector('.delete-btn').onclick = ()=>deleteTask(t.id);
    taskList.appendChild(li);
  });
}

function addTask(){
  const taskVal = taskInput.value.trim();
  const taskDate = taskDatePicker.value;
  if(!taskVal) return;
  const formData = new FormData();
  formData.append('add_task', true);
  formData.append('task', taskVal);
  formData.append('task_date', taskDate);

  fetch('',{method:'POST',body:formData}).then(res=>res.json()).then(res=>{
    if(res.status==='success'){
      tasks.unshift({id:res.id, task:taskVal, task_date:taskDate});
      renderTasks(); taskInput.value=''; taskDatePicker.value='';
    }
  });
}

function deleteTask(id){
  const formData = new FormData();
  formData.append('delete_task', true);
  formData.append('task_id', id);
  fetch('',{method:'POST',body:formData}).then(res=>res.json()).then(res=>{
    if(res.status==='deleted'){
      tasks = tasks.filter(t=>t.id!=id);
      renderTasks();
    }
  });
}

addTaskBtn.onclick = addTask;
renderLeads();
loadCharts();
renderTasks();
</script>
</body>
</html>