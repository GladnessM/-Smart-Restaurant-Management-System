/* app.js - shared logic for frontend-only prototype
   - Provides menu data
   - Cart management (localStorage)
   - Orders simulation via localStorage 'orders' array and storage events (to notify other tabs)
*/

const MENU = [
  { id:1, name:"Classic Beef Burger", price:550, img:"https://images.unsplash.com/photo-1604908177522-432f1a2c0d95" , category:'main'},
  { id:2, name:"Pepperoni Pizza", price:800, img:"https://images.unsplash.com/photo-1540189549336-e6e99c3679fe" , category:'main'},
  { id:3, name:"Grilled Chicken", price:700, img:"https://images.unsplash.com/photo-1617196034715-df0b7ccba0d6", category:'main'},
  { id:4, name:"Spaghetti Carbonara", price:650, img:"https://images.unsplash.com/photo-1617196034914-87a0c2bfb80f", category:'main'},
  { id:5, name:"Fresh Orange Juice", price:300, img:"https://images.unsplash.com/photo-1613470200727-7f4d6b9db8d9", category:'drinks'},
  { id:6, name:"Iced Latte", price:350, img:"https://images.unsplash.com/photo-1587731233445-21d19d1f0d36", category:'drinks'},
  { id:7, name:"Coca-Cola", price:200, img:"https://images.unsplash.com/photo-1622396481331-f2a97a27a37e", category:'drinks'},
  { id:8, name:"Chocolate Lava Cake", price:400, img:"https://images.unsplash.com/photo-1606788075761-1e58e0d1f9d9", category:'desserts'},
  { id:9, name:"Vanilla Ice Cream", price:300, img:"https://images.unsplash.com/photo-1612197593541-927f5b6f98f0", category:'desserts'},
  { id:10, name:"Strawberry Cheesecake", price:450, img:"https://images.unsplash.com/photo-1599785209707-28d5b4e4d2f0", category:'desserts'}
];

// CART helpers
function getCart(){ return JSON.parse(localStorage.getItem('cart_v1')||'[]'); }
function saveCart(cart){ localStorage.setItem('cart_v1', JSON.stringify(cart)); window.dispatchEvent(new Event('cart_updated')); }
function clearCart(){ localStorage.removeItem('cart_v1'); window.dispatchEvent(new Event('cart_updated')); }

// ORDERS helpers (simulate server by storing in localStorage)
function getOrders(){ return JSON.parse(localStorage.getItem('orders_v1')||'[]'); }
function saveOrders(list){ localStorage.setItem('orders_v1', JSON.stringify(list)); /* triggers storage event in other tabs */ }
function addOrder(order){
  const list = getOrders();
  list.unshift(order);
  saveOrders(list);
  // also write a small key so storage event fires even in same-tab dev
  localStorage.setItem('orders_ping_v1', Date.now());
}

// Generate simple order id
function generateOrderNumber(){
  return 'ORD-' + Math.floor(1000 + Math.random()*8999);
}

// Listen to storage events to simulate real-time across tabs
window.addEventListener('storage', (e)=>{
  if(e.key === 'orders_v1' || e.key === 'orders_ping_v1'){
    // notify listeners
    window.dispatchEvent(new Event('orders_updated'));
  }
  if(e.key === 'cart_v1'){
    window.dispatchEvent(new Event('cart_updated'));
  }
});

// small util
function currency(n){ return 'Ksh ' + Number(n).toLocaleString(); }
