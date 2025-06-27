// Login Fonksiyonu
async function login(email, password) {
  const response = await fetch('http://localhost:5000/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  if (!response.ok) {
    alert('Giriş başarısız.');
    return;
  }
  const data = await response.json();
  localStorage.setItem('token', data.token);
  window.location.href = 'teklif-liste.html';
}

// Token Kontrol
function checkAuth() {
  const token = localStorage.getItem('token');
  if (!token) {
    window.location.href = 'login.html';
  }
  return token;
}

// Logout
function logout() {
  localStorage.removeItem('token');
  window.location.href = 'login.html';
}
