<?php 

class JadwalException extends Exception { }

class Jadwal
{
	
	private $_id;
	private $_hari;
	private $_kuota;

	public function __construct($id, $hari, $kuota)
	{
		$this->setId($id);
		$this->setHari($hari);
		$this->setKuota($kuota);
	}

	// Setiap function set, kasih validasi
	public function setId($id)
	{
		if ($id !== null && (!is_numeric($id) || $id <= 0 || $id > 99 || $this->_id !== null)) {
			throw new PasienException("ID tidak valid");
		}
		$this->_id = $id;
	}

	public function setHari($hari)
	{
		if (strlen($hari) < 0 || strlen($hari) > 15) {
			throw new PasienException("Inputan hari tidak sesuai!");	
		}
		$this->_hari = $hari;
	}

	public function setKuota($kuota)
	{
		if ($kuota !== null && (!is_numeric($kuota) || $kuota <= 0 || $kuota > 999 || $this->_kuota !== null)) {
			throw new PasienException("Kuota tidak valid");
		}
		$this->_kuota = $kuota;
	}

	// mengembalikan nilai
	public function getId()
	{
		return $this->_id;
	}

	public function getHari()
	{
		return $this->_hari;
	}

	public function getKuota()
	{
		return $this->_kuota;
	}

	public function returnJadwalAsArray()
	{
		$jdwl = [];
		$jdwl['id'] = $this->getId();
		$jdwl['day'] = $this->getHari();
		$jdwl['total'] = $this->getKuota();

		return $jdwl;
	}


}