// Switch between login and signup
const loginTab = document.getElementById('loginTab');
const signupTab = document.getElementById('signupTab');
if (loginTab && signupTab) {
  loginTab.onclick = () => toggleForms('login');
  signupTab.onclick = () => toggleForms('signup');
}

function toggleForms(type) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.form').forEach(f => f.classList.remove('active'));

  if (type === 'login') {
    loginTab.classList.add('active');
    document.getElementById('loginForm').classList.add('active');
  } else {
    signupTab.classList.add('active');
    document.getElementById('signupForm').classList.add('active');
  }
}

// Fake authentication (temporary)
function loginUser() {
  const email = document.getElementById('loginEmail').value;
  const pass = document.getElementById('loginPassword').value;
  if (email && pass) {
    localStorage.setItem('user', email);
    window.location.href = 'menu.html';
  } else {
    alert('Please enter login details');
  }
}

function signupUser() {
  const email = document.getElementById('signupEmail').value;
  const pass = document.getElementById('signupPassword').value;
  const name = document.getElementById('signupName').value;
  if (email && pass && name) {
    localStorage.setItem('user', email);
    alert('Account created successfully!');
    window.location.href = 'menu.html';
  } else {
    alert('Please fill all fields');
  }
}
