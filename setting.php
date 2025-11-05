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
if($conn->connect_error){ die("Connection failed: ".$conn->connect_error); }

// ---------- Ensure user is logged in ----------
$userName = $_SESSION['user_name'] ?? 'Guest';
$userRole = $_SESSION['user_role'] ?? "user";
// ---- Admin guard ----
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
$me = $_SESSION['user_name'] ?? 'Admin';

// ---- Ensure uploads dir ----
if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0755, true);

// ---- Helpers ----
function flash_set($type, $text) {
    $_SESSION['flash'] = ['type'=>$type,'text'=>$text];
}
function flash_get() {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
function log_action($conn, $user, $action, $details = '') {
    $stmt = $conn->prepare("INSERT INTO logs (`user`, `action`, `details`) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $user, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

// Create tables if missing (safe defaults)
$conn->query("CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role VARCHAR(100) DEFAULT 'Staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL,
    permissions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    category VARCHAR(100) DEFAULT '',
    target_value DECIMAL(14,2) NOT NULL,
    achieved_value DECIMAL(14,2) DEFAULT 0,
    month VARCHAR(20),
    year INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user VARCHAR(100),
    action VARCHAR(100),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// If older targets table exists without the new columns, add them.
$cols = [];
$res = $conn->query("SHOW COLUMNS FROM targets");
if ($res) {
    while ($c = $res->fetch_assoc()) $cols[] = $c['Field'];
}
if (!in_array('category', $cols)) {
    $conn->query("ALTER TABLE targets ADD COLUMN category VARCHAR(100) DEFAULT ''");
}
if (!in_array('achieved_value', $cols)) {
    $conn->query("ALTER TABLE targets ADD COLUMN achieved_value DECIMAL(14,2) DEFAULT 0");
}
if (!in_array('updated_at', $cols)) {
    $conn->query("ALTER TABLE targets ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL");
}

// ---- POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save Employee (create/update)
    if (($_POST['action'] ?? '') === 'save_employee') {
        $emp_id = intval($_POST['emp_id'] ?? 0);
        $name = trim($_POST['emp_name'] ?? '');
        $email = trim($_POST['emp_email'] ?? '');
        $role = trim($_POST['emp_role'] ?? 'Staff');

        if ($name === '' || $email === '') {
            flash_set('error','Name and Email are required.');
            header("Location: setting.php");
            exit;
        }

        if ($emp_id === 0) {
            // check duplicate email
            $stmt = $conn->prepare("SELECT id FROM employees WHERE email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->close();
                    flash_set('error','Email already exists.');
                    header("Location: setting.php");
                    exit;
                }
                $stmt->close();
            }

            $stmt = $conn->prepare("INSERT INTO employees (name, email, role) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $name, $email, $role);
                $stmt->execute();
                $newid = $stmt->insert_id;
                $stmt->close();
                flash_set('success','Employee created.');
                log_action($conn, $me, 'create_employee', "Created employee #{$newid} ({$name})");
            } else {
                flash_set('error','DB error creating employee.');
            }
        } else {
            $stmt = $conn->prepare("UPDATE employees SET name = ?, email = ?, role = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssi", $name, $email, $role, $emp_id);
                $stmt->execute();
                $stmt->close();
                flash_set('success','Employee updated.');
                log_action($conn, $me, 'update_employee', "Updated employee #{$emp_id}");
            } else {
                flash_set('error','DB error updating employee.');
            }
        }
        header("Location: setting.php");
        exit;
    }

    // Save Role
    if (($_POST['action'] ?? '') === 'save_role') {
        $role_id = intval($_POST['role_id'] ?? 0);
        $role_name = trim($_POST['role_name'] ?? '');
        $permissions = trim($_POST['permissions'] ?? '');

        if ($role_name === '') {
            flash_set('error','Role name is required.');
            header("Location: setting.php");
            exit;
        }

        if ($role_id === 0) {
            $stmt = $conn->prepare("INSERT INTO roles (role_name, permissions) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $role_name, $permissions);
                $stmt->execute();
                $rid = $stmt->insert_id;
                $stmt->close();
                flash_set('success','Role added.');
                log_action($conn, $me, 'create_role', "Created role #{$rid} ({$role_name})");
            } else {
                flash_set('error','DB error adding role.');
            }
        } else {
            $stmt = $conn->prepare("UPDATE roles SET role_name = ?, permissions = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $role_name, $permissions, $role_id);
                $stmt->execute();
                $stmt->close();
                flash_set('success','Role updated.');
                log_action($conn, $me, 'update_role', "Updated role #{$role_id}");
            } else {
                flash_set('error','DB error updating role.');
            }
        }
        header("Location: setting.php");
        exit;
    }

    // Save Target (assign)
    if (($_POST['action'] ?? '') === 'save_target') {
        $employee_id = intval($_POST['employee_id'] ?? 0);
        $amount = floatval($_POST['target_value'] ?? 0);
        $month = trim($_POST['month'] ?? '');
        $year = intval($_POST['year'] ?? date('Y'));
        $category = trim($_POST['category'] ?? '');
        $achieved_value = floatval($_POST['achieved_value'] ?? 0);

        if (!$employee_id || $amount <= 0 || $month === '') {
            flash_set('error','Please complete all target fields correctly.');
            header("Location: setting.php");
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO targets (employee_id, category, target_value, achieved_value, month, year, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if ($stmt) {
            $stmt->bind_param("isdiss", $employee_id, $category, $amount, $achieved_value, $month, $year);
            $stmt->execute();
            $tid = $stmt->insert_id;
            $stmt->close();
            flash_set('success','Target assigned.');
            log_action($conn, $me, 'create_target', "Assigned target #{$tid} to employee #{$employee_id} amount {$amount} ({$month} {$year}) category: {$category}");
        } else {
            flash_set('error','DB error assigning target.');
        }
        header("Location: setting.php");
        exit;
    }

    // Edit Target inline (modal)
    if (($_POST['action'] ?? '') === 'edit_target') {
        $tid = intval($_POST['tid'] ?? 0);
        $t_amount = floatval($_POST['t_amount'] ?? 0);
        $t_category = trim($_POST['t_category'] ?? '');
        $t_achieved = floatval($_POST['t_achieved'] ?? 0);
        if (!$tid) { flash_set('error','Invalid target.'); header("Location: setting.php"); exit; }
        $stmt = $conn->prepare("UPDATE targets SET target_value = ?, category = ?, achieved_value = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("dsdi", $t_amount, $t_category, $t_achieved, $tid);
            $stmt->execute();
            $stmt->close();
            flash_set('success','Target updated.');
            log_action($conn, $me, 'update_target', "Updated target #{$tid} to {$t_amount}, category: {$t_category}, achieved: {$t_achieved}");
        } else {
            flash_set('error','DB error updating target.');
        }
        header("Location: setting.php");
        exit;
    }

    // Add Manual Log
    if (($_POST['action'] ?? '') === 'add_manual_log') {
        $msg = trim($_POST['manual_log_msg'] ?? '');
        if ($msg === '') {
            flash_set('error','Enter log message.');
            header("Location: setting.php?show_logs=1");
            exit;
        }
        log_action($conn, $me, 'manual_log', $msg);
        flash_set('success','Manual log added.');
        header("Location: setting.php?show_logs=1");
        exit;
    }
}

// ---- GET deletes and logs operations ----
if (isset($_GET['delete_employee'])) {
    $id = intval($_GET['delete_employee']);
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        flash_set('success','Employee deleted.');
        log_action($conn, $me, 'delete_employee', "Deleted employee #{$id}");
    } else {
        flash_set('error','DB error deleting employee.');
    }
    header("Location: setting.php");
    exit;
}

if (isset($_GET['delete_target'])) {
    $id = intval($_GET['delete_target']);
    $stmt = $conn->prepare("DELETE FROM targets WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        flash_set('success','Target deleted.');
        log_action($conn, $me, 'delete_target', "Deleted target #{$id}");
    } else {
        flash_set('error','DB error deleting target.');
    }
    header("Location: setting.php");
    exit;
}

if (isset($_GET['delete_role'])) {
    $id = intval($_GET['delete_role']);
    $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        flash_set('success','Role deleted.');
        log_action($conn, $me, 'delete_role', "Deleted role #{$id}");
    } else {
        flash_set('error','DB error deleting role.');
    }
    header("Location: setting.php");
    exit;
}

// Delete single log
if (isset($_GET['delete_log'])) {
    $id = intval($_GET['delete_log']);
    $stmt = $conn->prepare("DELETE FROM logs WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        flash_set('success','Log entry deleted.');
        log_action($conn, $me, 'delete_log', "Deleted log #{$id}");
    } else {
        flash_set('error','DB error deleting log.');
    }
    header("Location: setting.php?show_logs=1");
    exit;
}

// Clear all logs
if (isset($_GET['clear_logs']) && $_GET['clear_logs'] == '1') {
    $conn->query("TRUNCATE TABLE logs");
    flash_set('success','All logs cleared.');
    log_action($conn, $me, 'clear_logs', "Cleared all logs");
    header("Location: setting.php?show_logs=1");
    exit;
}

// ---------- Load data for UI ----------
$employees_res = $conn->query("SELECT id, name, email, role FROM employees ORDER BY name");
if (!$employees_res) $employees_res = false;

$targets_res = $conn->query("SELECT t.*, e.name AS emp_name FROM targets t LEFT JOIN employees e ON t.employee_id = e.id ORDER BY t.id DESC");
if (!$targets_res) $targets_res = false;

$roles_res = $conn->query("SELECT * FROM roles ORDER BY id ASC");
if (!$roles_res) $roles_res = false;

$logs_res = null;
$show_logs = isset($_GET['show_logs']) || isset($_GET['view_logs']) || (isset($_GET['show_logs']) && $_GET['show_logs']=='1');
if ($show_logs) {
    $logs_res = $conn->query("SELECT * FROM logs ORDER BY id DESC LIMIT 500");
    if (!$logs_res) $logs_res = false;
}

$flash = flash_get();

// ---- Logo path (optional) ----
$logoPath = 'nova.png';
if (file_exists(__DIR__.'/uploads/.settings_logo')) {
    $lp = trim(file_get_contents(__DIR__.'/uploads/.settings_logo'));
    if ($lp) $logoPath = $lp;
}

// ----- Export CSV endpoint (simple) -----
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="settings_export.csv"');
    $out = fopen('php://output', 'w');
    // Employees
    fputcsv($out, ['Employees']);
    fputcsv($out, ['id','name','email','role','created_at']);
    $res = $conn->query("SELECT id,name,email,role,created_at FROM employees");
    if ($res) {
        while ($r = $res->fetch_assoc()) fputcsv($out, [$r['id'],$r['name'],$r['email'],$r['role'],$r['created_at']]);
    }
    fputcsv($out, []);
    // Targets
    fputcsv($out, ['Targets']);
    fputcsv($out, ['id','employee_id','employee_name','category','target_value','achieved_value','month','year','created_at','updated_at']);
    $tres = $conn->query("SELECT t.*, e.name AS emp_name FROM targets t LEFT JOIN employees e ON t.employee_id = e.id");
    if ($tres) {
        while ($r = $tres->fetch_assoc()) fputcsv($out, [$r['id'],$r['employee_id'],$r['emp_name'],$r['category'],$r['target_value'],$r['achieved_value'],$r['month'],$r['year'],$r['created_at'],$r['updated_at']]);
    }
    fputcsv($out, []);
    // Roles
    fputcsv($out, ['Roles']);
    fputcsv($out, ['id','role_name','permissions','created_at']);
    $rres = $conn->query("SELECT id,role_name,permissions,created_at FROM roles");
    if ($rres) {
        while ($r = $rres->fetch_assoc()) fputcsv($out, [$r['id'],$r['role_name'],$r['permissions'],$r['created_at']]);
    }
    fclose($out);
    $conn->close();
    exit;
}

