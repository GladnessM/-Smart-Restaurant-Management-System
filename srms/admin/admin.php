<?php
session_start();
require_once '../backend/backend_system/config/db_config.php';

// ============================================
// POST REQUESTS: Handle Actions (AJAX)
// ============================================
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => 'Unknown error'];

    try {
        // --- 1. ADD STAFF ---
        if ($_POST['action'] === 'add_staff') {
            $name = $conn->real_escape_string($_POST['full_name']);
            $role = $conn->real_escape_string($_POST['role']);
            $code = rand(1000, 9999); // Generates the code
            
            $stmt = $conn->prepare("INSERT INTO staff (full_name, role, login_code, active) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("ssi", $name, $role, $code);
            
            if ($stmt->execute()) {
                // FIX: Matched keys to what JS expects ('code', not 'login_code')
                $response = ['success' => true, 'staff' => [
                    'id' => $stmt->insert_id, 
                    'full_name' => $name, 
                    'role' => $role, 
                    'code' => $code, 
                    'active' => true
                ]];
            } else { throw new Exception($conn->error); }
        }

        // --- 2. ADD TABLE ---
        elseif ($_POST['action'] === 'add_table') {
            $seats = intval($_POST['seats']);
            $waiterId = intval($_POST['waiter_id']);

            if ($seats < 1) throw new Exception('Table must have at least 1 seat');

            $countResult = $conn->query("SELECT count(*) as c FROM tables");
            $tableNum = $countResult->fetch_assoc()['c'] + 1;
            
            $stmt = $conn->prepare("INSERT INTO tables (table_number, seats, status, waiter_id) VALUES (?, ?, 'free', ?)");
            $stmt->bind_param("iii", $tableNum, $seats, $waiterId);
            
            if ($stmt->execute()) {
                $wName = "Unassigned";
                if($waiterId > 0){
                    $wRes = $conn->query("SELECT full_name FROM staff WHERE id = $waiterId");
                    if($wRow = $wRes->fetch_assoc()) $wName = $wRow['full_name'];
                }
                $response = ['success' => true, 'table' => ['id' => $stmt->insert_id, 'capacity' => $seats, 'status' => 'free', 'waiter' => $wName, 'waiter_id' => $waiterId]];
            } else { throw new Exception($conn->error); }
        }

        // --- 3. UPDATE TABLE ---
        elseif ($_POST['action'] === 'update_table') {
            $tableId = intval($_POST['table_id']);
            $waiterId = intval($_POST['waiter_id']);
            $status = $_POST['status']; 

            $stmt = $conn->prepare("UPDATE tables SET waiter_id = ?, status = ? WHERE id = ?");
            $stmt->bind_param("isi", $waiterId, $status, $tableId);
            
            if ($stmt->execute()) {
                if($status === 'occupied') {
                   $conn->query("INSERT INTO orders (table_id, status, created_at) VALUES ($tableId, 'pending', NOW())");
                }
                
                $wName = "Unassigned";
                $wRes = $conn->query("SELECT full_name FROM staff WHERE id = $waiterId");
                if($wRow = $wRes->fetch_assoc()) $wName = $wRow['full_name'];

                $response = ['success' => true, 'waiter_name' => $wName];
            } else { throw new Exception($conn->error); }
        }

        // --- 4. GET WAITERS ---
        elseif ($_POST['action'] === 'get_waiters') {
            $result = $conn->query("SELECT id, full_name as name FROM staff WHERE role = 'Waiter' AND active = 1");
            $waiters = [];
            while($row = $result->fetch_assoc()) { $waiters[] = $row; }
            $response = ['success' => true, 'list' => $waiters];
        }

        // --- 5. ADD MENU ITEM ---
        elseif ($_POST['action'] === 'add_menu_item') {
            $name = $conn->real_escape_string($_POST['name']);
            $price = floatval($_POST['price']);
            $category = $conn->real_escape_string($_POST['category']);
            $imgUrl = 'default_food.png'; 

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'img/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                    $imgUrl = $fileName;
                }
            }

            $stmt = $conn->prepare("INSERT INTO menu_items (name, category, price, img_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $name, $category, $price, $imgUrl);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'menu' => ['id' => $stmt->insert_id, 'name' => $name, 'category' => $category, 'price' => $price, 'image' => $imgUrl]];
            } else { throw new Exception($conn->error); }
        }

        // --- 6. STAFF TOGGLE/REMOVE ---
        elseif ($_POST['action'] === 'toggle_staff') {
            $code = intval($_POST['code']);
            // FIX: Using IF statement in SQL is safer for toggles
            $conn->query("UPDATE staff SET active = IF(active=1, 0, 1) WHERE login_code = $code");
            $response = ['success' => true];
        }
        elseif ($_POST['action'] === 'remove_staff') {
            $code = intval($_POST['code']);
            $conn->query("DELETE FROM staff WHERE login_code = $code");
            $response = ['success' => true];
        }

        // --- 7. GET REAL-TIME DASHBOARD STATS ---
        elseif ($_POST['action'] === 'get_dashboard_stats') {
            
            // A. Revenue: Sum of 'total_amount' where status is 'paid'
            // (Ensure your column in the database is named 'total_amount')
            $revResult = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status = 'paid'");
            $revenue = $revResult->fetch_assoc()['revenue'] ?? 0;

            // B. Active Orders (Not paid yet)
            $ordResult = $conn->query("SELECT count(*) as c FROM orders WHERE status != 'paid'");
            $activeOrders = $ordResult->fetch_assoc()['c'];

            // C. Occupied Tables
            $occResult = $conn->query("SELECT count(*) as c FROM tables WHERE status = 'occupied'");
            $occupiedTables = $occResult->fetch_assoc()['c'];

            // D. Active Staff
            $staffResult = $conn->query("SELECT count(*) as c FROM staff WHERE active = 1");
            $activeStaff = $staffResult->fetch_assoc()['c'];

            $response = [
                'success' => true, 
                'revenue' => number_format($revenue, 2),
                'active_orders' => $activeOrders,
                'occupied_tables' => $occupiedTables,
                'active_staff' => $activeStaff
            ];
        }

    } catch (Exception $e) { $response['error'] = $e->getMessage(); }

    echo json_encode($response);
    exit;
}

