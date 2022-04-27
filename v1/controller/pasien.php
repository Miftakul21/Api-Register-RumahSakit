<?php 

require_once('db.php');
require_once('../model/Response.php');
require_once('../model/Pasien.php');

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

if (array_key_exists('pasien_id', $_GET)) {
	$id = $_GET['pasien_id'];

	if ($id == '' || !is_numeric($id)) {
		$res = new Response();
		$res->setHttpStatusCode(400);
		$res->setSuccess(false);
		$res->setMessages('ID Pasien tidak boleh kosong atau harus berupa angka!');
		$res->send();
		exit;
	}

	// 
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		try {
			$query = $readDb->prepare('SELECT id, nama, jk, hp FROM pasien WHERE id = :id');
			$query->bindParam(':id', $id, PDO::PARAM_INT);
			$query->execute();

			$row = $query->rowCount();
			if ($row === 0) {
				$res = new Response();
				$res->setHttpStatusCode(400);
				$res->setSuccess(false);
				$res->setMessages('ID Pasien tidak ditemukan!');
				$res->send();
				exit;
			}

			$pasienArray = null;
			while ($v = $query->fetch(PDO::FETCH_ASSOC)) {
				$pasien = new Pasien($v['id'], $v['nama'], $v['jk'], $v['hp']);
				$pasienArray = $pasien->returnPasienAsArray();
			}

			$returnData = [];
			$returnData['row_returned'] = $row;
			$returnData['data'] = $pasienArray;

			$res = new Response();
			$res->setHttpStatusCode(200);
			$res->setSuccess(true);
			$res->toCache(true);
			$res->setData($returnData);
			$res->send();
			exit;
		} catch (PDOException $e) {
			$res = new Response();
			$res->setHttpStatusCode(500);
			$res->setSuccess(false);
			$res->setMessages('Gagal mendapatkan data pasien');
			$res->send();
			exit;
		} catch (PasienException $e) {
			$res = new Response();
			$res->setHttpStatusCode(500);
			$res->setSuccess(false);
			$res->setMessages($e->getMessage());
			$res->send();
			exit;
		}
	} else {
		$res = new Response();
		$res->setHttpStatusCode(405);
		$res->setSuccess(false);
		$res->setMessages('Request method not allowed');
		$res->send();
		exit;
	}
} else if(empty($_GET)){
	 if($_SERVER['REQUEST_METHOD'] === 'POST' ) {
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
			if(!isset($jsonData->nama)){
				$res = new Response();
				$res->setHttpStatusCode(400);
				$res->setSuccess(false);
				(!isset($json->nama) ? $res->setMessages("Nama is required") : false);
				$res->send();
				exit;	
			}

			$newPasien = new Pasien(null, $jsonData->nama, $jsonData->jk, $jsonData->hp);
			$nama = $newPasien->getNama();
			$jk = $newPasien->getJk();
			$hp = $newPasien->getHp();

			global $writeDb;
			$query = $writeDb->prepare("INSERT INTO pasien (nama, jk, hp) VALUES (:nama, :jk, :hp)");
			$query->bindParam(':nama', $nama, PDO::PARAM_STR);
			$query->bindParam(':jk', $jk, PDO::PARAM_STR);
			$query->bindParam(':hp', $hp, PDO::PARAM_STR);
			$query->execute();

			$row = $query->rowCount();
			
			// cek jumlah hasil insert jadwal
			if($row === 0 ){
				$res = new Response();
				$res->setHttpStatusCode(500);
				$res->setSuccess(false);
				$res->setMessages('Gagal, data pasien kosong');
				$res->send();
				exit; 
			}

			
			$pasienLastId = $writeDb->lastInsertId();
			$query = $writeDb->prepare("SELECT * FROM pasien WHERE id = :id");
			$query->bindParam(':id', $pasienLastId, PDO::PARAM_INT);
			$query->execute();

			// hitung jumlah hasil insert dari id terakhir
			$rowInsert = $query->rowCount();
			if($rowInsert === 0) {
				$res = new Response();
				$res->setHttpStatusCode(500);
				$res->setSuccess(false);
				$res->setMessages('Failed to retrive pasien after creation');
				$res->send();
				exit; 
			}

			$pasienArray = [];

			while($data = $query->fetch(PDO::FETCH_ASSOC)){
				$pasien = new Pasien($data['id'], $data['nama'], $data['jk'], $data['hp']);
				$pasienArray = $pasien->returnPasienAsArray();
			}

			$returnData = [];
			$returnData['row_returned'] = $rowInsert;
			$returnData['pasien'] = $pasienArray;

			// set response ketika berhasil
			$res = new Response();
			$res->setHttpStatusCode(201);
			$res->setSuccess(true);
			$res->setMessages('Pasien created successfully');
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
	      $response->setMessages("Failed to insert Pasien into database - check submitted data for errors ".$ex);
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

?>