<?php
session_start();
require_once '../backend/backend_system/config/db_config.php';

// --- 0. HANDLE LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: staff_login.php"); // Or staff_login.php if you renamed it
    exit;
}

// --- 1. AUTHENTICATION ---
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff_login.html");
    exit;
}

$waiterId = $_SESSION['staff_id'];
$waiterName = "Staff"; 
$stmt = $conn->prepare("SELECT full_name FROM staff WHERE id = ?");
$stmt->bind_param("i", $waiterId);
$stmt->execute();
$res = $stmt->get_result();
if($row = $res->fetch_assoc()) $waiterName = $row['full_name'];


// --- 2. HANDLE AJAX ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    // A. CREATE ORDER
    if (isset($input['action']) && $input['action'] === 'create_order') {
        $tableId = intval($input['tableId']);
        $items = $input['items']; 
        
        if(empty($items)) { echo json_encode(['success'=>false]); exit; }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO orders (table_id, status, created_at) VALUES (?, 'pending', NOW())");
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $orderId = $stmt->insert_id;

            $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price, item_status) VALUES (?, ?, 1, ?, ?)");
            
            foreach ($items as $itemId) {
                $res = $conn->query("SELECT price, category FROM menu_items WHERE id = $itemId");
                $itemData = $res->fetch_assoc();
                $status = 'pending'; 
                $stmtItem->bind_param("iids", $orderId, $itemId, $itemData['price'], $status);
                $stmtItem->execute();
            }
            
            $conn->query("UPDATE tables SET status = 'occupied' WHERE id = $tableId");
            
            $conn->commit();
            echo json_encode(['success'=>true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
        }
        exit;
    }

    // B. MARK DELIVERED (SERVED)
    if (isset($input['action']) && $input['action'] === 'mark_delivered') {
        $orderId = intval($input['orderId']);
        $conn->query("UPDATE order_items SET item_status = 'served' WHERE order_id = $orderId AND item_status = 'ready'");
        
        $res = $conn->query("SELECT count(*) as c FROM order_items WHERE order_id = $orderId AND item_status != 'served'");
        if($res->fetch_assoc()['c'] == 0) {
            $conn->query("UPDATE orders SET status = 'served' WHERE id = $orderId");
        }
        
        echo json_encode(['success'=>true]);
        exit;
    }
}

// --- 3. FETCH DATA FOR UI ---
$sql = "SELECT o.id, o.table_id, 
        GROUP_CONCAT(m.name SEPARATOR ', ') as items_txt,
        SUM(CASE WHEN m.category != 'Drinks' AND oi.item_status = 'ready' THEN 1 ELSE 0 END) as food_ready,
        SUM(CASE WHEN m.category != 'Drinks' AND oi.item_status != 'served' THEN 1 ELSE 0 END) as food_pending,
        SUM(CASE WHEN m.category = 'Drinks' AND oi.item_status = 'ready' THEN 1 ELSE 0 END) as drinks_ready,
        SUM(CASE WHEN m.category = 'Drinks' AND oi.item_status != 'served' THEN 1 ELSE 0 END) as drinks_pending
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN tables t ON o.table_id = t.id
        WHERE t.waiter_id = $waiterId AND o.status != 'paid' AND o.status != 'served'
        GROUP BY o.id
        ORDER BY o.created_at DESC";

