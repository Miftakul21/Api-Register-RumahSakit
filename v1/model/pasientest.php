<?php 

require_once('Pasien.php');

try {
	$pasien = new Pasien(1, "Brody", "L", '628512376778');
	header('Content-Type: application/json;charset=utf-8');

	echo json_encode($pasien->returnPasienAsArray());
} catch (PasienException $e) {
	echo 'Error : '.$e->getMessage();
}