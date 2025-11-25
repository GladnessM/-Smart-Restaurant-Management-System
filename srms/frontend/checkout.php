<?php
session_start();
require_once '../backend/backend_system/config/db_config.php';

// --- 1. HANDLE AJAX ORDER SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'No data received']);
        exit;
    }

    $tableId = intval($input['tableId']);
    $paymentMethod = $input['paymentMethod'];
    $cart = $input['cart'];
    $customerId = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;
    $totalAmount = 0;

    // Calculate total server-side for security
    foreach ($cart as $item) {
        $totalAmount += ($item['price'] * $item['qty']);
    }

    // Start Transaction (All or Nothing)
    $conn->begin_transaction();

    try {
        // A. Insert into ORDERS table
        $stmt = $conn->prepare("INSERT INTO orders (table_id, customer_id, status, total, payment_method, created_at) VALUES (?, ?, 'pending', ?, ?, NOW())");
        $stmt->bind_param("iids", $tableId, $customerId, $totalAmount, $paymentMethod); // 'd' for double/decimal
        $stmt->execute();
        $orderId = $stmt->insert_id;

        // B. Insert into ORDER_ITEMS table
        $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $item) {
            $stmtItem->bind_param("iiid", $orderId, $item['id'], $item['qty'], $item['price']);
            $stmtItem->execute();
        }

        // C. Award Points (if logged in)
        if ($customerId) {
            $pointsAwarded = 10; // Fixed 10 points per order
            $stmtPoints = $conn->prepare("UPDATE customers SET points = points + ? WHERE id = ?");
            $stmtPoints->bind_param("ii", $pointsAwarded, $customerId);
            $stmtPoints->execute();
        }

        // D. Update Table Status to Occupied
        $stmtTable = $conn->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
        $stmtTable->bind_param("i", $tableId);
        $stmtTable->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'orderId' => $orderId]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | DineEaseRestaurant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #E85D46; --secondary: #C6AD67; --dark: #333; --light: #FFF5F0; --white: #ffffff; }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { font-family: 'Poppins', sans-serif; background: var(--light); color: var(--dark); padding-top: 80px; }
        
        /* HEADER (Shared Style) */
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 15px 5%; 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(10px); 
            box-shadow: 0 2px 15px rgba(0,0,0,0.05); 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            z-index: 1000; 
        }
        
        .brand { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; color: var(--secondary); display: flex; align-items: center; gap: 10px; }
        
        .nav-btn { 
            padding: 10px 20px; 
            border: 1px solid #eee; 
            background: white; 
            color: var(--dark); 
            font-weight: 500; 
            font-size: 0.95rem; 
            cursor: pointer; 
            font-family: 'Poppins', sans-serif; 
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        .nav-btn:hover { border-color: var(--primary); color: var(--primary); transform: translateX(-2px); }
        
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        
        .checkout-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        h2 { font-family: 'Playfair Display', serif; color: var(--primary); margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        
        .order-summary { margin-bottom: 30px; }
        .item-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f9f9f9; }
        .item-name { font-weight: 500; color: var(--dark); }
        .item-price { font-weight: 600; color: #777; }
        
        .total-row { display: flex; justify-content: space-between; font-size: 1.4rem; font-weight: 700; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--secondary); color: var(--dark); }
        
        .payment-section { margin-top: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 1rem; }
        
        .btn-confirm { width: 100%; padding: 15px; background: var(--primary); color: white; border: none; border-radius: 10px; font-size: 1.2rem; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 20px; }
        .btn-confirm:hover { background: #d64d38; box-shadow: 0 5px 15px rgba(232, 93, 70, 0.3); }
        .btn-confirm:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; }
        
        .back-link { display: block; text-align: center; margin-top: 20px; color: #888; text-decoration: none; font-size: 0.9rem; }
        
        .table-info {
            background: #fff8e1;
            color: #f39c12;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            border: 1px solid #ffe0b2;
        }

        .empty-cart-msg { text-align: center; padding: 30px; color: #888; font-style: italic; }
    </style>
</head>
<body>

    <div class="header">
        <div class="brand"><i class="fas fa-utensils" style="color:var(--primary)"></i> Dine<span>Ease</span></div>
        <div><a href="menu.php"><button class="nav-btn">Back to Menu</button></a></div>
    </div>

    <div class="container">
        <div class="checkout-card">
            <h2>Finalize Order</h2>
            
            <div id="tableDisplay" class="table-info">
                Detecting Table...
            </div>
            
            <div class="order-summary" id="summaryList">
                </div>
            
            <div class="total-row">
                <span>Total Amount</span>
                <span id="finalTotal">Ksh 0</span>
            </div>

            <div class="payment-section">
                <h3>Payment Details</h3>
                
                <div class="form-group">
                    <label>Payment Method</label>
                    <select id="paymentMethod">
                        <option value="mpesa">M-Pesa</option>
                        <option value="cash">Cash</option>
                        <option value="card">Credit Card</option>
                    </select>
                </div>
                
                <button class="btn-confirm" id="confirmBtn" onclick="confirmOrder()">Confirm & Pay</button>
                <a href="menu.php?openCart=true" class="back-link">Modify Order</a>
            </div>
        </div>
    </div>

    <script>
        // --- SESSION HELPER ---
        // We check if there is a username in LocalStorage to determine cart key
        // But actual authentication is handled by PHP Session
        const currentUsername = "<?php echo isset($_SESSION['customer_full_name']) ? $_SESSION['customer_full_name'] : ''; ?>";
        
        // Use logic to get the cart key based on who is logged in
        // Note: You might need to adjust this if you store 'Guest' carts differently
        function getStorageKey() {
            // Since menu.php saves user object to JS const 'currentUser', let's rely on cart naming convention
            // Or simpler: check both and see which has items
            if(localStorage.getItem('Guest_cart') && JSON.parse(localStorage.getItem('Guest_cart')).length > 0) return 'Guest_cart';
            
            // Try to find a key ending in _cart
            for(let i=0; i<localStorage.length; i++) {
                const key = localStorage.key(i);
                if(key.endsWith('_cart')) return key;
            }
            return 'guest_cart'; // Default
        }

        const storageKey = getStorageKey();
        
        // 1. RETRIEVE TABLE ID
        const storedTable = localStorage.getItem('currentTable');
        const tableId = storedTable ? parseInt(storedTable) : 1; // Default to 1
        
        // Update UI
        document.getElementById('tableDisplay').innerText = `Ordering for Table ${tableId}`;

        function loadCart() {
            const cart = JSON.parse(localStorage.getItem(storageKey) || '[]');
            const list = document.getElementById('summaryList');
            const totalEl = document.getElementById('finalTotal');
            const confirmBtn = document.getElementById('confirmBtn');
            
            if(cart.length === 0) {
                list.innerHTML = '<div class="empty-cart-msg">Your cart is currently empty. <br><a href="menu.php" style="color:var(--primary)">Go back to Menu</a></div>';
                totalEl.innerText = 'Ksh 0';
                confirmBtn.disabled = true; 
                confirmBtn.innerText = "Cart is Empty";
                return;
            }

            confirmBtn.disabled = false;
            confirmBtn.innerText = "Confirm & Pay";

            list.innerHTML = '';
            let total = 0;

            cart.forEach(item => {
                const itemTotal = item.price * item.qty;
                total += itemTotal;
                list.innerHTML += `
                    <div class="item-row">
                        <div class="item-name">${item.name} <span style="font-size:0.8rem; color:#999">x${item.qty}</span></div>
                        <div class="item-price">Ksh ${itemTotal}</div>
                    </div>
                `;
            });
            
            totalEl.innerText = `Ksh ${total}`;
        }

        async function confirmOrder() {
            const method = document.getElementById('paymentMethod').value;
            const cart = JSON.parse(localStorage.getItem(storageKey) || '[]');
            const btn = document.getElementById('confirmBtn');
            
            if(cart.length === 0) return;

            btn.disabled = true;
            btn.innerText = "Processing...";

            // Prepare Payload
            const payload = {
                tableId: tableId,
                paymentMethod: method,
                cart: cart
            };

            try {
                const response = await fetch('checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    // Clear Cart
                    localStorage.removeItem(storageKey);
                    alert("Payment Successful! Order #" + result.orderId + " sent to kitchen.");
                    window.location.href = `tracking.php?orderId=${result.orderId}`;
                } else {
                    alert("Error: " + result.error);
                    btn.disabled = false;
                    btn.innerText = "Confirm & Pay";
                }
            } catch (error) {
                console.error(error);
                alert("Connection failed. Please try again.");
                btn.disabled = false;
                btn.innerText = "Confirm & Pay";
            }
        }

        loadCart();
    </script>
</body>
</html>