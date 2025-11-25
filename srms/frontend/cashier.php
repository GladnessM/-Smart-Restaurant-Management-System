<?php
session_start();
require_once '../backend/backend_system/config/db_config.php';

// --- 1. AUTHENTICATION ---
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff_login.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: staff_login.php");
    exit;
}

// --- 2. HANDLE PAYMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = intval($input['order_id']);
    
    $conn->begin_transaction();
    try {
        // FIX: Removed "payment_method = 'cash'"
        // We now keep the method the customer selected during checkout
        $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        // Free up the Table
        $stmtT = $conn->prepare("UPDATE tables SET status = 'free' WHERE id = (SELECT table_id FROM orders WHERE id = ?)");
        $stmtT->bind_param("i", $orderId);
        $stmtT->execute();
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --- 3. FETCH UNPAID ORDERS ---
// FIX: Added 'payment_method' to the SELECT query
$res = $conn->query("SELECT id, table_id, status, payment_method FROM orders WHERE status != 'paid' AND status != 'completed'");

$orders = [];
if ($res) {
    while($row = $res->fetch_assoc()) {
        $oid = $row['id'];
        
        // Calculate total
        $tRes = $conn->query("SELECT SUM(price * quantity) as total FROM order_items WHERE order_id = $oid");
        $total = $tRes->fetch_assoc()['total'] ?? 0;
        
        // Get items string
        $iRes = $conn->query("SELECT m.name, oi.quantity FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id WHERE oi.order_id = $oid");
        $items = [];
        while($iRow = $iRes->fetch_assoc()) {
            $items[] = $iRow['quantity'] . "x " . $iRow['name'];
        }
        
        $orders[] = [
            'id' => $oid,
            'tableId' => $row['table_id'],
            'total' => $total,
            'method' => ucfirst($row['payment_method']), // Capitalize (e.g., "Mpesa")
            'items' => implode(", ", $items)
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cashier | DineEaseRestaurant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #E85D46; --bg: #f7f8f9; --text: #222; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); display: flex; height: 100vh; margin:0; }
        
        .list-col { width: 350px; background: white; border-right: 1px solid #ddd; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; }
        .main-col { flex: 1; padding: 40px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        
        .order-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: 0.2s; }
        .order-item:hover { background: #f0fdf4; }
        .order-item.active { background: #e0f2f1; border-left: 4px solid var(--brand); }
        
        .bill-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        
        .total-row { display: flex; justify-content: space-between; font-size: 1.5rem; font-weight: bold; margin: 20px 0; border-top: 2px dashed #ddd; padding-top: 20px; }
        
        input { width: 100%; padding: 15px; font-size: 1.2rem; margin-bottom: 20px; border: 2px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-pay { width: 100%; padding: 15px; background: var(--brand); color: white; font-size: 1.2rem; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; }
        
        .logout-btn { margin-top: auto; padding: 10px; background: #333; color: white; text-align: center; text-decoration: none; border-radius: 5px; }
        
        .method-badge { background: #eee; padding: 5px 10px; border-radius: 5px; font-size: 0.9rem; }
    </style>
</head>
<body>

    <div class="list-col">
        <h3><i class="fas fa-cash-register"></i> Pending Payment</h3>
        <div id="paymentList">
            <?php if(empty($orders)): ?>
                <p style="color:#999; text-align:center; margin-top:20px;">No pending bills.</p>
            <?php else: ?>
                <?php foreach($orders as $order): ?>
                    <div class="order-item" onclick='selectOrder(<?php echo json_encode($order); ?>)'>
                        <div style="display:flex; justify-content:space-between">
                            <strong>Table <?php echo $order['tableId']; ?></strong>
                            <span style="font-weight:bold; color:var(--brand)">KSH <?php echo $order['total']; ?></span>
                        </div>
                        <div style="font-size:0.8rem; color:#666; display:flex; justify-content:space-between; margin-top:5px;">
                            <span>#<?php echo $order['id']; ?></span>
                            <span class="method-badge"><?php echo $order['method']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="cashier.php?logout=true" class="logout-btn">Logout</a>
    </div>

    <div class="main-col">
        <div class="bill-card" id="billArea" style="display:none;">
            <h2 style="text-align:center; color:var(--brand)">Table <span id="billTable"></span></h2>
            <p style="text-align:center; color:#777">Order #<span id="billOrder"></span></p>
            
            <div style="text-align:center; margin-bottom:15px;">
                Method: <strong id="billMethod" style="color:var(--brand); font-size:1.2rem"></strong>
            </div>

            <div id="billItems" style="margin: 20px 0; background:#f9f9f9; padding:15px; border-radius:8px; max-height:150px; overflow-y:auto;">
                </div>

            <div class="total-row">
                <span>Total Due:</span>
                <span>KSH <span id="billTotal">0</span></span>
            </div>

            <label>Amount Received</label>
            <input type="number" id="cashInput" oninput="calcChange()" placeholder="Enter Amount">
            
            <div style="margin-bottom:20px; font-weight:bold; color:#27ae60; font-size:1.2rem; text-align:right;">
                Change: KSH <span id="changeDisplay">0</span>
            </div>

            <button class="btn-pay" onclick="processPayment()">CONFIRM PAYMENT</button>
        </div>
        <div id="placeholder" style="color:#999; text-align:center">
            <i class="fas fa-receipt fa-3x"></i><br><br>
            Select an order from the list to proceed
        </div>
    </div>

    <script>
        let currentOrder = null;

        function selectOrder(order) {
            currentOrder = order;
            document.getElementById('billArea').style.display = 'block';
            document.getElementById('placeholder').style.display = 'none';
            
            document.getElementById('billTable').innerText = order.tableId;
            document.getElementById('billOrder').innerText = order.id;
            document.getElementById('billMethod').innerText = order.method; // Display Method
            document.getElementById('billTotal').innerText = order.total;
            document.getElementById('billItems').innerText = order.items;
            
            document.getElementById('cashInput').value = '';
            document.getElementById('changeDisplay').innerText = '0';
        }

        function calcChange() {
            const total = parseFloat(document.getElementById('billTotal').innerText);
            const given = parseFloat(document.getElementById('cashInput').value) || 0;
            const change = Math.max(0, given - total);
            document.getElementById('changeDisplay').innerText = change.toFixed(2);
        }

        async function processPayment() {
            if(!currentOrder) return;
            const given = parseFloat(document.getElementById('cashInput').value) || 0;
            const total = parseFloat(currentOrder.total);

            // Optional: Enforce amount entry even for Mpesa to verify receipt
            if (given < total) {
                alert("Amount received is less than total!");
                return;
            }

            if(!confirm(`Confirm ${currentOrder.method} payment for Order #${currentOrder.id}?`)) return;

            try {
                const res = await fetch('cashier.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: currentOrder.id })
                });
                
                const json = await res.json();
                if(json.success) {
                    alert("Payment Confirmed! Table is now free.");
                    window.location.reload();
                } else {
                    alert("Error: " + json.error);
                }
            } catch(e) { console.error(e); }
        }
        
        // Auto Refresh every 15s
        setTimeout(() => { window.location.reload(); }, 15000);
    </script>
</body>
</html>