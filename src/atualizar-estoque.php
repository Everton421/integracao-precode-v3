<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');
include(__DIR__.'/database/conexao_publico.php');
include(__DIR__.'/database/conexao_estoque.php'); 
include(__DIR__.'/database/conexao_vendas.php');


$curl;    	

$ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

$tabelaprecopadrao = 1;
$setor = 1;

if($ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco']) ){
    $tabelaprecopadrao =$ini['conexao']['tabelaPreco']; 
}


if($ini['conexao']['setor'] && !empty($ini['conexao']['setor']) ){
    $setor =$ini['conexao']['setor']; 
}
   

if(empty($ini['conexao']['token'] )){
    echo 'token da aplicação não fornecido';
        exit();
}
$appToken = $ini['conexao']['token'];


$indice; 
$publico = new CONEXAOPUBLICO();	
$vendas = new CONEXAOVENDAS();
$estoque = new CONEXAOESTOQUE();

$databaseEstoque = $estoque->getBase();
$databaseVendas = $vendas->getBase();
$databasePublico = $publico->getBase();

/*echo "<main class='login-form'>";
echo '<div class="cotainer">';
echo '<div class="row justify-content-center">';
echo '<div class="col-md-8">';*/
echo '<div class="card">';
echo '<div class="card-header alert alert-info" align="center"><h3 style="color: #008080;""><b>Buscando estoque</b></h3>'; //abrindo o header com informação
echo '</div>';
$buscaProdutos = $publico->Consulta("SELECT codigo_site,saldo_enviado, codigo_bd, data_recad, data_recad_estoque FROM produto_precode" ); 		
	 
