 <?php

include_once(__DIR__."/../utils/obter-vinculo-produto.php");
include_once(__DIR__."/../utils/enviar-saldo.php");

 $objeVinc = new ObterVinculo();
 
 

    $obj = new EnviarSaldo();

      //$result =  $obj->postSaldo(20);
//     print_r( mb_convert_encoding($result,'UTF-8',$o ) );

    //     $result = json_decode($result);
//
    // print_r(  $result->message  );

   $utlimoEnv = 2;

   $novoSaldo = 2;

   if( $novoSaldo == $utlimoEnv){

      $utlimoEnv =$utlimoEnv  - 1 ;
   }

   print_r($utlimoEnv);
 ?>