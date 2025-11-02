function updateClock() {
  const now = new Date();

  const timeElement = document.getElementById('time');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  timeElement.textContent = `${hours}:${minutes}:${seconds}`;

  const dateElement = document.getElementById('date');
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  dateElement.textContent = now.toLocaleDateString(undefined, options);
}

updateClock();
setInterval(updateClock, 1000);

document.getElementById("enlace-induccion").addEventListener("click", function(event) {
    event.preventDefault(); // Evita el comportamiento predeterminado de navegación
    
    if (event.target.tagName === "IMG") {
        window.open(this.getAttribute("url"), "_blank");
    } else {
        window.location.href = this.getAttribute("url");
    }
});