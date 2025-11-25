<?php
session_start();
require_once '../backend/backend_system/config/db_config.php';

$error = "";

// 1. IF ALREADY LOGGED IN, GO TO MENU
if (isset($_SESSION['customer_id'])) {
    header("Location: menu.php");
    exit;
}

// 2. HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password_hash FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        
        unset($_SESSION['staff_id']);
        unset($_SESSION['staff_name']);
        unset($_SESSION['staff_role']);

        $_SESSION['customer_id'] = $user['id'];
        $_SESSION['customer_full_name'] = $user['full_name'];
        
        header("Location: menu.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | DineEaseRestaurant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #E85D46; --text-dark: #333333; --white: #ffffff; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=2070');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            backdrop-filter: blur(5px);
        }
        h1 { color: var(--text-dark); font-weight: 600; font-size: 1.8rem; margin-bottom: 5px; }
        p.subtitle { color: #666; font-size: 0.9rem; margin-bottom: 25px; }
        .error-msg { background: #ffebee; color: #c62828; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; border: 1px solid #ffcdd2; }
        input { width: 100%; padding: 12px 15px; margin: 8px 0; border-radius: 8px; border: 1px solid #ddd; font-family: 'Poppins', sans-serif; font-size: 1rem; transition: 0.3s; background: #fdfdfd; }
        input:focus { outline: none; border-color: var(--primary); background: #fff; }
        button { width: 100%; padding: 14px; margin-top: 20px; border: none; border-radius: 8px; background: var(--primary); color: var(--white); font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(232, 93, 70, 0.3); }
        button:hover { background: #d64d38; transform: translateY(-2px); }
        .footer-link { display: block; margin-top: 20px; color: #E85D46; text-decoration: none; font-weight: 500; }
        .footer-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-box">
        <i class="fas fa-utensils fa-3x" style="color:var(--primary); margin-bottom:10px"></i>
        <h1>Welcome Back</h1>
        <p class="subtitle">Login to start your order</p>
        
        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <input type="email" name="email" placeholder="Enter your email" required value="" autocomplete="new-password" />
            <input type="password" name="password" placeholder="Enter password" required value="" autocomplete="new-password" />
            
            <button type="submit">Login</button>
        </form>

        <a href="index.php" class="footer-link">Don't have an account? Sign Up</a>
    </div>

    <script>
        // Double safety: Clear fields on load via JS
        window.onload = function() {
            document.getElementsByName('email')[0].value = '';
            document.getElementsByName('password')[0].value = '';
        }
    </script>

</body>
</html>