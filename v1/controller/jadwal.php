<?php

// load file koneksi
require_once('db.php');
// load model response json
require_once('../model/Response.php');
// load class jadwal
require_once('../model/Jadwal.php');

try {
	$writeDb = DB::connectWriteDb();
	$readDb = DB::connectReadDb();
} catch (Exception $e) {
	$res = new Response();
	$res->setHttpStatusCode(500);
	$res->setSuccess(false);
	$res->setMessages($e);
	$res->send();
	exit;
}

// Cek inputan masuk
if (array_key_exists('jadwal_id', $_GET)) {
	$id = $_GET['jadwal_id'];

	// VALIDASI ID HARUS BERUPA ANGKA
	if ($id == '' || !is_numeric($id)) {
		$res = new Response();
		$res->setHttpStatusCode(400);
		$res->setSuccess(false);
		$res->setMessages('ID Jadwal tidak boleh kosong atau harus berupa angka!');
		$res->send();
		exit;
	}

	// CEK HTTP METHOD / VERB
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		try {
			$query = $readDb->prepare('SELECT * FROM jadwal WHERE id_jadwal = :id');
			$query->bindParam(':id', $id, PDO::PARAM_INT);
			$query->execute();

			$row = $query->rowCount();
			if ($row === 0) {
				$res = new Response();
				$res->setHttpStatusCode(400);
				$res->setSuccess(false);
				$res->setMessages('ID Jadwal tidak ditemukan!');
				$res->send();
				exit;
			}

			$JadwalArray = null;
			while ($v = $query->fetch(PDO::FETCH_ASSOC)) {
				$jadwal = new Jadwal($v['id_jadwal'], $v['hari'], $v['kuota']);
				$jadwalArray = $jadwal->returnJadwalAsArray();
			}

			$returnData = [];
			$returnData['row_returned'] = $row;
			$returnData['data'] = $jadwalArray;

			$res = new Response();
			$res->setHttpStatusCode(200);
			$res->setSuccess(true);
			$res->toCache(true);
			$res->setData($returnData);
			$res->send();
			exit;
		} catch (PDOException $e) {
			// Perlu direkam ke error_log, karena kesalahan dari backend yang tidak diketahui
			error_log($e->getMessage());
			$res = new Response();
			$res->setHttpStatusCode(500);
			$res->setSuccess(false);
			$res->setMessages('Failed to get data jadwal!');
			$res->send();
			exit;
		} catch (JadwalException $e) {
			// Tidak perlu direkam ke error_log, karena pure kesalahan dr user
			$res = new Response();
			$res->setHttpStatusCode(400);
			$res->setSuccess(false);
			$res->setMessages($e->getMessage());
			$res->send();
			exit;
		}
	} else {
		// HTTP VERB POST, PUT, DELETE DIBLOCK
		$res = new Response();
		$res->setHttpStatusCode(405);
		$res->setSuccess(false);
		$res->setMessages('Request method not allowed');
		$res->send();
		exit;
	}
} else if (empty($_GET)) {
	if($_SERVER['REQUEST_METHOD'] === 'GET'){
		try {
		$query = $readDb->prepare('SELECT * FROM jadwal');
		$query->execute();
		$row = $query->rowCount();

		if ($row === 0) {
			$res = new Response();
			$res->setHttpStatusCode(400);
			$res->setSuccess(false);
			$res->setMessages('Jadwal tidak ditemukan!');
			$res->send();
			exit;
		}

		$JadwalArray = null;
		while ($v = $query->fetch(PDO::FETCH_ASSOC)) {
			$jadwal = new Jadwal($v['id_jadwal'], $v['hari'], $v['kuota']);
			$jadwalArray[] = $jadwal->returnJadwalAsArray();
		}

		$returnData = [];
		$returnData['row_returned'] = $row;
		$returnData['data'] = $jadwalArray;

		$res = new Response();
		$res->setHttpStatusCode(200);
		$res->setSuccess(true);
		$res->toCache(true);
		$res->setData($returnData);
		$res->send();
		exit;
		} catch (PDOException $e) {
			// Perlu direkam ke error_log, karena kesalahan dari backend yang tidak diketahui
			error_log($e->getMessage());
			$res = new Response();
			$res->setHttpStatusCode(500);
			$res->setSuccess(false);
			$res->setMessages('Failed to get jadwal!');
			$res->send();
			exit;
		} catch (JadwalException $e) {
			// Tidak perlu direkam ke error_log, karena pure kesalahan dr user
			$res = new Response();
			$res->setHttpStatusCode(404);
			$res->setSuccess(false);
			$res->setMessages($e->getMessage());
			$res->send();
			exit;
		}
	} else {
		// HTTP VERB POST, PUT, DELETE DIBLOCK
		$res = new Response();
		$res->setHttpStatusCode(405);
		$res->setSuccess(false);
		$res->setMessages('Request method not allowed');
		$res->send();
		exit;
	}
} else {
	// 404 Error alias endpoint tidak ditemukan!
	$response = new Response();
	$response->setHttpStatusCode(404);
	$response->setSuccess(false);
	$response->setMessages("Endpoint not found");
	$response->send();
	exit;
}