<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');
include_once('conexao_publico_rec.php');
include_once('conexao_estoque_rec.php'); 
include_once('conexao_vendas_rec.php');

$codigoProduto = $_POST['codigoProd'];

if(isset($_POST['prodzero']))
{
    $prodZero = 'S';
}else{
    $prodZero = 'N';
}



$curl;    	
$token = 'Basic dng0c29BenNKek9qSUFHQ0c6';
$tabelaprecopadrao = 4;
$indice; 
$Obj_Conexao_publico = new CONEXAOPUBLICO();	
$Obj_Conexao_vendas = new CONEXAOVENDAS();
$Obj_Conexao_estoque = new CONEXAOESTOQUE();

/*echo "<main class='login-form'>";
echo '<div class="cotainer">';
echo '<div class="row justify-content-center">';
echo '<div class="col-md-8">';*/
echo '<div class="card">';
echo '<div class="card-header alert alert-info" align="center"><h3 style="color: #008080;""><b>Buscando estoque</b></h3>'; //abrindo o header com informação
echo '</div>';
if($codigoProduto != ''){
    $buscaProdutos = $Obj_Conexao_publico->Consulta("SELECT codigo_site, codigo_bd, data_recad FROM produto_precode where codigo_bd = '$codigoProduto'" );
}else{
    $buscaProdutos = $Obj_Conexao_publico->Consulta("SELECT codigo_site, codigo_bd, data_recad FROM produto_precode" );
} 		
	 
if((mysqli_num_rows($buscaProdutos)) == 0){
    //$estoqueprod = 0; 
    echo "Este produto não está cadastrado, consulte a base de dados para concluir";
    
    
}else{
    while($row = mysqli_fetch_array($buscaProdutos, MYSQLI_ASSOC)){
        $codigoSite = $row['codigo_site'];
        $codigoBd = $row['codigo_bd'];
        $setor = 469;
        if($codigoSite == 0){
           

        }else{            
            $estoqueprod = 0;       
            $buscaEstoque = $Obj_Conexao_estoque->Consulta("select  
            est.CODIGO,
            est.DESCRICAO,
            est.ESTOQUE,
            FORMAT(if(est.pedido < 0, 0, est.pedido), 0) PEDIDO
            from 
                (select
                P.CODIGO,
                P.DESCRICAO,
                Sum(PS.ESTOQUE) ESTOQUE,
                (Select coalesce(Sum((If(PO.QTDE_SEPARADA > (PO.QUANTIDADE - PO.QTDE_MOV), PO.QTDE_SEPARADA, (PO.QUANTIDADE - PO.QTDE_MOV)) * PO.FATOR_QTDE) * If(CO.TIPO = '5', -1, 1)), 0)
                        From mesquita_vendas.cad_orca As CO
                        Left Outer Join mesquita_vendas.pro_orca As PO On PO.ORCAMENTO = CO.CODIGO
                        Left Outer Join mesquita_vendas.empresas As E On E.FILIAL = 2 
                        Where CO.SITUACAO In ('AI', 'AP', 'FP')
                        And ((E.MONT_FATUR <> 'S' And PO.APROVADO In ('A', 'C', 'G', 'P'))
                            Or
                            (E.MONT_FATUR = 'S' And PO.APROVADO In ('A', 'C', 'G')))
                        And PO.PRODUTO = P.CODIGO And CO.SETOR in(469,471)) as PEDIDO
            From mesquita_estoque.prod_setor PS		
            left join 	mesquita_publico.cad_prod P on P.CODIGO = PS.PRODUTO	
            left join 	mesquita_publico.cad_pgru G on P.GRUPO = G.CODIGO
            left join 	mesquita_vendas.empresas_setor S on PS.SETOR = S.SETOR and S.FILIAL = 2
            where P.NO_MKTP = 'S' AND P.ATIVO = 'S' 
            AND P.CODIGO = '$codigoBd' AND PS.SETOR in(469,471)
            group by P.CODIGO) as est ");
            $retornoestoque = mysqli_num_rows($buscaEstoque);
            //print_r($retornoestoque);
            
            if($retornoestoque > 0 ){   
                while($row_estoque = mysqli_fetch_array($buscaEstoque, MYSQLI_ASSOC)){	
                    $estoque1  = $row_estoque['ESTOQUE'];
                    $estoquePedido = $row_estoque['PEDIDO'];
                    //print_r($estoqueprod);
                    //echo "<br>";
                    $estoqueprod = $estoque1 - $estoquePedido;

                    if($prodZero == 'S'){
                        $estoqueprod = 0;
                    }else{
                        $estoqueprod = $estoqueprod;
                    }
                    
                    //$estoqueprod = 0; // alteração para enviar saldo zero para todos os itens 01/06/2021
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
                                    \r\n\"filialSaldo\": 2,
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
                        "Authorization: Basic dng0c29BenNKek9qSUFHQ0c6",
                        "Content-Type: application/json"
                    ),
                    ));
                    $result = curl_exec($curl);                    
                    $resultado = json_decode($result);
                    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);                    
                    $mensagem = $resultado->produto[0]->mensagem;
                    $codMensagem = $resultado->produto[0]->idMensagem;   
                    sleep(1);
                    print_r("
                    {
                        \r\n\"produto\": 
                        [\r\n
                        {
                            \r\n\"IdReferencia\": \"$codigoBd\",
                            \r\n\"sku\": 0,
                            \r\n\"estoque\": 
                            [\r\n                
                                {
                                    \r\n\"filialSaldo\": 2,
                                    \r\n\"saldoReal\": $estoqueprod,
                                    \r\n\"saldoDisponivel\": $estoqueprod
                                    \r\n                
                                }
                                \r\n            
                            ]\r\n        
                        }\r\n    
                        ]\r\n
                    }
                    ");
                            
                    if( $codMensagem == '0'){
                        echo '<div class="card-header alert alert-success"> <h3 style="color:green;" align="center">Estoque do produto '.$codigoBd.' atualizado com sucesso! <br> Novo Saldo: '.$estoqueprod.' Ref: '.$codigoBd;                          
                        echo '</div>';  
                        echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
                        print_r(date('d/m/Y h:i:s'));
                        echo '</div></b>';                   
                    }else{
                        echo '<div class="card-header alert alert-danger"> <h3 style="color:red;" align="center">Erro ao atualizar estoque '.$mensagem.' <br> Código da mensagem: '.$codMensagem.'<BR> HTTP Cód: '.$httpcode;                
                        echo '</div>';
                        echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
                        print_r(date('d/m/Y h:i:s'));
                        echo '</div></b>';             
                    }
                    curl_close($curl); 
                }
            }         
        }     
    }
}

?>




