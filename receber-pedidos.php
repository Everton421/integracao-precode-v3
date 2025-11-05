<?php

    include_once(__DIR__."/utils/receber-pedidos.php");
	$dadosEnvio = new recebePrecode();	
	$dadosEnvio->recebe();
?>
