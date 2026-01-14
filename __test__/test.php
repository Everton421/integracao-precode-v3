 <?php

include_once(__DIR__."/../utils/obter-vinculo-produto.php");
include_once(__DIR__."/../utils/enviar-saldo.php");
include_once(__DIR__."/../utils/obter-etiqueta.php");
include_once(__DIR__."/../utils/enviar-foto.php");
include_once(__DIR__."/../utils/enviar-preco.php");

 $obj = new EnviarPreco();

 
  $result = $obj->postPreco(659);
print_r($result);
 ?> 