if((mysqli_num_rows($buscaProdutos)) == 0){
    
}else{
    while($row = mysqli_fetch_array($buscaProdutos, MYSQLI_ASSOC)){
        $codigoSite = $row['codigo_site'];
        $codigoBd = $row['codigo_bd'];
        $saldoEnviadoPrecode = $row['saldo_enviado'];
        $dataRecadEstoquePrecode = new DateTime( $row['data_recad_estoque']);
        $dataRecadEstoquePrecode =  date_format($dataRecadEstoquePrecode, 'Y-m-d H:i:s') ; // data do ultimo envio do saldo, usuada para comparar se é necessario atualizar o saldo
         if($codigoSite == 0){

        }else{
            $estoqueprod = 0;       

        $buscaEstoque = $estoque->Consulta(  "  SELECT  
                                                est.CODIGO,
                                                    IF(est.estoque < 0, 0, est.estoque) AS ESTOQUE,
                                                    est.DATA_RECAD
                                                FROM 
                                                    (SELECT
                                                    P.CODIGO,
                                                    PS.DATA_RECAD,
                                                    (SUM(PS.ESTOQUE) - 
                                                        (SELECT COALESCE(SUM((IF(PO.QTDE_SEPARADA > (PO.QUANTIDADE - PO.QTDE_MOV), PO.QTDE_SEPARADA, (PO.QUANTIDADE - PO.QTDE_MOV)) * PO.FATOR_QTDE) * IF(CO.TIPO = '5', -1, 1)), 0)
                                                        FROM ".$databaseVendas.".cad_orca AS CO
                                                        LEFT OUTER JOIN ".$databaseVendas.".pro_orca AS PO ON PO.ORCAMENTO = CO.CODIGO
                                                        WHERE CO.SITUACAO IN ('AI', 'AP', 'FP')
                                                        AND PO.PRODUTO = P.CODIGO)) AS estoque
                                                    FROM ".$databaseEstoque.".prod_setor AS PS
                                                    LEFT JOIN ".$databasePublico.".cad_prod AS P ON P.CODIGO = PS.PRODUTO
                                                    INNER JOIN ".$databasePublico.".cad_pgru AS G ON P.GRUPO = G.CODIGO
                                                    LEFT JOIN ".$databaseEstoque.".setores AS S ON PS.SETOR = S.CODIGO
                                                WHERE P.CODIGO = '$codigoBd'
                                                    AND PS.SETOR = '$setor'
                                                    GROUP BY P.CODIGO) AS est " );
       
            $retornoestoque = mysqli_num_rows($buscaEstoque);
 
            if($retornoestoque > 0 ){   
                while($row_estoque = mysqli_fetch_array($buscaEstoque, MYSQLI_ASSOC)){	
                    $estoqueprod  = $row_estoque['ESTOQUE'];

                    $dataRecadEstoqueSistema = new DateTime( $row_estoque['DATA_RECAD']  ); // data atualização do saldo no sistema
                        $dataRecadEstoqueSistema = date_format($dataRecadEstoqueSistema, 'Y-m-d H:i:s');

                    if(  $estoqueprod != $saldoEnviadoPrecode){

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://www.replicade.com.br/api/v1/produtoLoja/saldo",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "PUT",
                    CURLOPT_POSTFIELDS =>"
                    {
                        \r\n\"produto\": 
                        [\r\n
                        {
                            \r\n\"IdReferencia\": \"$codigoBd\",
                            \r\n\"sku\": 0,
                            \r\n\"estoque\": 
                            [\r\n                
                                {
                                    \r\n\"filialSaldo\": 1,
                                    \r\n\"saldoReal\": $estoqueprod,
                                    \r\n\"saldoDisponivel\": $estoqueprod
                                    \r\n                
                                }
                                \r\n            
                            ]\r\n        
                        }\r\n    
                        ]\r\n
                    }
                    ",        
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Basic ".$appToken,
                        "Content-Type: application/json"
                    ),
                    ));
                    $result = curl_exec($curl);                    
                    $resultado = json_decode($result);
                    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);                    
                    $mensagem = $resultado->produto[0]->mensagem;
                    $codMensagem = $resultado->produto[0]->idMensagem;   
                    sleep(1);
                    
                            
                    if( $codMensagem == '0'){
                            $resultUpdateProduct = $publico->Consulta("UPDATE produto_precode set SALDO_ENVIADO =  $estoqueprod  ,DATA_RECAD_ESTOQUE = NOW() where CODIGO_SITE = '$codigoSite' ");

                            if($resultUpdateProduct != 1 ){
                                    echo '<div class="alert alert-warning" role="alert">';
                                    echo '<strong>Atenção!</strong> Ocorreu um erro ao tentar atualizar a data de envio do estoque na tabela produto_recode!.';
                                    echo '<br>';
                                    echo '</div>';
                            }

                        echo '<br>';

                        echo '<div class="card-header alert alert-success"> <h3 style="color:green;" align="center">Estoque do produto '.$codigoBd.' atualizado com sucesso! <br> Novo Saldo: '.$estoqueprod.' Ref: '.$codigoBd;                          
                        echo '</div>';  
                        echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
                        print_r(date('d/m/Y h:i:s'));
                        echo '</div></b>';                   
                    }else{
                        echo '<div class="card-header alert alert-danger"> <h3 style="color:red;" align="center">Erro ao atualizar estoque  <br>  '.$mensagem.' <br> Código da mensagem: '.$codMensagem.'<BR> HTTP Cód: '.$httpcode;                
                        echo '</div>';
                        echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
                        print_r(date('d/m/Y h:i:s'));
                        echo '</div></b>';           
                        echo '<br>';           

                    }
                    curl_close($curl); 
                   }else{
                      //  echo $dataRecadEstoqueSistema.' < '.$dataRecadEstoquePrecode.'<br>';
                echo '<div class="card-header alert alert-success"> <h3 style="color:green;" align="center">';
                 echo 'Não ouve atualização do saldo produto '.$codigoBd.' no sistema ! <br>  Ref: '.$codigoBd;                          
                        echo '</div>';  
                        echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
                        print_r(date('d/m/Y h:i:s'));
                        echo '</div></b>';
                  }
                }
            }
            
                
        }     
    }
}

?>




