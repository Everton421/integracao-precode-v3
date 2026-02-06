 <?php
/*
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');
require_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_vendas.php');
include_once(__DIR__.'/../database/conexao_estoque.php');
include_once(__DIR__.'/enviar-foto.php'); // Inclua o arquivo com a classe EnviarFotos

Class EnviarProduto{
    public function enviarProduto(int $codigoProduto ){


        $publico = new CONEXAOPUBLICO();
        $enviarFotos = new EnviarFotos();
        $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

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
                      return $this->response(false, ' token da aplicação não foi fornecido'  );
                }
    
            
            //verifica se o produto já foi incluido na tabela precode anteriormente
            $produtoPrecode = $publico->Consulta("SELECT * FROM produto_precode where codigo_bd = $codigoProduto");

           
            if ((mysqli_num_rows($produtoPrecode)) == 0) {
                $sqlProdutoIntersig = $publico->Consulta("SELECT p.CODIGO,
                p.OUTRO_COD,
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
                    p.ORIGEM,
                    p.FINALCATEGORIA_MKTPLACE,
                    p.MODELO_MKTPLACE,
                    p.NUM_FABRICANTE,       
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

                 $origem = 'Nacional';

                 if($prod['ORIGEM'] == 1 || $prod['ORIGEM'] == 2 || $prod['ORIGEM'] == 6 || $prod['ORIGEM'] == 7  ){
                    $origem = 'Importado';
                 }

                $json['product']['sku'] = null;
                $json['product']['name'] =  mb_convert_encoding( str_replace('"', ' ', $prod['DESCRICAO']), 'UTF-8', 'ISO-8859-1') ;
                $json['product']['shortName'] =  mb_convert_encoding( str_replace('"', ' ', $prod['DESCRICAO']), 'UTF-8', 'ISO-8859-1') ;
                $json['product']['description'] = mb_convert_encoding($prod['APLICACAO'], 'UTF-8', 'ISO-8859-1' ); //campo descricao detalhada do produto 
                $json['product']['googleDescription'] = mb_convert_encoding($prod['DESCRICAO'], 'UTF-8', 'ISO-8859-1' ); //campo descricao detalhada do produto 
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
                $json['product']['model'] =   !empty($prod['MODELO_MKTPLACE']) ? $this->removerAcentos($prod['MODELO_MKTPLACE']) : null ; 
                $json['product']['gender'] = '';
                $json['product']['volumes'] = !empty($prod['VOLUMES']) ? $prod['VOLUMES'] : 0 ;
                $json['product']['warrantyTime'] = $prod['GARANTIA'];
                $json['product']['category'] = !empty($prod['CATEGORIA']) ? $prod['CATEGORIA'] : '';
                $json['product']['subcategory'] = !empty($prod['SUBCATEGORIA']) ? $this->removerAcentos($prod['SUBCATEGORIA']) : '';
                $json['product']['endcategory'] = !empty($prod['FINALCATEGORIA_MKTPLACE']) ? $this->removerAcentos($prod['FINALCATEGORIA_MKTPLACE']) : '';
                $json['product']['manufacturing']  =  $origem;
                $json['product']['attribute'] = [['key' => '', 'value' => '']];
                $json['product']['variations'] = [
                    [
                        'ref' => $prod['OUTRO_COD'],
                        #'sku' => !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0,
                        #'sku' => '',
                        'qty' => 0,
                        'ean' => !empty($prod['NUM_FABRICANTE']) ? $prod['NUM_FABRICANTE'] : null,
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
                        if(!empty($retorno)){

                            $codigo_bd = $prod['CODIGO'];
                            $preco_site = $prod['PRECO'];
                            $codigo_site = isset($retorno['sku']) ? $retorno['sku'] : null; // Verifica se 'sku' existe
                            $data_recad = date('Y-m-d H:i:s');
                        $sql = "INSERT INTO produto_precode (codigo_site, codigo_bd, preco_site, data_recad) VALUES ('$codigo_site', $codigo_bd, $preco_site, '$data_recad')";
                            $envioPrecodeBase = $publico->Consulta($sql);
                                //echo '<br>';
                            if ($envioPrecodeBase) {
                                return $this->response(true, '  O produto foi enviado para a plataforma com sucesso!'  );
                            } else {
                               // echo '<strong>SQL:</strong> ' . htmlspecialchars($sql); // Mostra a query para debug
                                return $this->response(true, ' o  Produto '.$codigoProduto .' foi enviado para a plataforma <br> porém nao foi registrado na tabela de controle!  '  );
                            }
                        }else{
                                return $this->response(false, ' solicitação recebida, porém a api não deu retorno, verifique se foi feita a inclusao do produto '.$codigoProduto.' no precode  '  );

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
                 $publico->Desconecta();
            }

        private function response(bool $success, string $message, $data = null): string {
        return json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
    }


 private   function removerAcentos(string $string): string
{
  $map = array(
    '/[áàãâä]/u' => 'a',
    '/[ÁÀÃÂÄ]/u' => 'A',
    '/[éèêë]/u' => 'e',
    '/[ÉÈÊË]/u' => 'E',
    '/[íìîï]/u' => 'i',
    '/[ÍÌÎÏ]/u' => 'I',
    '/[óòõôö]/u' => 'o',
    '/[ÓÒÕÔÖ]/u' => 'O',
    '/[úùûü]/u' => 'u',
    '/[ÚÙÛÜ]/u' => 'U',
    '/[ç]/u' => 'c',
    '/[Ç]/u' => 'C',
    '/[ñ]/u' => 'n',
    '/[Ñ]/u' => 'N',
  );

    return preg_replace(array_keys($map), array_values($map), $string);

}
}
 */

?>

