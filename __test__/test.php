 <?php

include_once(__DIR__."/../utils/obter-vinculo-produto.php");
include_once(__DIR__."/../utils/enviar-saldo.php");
include_once(__DIR__."/../utils/obter-etiqueta.php");
include_once(__DIR__."/../utils/enviar-foto.php");

 $objfotos = new  EnviarFotos();
 $returnFunc = $objfotos->enviarFotos(661);
 print_r($returnFunc);
 
 ?>