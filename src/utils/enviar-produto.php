<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');
require_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_vendas.php');
include_once(__DIR__.'/../database/conexao_estoque.php');
include_once(__DIR__.'/enviar-foto.php'); // Inclua o arquivo com a classe EnviarFotos

Class EnviarProduto{
    public function enviarProduto(int $codigoProduto ){


        $vendas = new CONEXAOVENDAS();
        $publico = new CONEXAOPUBLICO();
        $enviarFotos = new EnviarFotos();

        $ini = parse_ini_file(__DIR__ .'/../../conexao.ini', true);

        $tabelaDePreco = 1;
        if($ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco']) ){
            $tabelaDePreco =$ini['conexao']['tabelaPreco']; 
        }

        if(empty($ini['conexao']['token'] )){
            echo 'token da aplicação não fornecido';
                exit();
        }

        $appToken = $ini['conexao']['token'];

        
        if(!$appToken || empty($appToken)){
                    echo '<div class="container">';
                        echo '<div class="alert alert-danger" role="alert">';
                        echo '<strong>Erro!</strong>token da aplicação não foi fornecido ';
                        echo '<br>';
                        echo '</div>';
                    echo '</div>';
                    exit();
            
                }
        if(!$tabelaDePreco || empty($tabelaDePreco)){
                    echo '<div class="container">';
                        echo '<div class="alert alert-danger" role="alert">';
                        echo '<strong>Erro!</strong>não foi fornecido o codigo da tabela de preço ';
                        echo '<br>';
                        echo '</div>';
                    echo '</div>';
                    exit();
            
                }


        if ($codigoProduto == '' || $codigoProduto == 0) {
            
            echo '<div class="container">';
                        echo '<div class="alert alert-danger" role="alert">';
                        echo '<strong>Erro!</strong>O Código do produto não foi preenchido ';
                        echo '<br>';
                        echo '</div>';
                    echo '</div>';

        } else {
            
            //verifica se o produto já foi incluido na tabela precode anteriormente
            $produtoPrecode = $publico->Consulta("SELECT * FROM produto_precode where codigo_bd = $codigoProduto");

            if ((mysqli_num_rows($produtoPrecode)) == 0) {
            //   echo 'Buscando o produto para ser enviado <br>';

                $sqlProdutoIntersig = $publico->Consulta("SELECT p.CODIGO,
                  p.DATA_RECAD,
                    p.SKU_MKTPLACE,
                    p.DESCR_REDUZ,
                    p.DESCR_CURTA,
                    p.DESCR_LONGA,
                    p.DESCRICAO,
                    p.APLICACAO,
                    p.GARANTIA,
                    p.COMPRIMENTO,
                    p.LARGURA,
                    p.ALTURA,         
                    p.PESO,       
                    tp.PRECO,       
                    m.descricao AS MARCA,
                    cf.NCM,
                    sg.DESCRICAO AS SUBCATEGORIA,
                    cg.NOME AS CATEGORIA
                FROM cad_prod p
                INNER JOIN prod_tabprecos tp ON p.CODIGO = tp.PRODUTO
                LEFT JOIN cad_pmar m ON m.codigo = p.marca
                LEFT JOIN class_fiscal cf ON cf.CODIGO = p.CLASS_FISCAL
                LEFT JOIN cad_pgru cg ON cg.CODIGO = p.GRUPO
                LEFT join subgrupos sg ON sg.CODIGO = p.SUBGRUPO
                WHERE (p.NO_MKTP='S'AND p.ATIVO='S')  AND tp.tabela = $tabelaDePreco AND p.CODIGO = $codigoProduto");
                
                //montagem de produto no intersig
                $prod = mysqli_fetch_array($sqlProdutoIntersig, MYSQLI_ASSOC);
                $resultFunction =[];
                if ($prod == null || empty($prod)) {
                    echo 'Produto não encontrado <br>';
                    return $this->response(false, 'Produto não encontrado'   );
                }
                if( $prod['MARCA'] == null  || $prod['MARCA'] == 0 ){
                    return $this->response(false, 'O campo MARCA não foi atribuido'   );
                }
                if( $prod['CATEGORIA'] == null  || $prod['CATEGORIA'] == 0 ){
                    return $this->response(false, 'O campo CATEGORIA/GRUPO não foi atribuido'   );
                }
                if ($prod['PESO'] == 0) {
                    return $this->response(false, 'O campo PESO não foi atribuido'   );
                }

                if ($prod['LARGURA'] == 0) {
                    return $this->response(false, 'O campo LARGURA não foi atribuido'  );
                }
                
                if ($prod['ALTURA'] == 0) {
                     return $this->response(false, 'O campo ALTURA não foi atribuido'  );
                }
                
                if ($prod['COMPRIMENTO'] == 0) {
                      return $this->response(false, 'O campo COMPRIMENTO não foi atribuido'  );
                }

                
                $json = [];

                // envio das fotos
                $fotos=[];        
                 try {
                     $fotos = $enviarFotos->enviarFotos($codigoProduto );   
                     //   print_r( $fotos );
                 } catch (Exception $e) {
                     echo "Erro: " . $e->getMessage();
                 }

            #  $json['product']['sku'] = !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0;

                $json['product']['sku'] = null;
                $json['product']['name'] =  mb_convert_encoding( str_replace('"', ' ', $prod['DESCRICAO']), 'UTF-8', 'ISO-8859-1') ;
                $json['product']['description'] = mb_convert_encoding($prod['DESCRICAO'], 'UTF-8', 'ISO-8859-1' ); //campo descricao detalhada do produto 
                $json['product']['status'] = 'enabled';
                $json['product']['price'] = floatval($prod['PRECO']);
                $json['product']['promotional_price'] = floatval($prod['PRECO']);
                $json['product']['cost'] = floatval($prod['PRECO']);
                $json['product']['weight'] = !empty($prod['PESO']) ? floatval($prod['PESO']) : 0;
                $json['product']['width'] = !empty($prod['LARGURA']) ? floatval($prod['LARGURA']) : 0;
                $json['product']['height'] = !empty($prod['ALTURA']) ? floatval($prod['ALTURA']) : 0;
                $json['product']['length'] = !empty($prod['COMPRIMENTO']) ? floatval($prod['COMPRIMENTO']) : 0;
                $json['product']['brand'] = $prod['MARCA'];
                $json['product']['nbm'] = !empty($prod['NCM']) ? str_replace(".","",$prod['NCM'])  : '';
                $json['product']['model'] = null;
                $json['product']['gender'] = '';
                $json['product']['volumes'] = !empty($prod['VOLUMES']) ? $prod['VOLUMES'] : 0 ;
                $json['product']['warrantyTime'] = $prod['GARANTIA'];
                $json['product']['category'] = !empty($prod['CATEGORIA']) ? $prod['CATEGORIA'] : '';
                $json['product']['subcategory'] = !empty($prod['SUBCATEGORIA']) ? $prod['SUBCATEGORIA'] : '';
                $json['product']['endcategory'] = !empty($prod['SUBCATEGORIA']) ? $prod['SUBCATEGORIA'] : '';
                $json['product']['attribute'] = [['key' => '', 'value' => '']];
                $json['product']['variations'] = [
                    [
                        'ref' => $prod['CODIGO'],
                        'sku' => !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0,
                        #'sku' => '',
                        'qty' => 0,
                        'ean' => !empty($prod['EAN']) ? $prod['EAN'] : null,
                        'images' => $fotos,
                        'specifications' => [
                            [
                                'key' => '', 
                                'value' => ''
                            ]
                        ]
                    ]
                ];

                
                 $curl = curl_init();

                
                curl_setopt($curl, CURLOPT_URL, 'https://www.replicade.com.br/api/v3/products');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json));
                curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . $appToken 
                ]);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);

                if (curl_errno($curl)) {
                    throw new Exception(curl_error($curl));
                }

                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                curl_close($curl);
                    $retorno = json_decode($response, true);

                if ($httpCode == 200 || $httpCode == 201) {

                        $codigo_bd = $prod['CODIGO'];
                        $preco_site = $prod['PRECO'];
                        $codigo_site = isset($retorno['sku']) ? $retorno['sku'] : null; // Verifica se 'sku' existe
                        $data_recad = date('Y-m-d H:i:s');
                        $sql = "INSERT INTO produto_precode (codigo_site, codigo_bd, preco_site, data_recad) VALUES ('" . htmlspecialchars($codigo_site) . "', " . intval($codigo_bd) . ", " . floatval($preco_site) . ", '" . htmlspecialchars($data_recad) . "')";

                        $envioPrecodeBase = $publico->Consulta($sql);

                        if ($envioPrecodeBase) {
                            return $this->response(true, '  O produto foi enviado para a plataforma com sucesso!'  );
                        } else {
                            echo '<strong>SQL:</strong> ' . htmlspecialchars($sql); // Mostra a query para debug
                            return $this->response(true, ' o  Produto '.$codigoProduto .' foi enviado para a plataforma <br> porém nao foi registrado na tabela de controle!  '  );

                        }
                } else {
                    if (isset($retorno['message'])) {
                     return $this->response(false, ' Houve um erro no envio do produto, contate a plataforma. <br>  <strong>Mensagem de Erro:</strong> ' . htmlspecialchars(print_r($retorno['message'], true))  );
                    } else {
                     return $this->response(false, ' Houve um erro no envio do produto, <strong>Mensagem de Erro:</strong> Detalhes não fornecidos.');
                    }
                }
                 
              
                } else {
                      return $this->response(false, ' Produto já foi enviado para a plataforma!'  );

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


 