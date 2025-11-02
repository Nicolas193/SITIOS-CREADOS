function scrollToBuscador() {
  const buscador = document.getElementById('buscadorAccesos');
  const y = buscador.getBoundingClientRect().top + window.pageYOffset - 100; 
  // ↑ podés cambiar el "100" por otro valor (más grande = más arriba)
  window.scrollTo({ top: y, behavior: 'smooth' });
}
