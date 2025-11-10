<?php
include(__DIR__.'/../database/conexao_publico.php');
include(__DIR__.'/../database/conexao_estoque.php'); 
include(__DIR__.'/../database/conexao_vendas.php');
include(__DIR__.'/../utils/enviar-saldo.php');

$ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

$setor = 1;

if($ini['conexao']['setor'] && !empty($ini['conexao']['setor']) ){
    $setor =$ini['conexao']['setor']; 
}

if(empty($ini['conexao']['token'] )){
    echo 'token da aplicação não fornecido';
        exit();
}
$appToken = $ini['conexao']['token'];

$objEnvSaldo = new EnviarSaldo();
$publico = new CONEXAOPUBLICO();	
$databasePublico = $publico->getBase();


$buscaProdutos = $publico->Consulta("SELECT codigo_site,saldo_enviado, codigo_bd, data_recad, data_recad_estoque FROM produto_precode" ); 		


  if((mysqli_num_rows($buscaProdutos))  == 0){
        return;
  }

    while($row = mysqli_fetch_array($buscaProdutos, MYSQLI_ASSOC)){
        $codigoBd = $row['codigo_bd'];
        sleep(1);
        $resultEnviSaldo = $objEnvSaldo->postSaldo($codigoBd);
        print_r($resultEnviSaldo);
    }
    $publico->Desconecta();
  print_r(date('d/m/Y h:i:s'));

?>