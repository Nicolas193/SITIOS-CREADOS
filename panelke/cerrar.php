<?php 

#destruye la sesion para que con el link no lo puedas iniciar 
	session_start();
	session_destroy();
	header("location:iniciosesionKe.php");
 ?>