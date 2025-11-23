// On menu.html load
const params = new URLSearchParams(location.search);
let tableId = params.get('table') || 'Guest';
let seatsInfo = JSON.parse(localStorage.getItem('table_' + tableId)) || null;

if(!seatsInfo){
  // Ask number of seats
  let seats = prompt("How many people are sitting at this table?");
  if(!seats || seats < 1) seats = 1;
  seatsInfo = { table: tableId, seats: seats };
  localStorage.setItem('table_' + tableId, JSON.stringify(seatsInfo));
}

console.log("Table:", tableId, "Seats:", seatsInfo.seats);

// When placing an order, include this info:
document.getElementById('placeOrderBtn').onclick = ()=>{
  const cart = getCart();
  if(cart.length===0) return alert('Cart empty');
  
  const order = {
    id: Date.now(),
    orderNumber: generateOrderNumber(),
    table: seatsInfo.table,
    seats: seatsInfo.seats,
    items: cart.map(c=>({id:c.id,name:c.name,price:c.price,qty:c.qty})),
    total: cart.reduce((s,i)=>s+i.price*i.qty,0),
    statuses: {kitchen:'pending', bar:'pending', waiter:'pending', cashier:'pending'},
    placedAt: new Date().toISOString()
  };

  addOrder(order); // Your function that stores orders
  clearCart();
  renderCart();
  alert('Order placed! Order #: ' + order.orderNumber);
};
// Load logged-in user
const currentUsername = localStorage.getItem("currentUser");
if(!currentUsername){
  alert("Please login first!");
  window.location.href = "login.html";
}
let user = JSON.parse(localStorage.getItem(currentUsername));

// Display user info
document.getElementById("welcomeUser").textContent = `Hello, ${user.name}!`;
document.getElementById("userPoints").textContent = user.points;

// Example: update points if cart or actions give points
function addPoints(points){
  user.points += points;
  document.getElementById("userPoints").textContent = user.points;
  localStorage.setItem(currentUsername, JSON.stringify(user));
}

// Example: when order is placed, give 10 points
document.getElementById('placeOrderBtn').onclick = ()=>{
  const cart = getCart();
  if(cart.length===0) return alert('Cart empty');
  const params = new URLSearchParams(location.search); 
  const table = params.get('table') || 'Guest';
  
  const order = {
    id: Date.now() + '-' + Math.floor(Math.random()*9000),
    orderNumber: generateOrderNumber(),
    table,
    items: cart.map(c=>({id:c.id,name:c.name,price:c.price,qty:c.qty})),
    total: cart.reduce((s,i)=>s + i.price * i.qty,0),
    statuses: { kitchen:'pending', bar:'pending', waiter:'pending', cashier:'pending' },
    placedBy: user.name,
    createdAt: new Date().toISOString()
  };

  addOrder(order); 
  clearCart(); 
  renderCart(); 
  document.getElementById('cartPanel').classList.remove('open');

  // Reward points for ordering
  addPoints(10);

  alert('Order placed! Order#: ' + order.orderNumber + "\n+10 points added to your account!");

  window.open('receipt.html?orderId=' + encodeURIComponent(order.id), '_blank');
};


