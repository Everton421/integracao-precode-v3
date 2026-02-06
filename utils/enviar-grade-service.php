<?php
/**  
ini_set('mysql.connect_timeout', '0');
ini_set('max_execution_time', '0');
date_default_timezone_set('America/Sao_Paulo');
require_once(__DIR__ . '/../database/conexao_publico.php');
include_once(__DIR__ . '/../database/conexao_vendas.php');
include_once(__DIR__ . '/../database/conexao_estoque.php');
include_once(__DIR__ . '/enviar-foto.php'); // Inclua o arquivo com a classe EnviarFotos

class EnviarGradeService
{
    public function enviar(int $codigo_grade)
    {


        $publico = new CONEXAOPUBLICO();
        $enviarFotos = new EnviarFotos();
        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

        $tabelaDePreco = 1;
        if ($ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco'])) {
            $tabelaDePreco = $ini['conexao']['tabelaPreco'];
        }

        if (empty($ini['conexao']['token'])) {
            echo 'token da aplicação não fornecido';
            exit();
        }

        $appToken = $ini['conexao']['token'];


        if (!$appToken || empty($appToken)) {
            return $this->response(false, ' token da aplicação não foi fornecido');
        }


        //verifica se o produto já foi incluido na tabela precode anteriormente
        $gradePrecode = $publico->Consulta("SELECT * FROM grade_precode where codigo_bd = $codigo_grade");


        if ((mysqli_num_rows($gradePrecode)) == 0) {
            $sqlGradeIntersig = $publico->Consulta("
                SELECT
                  p.GRADE ,
                  p.CODIGO,
                  p.OUTRO_COD,
                  p.DATA_RECAD, 
                  p.SKU_MKTPLACE,
                  p.DESCR_CURTA_MKTPLACE,
                  p.DESCR_LONGA_MKTPLACE,
                  p.DESCR_CURTA_SITE,
                  p.DESCRICAO,
                  p.APLICACAO,
                  P.GARANTIA,
                  p.COMPRIMENTO,
                  p.LARGURA,
                  p.ALTURA,
                  p.PESO,
                  p.ORIGEM,
                  p.CATEGORIA_MKTPLACE,
                  p.INTERM_CATEGORIA_MKTPLACE,
                  p.FINALCATEGORIA_MKTPLACE,
                  p.MODELO_MKTPLACE, 
                  p.NUM_FABRICANTE,
                  tp.PRECO,
                  tp.PROMOCAO,
                  m.descricao AS MARCA,
                  cf.NCM,
                  g.OUTRO_COD AS OUTRO_COD_GRADE
                FROM grades as g 
                LEFT JOIN cad_prod p on p.GRADE = g.codigo
                INNER JOIN prod_tabprecos tp ON p.CODIGO = tp.PRODUTO
                LEFT JOIN cad_pmar m ON m.codigo = p.marca
                LEFT JOIN class_fiscal cf ON cf.CODIGO = p.CLASS_FISCAL
                LEFT JOIN cad_pgru cg ON cg.CODIGO = p.GRUPO
                LEFT join subgrupos sg ON sg.CODIGO = p.SUBGRUPO
                WHERE (p.NO_MKTP='S' AND p.ATIVO='S')  
                AND tp.tabela = $tabelaDePreco AND g.CODIGO = '$codigo_grade'");

            //montagem de produto no intersig
            $grade = mysqli_fetch_array($sqlGradeIntersig, MYSQLI_ASSOC);


            if ($grade == null || empty($grade)) {
                echo 'Produto não encontrado <br>';
                return $this->response(false, 'Produto não encontrado');
            }


            if ($grade['MARCA'] == null  || $grade['MARCA'] == 0) {
                echo 'O campo MARCA não foi atribuido';
                return $this->response(false, 'O campo MARCA não foi atribuido');
            }
            $json = [];

            // envio das fotos
            $fotos = [];
            //        try {
            //            $fotos = $enviarFotos->enviarFotos($codigoProduto );   
            //            //   print_r( $fotos );
            //        } catch (Exception $e) {
            //            echo "Erro: " . $e->getMessage();
            //              
            //          }


             $sqlProdutosgrade = "
                SELECT  
                    p.CODIGO,
                    p.NUM_FABRICANTE,
                    p.ALTURA,
                    p.COMPRIMENTO,
                    p.DESCRICAO,
                    p.LARGURA,
                    p.PESO,
                    ig.CARAC as CODIGO_CARACTERISTICA,
                    ig.VALOR as VALOR_CARACTERISTICA, 
                    crg.DESCRICAO as DESCRICAO_CARACTERISTICA
                FROM  grades g
                JOIN   cad_prod p on p.GRADE = g.CODIGO
                JOIN   itens_grade ig on  p.CODIGO = ig.PRODUTO AND ig.GRADE = p.GRADE AND ig.VALOR <> '' AND ig.VALOR is NOT NULL
                JOIN   carac_grade crg on crg.CODIGO = ig.CARAC
                WHERE (p.NO_MKTP='S' AND p.ATIVO='S') AND g.CODIGO  = '$codigo_grade'
                GROUP BY p.CODIGO
            ";
                
            $resultProdutosGrade =  $publico->Consulta($sqlProdutosgrade);

         

            $origem = 'Nacional';

            if ($grade['ORIGEM'] == 1 || $grade['ORIGEM'] == 2 || $grade['ORIGEM'] == 6 || $grade['ORIGEM'] == 7) {
                $origem = 'Importado';
            }
                     $peso = $grade['PESO'];
                    $largura = $grade['LARGURA'];
                    $altura =  $grade['ALTURA'];
                    $comprimento = $grade['COMPRIMENTO'];

            $json['product']['sku'] = null;
            $json['product']['name'] =  mb_convert_encoding(str_replace('"', ' ', $grade['DESCRICAO']), 'UTF-8', 'ISO-8859-1');
            $json['product']['shortName'] =  mb_convert_encoding(str_replace('"', ' ', $grade['DESCRICAO']), 'UTF-8', 'ISO-8859-1');
            $json['product']['description'] = mb_convert_encoding($grade['APLICACAO'], 'UTF-8', 'ISO-8859-1'); //campo descricao detalhada do produto 
            $json['product']['googleDescription'] = ""; //campo descricao detalhada do produto 
            $json['product']['wordKeys'] = mb_convert_encoding($grade['DESCR_LONGA_MKTPLACE'] , 'UTF-8', 'ISO-8859-1');  ;

            $json['product']['status'] = 'enabled';
            $json['product']['price'] =    floatval($grade['PRECO']);
            $json['product']['promotional_price'] = floatval($grade['PRECO']);
            $json['product']['cost'] =   !empty($grade['ULT_CUSTO']) ? floatval($grade['ULT_CUSTO']) : 0.00;
            
            $json['product']['brand'] = str_replace('"', ' ', $grade['MARCA']);
            $json['product']['nbm'] = !empty($grade['NCM']) ? str_replace(".", "", $grade['NCM'])  : '';
            $json['product']['model'] =  !empty($grade['MODELO_MKTPLACE']) ? $grade['MODELO_MKTPLACE'] : '';
            $json['product']['gender'] = '';
            $json['product']['volumes'] = !empty($grade['VOLUMES']) ? $grade['VOLUMES'] : 0;
            $json['product']['warrantyTime'] = $grade['GARANTIA'];
            $json['product']['category'] = !empty($grade['CATEGORIA_MKTPLACE']) ?  $grade['CATEGORIA_MKTPLACE']  : '';
            $json['product']['subcategory'] = !empty($grade['INTERM_CATEGORIA_MKTPLACE']) ?  $grade['INTERM_CATEGORIA_MKTPLACE']  : '';
            $json['product']['endcategory'] = !empty($grade['FINALCATEGORIA_MKTPLACE']) ?  $grade['FINALCATEGORIA_MKTPLACE']  : '';
            $json['product']['manufacturing']  =  $origem;
            $json['product']['attribute'] = [['key' => '', 'value' => '']];

            $json['product']['variations'] = [];
            $specifications =[];
   
            while ($row = mysqli_fetch_array( $resultProdutosGrade , MYSQLI_ASSOC)) {
                  

                $key =  '';
                $value = '';

                        if($row['DESCRICAO_CARACTERISTICA'] == 'LADO'){
                            $key  ='LADO';
                            $value = $row['VALOR_CARACTERISTICA'];
                            }
                        if($row['DESCRICAO_CARACTERISTICA'] == 'TAMANHO'){
                            $key  ='TAMANHO';
                            $value = $row['VALOR_CARACTERISTICA'];
                        }
                        if($row['DESCRICAO_CARACTERISTICA'] == 'COR'){
                            $key  ='TAMANHO';
                            $value = $row['VALOR_CARACTERISTICA'];
                        }

                array_push(
                    $json['product']['variations'],
                    [
                        'ref' => $row['CODIGO'],
                        #'sku' => !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0,
                        #'sku' => '',
                       # 'tipogradex' => !empty($row['DESCRICAO_CARACTERISTICA']) && $row['DESCRICAO_CARACTERISTICA'] == 'COR' ? $row['DESCRICAO_CARACTERISTICA']  : '',
                       # 'nomegradex' => !empty($row['DESCRICAO_CARACTERISTICA']) && $row['DESCRICAO_CARACTERISTICA'] == 'COR' ? $row['VALOR_CARACTERISTICA']  : '',
                        'qty' => 0,
                        'ean' => !empty($row['NUM_FABRICANTE']) ? $row['NUM_FABRICANTE'] : null,
                        'images' => $fotos,
                         'specifications' => [
                      
                            [
                                'key' =>  $key,
                                'value' => $value,
                            ],
                           // [
                           //     'key' => !empty($row['DESCRICAO_CARACTERISTICA']) && $row['DESCRICAO_CARACTERISTICA'] == 'COR' ? $row['DESCRICAO_CARACTERISTICA']  : '',
                           //     'value' => !empty($row['DESCRICAO_CARACTERISTICA']) && $row['DESCRICAO_CARACTERISTICA'] == 'COR' ? $row['VALOR_CARACTERISTICA']  : '',
                           // ] 
                      
                        ]
                    ]
                );
            }

            $json['product']['weight'] = !empty($peso) ? floatval($peso) : 0.1;
            $json['product']['width'] =   !empty($largura) ?  floatval($largura) : 0.1;
            $json['product']['height'] =   !empty($altura) ? floatval($altura) : 0.1;
            $json['product']['length'] =   !empty($comprimento) ? floatval($comprimento) : 0.1;

                print_r(json_encode($json['product']));
  
            //  --------------------------------
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

            print_r($retorno);

            if ($httpCode == 200 || $httpCode == 201) {

                //---------------

                if (!empty($retorno)) {

                    $sku_site = isset($retorno['sku']) ? $retorno['sku'] : null; // Verifica se 'sku' existe

                    $sql_insert_grade = "INSERT INTO grade_precode (codigo_site, codigo_bd ) VALUES ('$sku_site', $codigo_grade )";

                    $result_insert_grade = $publico->Consulta($sql_insert_grade);
                    if ($result_insert_grade) {
                        echo ' <br> Grade registrada no banco de dados <br> ';
                    } else {
                        echo ' <br>  erro ao registrar Grade no banco de dados <br> ';
                    }
                    $variations = $retorno['variations'];
                    foreach ($variations as $value) {
                        $variation_sku = $value['sku'];
                        $variation_ref = $value['ref'];
                        $data_recad = date('Y-m-d H:i:s');
                        $sql_insert_variations = "INSERT INTO produto_precode (codigo_site, codigo_bd, preco_site, data_recad) VALUES ( '$variation_sku' , '$variation_ref', 0, '$data_recad')";
                        $envioPrecodeBase = $publico->Consulta($sql_insert_variations);

                        if ($envioPrecodeBase) {
                            echo " <br> $variation_sku variante registrada com sucesso! <br> /n";
                        } else {
                            echo " <br> erro ao tentar registrar variante $variation_sku <br> /n ";
                        }
                    }
                } else {
                    return $this->response(false, ' solicitação recebida, porém a api não deu retorno, verifique se foi feita a inclusao da grade ' . $codigo_grade . ' no precode  ');
                }
                //---------------
            } else {
                if (isset($retorno['message'])) {
                    return $this->response(false, ' Houve um erro no envio do produto, contate a plataforma. <br>  <strong>Mensagem de Erro:</strong> ' . htmlspecialchars(print_r($retorno['message'], true)));
                } else {
                    return $this->response(false, ' Houve um erro no envio do produto, <strong>Mensagem de Erro:</strong> Detalhes não fornecidos.');
                }
            }
 
            //    ----------------------
 
        } else {
            
            return $this->response(false, ' Produto já foi enviado para a plataforma!');
        }


        $publico->Desconecta();
    }

    private function response(bool $success, string $message, $data = null): string
    {
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