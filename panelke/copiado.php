<?php 
	session_start();
	if(isset($_SESSION['user'])!="Keinsumos"){

		
			header("location:iniciosesionKe.php"); #redirecciona al index

	}

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="   sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous"> 
	<script src="https://kit.fontawesome.com/019bb635e7.js" crossorigin="anonymous"></script> 
	<link rel="stylesheet"	type="text/css" href="copiados.css">
	<!-- bustrap es para mejor los estilos -->
	<title>PANELKEINSUMOS</title>
</head>
<body>

		<header>	
			<nav>
					<ul class="menu">
							<li class="menu__link"><a class="menu__link--escri" href="">PANEL</a></li>
							<li class="menu__link"><a class="menu__link--escri"  href="mail.php">MAIL</a></li>
							<li class="menu__link"><a class="menu__link--escri"  href="#procesodeproductos">ACTIVIDAD</a></li>
							<li class="menu__link"><a class="menu__link--escri"  href="producto\producto.html">PRODUCTOS</a>
							<ul class="menu__link_sub">
								<li class="menu_sub-link"><a href="producto\productosebo.html">COTIZADOR</a></li>
								<li class="menu_sub-link"><a href="producto\harinadecarneyhueso.html">ALGO</a></li>
								<li class="menu_sub-link"><a href="producto\grasa.html">ALGO</a></li>
							</ul></li>
							<li class="menu__link"><a class="menu__link--escri"  href="galeriainstalaciones/galeria.html">ALGO</a></li>
							<li class="menu__link"><a class="menu__link--escri"  href="">ALGO</a></li>
							<li class="menu__link"><a class="menu__link--escri"  href="iniciosesionKe.php">CERRAR</a></li>
					</ul>


			<div class="menucaja_responsi">
					<div class="boton_responsi"><label for="activador" class="fas fa-bars"> </label></div>
					<input type="checkbox" id="activador">
						<ul class="menu-responsi">
							<li class="menu__link-responsi"><a class="menu__link--escri-a"  href="">PANEL</a></li>
							<li class="menu__link-responsi"><a class="menu__link--escri-a" href="#nosotrosinicio">MAIL</a></li>
							<li class="menu__link-responsi"><a class="menu__link--escri-a" href="#procesodeproductos">ACTIVIDAD</a></li>
							<div class="boton_responsi--segun">
								<label for="activadorseg">
							<li class="menu__link-responsi"><a class="menu__link--escri-a">PRODUCTOS</a></li>
								</label>
							</div>
							<input type="checkbox" id="activadorseg">

							<ul class="menu__link_sub-responsi">
								<li class="menu_sub-link-responsi"><a href="producto\productosebo.html">ALGO</a></li>
								<li class="menu_sub-link-responsi"><a href="producto\harinadecarneyhueso.html">ALGO</a></li>
								<li class="menu_sub-link-responsi"><a href="producto\grasa.html">ALGO</a></li>
							</ul>

							<li class="menu__link-responsi"><a class="menu__link--escri-a" href="galeriainstalaciones/galeria.html">ALGO</a></li>
							<li class="menu__link-responsi"><a class="menu__link--escri-a" href="">ALGO</a></li>
							<li class="menu__link-responsi"><a class="menu__link--escri-a" href="iniciosesionKe.php">CERRAR</a></li>
					</div>
						</ul>
				</div>			
			</nav>
		</header>
</body>
</html>