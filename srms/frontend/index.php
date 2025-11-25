<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Smart Restaurant — Welcome</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: #fff;
    }
    
    .container { 
        max-width: 400px; width: 90%; padding: 40px; 
        background: rgba(255, 255, 255, 0.1); 
        border-radius: 20px; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
        backdrop-filter: blur(15px); 
        border: 1px solid rgba(255,255,255,0.2);
        text-align: center; 
    }
    
    h2 { margin-bottom: 10px; font-size: 2rem; font-weight: 700; color: #E85D46; }
    p { margin-bottom: 30px; color: #ddd; font-size: 0.9rem; }

    .form-group { margin-bottom: 15px; text-align: left; }
    label { display: block; margin-bottom: 5px; font-size: 0.85rem; color: #eee; font-weight: 600; }
    input { 
        width: 100%; padding: 12px; border-radius: 8px; border: none; 
        font-size: 1rem; background: rgba(255,255,255,0.9); color: #333;
        font-family: 'Poppins', sans-serif;
    }
    input:focus { outline: none; box-shadow: 0 0 0 3px rgba(232, 93, 70, 0.5); }

    .btn-main { 
        width: 100%; padding: 14px; border: none; border-radius: 8px; 
        background: #E85D46; color: white; font-size: 1rem; cursor: pointer; 
        font-weight: 600; transition: 0.2s; margin-top: 10px;
    }
    .btn-main:hover { background: #d64d38; transform: translateY(-2px); }

    .btn-guest { 
        width: 100%; padding: 12px; margin-top: 15px; background: transparent; 
        border: 1px solid rgba(255,255,255,0.5); color: #eee; 
        border-radius: 8px; cursor: pointer; font-size: 0.9rem; transition: 0.2s;
    }
    .btn-guest:hover { background: rgba(255,255,255,0.1); color: white; border-color: white; }

    .links { margin-top: 25px; display: flex; justify-content: space-between; font-size: 0.85rem; }
    .links a { color: #C6AD67; text-decoration: none; font-weight: 500; }
    .links a:hover { color: #fff; text-decoration: underline; }
  </style>
</head>
<body>

  <form id="signupForm" class="container" autocomplete="off">
    <h2>DineEase</h2>
    <p>Create an account to earn loyalty points</p>

    <div class="form-group">
        <label for="name">Full Name</label>
        <input id="name" name="name" required placeholder="User" autocomplete="off">
    </div>

    <div class="form-group">
        <label for="email">Email Address</label>
        <input id="email" name="email" type="email" required placeholder="user@gmail.com" autocomplete="off">
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required placeholder="••••••••" autocomplete="new-password">
    </div>

    <button type="submit" class="btn-main">Sign Up</button>
    
    <button type="button" class="btn-guest" onclick="continueGuest()">
      Skip & Continue as Guest
    </button>

    <div class="links">
        <a href="login.php">Have an account?</a>
        <a href="staff_login.php">Staff Access</a>
    </div>
  </form>

  <script>
    // NEW CODE
    function continueGuest() {
        // 1. Ask for the table number immediately
        let table = prompt("Welcome! Please enter your Table Number:") || '';

        // 2. Clear session via logout hook
        fetch('menu.php?logout=true').then(() => {
            // 3. Clear LOCAL STORAGE specifically to fix the "last logged in table" bug
            localStorage.removeItem('currentUser'); 
            localStorage.removeItem('currentTable'); // <--- CRITICAL FIX

            // 4. Redirect with the table number in the URL
            if(table) {
                window.location.href = 'menu.php?table=' + encodeURIComponent(table);
            } else {
                window.location.href = 'menu.php';
            }
        });
    }

    document.getElementById('signupForm').addEventListener('submit', function(e){
        e.preventDefault(); 

        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        if(!name || !email || !password) return alert('Please fill all fields');

        const API_URL = '../backend/backend_system/auth/signup.php'; 
        const TABLE_API = '../backend/backend_system/auth/update_table.php';

        fetch(API_URL, {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({name, email, password})
        })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                // --- MODIFIED LOGIC HERE ---
                let table = prompt('Signup Successful! Enter Table Number (Optional):') || '';

                if(table){
                    fetch(TABLE_API, {
                        method: 'POST',
                        headers: {'Content-Type':'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({email, table_number: table})
                    }).then(() => {
                        // Redirect to Login page with email pre-filled (optional UX enhancement)
                        window.location.href = "login.php";
                    });
                } else {
                    // Redirect to Login page
                    window.location.href = "login.php";
                }
            } else {
                alert('Error: ' + (data.message || "Signup failed"));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Connection error. Check console.');
        });
    });
    
    // Clear fields on load
    window.onload = function() {
        document.getElementById('name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
    }
  </script>

</body>
</html>