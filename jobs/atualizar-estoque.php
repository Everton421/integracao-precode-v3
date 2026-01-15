<?php
include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_estoque.php'); 
include_once(__DIR__.'/../database/conexao_vendas.php');
include_once(__DIR__.'/../utils/enviar-saldo.php');

$ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

$setor = 1;

if($ini['conexao']['setor'] && !empty($ini['conexao']['setor']) ){
    $setor = $ini['conexao']['setor']; 
}

if(empty($ini['conexao']['token'] )){
    echo 'token da aplicação não fornecido';
    exit();
}
$appToken = $ini['conexao']['token'];

$objEnvSaldo = new EnviarSaldo();

// 1. Instanciar TODAS as conexões FORA do loop
$publico = new CONEXAOPUBLICO();
$estoque = new CONEXAOESTOQUE();
$vendas = new CONEXAOVENDAS();

// Opcional: Testar se conectou corretamente
// (As classes já fazem die() se der erro, mas é bom saber que estão ativas aqui)

$databasePublico = $publico->getBase();

$buscaProdutos = $publico->Consulta("SELECT codigo_site,saldo_enviado, codigo_bd, data_recad, data_recad_estoque FROM produto_precode  " ); 

if((mysqli_num_rows($buscaProdutos)) == 0){
    // Se não tiver produtos, fechamos tudo e saímos
    $publico->Desconecta();
    $estoque->Desconecta();
    $vendas->Desconecta();
    return;
}

while($row = mysqli_fetch_array($buscaProdutos, MYSQLI_ASSOC)){
    $codigoBd = $row['codigo_bd'];
    sleep(1);
    
    // 2. Passar as instâncias das conexões para a função
    $resultEnviSaldo = $objEnvSaldo->postSaldo($codigoBd, $publico, $estoque, $vendas);
    print_r($resultEnviSaldo);
}

// 3. Desconectar tudo apenas NO FINAL do script
$publico->Desconecta();
$estoque->Desconecta();
$vendas->Desconecta();

//print_r(date('d/m/Y h:i:s'));
?>