
document.addEventListener('DOMContentLoaded', function() {
  const icon = document.getElementById('notification-icon');
  const list = document.getElementById('notification-list');
  const count = document.getElementById('notification-count');
  const items = document.getElementById('notification-items');

  icon.addEventListener('click', () => {
    list.style.display = list.style.display === 'none' ? 'block' : 'none';
  });

  function mostrarNotificaciones(data) {
    items.innerHTML = '';
    if (data.length > 0) {
      count.innerText = data.length;
      count.style.display = 'inline-block';
    } else {
      count.style.display = 'none';
      items.innerHTML = '<li style="color:#888;">No hay notificaciones nuevas</li>';
    }

data.forEach(msg => {
  const li = document.createElement('li');
  li.innerHTML = msg; // <- aquí interpretamos HTML
  li.style.padding = "5px 0";
  items.appendChild(li);
});

  }

  // Cargar notificaciones desde PHP
  fetch('notificaciones.php')
    .then(response => response.json())
    .then(data => mostrarNotificaciones(data))
    .catch(err => {
      console.error("Error al cargar notificaciones:", err);
      mostrarNotificaciones([]);
    });

});

