<?php
    include_once(__DIR__."/../services/pedidos/receber-pedidos.php");
    include_once(__DIR__."/../utils/obter-notas.php");

	$obj_nfs = new ObterNotas();
		$obj_nfs->getNotas();

	
?>
