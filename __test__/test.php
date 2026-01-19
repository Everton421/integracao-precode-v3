 <?php

include_once(__DIR__."/../utils/registrar-logs.php");
include_once(__DIR__.'/../database/conexao_vendas.php');
 $vendas = new CONEXAOVENDAS();

  $database = $vendas->getBase();
  $acao = 'teste';

    $result = Logs::registrar(
     $vendas,
     $database,
     'sucesso',
     'registrar pedido',
        '',
    );

     
  if( $result ){
    echo  $result;
  }

$vendas->Desconecta();
  
 ?> 