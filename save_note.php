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


// ---------- Ensure user is logged in ----------
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'User not logged in.']));
}

$user_id = $_SESSION['user_id'];
$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$note = trim($_POST['note'] ?? '');

if ($employee_id <= 0 || empty($note)) {
    die(json_encode(['status' => 'error', 'message' => 'Missing employee or note text.']));
}

// ---------- Fetch employee info ----------
$stmt = $conn->prepare("SELECT name, email, mobile FROM employees WHERE id = ?");
if (!$stmt) {
    die(json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]));
}

$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$emp = $result->fetch_assoc();

if (!$emp) {
    die(json_encode(['status' => 'error', 'message' => 'Employee not found.']));
}

// ---------- Insert note ----------
$insert = $conn->prepare("INSERT INTO employee_notes (employee_id, user_id, employee_name, employee_email, employee_phone, note)
                          VALUES (?, ?, ?, ?, ?, ?)");
if (!$insert) {
    die(json_encode(['status' => 'error', 'message' => 'Insert prepare failed: ' . $conn->error]));
}

$insert->bind_param("iissss", 
    $employee_id, 
    $user_id, 
    $emp['name'], 
    $emp['email'], 
    $emp['mobile'], 
    $note
);

if ($insert->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Note added successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Insert failed: ' . $insert->error]);
}
?>
