<?php
session_start();
include_once('config.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Check in the accounts table
    $stmt = $conn->prepare("SELECT id, username, password, office_id FROM accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    // If not found in accounts, check in main_admin_accounts
    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("SELECT id, username, password FROM main_admin_accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    // Process the result
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            
            // Check if the user is from accounts or main_admin_accounts
            if (isset($user['office_id'])) {
                $_SESSION['office_id'] = $user['office_id'];
                header("Location: manage_offices_interface.php?office_id=" . $user['office_id']);
            } else {
                // Redirect for main admin
                header("Location: main-manage.php");
            }
            exit();
        } else {
            $message = "Invalid username or password.";
        }
    } else {
        $message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login - Municipal Planning and Development Office</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
 <style>
    body {
      background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                  url("ASSETS/bg-lgu.JPG") no-repeat center center/cover;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: Arial, sans-serif;
      padding: 20px;
      position: relative;
    }
    .container {
      background: rgba(255, 255, 255, 0.15); /* Semi-transparent white */
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
      width: 90%;
      max-width: 500px;  /* Adjusted to center the form */
      backdrop-filter: blur(10px); /* Glassmorphism effect */
      border: 1px solid rgba(255, 255, 255, 0.2);
      text-align: center; /* Center align the content */
    }
    .login-container {
      width: 100%;
    }
    .login-header {
      text-align: center;
      margin-bottom: 20px;
    }
    .login-header img {
      width: 100px;
      animation: fadeIn 1s ease-in-out;
    }
    .login-header h4 {
      font-size: 22px;
      color: rgb(255, 255, 255);
      margin-top: 10px;
      font-weight: bold;
    }
    .form-group {
      margin-bottom: 15px;
      text-align: left;
    }
    .form-group label {
      font-weight: bold;
      color: #fff;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      outline: none;
      transition: 0.3s;
    }
    input[type="text"]:focus, input[type="password"]:focus {
      border-color: #388E3C;
      box-shadow: 0px 0px 5px rgba(56, 142, 60, 0.5);
    }
    .btn-login {
      background-color: #388E3C;
      border: none;
      color: white;
      padding: 12px;
      width: 100%;
      font-size: 16px;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s ease-in-out, transform 0.2s;
    }
    .btn-login:hover {
      background-color: #2E7D32;
      transform: translateY(-2px);
    }
    .extra-link {
      text-align: center;
      margin-top: 15px;
      color: #fff;
    }
    .extra-link a {
      color:rgb(237, 227, 227);
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s;
    }
    .extra-link a:hover {
      color:rgb(50, 182, 244);
    }
    .forgot-password {
      text-align: center;
      margin-top: 15px;
    }
    .forgot-password a {
      color:rgb(102, 217, 255);
      text-decoration: none;
      font-weight: bold;
    }
    .forgot-password a:hover {
      color:rgb(255, 255, 255);
    }
    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
 </style>
</head>
<body>
  <div class="container">
    <div class="login-container">
      <div class="login-header">
        <img src="ASSETS/LIBMANAN LOGO.png" alt="Office Logo">
        <h4>LGU-LIBMANAN OFFICE INFORMATION MANAGEMENT SYSTEM</h4>
      </div>

      <?php if (!empty($message)) : ?>
      <div class="alert alert-danger"><?php echo $message; ?></div>
      <?php endif; ?>

      <form action="login.php" method="POST">
        <div class="form-group">
          <label for="username">Admin Username</label>
          <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-login btn-block text-white">Login</button>
      </form>
      <div class="extra-link">
        <a href="signup.php">Don't have an account? Sign Up</a>
      </div>
      <div class="forgot-password">
          <a href="#">Forgot Password?</a>
      </div>
    </div>
  </div>
</body>
</html>
