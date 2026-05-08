const nomeLogado = document.getElementById('user-name').textContent.trim();
document.getElementById('user-avatar').textContent = nomeLogado.slice(0,2).toUpperCase();