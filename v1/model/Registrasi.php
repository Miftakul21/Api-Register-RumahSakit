<?php

class RegistException extends Exception { }

class Registrasi
{
	
	private $_id;
	private $_noreg;
	private $_idpasien;
	private $_tanggal;
	private $_created_at;

	public function __construct($id = null, $noreg, $idpasien, $tanggal, $created_at)
	{
		$this->setId($id);
		$this->setNoreg($noreg);
		$this->setIdPasien($idpasien);
		$this->setTanggal($tanggal);
		$this->setCreatedAt($created_at);
	}

	public function setId($id = null)
	{
		if ($id !== null && (!is_numeric($id) || $id <= 0 || $id > 2147483647 || $this->_id !== null)) {
			throw new RegistException("ID tidak valid");
		}

		$this->_id = $id;
	}

	public function setNoreg($noreg)
	{
		if ($noreg !== null && ($this->_id !== null || strlen($noreg) != 10)) {
			throw new RegistException("Noreg tidak valid");
		}

		$this->_noreg = $noreg;
	}

	public function setIdPasien($idpasien)
	{
		if ($idpasien !== null && (!is_numeric($idpasien) || $idpasien <= 0 || $idpasien > 2147483647 || $this->_idpasien !== null)) {
			throw new RegistException("ID pasien tidak valid");
		}

		$this->_idpasien = $idpasien;
	}

	public function setTanggal($tanggal)
	{
		if (strlen($tanggal) != 10 && ($tanggal === null || $this->_tanggal !== null)) {
			throw new RegistException("Tanggal tidak valid!");
		}

		$this->_tanggal = $tanggal;
	}

	public function setCreatedAt($created_at)
	{
		if (strlen($created_at) != 19 && ($created_at === null || $this->_created_at !== null)) {
			throw new RegistException("Registered Not Valid");
		}

		$this->_created_at = $created_at;
	}

	public function getId()
	{
		return $this->_id;
	}

	public function getNoreg()
	{
		return $this->_noreg;
	}

	public function getIdPasien()
	{
		return $this->_idpasien;
	}

	public function getTanggal()
	{
		return $this->_tanggal;
	}

	public function getCreatedAt()
	{
		return $this->_created_at;
	}

	public function returnRegistrasiAsArray()
	{
		$regist = [];
		$regist['regist_id'] = $this->getId();
		$regist['noreg'] = $this->getNoreg();
		$regist['pasien_id'] = $this->getIdPasien();
		$regist['tanggal'] = $this->getTanggal();
		$regist['registered_at'] = $this->getCreatedAt();

		return $regist; 
	}
}