// ============================================
// GET REQUESTS: Fetch Data for Initial Render
// ============================================

// 1. Fetch Menu
$menuData = [];
$result = $conn->query("SELECT * FROM menu_items ORDER BY category, name");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $img = $row['img_url'];
        if (strpos($img, 'http') !== 0 && strpos($img, 'img/') !== 0) {
            $img = 'img/' . $img;
        }
        $menuData[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'price' => $row['price'],
            'image' => $img
        ];
    }
}

// 2. Fetch Tables
$tableData = [];
$result = $conn->query("SELECT t.*, s.full_name as waiter_name FROM tables t LEFT JOIN staff s ON t.waiter_id = s.id");
while ($row = $result->fetch_assoc()) {
    $tableData[] = [
        'id' => $row['id'],
        'capacity' => $row['seats'],
        'status' => $row['status'],
        'waiter' => $row['waiter_name'] ?? 'Unassigned',
        'waiter_id' => $row['waiter_id']
    ];
}

// 3. Fetch Staff
$staffData = [];
$result = $conn->query("SELECT * FROM staff");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $staffData[] = [
            'id' => $row['id'], 
            'full_name' => $row['full_name'],
            'role' => $row['role'],
            'active' => (bool)$row['active'],
            'code' => $row['login_code'] // Ensure this matches JS expectation
        ];
    }
}

