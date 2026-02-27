<?php
    include_once(__DIR__."/../services/pedidos/receber-pedidos.php");
	$dadosEnvio = new recebePrecode();	
	$dadosEnvio->recebe();
	
?>
