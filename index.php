<?php
// Conexión a la base de datos
$config = parse_ini_file('config.ini');

$host = $config['host'];
$dbname = $config['db_name'];
$password = $config['password'];
$user = $config['username'];
$port = $config['port'];

//Conectate a la db!
try{
	$conn = new mysqli($host, $user, $password, $dbname);
	$results = $conn->query("SELECT * FROM clima");

	//Recibimos los datos de clima como array de objetos
	$datosClima = [];

	while($row = $results->fetch_object()) {
		array_push($datosClima, $row);
	}

	$conn->close();
} catch(Exception $e) {
    die('Error : ('. $conn->connect_errno .') '. $conn->connect_error);
}




?>

<head>
	<!-- Cargamos bootstrap, jquery, openstreetmap -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<!-- jQuery (Versión completa) -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<!-- Popper.js (Requerido por Bootstrap) -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
	<!-- Bootstrap JS -->
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</head>
<style>
	.leaflet-popup-content-wrapper {
		word-wrap: break-word;
	}
</style>
<body>
	<h2 class="my-4 text-center">Prueba técnica</h2>
	<div id="mapid" style="width: 800px; height: 600px; margin: auto;"></div>



	<!-- Modal de Bootstrap -->
	<div class="modal fade" id="confirmarAccionModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
		aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="modalLabel">Confirmar acción</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					¿Quieres añadir un nuevo dato de clima en esta ubicación?
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
					<button type="button" class="btn btn-primary" id="confirmarAccion">Sí</button>
				</div>
			</div>
		</div>
	</div>

	<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

	<script>
	//Recibimos los datos desde php
	var datosClima = <?= json_encode($datosClima) ?>; 
	
	// Inicializar el mapa
	var mymap = L.map('mapid').setView([40.416775, -3.703790], 13);

	// Cargar los tiles de OpenStreetMap
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 19,
	}).addTo(mymap);

	

	// Función para mostrar los datos en el mapa
	datosClima.forEach(function(dato) {
		//Creamos marcadores en el mapa
		var marker = L.marker([dato.lat, dato.lon]).addTo(mymap);

		var customPopup =  "Fecha: " + dato.fecha +"<br> Temperatura: " +  dato.temperatura +  "ºC <br> Humedad: " + dato.humedad + "% <br> Velocidad del viento: " + dato.viento + "km/h <br> Descripcion: " + dato.descripcion +  "<br> URL: " +dato.url  

		marker.bindPopup(customPopup)
		


	});

	// Ejemplo de interacción con un popup y AJAX para un nuevo dato
	mymap.on('click', function(e) {
		$('#confirmarAccionModal').modal('show'); // Mostrar el modal

		// Función para el botón de confirmar en el modal
		$('#confirmarAccion').off('click').on('click', function() {
			$('#confirmarAccionModal').modal('hide'); // Ocultar el modal

			// Datos a enviar
			var datos = {
				lat: e.latlng.lat,
				lon: e.latlng.lng
			};

			// Petición AJAX a http://localhost:5000/guardar-clima
			$.ajax({
				type: "POST",
				url: "http://localhost:5000/guardar-clima", // Asegúrate de que la URL es accesible y correcta
				data: datos,
				success: function(response) {
					var marker = L.marker([datos.lat, datos.lon]).addTo(mymap);

					var customPopup = "Fecha: " + response['fecha'] + "<br> Temperatura: " +  response['temperatura'] +  "ºC <br> Humedad: " + response['humedad'] + "% <br> Velocidad del viento: " + response['viento'] + "km/h <br> Descripcion: " + response['descripcion']+ "<br> URL: " + response['url'] 

					marker.bindPopup(customPopup)
				},
				error: function(error) {
					// Manejar errores
				}
			});
		});
	});
	</script>
</body>