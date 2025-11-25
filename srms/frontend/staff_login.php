<?php
session_start();
require_once '../backend/backend_system/config/db_config.php';

// --- HANDLE LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get and sanitize inputs
    $input = json_decode(file_get_contents('php://input'), true);
    $code = intval($input['code']); // Code is integer in DB
    
    // Check credentials
    $stmt = $conn->prepare("SELECT id, full_name, role, active FROM staff WHERE login_code = ?");
    $stmt->bind_param("i", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!$row['active']) {
            echo json_encode(['success' => false, 'error' => 'Account is inactive. Contact Manager.']);
            exit;
        }
        
        // Login Success: Set Session
        $_SESSION['staff_id'] = $row['id'];
        $_SESSION['staff_name'] = $row['full_name'];
        $_SESSION['staff_role'] = $row['role'];
        
        // Determine Redirect Page
        $redirect = '';
        switch(strtolower($row['role'])) { // Ensure case-insensitive matching
            case 'manager':   $redirect = '../admin/admin.php'; break;
            case 'waiter':    $redirect = 'waiter.php'; break;
            case 'chef':      $redirect = 'kitchen.php'; break; // Kitchen is Food
            case 'kitchen':   $redirect = 'kitchen.php'; break; // Handle both naming conventions
            case 'bartender': $redirect = 'bar.php'; break;
            case 'bar':       $redirect = 'bar.php'; break;
            case 'cashier':   $redirect = 'cashier.php'; break;
            default:          $redirect = 'index.html'; // Fallback
        }
        
        echo json_encode(['success' => true, 'redirect' => $redirect]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid Login Code']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | DineEaseRestaurant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #E85D46; 
            --secondary-color: #C6AD67; 
            --text-dark: #333333;
            --text-light: #666666;
            --bg-light: #f4f7f6;
            --white: #ffffff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), #ff8c73); 
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: var(--white);
            width: 90%;
            max-width: 450px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .logo-img { height: 60px; width: auto; margin-bottom: 10px; }

        h2 { color: var(--text-dark); font-weight: 600; font-size: 1.8rem; margin-bottom: 5px; }
        .subtitle { color: var(--text-light); font-size: 0.95rem; margin-bottom: 30px; }

        .form-group { text-align: left; margin-bottom: 20px; }
        label { display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }

        input {
            width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 1.2rem; letter-spacing: 2px; text-align: center; font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s; background: #fdfdfd;
        }

        input:focus { outline: none; border-color: var(--primary-color); background: #fff; }

        .btn-login {
            width: 100%; padding: 14px; background: var(--primary-color); color: white;
            border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600;
            cursor: pointer; transition: background 0.3s, transform 0.2s; margin-top: 10px;
            box-shadow: 0 4px 15px rgba(232, 93, 70, 0.3);
        }

        .btn-login:hover { background: #d64d38; transform: translateY(-2px); }

        .demo-creds {
            margin-top: 25px; font-size: 0.85rem; color: #888; line-height: 1.5;
            border-top: 1px solid #eee; padding-top: 15px;
        }
        
        .error-msg { color: #e74c3c; margin-bottom: 15px; display: none; font-weight: 600; }
    </style>
</head>
<body>

    <div class="login-card">
        <i class="fas fa-utensils fa-3x" style="color:var(--primary-color); margin-bottom:15px;"></i>
        
        <h2>Staff Login</h2>
        <p class="subtitle">Enter your 4-digit access code</p>

        <div id="errorDisplay" class="error-msg"></div>

        <div class="form-group">
            <label for="st_code">Access Code</label>
            <input type="password" id="st_code" placeholder="• • • •" maxlength="4" inputmode="numeric" autofocus />
        </div>

        <button id="loginBtn" class="btn-login">Login</button>

        <div class="demo-creds">
            <strong>For New Staff:</strong><br>
            Please ask the Manager for your unique login code.
        </div>
    </div>

    <script>
        document.getElementById('loginBtn').onclick = async () => {
            const code = document.getElementById('st_code').value.trim();
            const errorDiv = document.getElementById('errorDisplay');
            
            if (!code) { 
                errorDiv.innerText = "Please enter your code.";
                errorDiv.style.display = 'block';
                return; 
            }

            try {
                const res = await fetch('staff_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: code })
                });
                
                const json = await res.json();
                
                if(json.success) {
                    window.location.href = json.redirect;
                } else {
                    errorDiv.innerText = json.error;
                    errorDiv.style.display = 'block';
                }
            } catch (e) {
                errorDiv.innerText = "Connection Error. Try again.";
                errorDiv.style.display = 'block';
            }
        };

        // Allow "Enter" key submission
        document.getElementById('st_code').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') document.getElementById('loginBtn').click();
        });
    </script>

</body>
</html>