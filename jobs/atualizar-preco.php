<?php
include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../utils/enviar-preco.php');

$publico = new CONEXAOPUBLICO();	

  $postPreco = new EnviarPreco();
   
 $buscaProdutos = $publico->Consulta("SELECT codigo_site,saldo_enviado, codigo_bd, data_recad, data_recad_estoque FROM produto_precode;" ); 		
    if((mysqli_num_rows($buscaProdutos))  == 0){
         return;
   }
 
     while($row = mysqli_fetch_array($buscaProdutos, MYSQLI_ASSOC)){
         $codigoBd = $row['codigo_bd'];
         sleep(1);
         $resultEnvPreco = $postPreco->postPreco($codigoBd);
         print_r($resultEnvPreco);
     }
    $publico->Desconecta();
 
  print_r(date('d/m/Y h:i:s'));

?>




