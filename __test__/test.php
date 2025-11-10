 <?php

include_once(__DIR__."/../utils/obter-vinculo-produto.php");
include_once(__DIR__."/../utils/enviar-saldo.php");
include_once(__DIR__."/../utils/obter-etiqueta.php");

 

    $obj = new ObterEtiqueta();

       $obj->getEtiquetas(124483);
 ?>