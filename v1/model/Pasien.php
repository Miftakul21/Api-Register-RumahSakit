<?php 

class PasienException extends Exception { }

class Pasien
{
	private $_id;
	private $_nama;
	private $_jk;
	private $_hp;

	public function __construct($id, $nama, $jk, $hp)
	{
		$this->setId($id);
		$this->setNama($nama);
		$this->setJk($jk);
		$this->setHp($hp);
	}

	public function getId()
	{
		return $this->_id;
	}

	public function getNama()
	{
		return $this->_nama;
	}

	public function getJk()
	{
		return $this->_jk;
	}

	public function getHp()
	{
		return $this->_hp;
	}

	public function setId($id)
	{
		if ($id !== null && (!is_numeric($id) || $id <= 0 || $id > 922337322929 || $this->_id !== null)) {
			throw new PasienException("ID tidak valid");
		}

		$this->_id = $id;
	}

	public function setNama($nama)
	{
		if (strlen($nama) < 0 || strlen($nama) > 255) {
			throw new PasienException("Inputan nama pasien tidak sesuai!");	
		}

		$this->_nama = $nama;
	}

	public function setJk($jk)
	{
		if ($jk === null || !in_array($jk, ['L','P'])) {
			throw new PasienException("Inputan jenis kelamin tidak sesuai!");	
		}

		$this->_jk = $jk;
	}

	public function setHp($hp)
	{
		if ($hp === null || !is_numeric($hp) || strlen($hp) < 9 || strlen($hp) > 14) {
			throw new PasienException("Inputan No. HP tidak sesuai!");	
		}

		$this->_hp = $hp;
	}

	public function returnPasienAsArray()
	{
		$pasien = [];
		$pasien['id'] = $this->getId();
		$pasien['nama'] = $this->getNama();
		$pasien['jk'] = $this->getJk();
		$pasien['no'] = $this->getHp();

		return $pasien;
	}

}