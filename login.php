<?php
session_start();
include 'db.php';
$email = $_POST['email'];
$password = $_POST['password'];
$sql = "SELECT * FROM users WHERE email=? AND password=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $_SESSION['user_id'] = $user['id'];
    header("Location: dashboard.php");
    exit;
} else {
    // Show styled error message page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <title>Login Error</title>
      <style>
        body {
          background: #1e1e2f;
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          margin: 0;
          color: #fff;
        }
        .error-container {
          background: #2f2f3d;
          padding: 40px 50px;
          border-radius: 12px;
          box-shadow: 0 0 20px rgba(255, 0, 0, 0.7);
          text-align: center;
          max-width: 400px;
        }
        .error-icon {
          width: 60px;
          height: 60px;
          margin: 0 auto 20px;
          fill: #ff4c4c;
          animation: shake 0.6s ease-in-out;
        }
        h1 {
          font-size: 24px;
          margin-bottom: 15px;
        }
        p {
          font-size: 16px;
          margin-bottom: 25px;
          color: #f0b8b8;
        }
        a {
          display: inline-block;
          padding: 12px 25px;
          background: #ff4c4c;
          color: white;
          border-radius: 8px;
          text-decoration: none;
          font-weight: 600;
          transition: background-color 0.3s ease;
        }
        a:hover {
          background: #e04343;
        }
        @keyframes shake {
          0%, 100% { transform: translateX(0); }
          20%, 60% { transform: translateX(-10px); }
          40%, 80% { transform: translateX(10px); }
        }
      </style>
    </head>
    <body>
      <div class="error-container" role="alert">
        <svg class="error-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <path d="M11.001 10h2v5h-2zm0 7h2v2h-2z"/>
          <path d="M12 2C6.486 2 2 6.486 2 12c0 5.514 4.486 10 10 10s10-4.486 10-10c0-5.514-4.486-10-10-10zm0 18c-4.411 0-8-3.589-8-8
                   0-4.411 3.589-8 8-8 4.411 0 8 3.589 8 8 0 4.411-3.589 8-8 8z"/>
        </svg>
        <h1>Invalid Login!</h1>
        <p>Your email or password is incorrect.<br>Please try again or sign up first.</p>
        <a href="login.html">Back to Login</a>
      </div>
    </body>
    </html>
    <?php
}
?>
