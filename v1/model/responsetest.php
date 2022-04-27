<?php 

require_once('Response.php');

$res = new Response();
$res->setMessages('Something error with value');
$res->setSuccess(false);
$res->setHttpStatusCode(400);
$res->send();

?>