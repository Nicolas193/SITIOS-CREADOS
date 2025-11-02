<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("conexion.php");
include("AutenticadorUser.php");

$usuario = mysqli_real_escape_string($con, $_SESSION['username']);

// Si ya completó bienvenida, redirigir directamente
$result = mysqli_query($con, "SELECT bienvenida FROM usuarios WHERE usuario = '$usuario'");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    if ($row['bienvenida'] == 1) {
        header("Location: GESTION OTC/php/mistareas.php");
        exit;
    }
}

// Procesar la actualización cuando se envía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_intro'])) {
    $query = "UPDATE usuarios SET bienvenida = 1 WHERE usuario = '$usuario'";
    if (mysqli_query($con, $query)) {
        echo "OK";
    } else {
        echo "ERROR: " . mysqli_error($con);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Presentación Full-Screen - Sistema de Gestión OTCe</title>
<link rel="shortcut icon" href="imagen/presentacion.ico" />
<link rel="stylesheet" href="Estilos/introduccion.css" />
<style>
  .slide { display: none; }
  .slide.active { display: block; }
  #restartBtn { display: none; margin-top: 30px; }
</style>
</head>
<body>

<div id="presentation-container">
  <div id="slides-wrapper">

    <div class="slide active">
      <div class="slide-content">
        <div class="text-content">
          <h1>¡Bienvenido/a  <?php echo htmlspecialchars($usuario); ?> al Sistema de Gestión OTCE!</h1>
          <p>Este entorno fue diseñado para ayudarte a gestionar tareas, registrar actividades y tener una visión clara de tu trabajo y el de tu equipo.</p>
          <p>Haz clic en <strong>"Siguiente"</strong> para comenzar la guía rápida.</p>
        </div>
        <img src="imagen/presentacion.ico" alt="Icono de bienvenida" style="max-width: 250px; border: none; box-shadow: none;">
      </div>
    </div>

    <div class="slide">
      <div class="slide-content">
        <div class="text-content">
          <h2>🧭 Vista general del sistema</h2>
          <ul>
            <li><strong>Menú lateral:</strong> Desplegable a la izquierda, permite acceder a todas las secciones.</li>
            <li><strong>Chat:</strong> Contiene información útil y accesos directos.</li>
            <li><strong>Notificaciones:</strong> Te alerta sobre tareas pendientes o respondidas.</li>
          </ul>
          <p>Estos apartados los veras en todas las pestañas del sistema</p>
        </div>
        <img src="imagen/guia1.gif" alt="Vista general">
      </div>
    </div>

    <div class="slide">
      <div class="slide-content">
        <div class="text-content">
          <h2>1. Perfil de usuario</h2>
          <p>Aquí se muestra tu información. También podés:</p>
          <ul>
            <li>Cambiar el tema del sitio (Claro, Negro, Moderno).</li>
            <li>Actualiza tu información y agrega contactos para facilitar la comunicación con otros usuarios.</li>
          </ul>
        </div>
        <img src="imagen/guia2.gif" alt="Perfil de Usuario">
      </div>
    </div>

    <div class="slide">
      <div class="slide-content">
        <div class="text-content">
          <h2>2. Tareas</h2>
          <p>Esta sección es clave para tu trabajo diario. Aquí puedes:</p>
          <ul>
            <li><strong>Enviar tareas</strong> a otros usuarios.</li>
            <li>Ver tus <strong>tareas finalizadas y pendientes</strong>.</li>
            <li>Revisar <strong>respuestas y comentarios</strong> de tareas.</li>
          </ul>
        </div>
        <img src="imagen/guia3.gif" alt="Envío de Tareas">
      </div>
    </div>

    <div class="slide">
      <div class="slide-content">
        <div class="text-content">
          <h2>Detalle de la Tarea</h2>
          <p>Al hacer clic en cualquier tarea, accedes a su detalle. Es un entorno fundamental donde podés:</p>
          <ul>
            <li>Ver el seguimiento y evolución.</li>
            <li>Comentar y actualizar su estado.</li>
            <li>Visualizar los perfiles de los participantes.</li>
          </ul>
        </div>
        <img src="imagen/guia4.gif" alt="Detalle de la Tarea">
      </div>
    </div>

    <div class="slide">
      <div class="slide-content">
        <div class="text-content">
          <h2>3. Registro de tareas</h2>
          <p>Esta sección te da una vista global de tu actividad.</p>
          <ul>
            <li><strong>Mis movimientos:</strong> Visualiza todas tus participaciones en tareas.</li>
            <li><strong>Paneles informativos:</strong> Resumen gráfico de tus tareas enviadas y recibidas.</li>
          </ul>
        </div>
        <img src="imagen/guia5.gif" alt="Panel de Tareas Enviadas">
      </div>
    </div>

    <div class="slide">
      <div class="slide-content">
        <div class="text-content">
          <h2>4. Enlaces externos</h2>
          <p>Accede a sitios importantes o guarda tus propios enlaces frecuentes en <strong>“Mis direcciones”</strong> para tenerlos siempre a mano.</p>
        </div>
        <img src="imagen/guia6.gif" alt="Mis Direcciones">
      </div>
    </div>

    <div class="slide">
      <div class="slide-content">
        <div class="text-content">
          <h2>✅ ¡Todo listo para comenzar!</h2>
          <p>Esperamos que esta introducción te haya sido útil.</p>
          <p>Precione <strong>Finalizar Introduccion</strong> para diriguirlo al sistema.</p>
            <button id="restartBtn">Finalizar Introduccion</button>
            <div id="mensaje-espera" style="display:none; color:red; margin-top:10px;"></div>


        </div>
      </div>
    </div>

  </div>

  <div id="navigation-controls">
    <button id="prevBtn">Anterior</button>
    <span id="slide-counter"></span>
    <button id="nextBtn">Siguiente</button>
  </div>
</div>




<script>
document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('.slide');
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    const restartBtn = document.getElementById('restartBtn');
    const slideCounter = document.getElementById('slide-counter');
    const mensajeEspera = document.getElementById('mensaje-espera');

    let currentSlide = 0;
    const totalSlides = slides.length;

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        slides[index].classList.add('active');
        currentSlide = index;

        slideCounter.textContent = `Paso ${index + 1} de ${totalSlides}`;
        prevBtn.disabled = index === 0;
        nextBtn.disabled = index === totalSlides - 1;

        if (restartBtn) {
            restartBtn.style.display = (index === totalSlides - 1) ? 'inline-block' : 'none';
            restartBtn.style.marginTop = '30px';
        }
    }

    nextBtn.addEventListener('click', () => {
        if (currentSlide < totalSlides - 1) showSlide(currentSlide + 1);
    });

    prevBtn.addEventListener('click', () => {
        if (currentSlide > 0) showSlide(currentSlide - 1);
    });

    if (restartBtn) {
        restartBtn.addEventListener('click', () => {
            console.log('Click en finalizar');

            if (mensajeEspera) {
                mensajeEspera.style.display = 'block';
                mensajeEspera.textContent = 'Diriguiendo al sitio...';
                mensajeEspera.style.color = 'black';
            }

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'finalizar_intro=1'
            })
            .then(response => response.text())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                if (data.trim() === "OK") {
                    window.location.href = "GESTION OTC/php/mistareas.php";
                } else {
                    if (mensajeEspera) {
                        mensajeEspera.textContent = "Error al actualizar. Intenta de nuevo.";
                        mensajeEspera.style.color = "red";
                    }
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                if (mensajeEspera) {
                    mensajeEspera.textContent = "Error al conectar con el servidor.";
                    mensajeEspera.style.color = "red";
                }
            });
        });
    }

    showSlide(0);
});

</script>

</body>
</html>
