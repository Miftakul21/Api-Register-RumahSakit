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