<?php /**
 * 
 */
class Response
{
	private $_success;
	private $_httpStatusCode;
	private $_messages = [];
	private $_data;
	private $_toCache = false;
	private $_responseData = [];

	public function setSuccess($success)
	{
		$this->_success = $success;
	}

	public function setHttpStatusCode($httpStatusCode)
	{
		$this->_httpStatusCode = $httpStatusCode;
	}

	public function setMessages($messages)
	{
		$this->_messages[] = $messages;
	}

	public function setData($data)
	{
		$this->_data = $data;
	}

	public function toCache($toCache)
	{
		$this->_toCache = $toCache;
	}

	public function setResponseData($responseData)
	{
		$this->_responseData = $responseData;
	}

	public function send()
	{
		header('Content-Type: application/json;charset=utf-8');

		if ($this->_toCache == true) {
			header('Cache-Control: max-age=60');
		} else {
			header('Cache-Control: no-cache, no-store');
		}

		if ($this->_success !== true && $this->_success !== false) {
			http_response_code(500);

			$this->_responseData['statusCode'] = 500;
			$this->_success = false;
			$this->setMessages('Response creation error');
			$this->_responseData['messages'] = $this->_messages;
		} else {
			http_response_code($this->_httpStatusCode);
			$this->_responseData['statusCode'] = $this->_httpStatusCode;
			$this->_responseData['success'] = $this->_success;
			$this->_responseData['messages'] = $this->_messages;
			$this->_responseData['data'] = $this->_data;
		}

		echo json_encode($this->_responseData);
	}

}