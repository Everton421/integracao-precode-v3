<?php
ini_set('mysql.connect_timeout', '0');
ini_set('max_execution_time', '0');
date_default_timezone_set('America/Sao_Paulo');
require_once(__DIR__ . '/../database/conexao_publico.php');
include_once(__DIR__ . '/../database/conexao_vendas.php');
include_once(__DIR__ . '/../database/conexao_estoque.php');
include_once(__DIR__ . '/enviar-foto.php'); // Inclua o arquivo com a classe EnviarFotos

class EnviarProdutoGrade
{
    public function enviar( $produto)
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


            $codigo = $produto['codigo'];
            $descricao = $produto['descricao'];
            $descricaocurta = $produto['descricaocurta'];
            $aplicacao = $produto['aplicacao'];
            $garantia = $produto['garantia'];
            $comprimento = $produto['comprimento'];
            $largura = $produto['largura'];
            $altura = $produto['altura'];
            $peso = $produto['peso'];
            $preco = $produto['preco'];
            $promocao = $produto['promocao'];
            $origem = $produto['origem'];
            $ncm = $produto['ncm'];

            $categoria = $produto['categoria'];
            $categoriainterm = $produto['categoriainterm'];
            $categoriafinal = $produto['categoriafinal'];

            $modelo = $produto['modelo'];
            $marca = $produto['marca'];
            $palavrasChave =$produto['palavraschave'];
            $ncm = $produto['ncm'];
            $ult_custo = !empty($produto['ult_custo']) ? $produto['ult_custo'] : 0; 
            $volumes = 0;

            $variantes =  $produto['grade'];

            
            $json = [];

             $json['product']['sku'] = null;
             $json['product']['name'] =   str_replace('"', ' ', $descricao )  ;
            $json['product']['shortName'] =    str_replace('"', ' ', $descricaocurta );
            $json['product']['description'] = str_replace('"', ' ', $aplicacao );
            $json['product']['googleDescription'] = '';
            $json['product']['status'] = 'enabled';
            $json['product']['wordKeys'] =  $palavrasChave ;
            $json['product']['price'] =    floatval($preco);
            $json['product']['promotional_price'] =  floatval($preco);
            $json['product']['cost'] =   !empty($ult_custo) ? floatval($ult_custo) : 0.00;
            $json['product']['brand'] =  str_replace('"', ' ', $marca ); 
            $json['product']['nbm'] = !empty($ncm) ? str_replace(".", "", $ncm)  : '';
            $json['product']['model'] =  !empty($modelo) ? $modelo  : '';
            $json['product']['gender'] = '';
            $json['product']['volumes'] = !empty($volumes) ? $volumes : 0;
            $json['product']['warrantyTime'] = $garantia;
            $json['product']['category'] = !empty( $categoria) ? $categoria : '';
            $json['product']['subcategory'] = !empty($categoriainterm) ? $this->removerAcentos($categoriainterm) : '';
            $json['product']['endcategory'] = !empty($categoriafinal) ? $this->removerAcentos($categoriafinal) : '';
            $json['product']['manufacturing']  =  $origem;
            $json['product']['attribute'] = [['key' => '', 'value' => '']];
            $json['product']['promotional_price'] = floatval($promocao);
            $json['product']['model'] =   !empty($modelo) ? $this->removerAcentos($modelo) : null ; 

              $json['product']['weight'] = !empty($peso) ? floatval($peso) : 0;
                $json['product']['width'] = !empty($largura) ? floatval($largura) : 0;
                $json['product']['height'] = !empty($altura) ? floatval($altura) : 0;
                $json['product']['length'] = !empty($comprimento) ? floatval($comprimento) : 0;
            
          
            $json['product']['variations'] = [];
 

           /// monta o objeto com as variantes 