// ---------- Fetch existing data (for the three cards) ----------
$leadStatus = $conn->query("SELECT * FROM lead_status ORDER BY `name` ASC");
$leadType   = $conn->query("SELECT * FROM lead_type ORDER BY `name` ASC");
$leadVisa   = $conn->query("SELECT * FROM lead_visa ORDER BY `name` ASC");
$teamMembers = $conn->query("SELECT * FROM team_members ORDER BY name ASC");

// ---------- Add new Lead Status ----------
if(isset($_POST['add_status'])){
    $name = trim($_POST['status_name']);
    if($name != ''){
        $conn->query("INSERT INTO lead_status (name) VALUES ('".$conn->real_escape_string($name)."')");
        header("Location: setting.php");
        exit;
    }
}

// ---------- Add new Lead Type ----------
if(isset($_POST['add_type'])){
    $name = trim($_POST['type_name']);
    if($name != ''){
        $conn->query("INSERT INTO lead_type (name) VALUES ('".$conn->real_escape_string($name)."')");
        header("Location: setting.php");
        exit;
    }
}

// ---------- Add new Lead Visa ----------
if(isset($_POST['add_visa'])){
    $name = trim($_POST['visa_name']);
    if($name != ''){
        $conn->query("INSERT INTO lead_visa (name) VALUES ('".$conn->real_escape_string($name)."')");
        header("Location: setting.php");
        exit;
    }
}
// ----------- add new lead name---------
if (isset($_POST['add_member'])) {
    $n = trim($_POST['member_name']);
    if ($n != '') {
        $conn->query("INSERT INTO team_members (name) VALUES ('$n')");
        header("Location: try_setting.php");
        exit;
    }
}


