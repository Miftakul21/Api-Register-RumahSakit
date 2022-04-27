<?php 

// load file koneksi
require_once('db.php');
// load model response json
require_once('../model/Response.php');
// load class Registrasi
require_once('../model/Registrasi.php');

date_default_timezone_set('Asia/Jakarta');

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

if (array_key_exists('regist_id', $_GET)) {
	$id = $_GET['regist_id'];

	// VALIDASI ID HARUS BERUPA ANGKA
	if ($id == '' || !is_numeric($id)) {
		$res = new Response();
		$res->setHttpStatusCode(400);
		$res->setSuccess(false);
		$res->setMessages('ID Regist tidak boleh kosong atau harus berupa angka!');
		$res->send();
		exit;
	}

	if($_SERVER['REQUEST_METHOD'] === 'GET') {
		try {
			global $readDb;
			$query = $readDb->prepare('SELECT * FROM regist WHERE id_regist = :id ');
			$query->bindParam(':id', $id, PDO::PARAM_INT);
			$query->execute();

			$row = $query->rowCount();
			if ($row === 0) {
				$res = new Response();
				$res->setHttpStatusCode(400);
				$res->setSuccess(false);
				$res->setMessages('ID Register tidak ditemukan!');
				$res->send();
				exit;
			}

			$RegisterArray = null;
			while($v = $query->fetch(PDO::FETCH_ASSOC)) {
				$register = new Registrasi($v['id_regist'], $v['no_regist'], $v['id_pasien'], $v['tanggal'], $v['created_at']);
				$RegisterArray = $register->returnRegistrasiAsArray();
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
			$res->setMessages('Failed to get data register!');
			$res->send();
			exit;
		} catch (RegistException $e) {
			// Tidak perlu direkam ke error_log, karena pure kesalahan dr user
			$res = new Response();
			$res->setHttpStatusCode(400);
			$res->setSuccess(false);
			$res->setMessages($e->getMessage()." hahaha >_< ".$e);
			$res->send();
			exit;
		}
	}
	
	
} else if (empty($_GET)) {
	$server = $_SERVER['REQUEST_METHOD'];
	if ($server === 'POST') {
		// create regist
		try {
	      // cek content type header apakah JSON
		if(empty($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
	        // respon gagal
			$response = new Response();
			$response->setHttpStatusCode(400);
			$response->setSuccess(false);
			$response->setMessages("Content Type header not set to JSON");
			$response->send();
			exit;
		}	
	      
	    // get POST request body berformat JSON
	    $rawPostData = file_get_contents('php://input');
		
	    if(!$jsonData = json_decode($rawPostData)) {
	        // respon gagal
			$response = new Response();
			$response->setHttpStatusCode(400);
			$response->setSuccess(false);
			$response->setMessages("Request body is not valid JSON");
			$response->send();
			exit;
	    }
	      
	    // validasi inputan
	     if(!isset($jsonData->pasien_id)) {
	        $response = new Response();
	        $response->setHttpStatusCode(400);
	        $response->setSuccess(false);
	        (!isset($jsonData->pasien_id) ? $response->setMessages("Pasien ID is required") : false);
	        $response->send();
	        exit;
	     }

	      
	      $newRegistrasi = new Registrasi(null, 'REG-'.rand(211111, 999999), $jsonData->pasien_id, date('Y-m-d'), date('Y-m-d H:i:s'));
	      $noregist = $newRegistrasi->getNoreg();
	      $id_pasien = $newRegistrasi->getIdPasien();
	      $tanggal = $newRegistrasi->getTanggal();
	      $created_at = $newRegistrasi->getCreatedAt();

	    
		// nanti dicari cara penyelesaiannya waktu insert data

		// create db query
	    // $query = $writeDb->prepare('INSERT INTO regist (no_regist, id_pasien, tanggal, created_at) VALUES (:noregist, :id_pasien, STR_TO_DATE(:tanggal, \'%Y-%m-%d\'), STR_TO_DATE(:created_at, \'%Y-%m-%d %H:%i:%s\'))');
		  $query = $writeDb->prepare("INSERT INTO regist (no_regist, id_pasien, tanggal, created_at) VALUES (:no_regist, :id_pasien, :tanggal, :created_at)");
		  $query->bindParam(':no_regist', $noregist, PDO::PARAM_STR);
	      $query->bindParam(':id_pasien', $id_pasien, PDO::PARAM_STR);
	      $query->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
	      $query->bindParam(':created_at', $created_at, PDO::PARAM_STR);
	      $query->execute();
	      
	      // get row count
	      $rowCount = $query->rowCount();

	      if($rowCount === 0) {
	        // set up response for unsuccessful return
	        $response = new Response();
	        $response->setHttpStatusCode(500);
	        $response->setSuccess(false);
	        $response->setMessages("Gagal registrasi");
	        $response->send();
	        exit;
	      }
	    

		  global $writeDb;
		  
	      // get last task id so we can return the Task in the json
	      $lastregistID = $writeDb->lastInsertId();

		  $query = $writeDb->prepare('SELECT * FROM regist WHERE id_regist = :registid');
	      $query->bindParam(':registid', $lastregistID, PDO::PARAM_INT);
	      $query->execute();

	      // get row count
	      $rowCountInsert = $query->rowCount();
	      
	      // make sure that the new task was returned
	      if($rowCountInsert === 0) {
	        // set up response for unsuccessful return
	        $response = new Response();
	        $response->setHttpStatusCode(500);
	        $response->setSuccess(false);
	        $response->setMessages("Failed to retrieve regist after creation ");
	        $response->send();
	        exit;
	      }
	      
	      // create empty array to store tasks
	      $registArray = array();

	      // for each row returned - should be just one
	      while($row = $query->fetch(PDO::FETCH_ASSOC)) {
	        // create new Regist object
	        $regist = new Registrasi(null,$row['no_regist'], $row['id_pasien'], $row['tanggal'], $row['created_at']);

	        // create Regist and store in array for return in json data
	        $registArray = $regist->returnRegistrasiAsArray();
	      }
	      // bundle Regists and rows returned into an array to return in the json data
	      $returnData = array();
	      $returnData['rows_returned'] = $rowCountInsert;
	      $returnData['regists'] = $registArray;


	      //set up response for successful return
	      $response = new Response();
	      $response->setHttpStatusCode(201);
	      $response->setSuccess(true);
	      $response->setMessages("Regist created");
	      $response->setData($returnData);
	      $response->send();
	      exit;      
	    }
	    // if Regist fails to create due to data types, missing fields or invalid data then send error json
	    catch(RegistException $ex) {
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
	      $response->setMessages("Failed to insert Regist into database - check submitted data for errors ".$ex);
	      $response->send();
	      exit;
	    }
	} else if($server === 'GET') {
		try {
			// $readDb = DB::connectReadDb();
			global $writeDb;
			$query = $writeDb->prepare("SELECT * FROM regist");
			$query->execute();

			$row = $query->rowCount();

			if ($row === 0) {
				$res = new Response();
				$res->setHttpStatusCode(400);
				$res->setSuccess(false);
				$res->setMessages('Data register pasien tidak ditemukan!');
				$res->send();
				exit;
			}

			$RegisterArray = null;
			while ($v = $query->fetch(PDO::FETCH_ASSOC)) {
				$register = new Registrasi($v['id_regist'], $v['no_regist'], $v['id_pasien'], $v['tanggal'], $v['created_at']);
				$RegisterArray[] = $register->returnRegistrasiAsArray();
			}

			$returnData = [];
			$returnData['row_returned'] = $row;
			$returnData['data'] = $RegisterArray;

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
		} catch (RegistException $e) {
			// Tidak perlu direkam ke error_log, karena pure kesalahan dr user
			$res = new Response();
			$res->setHttpStatusCode(404);
			$res->setSuccess(false);
			$res->setMessages("OI OI Majikayo ".$e);
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