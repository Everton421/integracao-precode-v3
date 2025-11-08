<?php
date_default_timezone_set('America/Sao_Paulo');
include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_estoque.php'); 
include_once(__DIR__.'/../database/conexao_vendas.php');


class EnviarSaldo{

    /**
     * @codigo
     */
    public function postSaldo(int $codigo  ){
        set_time_limit(0);

            $setor=1;   
            $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

            if($ini['conexao']['setor'] && !empty($ini['conexao']['setor']) ){
                $setor =$ini['conexao']['setor']; 
            }
            if(empty($ini['conexao']['token'] )){
            echo 'token da aplicação não fornecido';
                return $this->response(false,'token da aplicação não fornecido');
            }

            
        $forcar_envio_estoque = false;

        if( isset($ini['config']['forcar_envio_estoque']) ){
            $forcar_envio_estoque  = filter_var($ini['config']['forcar_envio_estoque'], FILTER_VALIDATE_BOOLEAN );
        }

            $appToken = $ini['conexao']['token'];

                $publico = new CONEXAOPUBLICO();	
                $vendas = new CONEXAOVENDAS();
                $estoque = new CONEXAOESTOQUE();
                
                $databaseVendas = $vendas->getBase();
                $databaseEstoque = $estoque-> getBase();
                $databasePublico = $publico->getBase();


            $buscaProduto = $publico->Consulta("SELECT codigo_site,saldo_enviado, codigo_bd, data_recad, data_recad_estoque FROM produto_precode where codigo_bd= $codigo" ); 		
                if((mysqli_num_rows($buscaProduto)) == 0){
                        return $this->response(false,'produto '. $codigo .'não foi encontrado no erp!');
                }

            while($row = mysqli_fetch_array($buscaProduto, MYSQLI_ASSOC)){
                $codigoSite = $row['codigo_site'];
                $codigoBd = $row['codigo_bd'];
                $saldoEnviadoPrecode = $row['saldo_enviado'];
                $dataRecadEstoquePrecode = new DateTime( $row['data_recad_estoque']);
                $dataRecadEstoquePrecode =  date_format($dataRecadEstoquePrecode, 'Y-m-d H:i:s') ; // data do ultimo envio do saldo, usuada para comparar se é necessario atualizar o saldo
                if($codigoSite == 0){
                        return $this->response(false,'produto '. $codigo .'não foi encontrado,  codigo_site: '.$codigoSite.' inexistente na tabela produto_precode ! ');

                }else{
                    $estoqueprod = 0;       

                $buscaEstoque = $estoque->Consulta(  "  SELECT  
                                                        est.CODIGO, est.referencia,
                                                            IF(est.estoque < 0, 0, est.estoque) AS ESTOQUE,
                                                            est.DATA_RECAD
                                                        FROM 
                                                            (SELECT
                                                            P.CODIGO,P.OUTRO_COD as referencia,
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
                            $referencia = $row_estoque['referencia'];


                            // se for para forçar estoque, verifica se o saldo atual é igual ao ultimo enviado 
                            // e altera o valor para que fique diferente e faz o envio no proximo if
                            if($forcar_envio_estoque == true ){
                                    if($estoqueprod == $saldoEnviadoPrecode){
                                        $saldoEnviadoPrecode = $saldoEnviadoPrecode - 1;
                                    }
                            }


                            // verfica se o saldo atual é igual ao ultimo enviado 
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
                                        \r\n\"IdReferencia\": \"$referencia\",
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
                                //print_r( $resultado);
                                if( $codMensagem == '0'){
                                        $resultUpdateProduct = $publico->Consulta("UPDATE produto_precode set SALDO_ENVIADO =  $estoqueprod  ,DATA_RECAD_ESTOQUE = NOW() where CODIGO_SITE = '$codigoSite' ");

                                        if($resultUpdateProduct != 1 ){
                                        return $this->response(false,'Ocorreu um erro ao tentar atualizar a data de envio do estoque do produto '.$codigo.'na tabela produto_recode!');
                                        }
                                        return $this->response(true,"$mensagem | estoque  atualizado para o produto $codigo  |  Código da mensagem:  $codMensagem  ");
                                                
                                }else{
                                        return $this->response(false, "Erro ao atualizar estoque   $mensagem Código da mensagem:  $codMensagem <BR> HTTP Cód:  $httpcode");
                                }
                                curl_close($curl); 
                        }
                        }
                    }
                    
                        
                }
            
            }
    }


       private function response(bool $success, string $message, $data = null): string {
        return json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
    }
}

?>