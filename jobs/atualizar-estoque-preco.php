<?php
include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_estoque.php'); 
include_once(__DIR__.'/../database/conexao_vendas.php');
include_once(__DIR__.'/../database/conexao_eventos.php');
include_once(__DIR__.'/../utils/enviar-saldo.php');
include_once(__DIR__.'/../utils/enviar-preco.php');

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

$obj_env_saldo = new EnviarSaldo();
$obj_env_preco = new EnviarPreco();

$publico = new CONEXAOPUBLICO();
$estoque = new CONEXAOESTOQUE();
$vendas = new CONEXAOVENDAS();
$eventos = new CONEXAOEVENTOS();


$databasePublico = $publico->getBase();
$database_eventos = $eventos->getBase();

              while( true ){
                sleep(5);

                   $eventos_produtos = $eventos->Consulta("SELECT * from ".$database_eventos.".eventos_produtos_sistema where status = 'PENDENTE';");

                       while($row1 = mysqli_fetch_array($eventos_produtos, MYSQLI_ASSOC)){
                            //echo '<br>';
                            //echo 'Evento capturado  ';
                            // print_r($row1);
                            //echo '<br>';

                            $codigo_produto = $row1['id_registro'];
                            $tabela_origem = $row1['tabela_origem'];
                            $id_evento = $row1['id'];

                                /// eventos nas tabelas pro_orca e nas tabelas prod_setor
                            if($tabela_origem == 'pro_orca' || $tabela_origem == 'prod_setor'){

                                echo "produto [ $codigo_produto ] Atualzando estoque...";

                                    // verifica se o produto possui vinculo com a precode
                                $result_itens = $publico->Consulta("SELECT codigo_site,saldo_enviado, codigo_bd, data_recad, data_recad_estoque FROM produto_precode WHERE CODIGO_BD = $codigo_produto;" );
                                    if((mysqli_num_rows($result_itens)) > 0){

                                            /// verifica a necessidade do envio do saldo do produto
                                            $resultEnviSaldo = $obj_env_saldo->postSaldo($codigo_produto, $publico, $estoque, $vendas);
                    
                                                $resultEnviSaldo = json_decode($resultEnviSaldo);

                                                 $eventos->Consulta( "UPDATE  ".$database_eventos.".eventos_produtos_sistema SET status = 'PROCESSADO' WHERE id = $id_evento;" );
                                        }
                             }

                             if( $tabela_origem =='prod_tabprecos'){
                                    // verifica se o produto possui vinculo com a precode
                                echo "produto [ $codigo_produto ] Atualzando preço...";

                                  $result_itens = $publico->Consulta("SELECT codigo_site,saldo_enviado, codigo_bd, data_recad, data_recad_estoque FROM produto_precode WHERE CODIGO_BD = $codigo_produto;" );
                                    if((mysqli_num_rows($result_itens)) > 0){
                                           $resultEnviPreco = $obj_env_preco->postPreco($codigo_produto, $publico);

                                          $resultEnviPreco = json_decode($resultEnviPreco);

                                                 $eventos->Consulta( "UPDATE  ".$database_eventos.".eventos_produtos_sistema SET status = 'PROCESSADO' WHERE id = $id_evento;" );
                                    }
                             }
                     }
              }
$publico->Desconecta();
$estoque->Desconecta();
$vendas->Desconecta();
$eventos->Desconecta();

?>