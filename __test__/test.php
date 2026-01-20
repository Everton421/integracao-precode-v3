 <?php

include_once(__DIR__."/../utils/registrar-logs.php");
include_once(__DIR__.'/../database/conexao_vendas.php');
include_once(__DIR__.'/../utils/obter-vinculo-produto.php');
 $vendas = new CONEXAOVENDAS();

  $database = $vendas->getBase();
  $acao = 'teste';

 
$obj = new ObterVinculo();

$obj->getVinculo(679);

$vendas->Desconecta();
  
 ?> 