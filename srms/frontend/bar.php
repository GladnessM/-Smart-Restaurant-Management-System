<?php
session_start();
require_once '../backend/backend_system/config/db_config.php';

// --- FETCH DRINK ORDERS ---
// We filter for 'Drinks' and query the order_items table for status
$sql = "SELECT o.id as order_id, o.table_id, o.target_time, o.created_at,
               oi.quantity, oi.item_status, m.name as item_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE m.category = 'Drinks' 
        AND oi.item_status IN ('pending', 'preparing', 'ready')
        ORDER BY o.created_at ASC"; 

$result = $conn->query($sql);
$orders = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        $id = $row['order_id'];
        if (!isset($orders[$id])) {
            $orders[$id] = [
                'id' => $id,
                'table' => $row['table_id'],
                'status' => $row['item_status'], // Using Item Status
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
    <title>Bar Display</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #2d3436; --card: #353b48; --text: #dfe6e9; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #a29bfe; padding-bottom: 10px; }
        .brand { font-size: 1.8rem; font-weight: bold; color: #a29bfe; }
        .ticket-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .ticket { background: var(--card); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.3); border-top: 5px solid #e74c3c; }
        .ticket.preparing { border-top-color: #0984e3; } 
        .ticket.ready { border-top-color: #00cec9; opacity: 0.7; }
        .ticket-header { background: rgba(0,0,0,0.2); padding: 10px 15px; display: flex; justify-content: space-between; font-weight: bold; }
        .ticket-body { padding: 15px; min-height:80px; }
        .item-row { margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px; }
        .qty-badge { background: #6c5ce7; color: white; padding: 2px 8px; border-radius: 4px; margin-right: 10px; font-weight:bold; }
        .prep-controls { padding: 15px; background: rgba(0,0,0,0.1); }
        .btn { width: 100%; padding: 12px; border: none; cursor: pointer; font-weight: bold; font-size: 1rem; border-radius:5px; color:white; margin-top:5px; }
        .btn-start { background: #0984e3; }
        .btn-done { background: #00cec9; }
        .btn-clear { background: #636e72; }
        .empty-state { grid-column: 1/-1; text-align: center; padding: 50px; opacity: 0.5; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand"><i class="fas fa-cocktail"></i> BAR DISPLAY</div>
        <div id="clock" style="font-size: 1.2rem;">00:00:00</div>
    </div>

    <div class="ticket-grid">
        <?php if (empty($orders)): ?>
            <div class="empty-state"><h3>No Drinks Ordered</h3></div>
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
                    <div class="prep-controls">
                        <?php if ($order['status'] === 'pending'): ?>
                            <button class="btn btn-start" onclick="updateStatus(<?php echo $order['id']; ?>, 'preparing')">Mix Drinks</button>
                        <?php elseif ($order['status'] === 'preparing'): ?>
                            <div style="text-align:center; margin-bottom:5px; font-weight:bold">Mixing...</div>
                            <button class="btn btn-done" onclick="updateStatus(<?php echo $order['id']; ?>, 'ready')">Drinks Up</button>
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

        async function updateStatus(id, status) {
            if(!confirm("Confirm?")) return;
            const fd = new FormData(); 
            fd.append('order_id', id); 
            fd.append('status', status); 
            fd.append('type', 'drink'); // IMPORTANT: Tells kitchen.php to update drink items
            
            await fetch('kitchen.php', { method: 'POST', body: fd });
            window.location.reload();
        }
    </script>
</body>
</html>