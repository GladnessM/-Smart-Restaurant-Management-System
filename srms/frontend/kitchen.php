<?php
session_start();
require_once '../backend/backend_system/config/db_config.php';

// --- 1. HANDLE AJAX UPDATES (SMART SPLIT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $orderId = intval($_POST['order_id']);
    $status = $_POST['status'];
    $minutes = isset($_POST['minutes']) ? intval($_POST['minutes']) : 0;
    $type = $_POST['type']; // 'food' or 'drink'

    // Logic: Update order_items based on Category
    // If type is 'food', we update items that are NOT Drinks. 
    // If type is 'drink', we update items that ARE Drinks.
    
    $operator = ($type === 'drink') ? "=" : "!=";
    
    // We use a JOIN update to target specific items in this order
    $sql = "UPDATE order_items oi
            JOIN menu_items m ON oi.menu_item_id = m.id
            SET oi.item_status = ?
            WHERE oi.order_id = ? AND m.category $operator 'Drinks'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $orderId);
    
    // Optional: We can still set the timer on the main order for visual reference
    if ($status === 'preparing' && $minutes > 0) {
        $conn->query("UPDATE orders SET target_time = DATE_ADD(NOW(), INTERVAL $minutes MINUTE) WHERE id = $orderId");
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

// --- 2. FETCH FOOD ORDERS ---
// We check oi.item_status instead of o.status
$sql = "SELECT o.id as order_id, o.table_id, o.target_time, o.created_at,
               oi.quantity, oi.item_status, m.name as item_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE m.category != 'Drinks' 
        AND oi.item_status IN ('pending', 'preparing', 'ready')
        ORDER BY o.created_at ASC";

$result = $conn->query($sql);
$orders = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        $id = $row['order_id'];
        // Grouping Logic
        if (!isset($orders[$id])) {
            $orders[$id] = [
                'id' => $id,
                'table' => $row['table_id'],
                // We take the status of the first item as the "Ticket Status" for simplicity
                'status' => $row['item_status'], 
                'time' => date('H:i', strtotime($row['created_at'])),
                'target_time' => $row['target_time'] ? date('Y-m-d\TH:i:s', strtotime($row['target_time'])) : null,
                'items' => []
            ];
        }
        $orders[$id]['items'][] = ['qty' => $row['quantity'], 'name' => $row['item_name']];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kitchen (Food)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #2c3e50; --card: #34495e; --text: #ecf0f1; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #E85D46; padding-bottom: 10px; }
        .brand { font-size: 1.8rem; font-weight: bold; color: #FE9C00; }
        .ticket-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .ticket { background: var(--card); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.3); border-top: 5px solid #e74c3c; }
        .ticket.preparing { border-top-color: #f1c40f; }
        .ticket.ready { border-top-color: #2ecc71; opacity: 0.7; }
        .ticket-header { background: rgba(0,0,0,0.2); padding: 12px; display: flex; justify-content: space-between; font-weight: bold; }
        .ticket-body { padding: 15px; min-height: 100px; }
        .item-row { margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px; font-size: 1.1rem; }
        .qty-badge { background: #fff; color: #333; padding: 2px 8px; border-radius: 4px; font-weight: bold; margin-right: 10px; }
        .prep-controls { padding: 15px; background: rgba(0,0,0,0.1); display: flex; gap: 10px; align-items: center; }
        .time-input { width: 60px; padding: 10px; border-radius: 5px; border: none; text-align: center; font-weight: bold; }
        .countdown-display { width: 100%; text-align: center; font-size: 1.5rem; font-weight: bold; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .countdown-display.overdue { color: #e74c3c; animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0.5; } }
        .btn { flex: 1; padding: 12px; border: none; cursor: pointer; font-weight: bold; font-size: 1rem; border-radius: 5px; color: white; }
        .btn-start { background: #f1c40f; color: #333; }
        .btn-done { background: #2ecc71; }
        .btn-clear { background: #7f8c8d; }
        .empty-state { grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand"><i class="fas fa-utensils"></i> KITCHEN (Food)</div>
        <div id="clock" style="font-size: 1.2rem;">00:00:00</div>
    </div>

    <div class="ticket-grid">
        <?php if (empty($orders)): ?>
            <div class="empty-state"><h1>No Food Orders</h1></div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="ticket <?php echo $order['status']; ?>">
                    <div class="ticket-header">
                        <span>Table <?php echo $order['table']; ?></span>
                        <span>#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="ticket-body">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="item-row"><span class="qty-badge"><?php echo $item['qty']; ?></span><?php echo $item['name']; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="prep-controls" style="display:block">
                        <?php if ($order['status'] === 'pending'): ?>
                            <div style="display:flex; gap:10px">
                                <input type="number" id="time-<?php echo $order['id']; ?>" class="time-input" value="15" min="1">
                                <button class="btn btn-start" onclick="startCooking(<?php echo $order['id']; ?>)">Start</button>
                            </div>
                        <?php elseif ($order['status'] === 'preparing'): ?>
                            <div class="countdown-display" id="timer-<?php echo $order['id']; ?>" data-target="<?php echo $order['target_time']; ?>">...</div>
                            <button class="btn btn-done" onclick="updateStatus(<?php echo $order['id']; ?>, 'ready')">Ready</button>
                        <?php elseif ($order['status'] === 'ready'): ?>
                            <button class="btn btn-clear" onclick="updateStatus(<?php echo $order['id']; ?>, 'served')">Served</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        setInterval(() => { document.getElementById('clock').innerText = new Date().toLocaleTimeString(); }, 1000);
        setTimeout(() => { window.location.reload(); }, 15000); 

        function updateTimers() {
            const timers = document.querySelectorAll('.countdown-display');
            const now = new Date().getTime();
            timers.forEach(timer => {
                const target = new Date(timer.getAttribute('data-target')).getTime();
                const distance = target - now;
                if (distance < 0) {
                    timer.innerHTML = "OVERDUE"; timer.classList.add('overdue');
                } else {
                    const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((distance % (1000 * 60)) / 1000);
                    timer.innerHTML = `${m}m ${s}s`; timer.classList.remove('overdue');
                }
            });
        }
        setInterval(updateTimers, 1000); updateTimers();

        // UPDATED: Send type='food'
        async function startCooking(id) {
            const min = document.getElementById('time-'+id).value;
            updateStatus(id, 'preparing', min);
        }
        async function updateStatus(id, status, min = 0) {
            if(!confirm("Confirm?")) return;
            const fd = new FormData(); 
            fd.append('order_id', id); 
            fd.append('status', status); 
            fd.append('minutes', min);
            fd.append('type', 'food'); // Identify this as a FOOD update
            
            await fetch('kitchen.php', { method: 'POST', body: fd });
            window.location.reload();
        }
    </script>
</body>
</html>