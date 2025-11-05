<?php
session_start();
$status = "";
$status_class = "";

// ---------- DB CONNECTION ----------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "crm"; // Change this to your database name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------- FORM SUBMISSION ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email     = $_POST['email'];
    $phone     = $_POST['phone'];
    $role      = $_POST['role'];
    $job_role  = isset($_POST['job_role']) ? $_POST['job_role'] : NULL;
    $password  = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    // Password match check
    if ($password !== $cpassword) {
        $status = "Passwords do not match!";
        $status_class = "error";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into DB
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, role, job_role, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $full_name, $email, $phone, $role, $job_role, $hashed_password);

       if ($stmt->execute()) {
    $status = "✅ Registration successful!";
    $status_class = "success";

    // 🔹 Instant redirect to login page
    header("Location: login.php");
    exit;
} else {
    $status = "❌ Error: " . $stmt->error;
    $status_class = "error";
}

$stmt->close();

    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', sans-serif;
}

body {
  background: #f4f6fb;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}

.container {
  display: flex;
  max-width: 1050px;
  width: 100%;
  background: #fff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

/* ---------- Left Panel ---------- */
.left-panel {
  flex: 1;
  background: url('CRM_img.jpg') no-repeat center center/cover;
  min-height: 580px;
  position: relative;
}
.left-panel::after {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
}

/* ---------- Right Panel ---------- */
.right-panel {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #fafafa;
  padding: 40px 20px;
}

.register-form {
  width: 100%;
  max-width: 500px;
  background: #fff;
  padding: 30px;
  border-radius: 15px;
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
}

.register-form h2 {
  text-align: center;
  margin-bottom: 20px;
  font-size: 26px;
  color: #222;
  font-weight: 600;
}

.register-form input,
.register-form select {
  width: 100%;
  padding: 12px 14px;
  margin: 8px 0;
  border: 1px solid #dcdcdc;
  border-radius: 10px;
  font-size: 14px;
  background: #f9f9f9;
  transition: all 0.3s ease;
}

.register-form input:focus,
.register-form select:focus {
  outline: none;
  background: #fff;
  border-color: #6a5acd;
  box-shadow: 0 0 6px rgba(106, 90, 205, 0.25);
}

.register-form button {
  width: 100%;
  padding: 12px;
  background: linear-gradient(90deg, #667eea, #764ba2);
  border: none;
  color: white;
  font-size: 15px;
  border-radius: 10px;
  margin-top: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.register-form button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(106, 90, 205, 0.3);
}

.register-form p {
  text-align: center;
  margin: 15px 0 10px;
  color: #666;
  font-size: 14px;
}

/* ---------- Social Login ---------- */
.social-login{display:flex;justify-content:center;gap:15px;margin:15px 0;}
.social-login a{display:flex;align-items:center;justify-content:center;width:45px;height:45px;border-radius:50%;background:#fff;color:#333;font-size:1.2rem;box-shadow:0 5px 15px rgba(0,0,0,0.1);transition: all 0.3s ease;}
.social-login a:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,0.2);}
.social-login .google{color:#DB4437;}
.social-login .facebook{color:#4267B2;}
.social-login .linkedin{color:#0077B5;}

/* ---------- Message Styling ---------- */
.message {
  margin: 10px 0;
  padding: 10px;
  border-radius: 8px;
  font-weight: 500;
  text-align: center;
}
.message.success { background: #d4edda; color: #155724; }
.message.error   { background: #f8d7da; color: #721c24; }

/* ---------- Responsive ---------- */
@media (max-width: 900px) {
  .container {
    flex-direction: column;
    width: 95%;
  }
  .left-panel {
    display: none;
  }
  .right-panel {
    padding: 20px;
  }
}
</style>
</head>
<body>
  <div class="container">
    <div class="left-panel"></div>

    <div class="right-panel">
      <form class="register-form" method="POST" action="">
        <h2>Register</h2>
        <?php if(isset($status) && $status) echo "<div class='message $status_class'>$status</div>"; ?>

        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="phone" placeholder="Phone Number" required>

        <!-- Role -->
        <select name="role" id="roleSelect" required>
          <option value="">Select Role</option>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>

        <!-- Job Role -->
        <select name="job_role" id="jobRoleSelect" style="display:none;">
          <option value="">Select Job Role</option>
          <option value="sales_person">Sales Person</option>
          <option value="lead_generator">Lead Generator</option>
          <option value="recruiter">Recruiter</option>
        </select>

        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="cpassword" placeholder="Confirm Password" required>

        <button type="submit">Register</button>

        <p>Or login with</p>
        <div class="social-login">
            <a href="#" class="social google"><i class="fab fa-google"></i></a>
            <a href="#" class="social facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social linkedin"><i class="fab fa-linkedin-in"></i></a>
        </div>

        <p>Already have an account? <a href="login.php">Login here</a></p>
      </form>
    </div>
  </div>

<script>
const roleSelect = document.getElementById('roleSelect');
const jobRoleSelect = document.getElementById('jobRoleSelect');

roleSelect.addEventListener('change', function() {
  if (this.value === 'user') {
    jobRoleSelect.style.display = 'block';
    jobRoleSelect.setAttribute('required', 'required');
  } else {
    jobRoleSelect.style.display = 'none';
    jobRoleSelect.removeAttribute('required');
    jobRoleSelect.value = '';
  }
});
</script>
</body>
</html>