            foreach( $variantes as $item ) {

                    $key =  '';
                    $value = '';

                        if($item['descricao_caracteristica'] == 'LADO'){
                            $key  ='LADO';
                            $value = $item['valor_caracteristica'];
                            }
                        if($item['descricao_caracteristica'] == 'MEDIDA'){
                            $key  ='MEDIDA';
                            $value = $item['valor_caracteristica'];
                        }
                        if($item['descricao_caracteristica'] == 'COR'){
                            $key  ='COR';
                            $value = $item['valor_caracteristica'];
                        }

                     array_push(
                    $json['product']['variations'],
                    [
                        'ref' => $item['codigo'],
                        #'sku' => !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0,
                        #'sku' => '',
                       # 'tipogradex' => !empty($row['DESCRICAO_CARACTERISTICA']) && $row['DESCRICAO_CARACTERISTICA'] == 'COR' ? $row['DESCRICAO_CARACTERISTICA']  : '',
                       # 'nomegradex' => !empty($row['DESCRICAO_CARACTERISTICA']) && $row['DESCRICAO_CARACTERISTICA'] == 'COR' ? $row['VALOR_CARACTERISTICA']  : '',
                        'qty' => $item['estoque'],
                       # 'ean' => !empty($item['num_fabricante']) ? $item['num_fabricante'] : null,
                        'images' => [],
                         'specifications' => [
                      
                            [
                                'key' =>  $key,
                                'value' => $value,
                            ],
                        
                      
                        ]
                    ]
                );

            }
      

        // print_r(  json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
 
          $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, 'https://www.replicade.com.br/api/v3/products');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS,  json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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

                //---------------

                if (!empty($retorno)) {

                    $sku_site = isset($retorno['sku']) ? $retorno['sku'] : null; // Verifica se 'sku' existe

                    $sql_insert_grade = "INSERT INTO grade_precode (codigo_site, codigo_bd ) VALUES ('$sku_site', $codigo )";

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
                        $modelo_db          = mb_convert_encoding($modelo, 'ISO-8859-1', 'UTF-8');
                        $categoria_db       = mb_convert_encoding($categoria, 'ISO-8859-1', 'UTF-8');
                        $cat_interm_db      = mb_convert_encoding($categoriainterm, 'ISO-8859-1', 'UTF-8');
                        $cat_final_db       = mb_convert_encoding($categoriafinal, 'ISO-8859-1', 'UTF-8');
                        $garantia_db        = mb_convert_encoding($garantia, 'ISO-8859-1', 'UTF-8');

                         $update_cad_prod  = " UPDATE cad_prod SET
                                MODELO_MKTPLACE = '$modelo_db',
                                CATEGORIA_MKTPLACE = '$categoria_db',
                                INTERM_CATEGORIA_MKTPLACE = '$cat_interm_db',
                                FINALCATEGORIA_MKTPLACE = '$cat_final_db',
                                PESO = '$peso',
                                ALTURA = '$altura',
                                GARANTIA = '$garantia_db' 
                                WHERE CODIGO =  '$variation_ref' ";
                                $envioPrecodeBase = $publico->Consulta($update_cad_prod);
 
                        } else {
                            echo " <br> erro ao tentar registrar variante $variation_sku <br> /n ";

                        }
                           
                    }
                         return $this->response( true , 'Grade ' . $codigo . ' enviada com sucesso para precode  ');

                } else {
                    return $this->response(false, ' solicitação recebida, porém a api não deu retorno, verifique se foi feita a inclusao da grade ' . $codigo . ' no precode  ');
                }
                //---------------
            } else {
                if (isset($retorno['message'])) {
                    return $this->response(false, ' Houve um erro no envio do produto, contate a plataforma. <br>  <strong>Mensagem de Erro:</strong> ' . htmlspecialchars(print_r($retorno['message'], true)));
                } else {
                    return $this->response(false, ' Houve um erro no envio do produto, <strong>Mensagem de Erro:</strong> Detalhes não fornecidos.');
                }
            }
            
 
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