// 4. Fetch Orders
$orderData = [];
$result = $conn->query("SELECT o.id, o.table_id, o.status, s.full_name as waiter_name 
                        FROM orders o 
                        LEFT JOIN tables t ON o.table_id = t.id 
                        LEFT JOIN staff s ON t.waiter_id = s.id 
                        WHERE o.status != 'completed' 
                        ORDER BY o.created_at DESC");

if ($result) {
    while($row = $result->fetch_assoc()) {
        $orderData[] = [
            'id' => $row['id'],
            'tableId' => $row['table_id'],
            'waiter' => $row['waiter_name'] ?? 'Unassigned',
            'status' => $row['status']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | DineEase SRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #E85D46; 
            --secondary-color: #C6AD67; 
            --text-dark: #333333;
            --bg-light: #f9f9f9;
            --white: #ffffff;
            --danger: #e74c3c;
            --success: #2ecc71;
            --muted: #7a7a7a;
        }

        * { box-sizing: border-box; }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; background: var(--bg-light); color: var(--text-dark); 
            display: flex; height: 100vh; overflow: hidden;
        }

        .sidebar {
            width: 260px; background: var(--text-dark); color: white;
            display: flex; flex-direction: column; padding: 20px; flex-shrink: 0;
            border-right: 4px solid var(--secondary-color);
        }

        .brand { font-size: 1.6rem; font-weight: bold; color: white; margin-bottom: 40px; display: flex; align-items: center; gap: 12px; }
        .brand span { color: var(--primary-color); }

        .nav-item {
            padding: 14px 18px; color: rgba(255,255,255,0.8); cursor: pointer; text-decoration: none;
            display: flex; align-items: center; gap: 12px; transition: 0.3s; border-radius: 5px; margin-bottom: 8px;
        }
        .nav-item:hover, .nav-item.active { background: var(--primary-color); color: white; }
        .logout-link { margin-top: auto; color: var(--secondary-color) !important; border: 1px solid var(--secondary-color); }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .top-header { background: var(--white); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .scrollable-content { flex: 1; padding: 30px; overflow-y: auto; }

        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-top: 4px solid var(--secondary-color); }
        .stat-number { font-size: 1.8rem; font-weight: bold; margin-top: 5px; }

        .tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
        .table-card {
            background: white; padding: 20px; border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center; 
            border-top: 6px solid var(--success); cursor: pointer; transition: transform 0.2s;
        }
        .table-card:hover { transform: translateY(-5px); }
        .table-card.occupied { border-top-color: var(--primary-color); background: #fff5f5; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; text-transform: uppercase; }
        .status-free { background: #dcfce7; color: var(--success); }
        .status-busy { background: #ffe6e6; color: var(--primary-color); }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        th { background: var(--text-dark); color: white; padding: 16px; text-align: left; }
        td { padding: 16px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }

        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white; font-weight: 600; }
        .btn-primary { background: var(--primary-color); }
        .btn-success { background: var(--success); }
        .btn-danger { background: var(--danger); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }

        input, select { padding: 12px; border: 1px solid #ddd; border-radius: 5px; width: 100%; margin-bottom: 15px; }
        
        .section { display: none; }
        .section.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .staff-list-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .staff-status { font-size: 0.8rem; cursor: pointer; padding: 2px 8px; border-radius: 4px; }
        .status-active { color: var(--success); background: #e6ffed; border: 1px solid var(--success); }
        .status-absent { color: var(--danger); background: #ffe6e6; border: 1px solid var(--danger); }
        
        .menu-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }

        /* MODAL STYLES */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);
            align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: #fefefe; margin: auto; padding: 30px; border: 1px solid #888;
            width: 90%; max-width: 500px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .close-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-modal:hover { color: black; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-utensils"></i> Dine<span>Ease</span></div>
        <div class="nav-item active" onclick="showSection('dashboard')"><i class="fas fa-th-large"></i> Dashboard</div>
        <div class="nav-item" onclick="showSection('tables')"><i class="fas fa-chair"></i> Floor Plan</div>
        <div class="nav-item" onclick="showSection('staff')"><i class="fas fa-users"></i> Staff Dept.</div>
        <div class="nav-item" onclick="showSection('qr-gen')"><i class="fas fa-qrcode"></i> QR Tools</div>
        <div class="nav-item" onclick="showSection('menu')"><i class="fas fa-hamburger"></i> Menu</div>
        <a href="../frontend/staff_login.php" class="nav-item logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h2 style="margin:0;">Overview</h2>
            <div>Logged in as <strong style="color:var(--primary-color);">Manager</strong></div>
        </div>

        <div class="scrollable-content">
            
            <div id="dashboard" class="section active">
                <div class="stats-grid">
                    <div class="stat-card"> <div class="stat-label">Active Orders</div> <div class="stat-number" id="activeOrdersCount">0</div> </div>
                    <div class="stat-card"> <div class="stat-label">Occupied Tables</div> <div class="stat-number" id="occupiedTablesCount">0</div> </div>
                    <div class="stat-card"> <div class="stat-label">Active Staff</div> <div class="stat-number" id="activeStaffCountDash">0</div> </div>
                    <div class="stat-card" style="border-top-color: #27ae60;"> <div class="stat-label">Total Revenue</div> <div class="stat-number" style="color:#27ae60"><small style="font-size:0.5em">KSH</small> <span id="totalRevenueCount">0.00</span></div> 
                </div>
                </div>
                <h3>Live Order Monitor</h3>
                <table>
                    <thead><tr><th>#ID</th><th>Table</th><th>Waitstaff</th><th>Status</th></tr></thead>
                    <tbody id="incomingOrdersTable"></tbody>
                </table>
            </div>

            <div id="tables" class="section">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <h3>Floor Plan</h3>
                    <div style="display:flex; gap:10px">
                        <input type="number" id="newTableSeats" placeholder="Seats" style="width:80px;">
                        <select id="waiterSelect" style="width:150px;"><option value="">Loading...</option></select>
                        <button class="btn btn-primary btn-sm" onclick="addTable()">Add Table</button>
                    </div>
                </div>
                <div style="margin-bottom:15px; font-size:0.9rem; color:#666;">
                    <i class="fas fa-info-circle"></i> Click any table to Edit Waiter or Change Status (Free/Occupied).
                </div>
                <div class="tables-grid" id="tablesGrid"></div>
            </div>

            <div id="staff" class="section">
                <h3>Staff Management</h3>
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
                    <div class="stat-card">
                        <h4>Register New Staff</h4>
                        <input type="text" id="newStaffName" placeholder="Full Name">
                        <select id="newStaffRole">
                            <option value="Waiter">Waiter</option>
                            <option value="Chef">Chef</option>
                            <option value="Bartender">Bartender</option>
                        </select>
                        <button class="btn btn-success" style="width:100%" onclick="addStaff()">Register</button>
                    </div>
                    <div class="stat-card" id="staffList"></div>
                </div>
            </div>

            <div id="qr-gen" class="section">
                <div style="text-align:center; background:white; padding:40px; border-radius:10px;">
                    <h3>Generate Table QR</h3>
                    <select id="qrTableSelect" onchange="generateQR()" style="max-width:200px; margin:auto;"></select>
                    <div style="margin:20px;"><img id="qrImage" src="" alt="QR Code" style="width:150px;"></div>
                    <button class="btn btn-primary" onclick="printQR()">Print Label</button>
                </div>
            </div>

            <div id="menu" class="section">
                <h3>Menu Management</h3>
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
                    <div class="stat-card">
                        <h4>Add New Item</h4>
                        <input type="text" id="newItemName" placeholder="Item Name">
                        <input type="file" id="newItemImage" accept="image/*">
                        <input type="number" id="newItemPrice" placeholder="Price (KSH)">
                        <select id="newItemCategory">
                            <option value="Main Course">Main Course</option>
                            <option value="Drinks">Drinks</option>
                            <option value="Desserts">Desserts</option>
                        </select>
                        <button class="btn btn-success" style="width:100%" onclick="addMenuItem()">Save to Menu</button>
                    </div>
                    <div>
                        <table id="menuTable">
                            <thead><tr><th>Image</th><th>Item</th><th>Category</th><th>Price</th><th>Action</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div id="tableModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Table Details</h2>
            
            <label><strong>Assigned Waiter:</strong></label>
            <select id="modalWaiterSelect"></select>
            
            <label style="margin-top:15px; display:block;"><strong>Current Status:</strong></label>
            <div id="modalStatusBadge" style="margin-bottom:20px;"></div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button class="btn btn-success" id="btnOccupy" style="flex:1" onclick="updateTableStatus('occupied')">Simulate Arrival</button>
                <button class="btn btn-primary" id="btnFree" style="flex:1; background-color:#3498db;" onclick="updateTableStatus('free')">Mark as Free</button>
            </div>
            
            <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">
            
            <button class="btn btn-primary" style="width:100%" onclick="saveTableWaiter()">Save Waiter Assignment</button>
            
            <input type="hidden" id="modalTableId">
        </div>
    </div>

    <script>
        // --- DATA INJECTION ---
        let staffMembers = <?php echo json_encode($staffData); ?>;
        let tables = <?php echo json_encode($tableData); ?>;
        let menuItems = <?php echo json_encode($menuData); ?>;
        let orders = <?php echo json_encode($orderData); ?>;

        // --- AJAX HELPER ---
        async function postData(action, data) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) formData.append(key, data[key]);
            try {
                const res = await fetch('admin.php', { method: 'POST', body: formData });
                const json = await res.json();
                if(json.success) return json;
                else { alert(json.error || "Operation failed"); return null; }
            } catch (e) { console.error(e); alert("Server Error"); return null; }
        }

        // --- UI FUNCTIONS ---
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            if(event && event.currentTarget) event.currentTarget.classList.add('active');
            if(sectionId === 'qr-gen') updateQROptions(); 
            if(sectionId === 'tables') loadWaiters();
        }

        // --- MODAL LOGIC (FIXED: TYPE MISMATCH) ---
        function openTableModal(tableId) {
            // FIX: Use '==' instead of '===' because tableId from HTML is a Number, but JSON ID might be String
            const t = tables.find(tbl => tbl.id == tableId);
            if(!t) return;

            document.getElementById('modalTableId').value = tableId;
            document.getElementById('modalTitle').innerText = `Manage Table ${tableId}`;
            document.getElementById('tableModal').style.display = 'flex';

            const select = document.getElementById('modalWaiterSelect');
            select.innerHTML = '<option value="">Unassigned</option>';
            staffMembers.filter(s => s.role === 'Waiter' && s.active).forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.full_name;
                // FIX: Loose equality for Waiter ID check
                if(s.id == t.waiter_id) opt.selected = true;
                select.appendChild(opt);
            });

            const badge = document.getElementById('modalStatusBadge');
            badge.innerHTML = `<span class="status-badge ${t.status === 'free' ? 'status-free' : 'status-busy'}" style="font-size:1rem">${t.status}</span>`;

            if(t.status === 'free') {
                document.getElementById('btnOccupy').style.display = 'block';
                document.getElementById('btnFree').style.display = 'none';
            } else {
                document.getElementById('btnOccupy').style.display = 'none';
                document.getElementById('btnFree').style.display = 'block';
            }
        }

        function closeModal() { document.getElementById('tableModal').style.display = 'none'; }
        window.onclick = function(event) { if (event.target == document.getElementById('tableModal')) closeModal(); }

        // --- ACTION FUNCTIONS ---
        async function updateTableStatus(newStatus) {
            const tableId = document.getElementById('modalTableId').value;
            const waiterId = document.getElementById('modalWaiterSelect').value;

            const res = await postData('update_table', { table_id: tableId, waiter_id: waiterId, status: newStatus });
            if(res) {
                // FIX: Loose equality again
                const t = tables.find(tbl => tbl.id == tableId);
                t.status = newStatus;
                t.waiter_id = waiterId;
                t.waiter = res.waiter_name;
                
                renderTables();
                closeModal();
                alert(`Table is now ${newStatus}`);
            }
        }

        async function saveTableWaiter() {
            const tableId = document.getElementById('modalTableId').value;
            // FIX: Loose equality
            const t = tables.find(tbl => tbl.id == tableId);
            const waiterId = document.getElementById('modalWaiterSelect').value;
            
            const res = await postData('update_table', { table_id: tableId, waiter_id: waiterId, status: t.status });
            if(res) {
                t.waiter_id = waiterId;
                t.waiter = res.waiter_name;
                renderTables();
                closeModal();
                alert("Waiter updated!");
            }
        }

        async function addStaff() {
            const full_name = document.getElementById('newStaffName').value;
            const role = document.getElementById('newStaffRole').value;
            if(!full_name) return;
            const result = await postData('add_staff', { full_name, role });
            if(result) {
                staffMembers.push(result.staff);
                renderStaffList();
                document.getElementById('newStaffName').value = '';
                // Code is now correctly in result.staff.code
                alert(`Staff Added! Code: ${result.staff.code}`);
            }
        }

        async function toggleStaffStatus(code) {
            await postData('toggle_staff', { code });
            // FIX: Loose equality match for code
            const s = staffMembers.find(m => m.code == code);
            if(s) s.active = !s.active;
            renderStaffList();
        }

        async function addTable() {
            const seats = document.getElementById("newTableSeats").value;
            const waiter_id = document.getElementById("waiterSelect").value;
            if (!seats) return alert("Enter seats");

            const result = await postData('add_table', { seats, waiter_id: waiter_id || 0 });
            if(result) {
                tables.push(result.table);
                renderTables();
                document.getElementById("newTableSeats").value = '';
            }
        }

        async function loadWaiters() {
            const result = await postData('get_waiters', {});
            const select = document.getElementById('waiterSelect');
            select.innerHTML = '<option value="">Assign Waiter...</option>';
            if(result && result.list) {
                result.list.forEach(w => {
                    const opt = document.createElement('option');
                    opt.value = w.id;
                    opt.textContent = w.name;
                    select.appendChild(opt);
                });
            }
        }

        async function addMenuItem() {
            const name = document.getElementById('newItemName').value;
            const price = document.getElementById('newItemPrice').value;
            const cat = document.getElementById('newItemCategory').value;
            const file = document.getElementById('newItemImage').files[0];
            if(!name || !price) return;

            const formData = new FormData();
            formData.append('action', 'add_menu_item');
            formData.append('name', name);
            formData.append('price', price);
            formData.append('category', cat);
            if(file) formData.append('image', file);

            try {
                const res = await fetch('admin.php', { method: 'POST', body: formData });
                const json = await res.json();
                if(json.success) {
                    let newItem = json.menu;
                    if(!newItem.image.includes('img/') && !newItem.image.includes('http')) {
                        newItem.image = 'img/' + newItem.image;
                    }
                    menuItems.push(newItem);
                    renderMenu();
                    document.getElementById('newItemName').value = '';
                    document.getElementById('newItemPrice').value = '';
                    alert("Item Added!");
                } else alert(json.error);
            } catch(e) { console.error(e); }
        }

                    // --- REAL-TIME UPDATER ---
            async function updateDashboard() {
                // Only run if user is looking at the dashboard tab
                if(!document.getElementById('dashboard').classList.contains('active')) return;

                const data = await postData('get_dashboard_stats', {});
                if(data && data.success) {
                    document.getElementById('totalRevenueCount').innerText = data.revenue;
                    document.getElementById('activeOrdersCount').innerText = data.active_orders;
                    document.getElementById('occupiedTablesCount').innerText = data.occupied_tables;
                    document.getElementById('activeStaffCountDash').innerText = data.active_staff;
                }
            }

            // Run immediately on load, then every 5 seconds
            updateDashboard();
            setInterval(updateDashboard, 5000);

        // --- RENDER FUNCTIONS ---
        function renderTables() {
            const grid = document.getElementById('tablesGrid');
            grid.innerHTML = '';
            let occupied = 0;
            tables.forEach(t => {
                if(t.status === 'occupied') occupied++;
                grid.innerHTML += `
                    <div class="table-card ${t.status === 'occupied' ? 'occupied' : ''}" onclick="openTableModal(${t.id})">
                        <h3>Table ${t.id}</h3>
                        <div class="status-badge ${t.status === 'free' ? 'status-free' : 'status-busy'}">${t.status}</div>
                        <p>${t.capacity} Seats</p>
                        ${t.status === 'occupied' ? `<p><strong>${t.waiter || 'No Waiter'}</strong></p>` : ''}
                    </div>`;
            });
            document.getElementById('occupiedTablesCount').innerText = occupied;
        }

        function renderMenu() {
            const tbody = document.querySelector('#menuTable tbody');
            tbody.innerHTML = '';
            menuItems.forEach(item => {
                const placeholder = 'https://via.placeholder.com/50?text=Food';
                tbody.innerHTML += `
                    <tr>
                        <td>
                            <img src="${item.image}" class="menu-thumb" onerror="this.onerror=null;this.src='${placeholder}';">
                        </td>
                        <td>${item.name}</td>
                        <td>${item.category}</td> 
                        <td>KSH ${item.price}</td>
                        <td><button class="btn btn-danger btn-sm">X</button></td>
                    </tr>`;
            });
        }

        function renderStaffList() {
            const list = document.getElementById('staffList');
            list.innerHTML = '';
            let activeCount = 0;
            staffMembers.forEach(s => {
                if(s.active) activeCount++;
                list.innerHTML += `
                    <div class="staff-list-item">
                        <div><strong>${s.full_name}</strong> (${s.role}) <br> <small>Code: ${s.code}</small></div>
                        <span class="staff-status ${s.active ? 'status-active' : 'status-absent'}" 
                              onclick="toggleStaffStatus(${s.code})">${s.active ? 'Active' : 'Absent'}</span>
                    </div>`;
            });
            document.getElementById('activeStaffCountDash').innerText = activeCount;
        }

        function renderOrders() {
            const tbody = document.getElementById('incomingOrdersTable');
            tbody.innerHTML = '';
            orders.forEach(o => {
                tbody.innerHTML += `
                    <tr>
                        <td>#${o.id}</td>
                        <td>Table ${o.tableId}</td>
                        <td>${o.waiter}</td>
                        <td><span class="status-badge status-busy">${o.status}</span></td>
                    </tr>`;
            });
            document.getElementById('activeOrdersCount').innerText = orders.length;
        }

        function updateQROptions() {
            const select = document.getElementById('qrTableSelect');
            select.innerHTML = '';
            tables.forEach(t => { select.innerHTML += `<option value="${t.id}">Table ${t.id}</option>`; });
            generateQR();
        }
        function generateQR() {
            const id = document.getElementById('qrTableSelect').value;
            if(!id) return;
            const data = `http://localhost/smart_restaurant/frontend/menu.php?table=${id}`;
            document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(data)}`;
        }
        function printQR() {
            const img = document.getElementById('qrImage').src;
            const w = window.open('');
            w.document.write(`<img src="${img}" onload="window.print();window.close()">`);
        }

        // Init
        renderTables();
        renderMenu();
        renderStaffList();
        renderOrders();


    </script>
</body>
</html>