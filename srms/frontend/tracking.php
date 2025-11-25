<?php
require_once '../backend/backend_system/config/db_config.php';

// --- HANDLE AJAX STATUS CHECK ---
if (isset($_GET['ajax_order_id'])) {
    header('Content-Type: application/json');
    $orderId = intval($_GET['ajax_order_id']);

    // 1. Get Main Order Info (Target Time)
    $stmt = $conn->prepare("SELECT status, target_time FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) { echo json_encode(['error' => 'Order not found']); exit; }

    // 2. Get Status of Items (to determine overall progress)
    // We prioritize the "earliest" stage. If ANY item is pending, whole order is pending.
    $sql = "SELECT item_status FROM order_items WHERE order_id = $orderId";
    $res = $conn->query($sql);
    
    $statuses = [];
    while($row = $res->fetch_assoc()) { $statuses[] = $row['item_status']; }

    // Logic: Determine Overall Status based on items
    // Hierarchy: pending < preparing < ready < served
    $overallStatus = 'served'; // Default best case
    
    if (in_array('pending', $statuses)) $overallStatus = 'pending';
    elseif (in_array('preparing', $statuses)) $overallStatus = 'preparing';
    elseif (in_array('ready', $statuses)) $overallStatus = 'ready';
    
    // Override if main order status is specifically set (e.g., paid)
    if ($order['status'] === 'paid') $overallStatus = 'pending'; 

    echo json_encode([
        'status' => $overallStatus,
        'target_time' => $order['target_time'] ? date('Y-m-d\TH:i:s', strtotime($order['target_time'])) : null
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order | DineEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #E85D46; --secondary: #C6AD67; --dark: #333; --light: #FFF5F0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: var(--light); color: var(--dark); padding-top: 80px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background: rgba(255,255,255,0.95); position: fixed; top: 0; width: 100%; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .brand { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; color: var(--secondary); }
        .nav-btn { padding: 10px 20px; border: 1px solid #eee; background: white; font-family: 'Poppins', sans-serif; font-weight: 500; cursor: pointer; color: var(--dark); border-radius: 30px; transition: all 0.3s ease; }
        .nav-btn:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 600px; margin: 40px auto; padding: 20px; text-align: center; }
        .status-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        .order-id { font-size: 1.2rem; color: #777; margin-bottom: 20px; }
        .timer { font-size: 3rem; font-weight: 700; color: var(--primary); margin: 20px 0; font-family: 'Playfair Display', serif; }
        
        .timeline { position: relative; margin-top: 40px; padding-left: 20px; text-align: left; border-left: 3px solid #eee; margin-left: 20px; }
        .step { position: relative; margin-bottom: 40px; padding-left: 20px; opacity: 0.5; transition: 0.3s; }
        .step.active { opacity: 1; font-weight: bold; }
        .step::before { content: ''; position: absolute; left: -26px; top: 5px; width: 15px; height: 15px; border-radius: 50%; background: #eee; border: 3px solid white; box-shadow: 0 0 0 2px #eee; transition: 0.3s; }
        
        .step.active::before { background: var(--primary); box-shadow: 0 0 0 2px var(--primary); }
        .step.completed::before { background: var(--secondary); box-shadow: 0 0 0 2px var(--secondary); }
        
        .step h4 { margin: 0; font-size: 1.1rem; color: var(--dark); }
        .step p { margin: 5px 0 0; font-size: 0.9rem; color: #777; }
        .msg-box { background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 10px; margin-top: 20px; display: none; }
    </style>
</head>
<body>

    <div class="header">
        <div class="brand">Order Tracking</div>
        <a href="menu.php"><button class="nav-btn">Back to Menu</button></a>
    </div>

    <div class="container">
        <div class="status-card">
            <h2>Order Status</h2>
            <div class="order-id">Order #<span id="orderIdDisplay">---</span></div>
            
            <div class="timer" id="timerDisplay">--:--</div>
            <p id="statusLabel">Checking status...</p>

            <div class="timeline">
                <div class="step" id="step-pending">
                    <h4>Order Received</h4>
                    <p>Your order has been sent to the kitchen.</p>
                </div>
                <div class="step" id="step-preparing">
                    <h4>Preparing</h4>
                    <p>Our chefs are working on your meal.</p>
                </div>
                <div class="step" id="step-ready">
                    <h4>Ready to Serve</h4>
                    <p>Your food is being plated.</p>
                </div>
                <div class="step" id="step-served">
                    <h4>Served</h4>
                    <p>Enjoy your meal!</p>
                </div>
            </div>

            <div class="msg-box" id="successMsg">
                Your order is complete! <br> You earned <strong>10 Loyalty Points</strong>.
            </div>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = parseInt(urlParams.get('orderId'));
        
        document.getElementById('orderIdDisplay').innerText = orderId || 'Unknown';

        async function checkStatus() {
            if(!orderId) return;
            
            try {
                const res = await fetch(`tracking.php?ajax_order_id=${orderId}`);
                const data = await res.json();
                
                if(data.error) {
                    document.getElementById('statusLabel').innerText = "Order not found";
                    return;
                }

                updateTimeline(data.status, data.target_time);
                
            } catch(e) { console.error(e); }
        }

        function updateTimeline(status, targetTime) {
            // Update Steps UI
            const steps = ['pending', 'preparing', 'ready', 'served'];
            let activeFound = false;

            steps.forEach(step => {
                const el = document.getElementById(`step-${step}`);
                el.classList.remove('active', 'completed');
                
                if (step === status) {
                    el.classList.add('active');
                    activeFound = true;
                } else if (!activeFound) {
                    el.classList.add('completed'); 
                }
            });

            // Update Timer & Label
            const timer = document.getElementById('timerDisplay');
            const label = document.getElementById('statusLabel');

            if (status === 'pending') {
                timer.innerText = "...";
                label.innerText = "Waiting for Kitchen...";
                timer.style.color = "#777";
            } 
            else if (status === 'preparing') {
                label.innerText = "Chefs are cooking";
                timer.style.color = "var(--primary)";
                
                // Calculate Countdown if target time exists
                if (targetTime) {
                    const now = new Date().getTime();
                    const target = new Date(targetTime).getTime();
                    const diff = target - now;
                    
                    if(diff > 0) {
                        const m = Math.floor(diff / 60000);
                        timer.innerText = `${m} mins`;
                    } else {
                        timer.innerText = "Soon!";
                    }
                } else {
                    timer.innerText = "Cooking";
                }
            } 
            else if (status === 'ready') {
                timer.innerText = "Ready!";
                label.innerText = "Waiter is bringing your order";
                timer.style.color = "var(--secondary)";
            } 
            else if (status === 'served') {
                timer.innerText = "Done";
                label.innerText = "Order Completed";
                timer.style.color = "#2ecc71";
                document.getElementById('successMsg').style.display = 'block';
            }
        }

        // Poll every 5 seconds
        setInterval(checkStatus, 5000);
        checkStatus(); 
    </script>
</body>
</html>