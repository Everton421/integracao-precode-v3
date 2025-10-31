<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');
include(__DIR__.'/database/conexao_publico.php');
include(__DIR__.'/database/conexao_estoque.php'); 
include(__DIR__.'/database/conexao_vendas.php');


$curl;    	
      
$ini = parse_ini_file(__DIR__ .'/conexao.ini', true);

$tabelaprecopadrao = 1;
if($ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco']) ){
    $tabelaprecopadrao =$ini['conexao']['tabelaPreco']; 
}


if(empty($ini['conexao']['token'] )){
    echo 'token da aplicação não fornecido';
        exit();
}

  $appToken =  $ini['conexao']['token'];

$indice; 
$publico = new CONEXAOPUBLICO();	
$vendas = new CONEXAOVENDAS();
$estoque = new CONEXAOESTOQUE();


echo '<div class="card-header alert alert-info" align="center"><h3 style="color: #008080;""><b>Buscando informações fiscais..</b></h3>'; //abrindo o header com informação
echo '</div>';

$busca_nf = $vendas->Consulta("SELECT pid.situacao, co.COD_SITE, cnf.CHAVE_NFE, cnf.NUMERO_NF, cnf.DATA_EMISSAO, cnf.SERIE, xf.XML_NFE FROM cad_orca co 
                                                inner join pedido_precode pid on co.codigo = pid.codigo_pedido_bd
                                                inner join cad_nf cnf on cnf.pedido = co.codigo
                                                inner join xml_fatur xf on xf.FATUR = cnf.CODIGO
                                                where cnf.CHAVE_NFE != ''
                                                and pid.situacao = 'aprovado'");
$retorno = mysqli_num_rows($busca_nf);
if($retorno > 0 ){
    while($row = mysqli_fetch_array($busca_nf, MYSQLI_ASSOC)){
        $id_pedido  = $row['COD_SITE'];
        $chave_nf  = $row['CHAVE_NFE'];
        $numero_nf  = $row['NUMERO_NF'];
        $data_emissao  = $row['DATA_EMISSAO'];
        $xml  = base64_encode($row['XML_NFE']);
        $status = $row['situacao'];
        $serie = $row['SERIE'];

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/faturamento",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS =>"
        {
            \r\n\"pedido\": 
            [
                \r\n
                {
                    \r\n\"codigoPedido\": $id_pedido,
                    \r\n\"chaveNF\": \"$chave_nf\",
                    \r\n\"xml\": \"$xml\"\r\n        
                }
                \r\n    
            ]
            \r\n
        }",
        CURLOPT_HTTPHEADER => array(
            "Authorization: $appToken",
            "Content-Type: application/json"
        ),
        ));
        print_r($curl);
        $response = curl_exec($curl);
        print_r($response);        
        curl_close($curl);
        $decode = json_decode($response);
        $codMensagem = $decode->pedido[0]->idRetorno; 
        $mensagem_nf_err = $decode->pedido[0]->mensagem;
        $numeroPedido = $decode->pedido[0]->numeroPedido;
        $busca_status = $vendas->Consulta("select * from pedido_precode where codigo_pedido_site = '$id_pedido' and situacao = 'aprovado'");
        $retorno2 = mysqli_num_rows($busca_status);					
        
        if ($codMensagem == 0){
            echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> Nota inserida com sucesso!<br>Cód:'.$id_pedido.'<br>'.$mensagem_nf_err;   
            echo '</div>';
            $sql = "update pedido_precode set situacao = 'nota_enviada' where codigo_pedido_site = '$id_pedido'";
            print_r($sql);
            if(mysqli_query($vendas->link, $sql) === TRUE){
                echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> XML da nota inserida com sucesso!';   
                echo "<br><br>";
                print_r("
                {
                    \r\n\"pedido\": 
                    [
                        \r\n
                        {
                            \r\n\"codigoPedido\": $id_pedido,
                            \r\n\"chaveNF\": \"$chave_nf\",
                            \r\n\"xml\": \"$xml\"\r\n        
                        }
                        \r\n    
                    ]
                    \r\n
                }"); 
                echo "<br><br>";
                echo '</div>';
            }else{
                echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center">Falha ao inserir XML da  nota fiscal';  
                echo "<br><br>";
                print_r("
                {
                    \r\n\"pedido\": 
                    [
                        \r\n
                        {
                            \r\n\"codigoPedido\": $id_pedido,
                            \r\n\"chaveNF\": \"$chave_nf\",
                            \r\n\"xml\": \"$xml\"\r\n        
                        }
                        \r\n    
                    ]
                    \r\n
                }"); 
                echo "<br><br>";
                echo '</div>';
            }
            echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
            print_r(date('d/m/Y h:i:s'));                    
            echo '</div></b>';
        } else{
            echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center">Falha ao inserir nota fiscal <br>Cód:'.$numeroPedido.'<br>'.$mensagem_nf_err;   
            echo "<br><br>";
                print_r("
                {
                    \r\n\"pedido\": 
                    [
                        \r\n
                        {
                            \r\n\"codigoPedido\": $id_pedido,
                            \r\n\"chaveNF\": \"$chave_nf\",
                            \r\n\"xml\": \"$xml\"\r\n        
                        }
                        \r\n    
                    ]
                    \r\n
                }"); 
                echo "<br><br>";
            echo '</div>';
            echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
            print_r(date('d/m/Y h:i:s'));                    
            echo '</div></b>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>'; 
            echo "</main>";
        } 
    }
}else{
    echo '<div class="card-header alert alert-warning"> <h3 style="color: orange;" align="center">Não há notas/XML pendentes';   
    echo '</div>';
    echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
    print_r(date('d/m/Y h:i:s'));                    
    echo '</div></b>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>'; 
    echo "</main>";    
} 
?>




