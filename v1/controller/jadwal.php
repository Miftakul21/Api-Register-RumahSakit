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
} else if (empty($_GET) || !empty($_GET)) {
	if($_SERVER['REQUEST_METHOD'] === 'GET'){
		try{
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
		}
		catch (PDOException $e) {
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
	} else if($_SERVER['REQUEST_METHOD'] === 'POST' ) {
		try{
			if(empty($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
				// respon gagal
				$response = new Response();
				$response->setHttpStatusCode(400);
				$response->setSuccess(false);
				$response->setMessages("Content Type header not set to JSON");
				$response->send();
				exit;
			}

			$rawPostData = file_get_contents('php://input');

			// validasi penggunaan raw
			if(!$jsonData = json_decode($rawPostData)){
				$res = new Response();
				$res->setHttpStatusCode(400);
				$res->setSuccess(false);
				$res->setMessages('Request body is not valid JSON');
				$res->send();
				exit;
			}

			// validasi input
			if(!isset($jsonData->hari)){
				$res = new Response();
				$res->setHttpStatusCode(400);
				$res->setSuccess(false);
				(!isset($json->hari) ? $res->setMessages("Hari is required") : false);
				$res->send();
				exit;	
			}

			$newJadwal = new Jadwal(null, $jsonData->hari, $jsonData->kuota);
			$hari = $newJadwal->getHari();
			$kuota = $newJadwal->getKuota();

			global $writeDb;
			$query = $writeDb->prepare("INSERT INTO jadwal (hari, kuota) VALUES (:hari, :kuota)");
			$query->bindParam(':hari', $hari, PDO::PARAM_STR);
			$query->bindParam(':kuota', $kuota, PDO::PARAM_STR);
			$query->execute();

			$row = $query->rowCount();
			
			// cek jumlah hasil insert jadwal
			if($row === 0 ){
				$res = new Response();
				$res->setHttpStatusCode(500);
				$res->setSuccess(false);
				$res->setMessages('Gagal, data jadwal kosong');
				$res->send();
				exit; 
			}

			
			$jadwalLastId = $writeDb->lastInsertId();
			$query = $writeDb->prepare("SELECT * FROM jadwal WHERE id_jadwal = :id");
			$query->bindParam(':id', $jadwalLastId, PDO::PARAM_INT);
			$query->execute();

			// hitung jumlah hasil insert dari id terakhir
			$rowInsert = $query->rowCount();
			if($rowInsert === 0) {
				$res = new Response();
				$res->setHttpStatusCode(500);
				$res->setSuccess(false);
				$res->setMessages('Failed to retrive jadwal after creation');
				$res->send();
				exit; 
			}

			$jadwalArray = array();

			while($data = $query->fetch(PDO::FETCH_ASSOC)){
				$jadwal = new Jadwal($data['id_jadwal'], $data['hari'], $data['kuota']);
				$jadwalArray = $jadwal->returnJadwalAsArray();
			}

			$returnData = array();
			$returnData['row_returned'] = $rowInsert;
			$returnData['jadwal'] = $jadwalArray;

			// set response ketika berhasil
			$res = new Response();
			$res->setHttpStatusCode(201);
			$res->setSuccess(true);
			$res->setMessages('Jadwal created successfully');
			$res->setData($returnData);
			$res->send();
			exit;
		}
	    // if jadwal fails to create due to data types, missing fields or invalid data then send error json
	    catch(JadwalException $ex) {
	      $response = new Response();
	      $response->setHttpStatusCode(400);
	      $response->setSuccess(false);
	      $response->setMessages($ex->getMessage());
	      $response->send();
	      exit;
	    }
	    // if error with sql query return a json error
	    catch(PDOException $ex) {
	      error_log("Database Query Error: ".$ex, 0);
	      $response = new Response();
	      $response->setHttpStatusCode(500);
	      $response->setSuccess(false);
	      $response->setMessages("Failed to insert Jadwal into database - check submitted data for errors ".$ex);
	      $response->send();
	      exit;
	    }
	}
	else {
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