// ---------- Delete actions ----------
if(isset($_GET['del_status'])){
    $id = intval($_GET['del_status']);
    $conn->query("DELETE FROM lead_status WHERE id=$id");
    header("Location: setting.php");
    exit;
}

if(isset($_GET['del_type'])){
    $id = intval($_GET['del_type']);
    $conn->query("DELETE FROM lead_type WHERE id=$id");
    header("Location: setting.php");
    exit;
}

if(isset($_GET['del_visa'])){
    $id = intval($_GET['del_visa']);
    $conn->query("DELETE FROM lead_visa WHERE id=$id");
    header("Location: setting.php");
    exit;
}

if (isset($_GET['del_member'])) {
    $id = intval($_GET['del_member']);
    $conn->query("DELETE FROM team_members WHERE id=$id");
    header("Location: try_setting.php");
    exit;
}





// ---------- DELETE Target ----------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM month_target WHERE id = $id");
    header("Location: setting.php");
    exit;
}

// ---------- EDIT (Load for Update) ----------
$editData = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM month_target WHERE id = $id");
    $editData = $result->fetch_assoc();
}

// ---------- INSERT / UPDATE ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $employee = $_POST['employee'];
    $category = $_POST['category'];
    $month = $_POST['month'];
    $year = $_POST['year'];

    $closure_sales = $_POST['closure_sales'] ?? 0;
    $closure_lead  = $_POST['closure_lead'] ?? 0;
    $revenue       = $_POST['revenue'] ?? 0;
    $cold_leads    = $_POST['cold_leads'] ?? 0;
    $hot_leads     = $_POST['hot_leads'] ?? 0;
    $applications  = $_POST['applications'] ?? 0;
    $interviews    = $_POST['interviews'] ?? 0;
    $placements    = $_POST['placements'] ?? 0;

    if (!empty($_POST['update_id'])) {
        $id = intval($_POST['update_id']);
        $stmt = $conn->prepare("UPDATE month_target SET employee=?, category=?, month=?, year=?, closure_sales=?, closure_lead=?, revenue=?, cold_leads=?, hot_leads=?, applications=?, interviews=?, placements=? WHERE id=?");
        $stmt->bind_param("ssssddddddddd", $employee, $category, $month, $year, $closure_sales, $closure_lead, $revenue, $cold_leads, $hot_leads, $applications, $interviews, $placements, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO month_target (employee, category, month, year, closure_sales, closure_lead, revenue, cold_leads, hot_leads, applications, interviews, placements, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssdddddddd", $employee, $category, $month, $year, $closure_sales, $closure_lead, $revenue, $cold_leads, $hot_leads, $applications, $interviews, $placements);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: setting.php");
    exit;
}

$result = $conn->query("SELECT * FROM month_target ORDER BY id DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Settings — CRM</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{
      --accent1: #96a7cb;
      --accent2: #1e3c72;
      --muted: #748094;
      --card-bg: rgba(255,255,255,0.95);
      --glass: rgba(255,255,255,0.55);
      --radius: 12px;
      --shadow: 0 8px 28px rgba(50,70,90,0.06);
    }
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
.sidebar button.active{ background:#d6d6d6; color:#000; font-weight:600; border-radius:0px;}

    .main{
      margin-left:260px;
      margin-top:100px;
      padding:28px;
      min-height:calc(100vh - 120px);
      padding-bottom:80px;
    }


    .card{
      background: var(--card-bg);
      border-radius: var(--radius);
      padding:16px;
      box-shadow: var(--shadow);
      border:1px solid rgba(30,60,114,0.04);
      margin-bottom:18px;
    }
    .card .title{ font-weight:700; color:#17324d; margin-bottom:8px }

    .input { padding:8px 10px; border-radius:8px; border:1px solid #e6eef7; }

    .btn{background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40; padding:8px 12px; border-radius:10px; border:0; cursor:pointer; font-weight:700 }
    .btn.ghost{ background:transparent; border:1px solid rgba(30,60,114,0.08); color:var(--accent2) }
    .btn.warn{ background:#ff9f43 }
    .btn.danger{background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;  }
    .btn.gray{ background:#6b7280 }
    
    .table-wrap{ overflow:auto; border-radius:10px; border:1px solid rgba(30,60,114,0.04) }
    table{ width:100%; border-collapse:collapse; min-width:600px; }
    thead tr{ background:linear-gradient(90deg, rgba(255,255,255,0.7), rgba(255,255,255,0.5)); }
    th, td{ padding:12px 10px; text-align:left; border-bottom:1px solid rgba(30,60,114,0.04); color: #123047; font-size:14px; width:56px }
    tbody tr:hover{ background: rgba(30,60,114,0.03); }
    tr.no-data td{ text-align:center; color:var(--muted) }
    .table-mb-0{background-color: #123047 ;}

    .action-btn{ padding:6px 10px; border-radius:8px; border:0; cursor:pointer; font-weight:700 }
    .action-edit{ background:#ff9f43; color:#fff }
    .action-delete{ background:#ef5350; color:#fff }

    .modal{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; background: rgba(8,12,20,0.45); z-index:9999 }
    .modal .box{ width:520px; background: #fff; border-radius:12px; padding:18px; box-shadow: 0 18px 60px rgba(10,30,70,0.45) }
    .modal input.input, .modal select.input{ width:100%; padding:10px; border-radius:8px; border:1px solid #e6eef7; margin-bottom:10px }

    /* targets styling adjustments (light theme) */
    .targets-summary{ display:flex; gap:12px; align-items:center; margin-top:12px; flex-wrap:wrap }
    .targets-summary .stat{ background:rgba(14,43,74,0.04); padding:8px 12px; border-radius:10px; font-weight:700; color:#17324d }

    @media (max-width:980px){
      .sidebar{ display:none }
      .main{ margin-left:20px; padding:16px }
      header{ padding:10px 14px }
      .modal .box{ width:94% }
      .target-form-row select, .target-form-row input { width:100% !important; }
    }

    .small { font-size:13px; color:var(--muted) }

    .main {
      margin-left:230px; padding:20px;
    }

    .header {
      display:flex; align-items:center; justify-content:space-between;
      background:#fff; padding:12px 20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05);
    }

    .user-info {
      display:flex; align-items:center; gap:10px;
    }

    .badge-item {
      display:inline-flex; align-items:center; gap:6px;
      background:#e0ebff; color:#1e3a8a; padding:6px 12px;
      border-radius:20px; margin:4px; font-weight:500;
    }
.badge-item a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;

  border-radius: 50%;
  color: #1e3a8a;
  font-size: 15px;
  line-height: 1;
  text-decoration: none;
  margin-left: 4px;
  transition: 0.2s;
}
.badge-item a:hover {
  color: #6d8de4ff;
  transform: scale(1.1);
}


    .card {
      border:none; border-radius:14px;
      box-shadow:0 2px 8px rgba(0,0,0,0.05);
      
    }
.text-muted {
  text-align: center;
  color: #64748b !important;
  font-size: 0.95rem;
}

    .section-title {
      font-weight:600; font-size:1.1rem; color:#1e293b;
    }

    .btn-new {
      border:none; background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
      border-radius:10px; padding:8px 16px;
    }
    
    input[type=text] {
      border-radius:10px; border:1px solid #ccc; padding:8px 12px; width:100%;
    }


.target{
   background: var(--card-bg);
      border-radius: var(--radius);
      padding:16px;
      box-shadow: var(--shadow);
      border:1px solid rgba(30,60,114,0.04);
      margin-bottom:18px;
}

        .container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 20px;
    }
    h2 {
      text-align: center;
      color: #1e3a8a;
      font-size: 26px;
      margin-bottom: 20px;
    }
    form {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.1);
      margin-bottom: 40px;
    }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 25px;
    }
    label {
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 4px;
      display: block;
    }
    input, select {
      width: 100%;
      padding: 8px 10px;
      border-radius: 6px;
      border: 1px solid #cbd5e0;
      background: #f9fafb;
      color: #333;
      font-size: 13px;
      box-sizing: border-box;
    }
    .field-row {
      display: flex;
      justify-content: space-between;
      gap: 15px;
      margin-top: 15px;
    }
    .field-row div {
      flex: 1;
    }
    button {
     background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
      border: none;
      padding: 10px 22px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
    }
   
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 10px 12px;
      border-bottom: 1px solid #e5e7eb;
      text-align: center;
    }
    th {
 background-color: #cfdbe7; color: #1e3c72; 

      font-weight: 600;
    }
    tr:nth-child(even) { background: #f1f5f9; }

    .action-btn {
      padding: 6px 12px;
      border-radius: 6px;
      color: white;
      border: none;
      cursor: pointer;
      font-weight: 500;
      text-decoration: none;
    }
    .edit-btn { background: #16a34a; }
    .delete-btn { background: #dc2626; margin-left: 5px; }
    .edit-btn:hover { background: #15803d; }
    .delete-btn:hover { background: #b91c1c; }

    
/* 3-dot menu styling */
.action-menu {
  position: relative;
  display: inline-block;
}
.menu-toggle {
  background: transparent;
  border: none;
  font-size: 20px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  transition: background 0.2s ease;
}
.menu-toggle:hover {
  background: rgba(0,0,0,0.05);
}
.menu-dropdown {
  display: none;
  position: absolute;
  right: 0;
  top: 120%;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 99;
  min-width: 130px;
  overflow: hidden;
}
.menu-dropdown.show {
  display: block;
}
.menu-dropdown button,
.menu-dropdown a {
  display: block;
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  color: #333;
  padding: 8px 12px;
  text-decoration: none;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.2s ease;
}
.menu-dropdown button:hover,
.menu-dropdown a:hover {
  background: #f2f2f2;
}


/* --- Dark Mode --- */
body.dark .menu-toggle { color: #e5e7eb; }
body.dark .menu-dropdown {
  background: #1f2937;
  border-color: #374151;
}
body.dark .menu-dropdown button,
body.dark .menu-dropdown a { color: #e5e7eb; }
body.dark .menu-dropdown button:hover,
body.dark .menu-dropdown a:hover { background: #374151; }

 .btn.ghost{background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}
body.dark h1{color:#93c5fd; ;}

body.dark .sidebar{background: linear-gradient(145deg,#1f2937,#111827);border-color:white;}
body.dark .sidebar button{ background-color:transparent; color:white}  
body.dark .sidebar button.active{ background:#ababa5ff; color:#000; font-weight:600;}
body.dark .sidebar button:hover{ background:#ababa5ff; color:#000;}
/* 🌙 Dark Mode Box Design */
body.dark .card,
body.dark .target {
  background: linear-gradient(145deg, #1b2433, #24344a);
  border: 1px solid #2b3a50;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.45);
  color: #f1f5f9;
  transition: all 0.3s ease;
}

body.dark .card:hover,
body.dark .target:hover {
  background: linear-gradient(145deg, #202b3f, #2d405a);
  box-shadow: 0 6px 16px rgba(0,0,0,0.6);
}

body.dark h3,
body.dark .section-title {
  color: #93c5fd; /* light blue heading */
  letter-spacing: 0.5px;
}

/* Counter style */
body.dark .metric-value {
  background: rgba(0,0,0,0.2);
  color: #fff;
  border: 2px solid #3b4d63;
  border-radius: 10px;
  padding: 4px 12px;
  font-weight: 600;
  transition: all 0.3s ease;
}
body.dark .metric-value:hover {
  background: rgba(59, 130, 246, 0.25);
  border-color: #60a5fa;
}
/* === Dark Mode Input / Select Box Styling === */
body.dark input[type="text"],
body.dark input[type="email"],
body.dark input[type="number"],
body.dark input[type="password"],
body.dark select,
body.dark textarea {
  background: #111c2e;  /* inner dark navy tone */
  color: #e5e7eb;       /* light text */
  border: 1px solid #2a3a4f;
  border-radius: 8px;
  padding: 10px 14px;
  transition: all 0.3s ease;
}

/* Focus effect (blue glow when active) */
body.dark input:focus,
body.dark select:focus,
body.dark textarea:focus {
  background: #15243a;
  border-color: #3b82f6;
  box-shadow: 0 0 6px rgba(59, 130, 246, 0.4);
  outline: none;
}

/* Placeholder color */
body.dark ::placeholder {
  color: #9ca3af;
  opacity: 0.8;
}

/* Dropdown (select) arrow color fix */
body.dark select {
  appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='white' height='16' width='16' xmlns='http://www.w3.org/2000/svg'><polygon points='0,0 16,0 8,8'/></svg>");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 10px;
  padding-right: 30px;
}
body.dark .d-flex.mt-3 {
  background: #15243a;
}

/* 🌙 Dark Mode - Target Section Table & Form */
body.dark .target table {
  background: #1b2537;
  border: 1px solid #2b3a50;
  color: #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.45);
}

body.dark .target th {
  background: #24344a;
color: #e5e7eb;
  border-bottom: 1px solid #2f3f56;
}

body.dark .target td{color: #e5e7eb;}
body.dark .target tr:nth-child(odd){ background: #1e293b; color: #e5e7eb; }
body.dark .target tr:nth-child(even){ background: #273548; color: #e5e7eb;}
body.dark .target tr:hover td {
  background: #24344a;
}

body.dark .dots-btn{color: #e5e7eb;}
body.dark .target form {
  background: #24344a;
  border: 1px solid #2a3a4f;
  color: #e5e7eb;
}


body.dark .target h2 {
  color: #93c5fd;
}

/* Dropdown menu for actions (⋮) */
body.dark .dropdown-content {
  background: #1e293b;
  border: 1px solid #2b3a50;
}

body.dark .dropdown-content a {
  color: #e5e7eb;
}

body.dark .dropdown-content a:hover {
  background: #24344a;
  color: #93c5fd;
}

/* 🌙 Dark mode version */
body.dark .card .title {
  color: #93c5fd; /* Soft blue for dark mode */
}


/* 🌙 Dark mode */
body.dark .input {
  background: #1e293b;       /* Deep navy background */
  border: 1px solid #334155; /* Softer dark border */
  color: #e2e8f0;           
}

/* Optional: input focus state for dark mode */
body.dark .input:focus {
  outline: none;
  border-color: #3b82f6;     
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}
/* ---------- Employees Section ---------- */

/* Card wrapper */
.card:has(.title:contains("Employees")) {
  margin-top: 60px;
}
/* Header layout */
.d-flex {
  display: flex;
}

.justify-content-between {
  justify-content: space-between;
}

.align-items-center {
  align-items: center;
}

.mb-3 {
  margin-bottom: 1rem;
}

/* Title + subtitle */
.title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #0f172a;
}

.muted {
  color: #64748b;
  font-size: 14px;
}

/* Right side controls */
.d-flex > div[style], 
.d-flex[style] {
  display: flex;
  gap: 10px;
  align-items: center;
}

/* Search box */
.input {
  min-width: 260px;
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  transition: border 0.2s ease-in-out;
}

.input:focus {
  border-color: #3b82f6;
}

/* Button (Open Employees) */
.btn.ghost {
  background: #e0f2fe;
  color: #0c4a6e;
  font-weight: 600;
  border: none;
  border-radius: 10px;
  padding: 10px 15px;
  cursor: pointer;
  transition: background 0.2s ease-in-out;
}

.btn.ghost:hover {
  background: #bae6fd;
}

/* Table styling */
.table-wrap {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.table th {
  background: #f8fafc;
  font-weight: 600;
  color: #334155;
}

.table td {
  background: #fff;
  color: #1e293b;
}

.no-data td {
  text-align: center;
  color: #64748b;
  padding: 16px;
}

/* Action buttons */
.action-btn {
  border: none;
  border-radius: 6px;
  padding: 6px 12px;
  font-size: 14px;
  cursor: pointer;
  transition: 0.2s;
  color: #fff;
}

.action-edit {
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
}

.action-edit:hover {
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
}

.action-delete {
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
  text-decoration: none;
  display: inline-block;
}

.action-delete:hover {
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
}


/* =======================
   Dark Mode Styling
======================= */
body.dark {
  background-color: #0f172a;
  color: #e2e8f0;
}

/* Card wrapper */
body.dark .card:has(.title:contains("Employees")) {
  background: #1e293b;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

/* Title + subtitle */
body.dark .title {
  color: #f8fafc;
}

body.dark .muted {
  color: #94a3b8;
}

/* Search box */
body.dark .input {
  background: #1e293b;
  color: #f1f5f9;
  border: 1px solid #334155;
}

body.dark .input::placeholder {
  color: #94a3b8;
}

body.dark .input:focus {
  border-color: #60a5fa;
}

/* Button (Open Employees) */
body.dark .btn.ghost {
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
}

body.dark .btn.ghost:hover {
  background: #1e3a8a;
}

/* Table */
body.dark .table-wrap {
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
}

body.dark .table th {
  background: #273449;
  color: #e2e8f0;
  border-bottom: 1px solid #334155;
}

body.dark .table td {
  background: #1e293b;
  color: #f1f5f9;
  border-bottom: 1px solid #334155;
}

body.dark .table tr:hover td {
  background: #2d3748;
}

body.dark .no-data td {
  color: #94a3b8;
}

/* Action buttons */
body.dark .action-edit {
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
}

body.dark .action-edit:hover {
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
}

body.dark .action-delete {
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;
}

body.dark .action-delete:hover {
background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;}


  </style>
</head>
<body>
<header>
  <div class="header-left"><img src="<?=htmlspecialchars($logoPath)?>" alt="nova staff" class="logo"></div>
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
    <button  onclick="location.href='payment.php'">Payment</button>
    <button class="active" onclick="location.href='setting.php'">⚙ Settings</button>
  <?php endif; ?>
</div>

  <main class="main"><?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>"><?= htmlspecialchars($flash['text']) ?></div>
    <?php endif; ?>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
      <div>
        <h1 style="margin:0; font-size:32px;">Settings</h1>
        <div style="color:var(--muted); margin-top:6px;">Manage employees, targets, roles and quick CRM configuration</div>
      </div>
      <div style="display:flex;gap:10px;align-items:center">
        <a href="?export_settings=1" class="btn btn-outline-primary">Export JSON</a>
        <a href="?export_csv=1" class="btn btn-outline-secondary">Export CSV</a>
        <button class="btn btn-primary" onclick="openEmployeeModal()">+ Add Employee</button>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row g-4">


 <!-- Lead Status -->
<div class="col-md-6">
  <div class="card p-3 h-100">
    <div class="section-title mb-2">Lead Status</div>
    <div>
      <?php if ($leadStatus && $leadStatus->num_rows): ?>
        <?php while ($row = $leadStatus->fetch_assoc()): ?>
          <span class="badge-item">
            <?= htmlspecialchars($row['name']) ?>
            <a href="?del_status=<?= $row['id'] ?>">×</a>
          </span>
        <?php endwhile; ?>
        <?php else: ?>
  <p class="text-muted mb-0" style="font-style:italic;">No records found</p>
<?php endif; ?>
    </div>
    <form method="post" class="d-flex mt-3" style="gap:10px;">
      <input type="text" name="status_name" placeholder="Add status (e.g. Qualified)">
      <button type="submit" name="add_status" class="btn-new">New</button>
    </form>
  </div>
</div>

<!-- Lead Types -->
<div class="col-md-6">
  <div class="card p-3 h-100">
    <div class="section-title mb-2">Lead Types</div>
    <div>
      <?php if ($leadType && $leadType->num_rows): ?>
        <?php while ($row = $leadType->fetch_assoc()): ?>
          <span class="badge-item">
            <?= htmlspecialchars($row['name']) ?>
            <a href="?del_type=<?= $row['id'] ?>">×</a>
          </span>
        <?php endwhile; ?>
                 <?php else: ?>
  <p class="text-muted mb-0" style="font-style:italic;">No record found.</p>
             <?php endif; ?>
    </div>
    <form method="post" class="d-flex mt-3" style="gap:10px;">
      <input type="text" name="type_name" placeholder="Add type (e.g. Hot)">
      <button type="submit" name="add_type" class="btn-new">New</button>
    </form>
  </div>
</div>

<!-- Lead Visa -->
<div class="col-md-6"style="margin-top:30px;">
  <div class="card p-3 h-100">
    <div class="section-title mb-2">Lead Visa</div>
    <div>
      <?php if ($leadVisa && $leadVisa->num_rows): ?>
        <?php while ($row = $leadVisa->fetch_assoc()): ?>
          <span class="badge-item">
            <?= htmlspecialchars($row['name']) ?>
            <a href="?del_visa=<?= $row['id'] ?>">×</a>
          </span>
        <?php endwhile; ?>
            <?php else: ?>
  <p class="text-muted mb-0" style="font-style:italic;">No record found.</p>
             <?php endif; ?>
    </div>
    <form method="post" class="d-flex mt-3" style="gap:10px;">
      <input type="text" name="visa_name" placeholder="Add visa type (e.g. H1B)">
      <button type="submit" name="add_visa" class="btn-new">New</button>
    </form>
  </div>
</div>


<!--Team member -->
<div class="col-md-6"style="margin-top:30px;">
  <div class="card p-3 h-100">
    <div class="section-title mb-2">Team Members</div>
    <div>
      <?php if($teamMembers && $teamMembers->num_rows>0): ?>
        <?php while($m=$teamMembers->fetch_assoc()): ?>
          <span class="badge-item">
            <?=htmlspecialchars($m['name'])?>
            <a href="?del_member=<?=$m['id']?>">×</a>
          </span>
        <?php endwhile; ?>
      <?php else: ?>
  <p class="text-muted mb-0" style="font-style:italic;">No record found.</p>
      <?php endif; ?>
    </div>
    <form method="post" class="d-flex mt-3" style="gap:10px;">
      <input type="text" name="member_name" placeholder="Add team member">
      <button type="submit" name="add_member" class="btn-new">New</button>
    </form>
  </div>
</div>


        <!-- === End replaced cards === -->

      </div>
    </div>

    <!-- Employees -->
    <div class="card" style="margin-top:30px;">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="title">Employees</div>
          <div class="muted">Create, edit or delete employees</div>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
          <input id="userSearch" class="input" placeholder="Search by name, email or role" style="min-width:260px">
          <button class="btn ghost" onclick="location.href='employees.php'">Open Employees</button>
        </div>
      </div>

      <div class="table-wrap">
        <table class="table mb-0">
          <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
          </thead>
          <tbody id="userTableBody">
            <?php if ($employees_res && $employees_res->num_rows): while ($u = $employees_res->fetch_assoc()): ?>
            <tr>
              <td><?= intval($u['id']) ?></td>
              <td><?= htmlspecialchars($u['name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>
              <td>
                <button class="action-btn action-edit" onclick='openEmployeeModal(<?= json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>Edit</button>
                <a class="action-btn action-delete" href="setting.php?delete_employee=<?= $u['id'] ?>" onclick="return confirm('Delete employee?')">Delete</a>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr class="no-data"><td colspan="5">No employees yet</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

<!-- ================= TARGETS SECTION ================= -->

 
  <!-- Targets Table -->
   <div class="target">
  <div class="container">
  <h2>🎯 <?= $editData ? 'Edit Target' : 'Assign Monthly Targets' ?></h2>
  
  <form method="POST">
    <input type="hidden" name="update_id" value="<?= $editData['id'] ?? '' ?>">

    <div class="form-grid">
      <div>
        <label>Employee Name</label>
        <input type="text" name="employee" value="<?= $editData['employee'] ?? '' ?>" required>
      </div>

      <div>
        <label>Category</label>
        <select name="category" id="category" onchange="toggleFields()" required>
          <option value="">Select Category</option>
          <option value="Sales" <?= ($editData['category'] ?? '') == 'Sales' ? 'selected' : '' ?>>Sales</option>
          <option value="Leads" <?= ($editData['category'] ?? '') == 'Leads' ? 'selected' : '' ?>>Leads</option>
          <option value="Recruiter" <?= ($editData['category'] ?? '') == 'Recruiter' ? 'selected' : '' ?>>Recruiter</option>
        </select>
      </div>

      <div>
        <label>Month</label>
        <select name="month" required>
          <?php
            $months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
            foreach($months as $m) echo "<option value='$m'>$m</option>";
          ?>
        </select>
      </div>
      <div>
        <label>Year</label>
        <input type="number" name="year" value="<?= date('Y') ?>" required>
      </div>
    </div>

    <!-- Conditional Fields (Horizontal Row Boxes) -->
    <div id="sales-fields" class="field-row" style="display:none;">
      <div><label>Sales Closures</label><input type="number" name="closure_sales" value="<?= $editData['closure_sales'] ?? '' ?>"></div>
      <div><label>Revenue</label><input type="number" name="revenue" value="<?= $editData['revenue'] ?? '' ?>"></div>
       </div>

    <div id="leads-fields" class="field-row" style="display:none;">
      <div><label>Lead Closures</label><input type="number" name="closure_lead" value="<?= $editData['closure_lead'] ?? '' ?>"></div>
      <div><label>Cold Leads</label><input type="number" name="cold_leads" value="<?= $editData['cold_leads'] ?? '' ?>"></div>
      <div><label>Hot Leads</label><input type="number" name="hot_leads" value="<?= $editData['hot_leads'] ?? '' ?>"></div>
    </div>

    <div id="recruiter-fields" class="field-row" style="display:none;">
      <div><label>Total Interviews</label><input type="number" name="interviews" value="<?= $editData['interviews'] ?? '' ?>"></div>
      <div><label>Placements</label><input type="number" name="placements" value="<?= $editData['placements'] ?? '' ?>"></div>
      <div><label>Applications</label><input type="number" name="applications" value="<?= $editData['applications'] ?? '' ?>"></div>
    </div>
  

    <button type="submit"><?= $editData ? '🔁 Update Target' : '💾 Save Target' ?></button>
  </form>

  <h2>📊 Monthly Targets Overview</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Employee</th>
        <th>Category</th>
        <th>Month</th>
        <th>Year</th>
        <th>Sales Closures</th>
        <th>Lead Closures</th>
        <th>Revenue</th>
        <th>Hot Leads</th>
        <th>Cold Leads</th>
        <th>Applications</th>
        <th>Interviews</th>
        <th>Placements</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['employee']) ?></td>
        <td><?= htmlspecialchars($row['category']) ?></td>
        <td><?= htmlspecialchars($row['month']) ?></td>
        <td><?= htmlspecialchars($row['year']) ?></td>
        <td><?= $row['closure_sales'] ?? 0 ?></td>
        <td><?= $row['closure_lead'] ?? 0 ?></td>
        <td><?= $row['revenue'] ?? 0 ?></td>
        <td><?= $row['hot_leads'] ?? 0 ?></td>
        <td><?= $row['cold_leads'] ?? 0 ?></td>
        <td><?= $row['applications'] ?? 0 ?></td>
        <td><?= $row['interviews'] ?? 0 ?></td>
        <td><?= $row['placements'] ?? 0 ?></td>
<td>
  <!-- Existing Edit/Delete buttons stay here -->
  <div class="action-menu">
    <button type="button" class="menu-toggle">⋮</button>
    <div class="menu-dropdown">
      <button type="button" onclick="openEditTarget(<?= $row['id'] ?>)">Edit</button>
      <a href="setting.php?delete_target=<?= $row['id'] ?>" onclick="return confirm('Delete this target?')">Delete</a>
    </div>
  </div>
</td>



      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</div>

    <!-- Roles -->
    <div class="card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="title">Roles</div>
          <div class="muted">Define roles and permissions</div>
        </div>
        <div>
          <button class="btn" onclick="openRoleModal()">+ New Role</button>
        </div>
      </div>

      <div class="d-flex flex-column gap-2">
        <?php if ($roles_res && $roles_res->num_rows): while ($r = $roles_res->fetch_assoc()): ?>
          <div class="d-flex justify-content-between align-items-center p-3" style="border-radius:10px;background: #c8d3e4ff;border:1px solid rgba(30,60,114,0.04);color:black">
            <div>
              <div style="font-weight:700"><?= htmlspecialchars($r['role_name']) ?></div>
              <div class="muted"><?= htmlspecialchars($r['permissions']) ?></div>
            </div>
            <div style="display:flex;gap:8px">
              <button class="btn" onclick='openRoleModal(<?= json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' style="background:linear-gradient(135deg,#87b6c6,#cfe9f6);color:#001a40;">Edit</button>
              <a class="btn danger" href="setting.php?delete_role=<?= intval($r['id']) ?>" onclick="return confirm('Delete role?')">Delete</a>
            </div>
          </div>
        <?php endwhile; else: ?>
          <div class="muted">No roles defined</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Logs -->
    <div id="logsContainer" class="card" style="display:<?= $show_logs ? 'block' : 'none' ?>;">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="title">Audit Logs</div>
          <div class="muted">Actions recorded automatically. You may add a manual log below.</div>
        </div>
        <div style="display:flex;gap:8px">
          <a class="btn danger" href="setting.php?clear_logs=1" onclick="return confirm('Clear all logs? This cannot be undone.')">Clear All Logs</a>
          <button class="btn gray" onclick="toggleLogs()">Close Logs</button>
        </div>
      </div>

      <form method="post" style="margin-top:12px;display:flex;gap:8px;align-items:center">
        <input type="hidden" name="action" value="add_manual_log">
        <input name="manual_log_msg" class="input form-control" placeholder="Add manual log (eg: Server backup complete)" style="flex:1">
        <button class="btn" type="submit">Add Log</button>
      </form>

      <div style="margin-top:12px; max-height:420px; overflow:auto;">
        <?php
          $logs_list = $conn->query("SELECT * FROM logs ORDER BY id DESC LIMIT 500");
          if ($logs_list && $logs_list->num_rows):
        ?>
          <div class="table-wrap">
            <table class="table mb-0">
              <thead><tr><th style="width:160px">Time</th><th>User</th><th>Action</th><th>Details</th><th style="width:120px">Manage</th></tr></thead>
              <tbody>
                <?php while ($l = $logs_list->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($l['created_at']) ?></td>
                  <td><?= htmlspecialchars($l['user']) ?></td>
                  <td><?= htmlspecialchars($l['action']) ?></td>
                  <td><pre style="white-space:pre-wrap;margin:0"><?= htmlspecialchars($l['details']) ?></pre></td>
                  <td><a class="btn danger" href="setting.php?delete_log=<?= intval($l['id']) ?>" onclick="return confirm('Delete this log?')">Delete</a></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="muted" style="padding:12px 0">No logs found.</div>
        <?php endif; ?>
      </div>
    </div>

  </main>

  <!-- Employee Modal -->
  <div id="employeeModal" class="modal" onclick="if(event.target===this) closeEmployeeModal()">
    <div class="box">
      <h3 id="empTitle">Add Employee</h3>
      <form method="post">
        <input type="hidden" name="action" value="save_employee">
        <input type="hidden" name="emp_id" id="emp_id_field">
        <input id="emp_name_field" name="emp_name" class="input form-control" placeholder="Full name" required>
        <input id="emp_email_field" name="emp_email" class="input form-control" placeholder="Email" type="email" required>
        <select id="emp_role_field" name="emp_role" class="input form-select">
          <?php
            $rsl = $conn->query("SELECT id, role_name FROM roles ORDER BY role_name");
            $has = false;
            if ($rsl && $rsl->num_rows) {
                while ($rr = $rsl->fetch_assoc()) { $has = true; echo "<option value=\"".htmlspecialchars($rr['role_name'])."\">".htmlspecialchars($rr['role_name'])."</option>"; }
            }
            if (!$has) echo '<option>Admin</option><option>Staff</option>';
          ?>
        </select>

        <div class="d-flex justify-content-end gap-2 mt-2">
          <button type="button" class="btn ghost" onclick="closeEmployeeModal()">Cancel</button>
          <button class="btn" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Role Modal -->
  <div id="roleModal" class="modal" onclick="if(event.target===this) closeRoleModal()">
    <div class="box">
      <h3 id="roleTitle">Add Role</h3>
      <form method="post">
        <input type="hidden" name="action" value="save_role">
        <input type="hidden" name="role_id" id="role_id_field">
        <input id="role_name_field" name="role_name" class="input form-control" placeholder="Role name" required>
        <input id="role_perms_field" name="permissions" class="input form-control" placeholder="Permissions (comma separated)">
        <div class="d-flex justify-content-end gap-2 mt-2">
          <button type="button" class="btn ghost" onclick="closeRoleModal()">Cancel</button>
          <button class="btn" type="submit">Save Role</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Target Modal -->
  <div id="editTargetModal" class="modal" onclick="if(event.target===this) closeEditTargetModal()">
    <div class="box">
      <h3 id="editTargetTitle">Edit Target</h3>
      <form method="post">
        <input type="hidden" name="action" value="edit_target">
        <input type="hidden" name="tid" id="edit_tid">
        <label class="small">Amount (₹)</label>
        <input id="edit_t_amount" name="t_amount" type="number" step="0.01" class="input form-control" placeholder="Amount" required>
        <label class="small mt-1">Category</label>
        <select id="edit_t_category" name="t_category" class="input form-control">
          <option value="">Category</option>
          <option value="Sales">Sales</option>
          <option value="Lead generation">Lead generation</option>
          <option value="Recruiter">Recruiter</option>
        </select>
        <label class="small mt-1">Achieved (optional)</label>
        <input id="edit_t_achieved" name="t_achieved" type="number" step="0.01" class="input form-control" placeholder="Achieved amount (₹)">

        <div class="d-flex justify-content-end gap-2 mt-2">
          <button type="button" class="btn ghost" onclick="closeEditTargetModal()">Cancel</button>
          <button class="btn" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

// Employee modal handlers
function openEmployeeModal(emp=null){
  document.getElementById('employeeModal').style.display='flex';
  document.getElementById('emp_id_field').value = emp ? emp.id : '';
  document.getElementById('emp_name_field').value = emp ? emp.name : '';
  document.getElementById('emp_email_field').value = emp ? emp.email : '';
  document.getElementById('emp_role_field').value = emp ? emp.role : '';
  document.getElementById('empTitle').innerText = emp ? 'Edit Employee' : 'Add Employee';
}
function closeEmployeeModal(){ document.getElementById('employeeModal').style.display='none'; }

// Role modal handlers
function openRoleModal(role=null){
  document.getElementById('roleModal').style.display='flex';
  document.getElementById('role_id_field').value = role ? role.id : '';
  document.getElementById('role_name_field').value = role ? role.role_name : '';
  document.getElementById('role_perms_field').value = role ? role.permissions : '';
  document.getElementById('roleTitle').innerText = role ? 'Edit Role' : 'Add Role';
}
function closeRoleModal(){ document.getElementById('roleModal').style.display='none'; }

// Targets: Edit modal
function openEditTargetModal(tid, amount, category, achieved){
  document.getElementById('editTargetModal').style.display='flex';
  document.getElementById('edit_tid').value = tid;
  document.getElementById('edit_t_amount').value = amount;
  document.getElementById('edit_t_category').value = category || '';
  document.getElementById('edit_t_achieved').value = achieved || 0;
  document.getElementById('editTargetTitle').innerText = 'Edit Target #' + tid;
}
function closeEditTargetModal(){ document.getElementById('editTargetModal').style.display='none'; }

// User search
document.getElementById('userSearch').addEventListener('input', function(e){
  const f = e.target.value.toLowerCase().trim();
  const rows = document.querySelectorAll('#userTableBody tr');
  rows.forEach(r=>{
    const txt = r.innerText.toLowerCase();
    r.style.display = txt.includes(f) ? '' : 'none';
  });
});

// toggle logs
function toggleLogs(){
  const el=document.getElementById('logsContainer');
  if(!el) return;
  el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
  if (el.style.display === 'block') el.scrollIntoView({behavior:'smooth', block:'center'});
}

// Targets filtering + summary
function parseCurrency(str){
  return parseFloat(String(str).replace(/[^\d\.\-]/g,'') || 0);
}
function updateTargetsSummary(){
  const rows = document.querySelectorAll('#targetTableBody tr');
  let total = 0, count = 0;
  rows.forEach(r=>{
    if (r.classList.contains('no-data')) return;
    if (r.style.display === 'none') return;
    const el = r.querySelector('.target-amount');
    if (!el) return;
    const val = parseCurrency(el.textContent);
    total += val;
    count++;
  });
  document.getElementById('totalTarget').innerText = '₹' + total.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('totalRows').innerText = count;
}

// initial summary
updateTargetsSummary();

function applyTargetFilter(){
  const month = document.getElementById('filterMonth').value;
  const year = document.getElementById('filterYear').value;
  const category = document.getElementById('filterCategory').value;
  const rows = document.querySelectorAll('#targetTableBody tr');
  rows.forEach(r=>{
    const rmonth = r.getAttribute('data-month') || '';
    const ryear = r.getAttribute('data-year') || '';
    const rcat = r.getAttribute('data-category') || '';
    let show = true;
    if (month && month !== rmonth) show = false;
    if (year && String(year) !== '') {
      show = show && (String(ryear) === String(year));
    }
    if (category && category !== '' && category !== rcat) show = false;
    r.style.display = show ? '' : 'none';
  });
  updateTargetsSummary();
}
function clearTargetFilter(){
  document.getElementById('filterMonth').value = '';
  document.getElementById('filterCategory').value = '';
  document.getElementById('filterYear').value = '<?= date('Y') ?>';
  const rows = document.querySelectorAll('#targetTableBody tr');
  rows.forEach(r=> r.style.display = '');
  updateTargetsSummary();
}

document.getElementById('applyFilter').addEventListener('click', applyTargetFilter);
document.getElementById('clearFilter').addEventListener('click', clearTargetFilter);

// small helper: close modals on Escape
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') {
    closeEmployeeModal();
    closeRoleModal();
    closeEditTargetModal();
  }
});

// Recalculate summary after page load (in case of dynamic rows)
window.addEventListener('load', function(){ updateTargetsSummary(); });



// Handle 3-dot dropdown menu behavior
document.addEventListener("click", function (e) {
  const isToggle = e.target.classList.contains("menu-toggle");
  const allMenus = document.querySelectorAll(".menu-dropdown");

  // Close all open menus
  allMenus.forEach(m => m.classList.remove("show"));

  // Open the clicked one
  if (isToggle) {
    const dropdown = e.target.nextElementSibling;
    dropdown.classList.toggle("show");
    e.stopPropagation();
  }
});



function toggleFields() {
  const cat = document.getElementById("category").value;
  document.getElementById("sales-fields").style.display = (cat === "Sales") ? "flex" : "none";
  document.getElementById("leads-fields").style.display = (cat === "Leads") ? "flex" : "none";
  document.getElementById("recruiter-fields").style.display = (cat === "Recruiter") ? "flex" : "none";
}
toggleFields();
</script>

<?php
// optional export endpoint (JSON)
if (isset($_GET['export_settings'])) {
    header('Content-Type: application/json');
    $data = [];
    $e = $conn->query("SELECT id,name,email,role FROM employees");
    $data['employees'] = $e ? $e->fetch_all(MYSQLI_ASSOC) : [];
    $t = $conn->query("SELECT * FROM targets");
    $data['targets'] = $t ? $t->fetch_all(MYSQLI_ASSOC) : [];
    $r = $conn->query("SELECT * FROM roles");
    $data['roles'] = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    $l = $conn->query("SELECT * FROM logs ORDER BY id DESC LIMIT 500");
    $data['logs'] = $l ? $l->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode($data, JSON_PRETTY_PRINT);
    $conn->close();
    exit;
}

$conn->close();
?>
</body>
</html>