$orders = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$tables = $conn->query("SELECT id FROM tables WHERE waiter_id = $waiterId")->fetch_all(MYSQLI_ASSOC);
$menu = $conn->query("SELECT id, name, category, price, img_url FROM menu_items ORDER BY category, name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Waiter Portal | DineEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #E85D46; --accent: #FE9C00; --bg: #f4f7f6; --text: #333; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); margin: 0; }
        
        .navbar { background: var(--brand); color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .nav-icons { display: flex; gap: 15px; align-items: center; }
        .logout-btn { color: white; text-decoration: none; font-size: 1.2rem; cursor: pointer; }
        
        .container { padding: 20px; max-width: 600px; margin: 0 auto; padding-bottom: 80px; }
        .card { background: white; border-radius: 12px; padding: 15px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-left: 5px solid var(--brand); }
        .card h3 { margin: 0 0 10px 0; display: flex; justify-content: space-between; }
        .status-pill { padding: 4px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; display: inline-block; margin-right:5px;}
        .ready { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; animation: pulse 2s infinite; }
        .pending { background: #fff3cd; color: #856404; }
        .served { background: #e2e6ea; color: #6c757d; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(46, 204, 113, 0); } 100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); } }
        
        .fab { position: fixed; bottom: 20px; right: 20px; background: var(--accent); color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 15px rgba(254, 156, 0, 0.4); cursor: pointer; z-index: 100; transition: transform 0.2s; }
        .fab:active { transform: scale(0.9); }
        
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: flex-end; z-index: 200; }
        .modal-content { background: white; width: 100%; max-width: 600px; border-radius: 20px 20px 0 0; padding: 20px; max-height: 80vh; overflow-y: auto; animation: slideUp 0.3s; }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        
        .menu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px; }
        .menu-item { border: 1px solid #ddd; padding: 10px; border-radius: 8px; text-align: center; cursor: pointer; transition: 0.2s; }
        .menu-item.selected { border-color: var(--brand); background: #e0f2f1; color: var(--brand); font-weight: bold; }
        .menu-item img { width: 100%; height: 80px; object-fit: cover; border-radius: 5px; margin-bottom: 5px; }
        
        select { width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #ddd; font-size: 1rem; }
        .btn-submit { width: 100%; padding: 15px; background: var(--brand); color: white; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: bold; margin-top: 20px; cursor: pointer; }
        .btn-serve { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; margin-top: 10px; font-weight: bold; cursor: pointer; }
        .close-modal { float: right; font-size: 1.5rem; cursor: pointer; color: #888; }
    </style>
</head>
<body>

    <div class="navbar">
        <div style="font-weight:bold;"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($waiterName); ?></div>
        
        <div class="nav-icons">
            <span style="font-size:0.9rem"><?php echo count($tables); ?> Tables</span>
            <a href="waiter.php?logout=true" class="logout-btn" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <div class="container" id="orderList">
        <?php if(empty($orders)): ?>
            <div style="text-align:center; margin-top:50px; color:#aaa">
                <i class="fas fa-utensils fa-3x"></i><p>No active orders.</p>
            </div>
        <?php else: ?>
            <?php foreach($orders as $o): ?>
                <?php 
                    $kLabel = ($o['food_ready'] > 0) ? 'Ready' : (($o['food_pending'] > 0) ? 'Prep...' : 'Served/None');
                    $kClass = ($o['food_ready'] > 0) ? 'ready' : (($o['food_pending'] > 0) ? 'pending' : 'served');
                    
                    $bLabel = ($o['drinks_ready'] > 0) ? 'Ready' : (($o['drinks_pending'] > 0) ? 'Prep...' : 'Served/None');
                    $bClass = ($o['drinks_ready'] > 0) ? 'ready' : (($o['drinks_pending'] > 0) ? 'pending' : 'served');
                ?>
                <div class="card">
                    <h3>Table <?php echo $o['table_id']; ?> <span style="font-size:0.8rem; color:#777">#<?php echo $o['id']; ?></span></h3>
                    <p style="margin:5px 0 15px 0; font-size:1rem; color:#555"><?php echo $o['items_txt']; ?></p>
                    <div style="display:flex; gap:15px; border-top:1px solid #eee; padding-top:10px;">
                        <div><small>Kitchen:</small> <span class="status-pill <?php echo $kClass; ?>"><?php echo $kLabel; ?></span></div>
                        <div><small>Bar:</small> <span class="status-pill <?php echo $bClass; ?>"><?php echo $bLabel; ?></span></div>
                    </div>
                    <?php if($o['food_ready'] > 0 || $o['drinks_ready'] > 0): ?>
                        <button class="btn-serve" onclick="markDelivered(<?php echo $o['id']; ?>)">Mark Delivered</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="fab" onclick="openOrderModal()"><i class="fas fa-plus"></i></div>

    <div class="modal-overlay" id="orderModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeOrderModal()">&times;</span>
            <h2 style="margin-top:0; color:var(--brand)">New Order</h2>
            
            <label style="font-weight:bold; display:block; margin-bottom:5px;">Select Table</label>
            <select id="tableSelect">
                <option value="">-- Choose Assigned Table --</option>
                <?php foreach($tables as $t): ?>
                    <option value="<?php echo $t['id']; ?>">Table <?php echo $t['id']; ?></option>
                <?php endforeach; ?>
            </select>

            <label style="font-weight:bold;">Select Items</label>
            <div class="menu-grid" id="menuGrid">
                <?php foreach($menu as $item): ?>
                    <?php 
                        $img = $item['img_url'];
                        if (strpos($img, 'http') !== 0 && strpos($img, 'img/') !== 0) $img = 'img/' . $img;
                    ?>
                    <div class="menu-item" onclick="toggleItem(this, <?php echo $item['id']; ?>)">
                        <img src="<?php echo $img; ?>" onerror="this.src='https://via.placeholder.com/100'">
                        <div><?php echo $item['name']; ?></div>
                        <div style="font-size:0.8rem; color:#777"><?php echo $item['category']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="btn-submit" onclick="submitOrder()">Send to Kitchen</button>
        </div>
    </div>

    <script>
        let selectedItems = [];

        function openOrderModal() {
            document.getElementById('orderModal').style.display = 'flex';
            selectedItems = [];
            document.querySelectorAll('.menu-item').forEach(el => el.classList.remove('selected'));
        }

        function closeOrderModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        function toggleItem(el, id) {
            el.classList.toggle('selected');
            if (el.classList.contains('selected')) {
                selectedItems.push(id);
            } else {
                selectedItems = selectedItems.filter(i => i !== id);
            }
        }

        async function submitOrder() {
            const tableId = document.getElementById('tableSelect').value;
            
            if (!tableId) { alert("Please select a table."); return; }
            if (selectedItems.length === 0) { alert("Please select at least one item."); return; }

            const payload = { action: 'create_order', tableId: tableId, items: selectedItems };

            try {
                const res = await fetch('waiter.php', { 
                    method: 'POST', 
                    body: JSON.stringify(payload),
                    headers: { 'Content-Type': 'application/json' }
                });
                const json = await res.json();
                if(json.success) {
                    alert("Order Sent!");
                    window.location.reload();
                } else {
                    alert("Error: " + json.error);
                }
            } catch(e) { console.error(e); }
        }

        async function markDelivered(orderId) {
            if(!confirm("Confirm delivery of ready items?")) return;
            
            try {
                const res = await fetch('waiter.php', { 
                    method: 'POST', 
                    body: JSON.stringify({ action: 'mark_delivered', orderId: orderId }),
                    headers: { 'Content-Type': 'application/json' }
                });
                const json = await res.json();
                if(json.success) window.location.reload();
            } catch(e) { console.error(e); }
        }
        
        setTimeout(() => { window.location.reload(); }, 15000);
    </script>
</body>
</html>