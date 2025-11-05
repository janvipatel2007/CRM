<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------- DB CONNECTION ----------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "crm";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

$login_msg = "";

// ---------- LOGIN LOGIC ----------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {

    $email = trim($_POST['email']);
    $pass  = trim($_POST['password']);
    $role  = $_POST['role'] ?? '';

    // Check if email exists in users table
    $sql = "SELECT * FROM users WHERE email=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check role match
        if ($row['role'] !== $role) {
            $login_msg = "❌ Role mismatch!";
        }
        // Verify password
        elseif (password_verify($pass, $row['password'])) {
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['full_name'];
            $_SESSION['user_role'] = $row['role'];

            // Optional: store job role for users
            if ($row['role'] === 'user' && !empty($row['job_role'])) {
                $_SESSION['job_role'] = $row['job_role'];
            }

            // Redirect to dashboard after login
            header("Location: index.php");
            exit;
        } else {
            $login_msg = "❌ Invalid password!";
        }
    } else {
        $login_msg = "❌ Email not registered!";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body, html{height:100%;width:100%;background:#f0f2f5;display:flex;justify-content:center;align-items:center;}
.container{display:flex;flex-wrap:wrap;max-width:1000px;width:95%;background: rgba(255,255,255,0.1);backdrop-filter: blur(20px);border-radius:20px;box-shadow: 0 8px 32px rgba(0,0,0,0.2);overflow:hidden;}
.left-panel{flex:1;background:url('CRM_img.jpg') no-repeat center/cover;position:relative;min-height:550px;}
.left-panel::after{content:"";position:absolute;inset:0;background:rgba(0,0,0,0.45);}
.right-panel{flex:1;display:flex;justify-content:center;align-items:center;padding:50px;}
.login-form{width:100%;max-width:500px;min-height:450px;background: rgba(255,255,255,0.85);backdrop-filter: blur(15px);padding:40px 30px;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.1);text-align:center;display:flex;flex-direction:column;justify-content:center;}
.login-form h2{margin-bottom:30px;color:#333;font-weight:600;}
.login-form input, .login-form select{width:100%;padding:14px 20px;margin:10px 0;border:1px solid #ccc;border-radius:12px;font-size:1rem;transition: all 0.3s ease;}
.login-form input:focus, .login-form select:focus{border-color:#764ba2;box-shadow:0 0 10px rgba(118,75,162,0.3);outline:none;}
.login-form button{width:100%;padding:14px;margin:15px 0;border:none;border-radius:12px;background:linear-gradient(to right,#667eea,#764ba2);color:#fff;font-size:1rem;font-weight:500;cursor:pointer;transition: all 0.3s ease;}
.login-form button:hover{transform:translateY(-2px);background:linear-gradient(to right,#764ba2,#667eea);}
.login-form p{margin:15px 0;color:#666;}
.login-form a{color:#764ba2;text-decoration:none;font-weight:500;}
.login-form a:hover{text-decoration:underline;}
.message{margin:10px 0;padding:10px;border-radius:8px;background:#d4edda;color:#155724;font-weight:500;}
.social-login{display:flex;justify-content:center;gap:15px;margin:15px 0;}
.social-login a{display:flex;align-items:center;justify-content:center;width:45px;height:45px;border-radius:50%;background:#fff;color:#333;font-size:1.2rem;box-shadow:0 5px 15px rgba(0,0,0,0.1);transition: all 0.3s ease;}
.social-login a:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,0.2);}
.social-login .google{color:#DB4437;}
.social-login .facebook{color:#4267B2;}
.social-login .linkedin{color:#0077B5;}
@media(max-width:900px){.container{flex-direction:column;}.left-panel,.right-panel{width:100%;min-height:400px;}.login-form{min-height:400px;}}

/* Admin Modal */
.close-btn{
position: absolute;
  top: 10px;
  right: 15px;
  font-size: 25px;
  cursor: pointer;
color: white;
}
.popup-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;justify-content:center;align-items:center;z-index:1000;backdrop-filter: blur(6px);display:none;}
.popup{background: rgba(255,255,255,0.15);backdrop-filter: blur(20px);border-radius:20px;padding:30px;text-align:center;max-width:350px;width:90%;box-shadow:0 10px 25px rgba(0,0,0,0.3);}
.popup h3{margin-bottom:20px;color:#fff;font-weight:600;}
.popup input{width:100%;padding:12px;border:none;border-radius:10px;margin-bottom:15px;background:rgba(255,255,255,0.8);}
.popup button{padding:10px 20px;border:none;border-radius:10px;background:linear-gradient(to right,#667eea,#764ba2);color:#fff;font-weight:500;cursor:pointer;}
.popup button:hover{transform:translateY(-2px);}
</style>
</head>
<body>
<div class="container">
    <div class="left-panel"></div>
    <div class="right-panel">
        <!-- Only the form part is shown here — no job role select anymore -->

<form class="login-form" method="POST" action="">
    <h2>Login</h2>
    <?php if($login_msg) echo "<div class='message'>$login_msg</div>"; ?>

    <input type="email" name="email" placeholder="Email Address" required>

    <!-- Main Role -->
    <select name="role" id="roleSelect" required>
        <option value="">Select Role</option>
        <option value="user">User</option>
        <option value="admin">Admin</option>
    </select>

    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>

    <p>Don’t have an account? <a href="register.php"  id="showRegisterModal">Register here</a></p>
</form>

    </div>
</div>

<!-- Admin modal -->
<div class="popup-overlay" id="adminModal">
   
    <div class="popup">
          <span class="close-btn" id="closePanel">&times;</span>
        <h3>🔒 Admin Access Required</h3>
        <form id="adminForm">
            <input type="password" id="admin_password" name="admin_password" placeholder="Enter admin password" required>
            <button type="submit">Verify</button>
        </form>
    </div>
</div>

<script>
const showBtn = document.getElementById('showRegisterModal');
const adminModal = document.getElementById('adminModal');
const adminForm = document.getElementById('adminForm');
const closePanel = document.getElementById('closePanel');

// --- Open popup on “Register here” ---
showBtn.addEventListener('click', function(e){
    e.preventDefault();
    adminModal.style.display = 'flex';
});

// --- Verify admin password ---
adminForm.addEventListener('submit', function(e){
    e.preventDefault();
    const password = document.getElementById('admin_password').value;

    fetch('', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'admin_password=' + encodeURIComponent(password)
    })
    .then(res => res.text())
    .then(data => {
        if(data.includes('✅ Admin verified')){
            window.location.href = 'register.php';
        } else {
            alert('❌ Incorrect admin password!');
        }
    });
});

// --- ❌ Close popup and stay on login page ---
closePanel.addEventListener('click', () => {
    adminModal.style.display = 'none';
});

// --- Optional: close when clicking outside popup ---
adminModal.addEventListener('click', (e) => {
    if(e.target === adminModal){
        adminModal.style.display = 'none';
    }
});
</script>

</body>
</html>
