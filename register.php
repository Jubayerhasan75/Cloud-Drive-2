<?php
include 'db.php';
$email = $_POST['email'];
$password = $_POST['password'];
$sql = "INSERT INTO users (email, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $password);

if ($stmt->execute()) {
    // Success page with softer green background
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <title>Registration Successful</title>
      <style>
        body {
          background: #a8c783ff; /* lighter green */
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          margin: 0;
          color: #33691e; /* dark green */
        }
        .message-container {
          background: #aed581; /* softer green box */
          padding: 40px 50px;
          border-radius: 12px;
          box-shadow: 0 0 20px rgba(105, 141, 49, 0.4);
          text-align: center;
          max-width: 400px;
        }
        .icon {
          width: 60px;
          height: 60px;
          margin: 0 auto 20px;
          fill: #558b2f;
          animation: pop 0.5s ease forwards;
        }
        h1 {
          font-size: 24px;
          margin-bottom: 15px;
        }
        p {
          font-size: 16px;
          margin-bottom: 25px;
          color: #33691e;
        }
        a {
          display: inline-block;
          padding: 12px 25px;
          background: #558b2f;
          color: white;
          border-radius: 8px;
          text-decoration: none;
          font-weight: 600;
          transition: background-color 0.3s ease;
        }
        a:hover {
          background: #33691e;
        }
        @keyframes pop {
          0% { transform: scale(0.7); opacity: 0; }
          100% { transform: scale(1); opacity: 1; }
        }
      </style>
    </head>
    <body>
      <div class="message-container" role="alert">
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <path d="M9 16.17l-3.5-3.5L4 14.17l5 5 12-12-1.41-1.41z"/>
        </svg>
        <h1>Registration Successful!</h1>
        <p>You can now login with your credentials.</p>
        <a href="login.html">Login Now</a>
      </div>
    </body>
    </html>
    <?php
} else {
    // Error page (email exists) with softer red background
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <title>Registration Failed</title>
      <style>
        body {
          background: #8b2333ff; /* soft red background */
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          margin: 0;
          color: #b71c1c; /* dark red text */
        }
        .message-container {
          background: #ef9a9a; /* light red box */
          padding: 40px 50px;
          border-radius: 12px;
          box-shadow: 0 0 20px rgba(183, 28, 28, 0.4);
          text-align: center;
          max-width: 400px;
        }
        .icon {
          width: 60px;
          height: 60px;
          margin: 0 auto 20px;
          fill: #b71c1c;
          animation: shake 0.6s ease-in-out;
        }
        h1 {
          font-size: 24px;
          margin-bottom: 15px;
        }
        p {
          font-size: 16px;
          margin-bottom: 25px;
          color: #b71c1c;
        }
        a {
          display: inline-block;
          padding: 12px 25px;
          background: #b71c1c;
          color: white;
          border-radius: 8px;
          text-decoration: none;
          font-weight: 600;
          transition: background-color 0.3s ease;
        }
        a:hover {
          background: #7f1414;
        }
        @keyframes shake {
          0%, 100% { transform: translateX(0); }
          20%, 60% { transform: translateX(-10px); }
          40%, 80% { transform: translateX(10px); }
        }
      </style>
    </head>
    <body>
      <div class="message-container" role="alert">
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <path d="M11.001 10h2v5h-2zm0 7h2v2h-2z"/>
          <path d="M12 2C6.486 2 2 6.486 2 12c0 5.514 4.486 10 10 10s10-4.486 10-10c0-5.514-4.486-10-10-10zm0 18c-4.411 0-8-3.589-8-8
                   0-4.411 3.589-8 8-8 4.411 0 8 3.589 8 8 0 4.411-3.589 8-8 8z"/>
        </svg>
        <h1>Registration Failed!</h1>
        <p>Email already exists.<br>Please try again with another email.</p>
        <a href="signup.html">Try Again</a>
      </div>
    </body>
    </html>
    <?php
}
?>
