  <?php
include_once(__DIR__ .'/../database/conexao_publico.php');
include_once(__DIR__.'/../utils/enviar-foto.php');

    class EnviarProduto{
    public function enviarProduto( $produto){
      set_time_limit(0);
        $ObjEnvFotos = new EnviarFotos();

        $publico = new CONEXAOPUBLICO();
        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

        if( isset($ini['config']['envio_fotos']) ){
            $envio_fotos  = filter_var($ini['config']['envio_fotos'], FILTER_VALIDATE_BOOLEAN );
        }
                $fotos=[];        
        
         if($envio_fotos  > 0){
              // envio das fotos
                      $fotos = $ObjEnvFotos->enviarFotos($produto['codigo']); 

                        // Verifique se $fotos é um array e se não está vazio
                       if (is_array($fotos) && isset($fotos['success']) && $fotos['success'] === false) {
                            // Se $fotos não for um array ou estiver vazio, houve um erro
                            echo "Erro ao enviar imagens:\n";
                            print_r($fotos); // Exibe a mensagem de erro retornada pela função
                            return $this->response(false, "Erro ao enviar imagens: " . json_encode($fotos));
                        }else{
                            $json['product']['variations'][0]['images'] = $fotos;
                        }
             
         }


            $codigo = $produto['codigo'];
            $descricao = $produto['descricao'];
            $descricaocurta = $produto['descricaocurta'];
            $aplicacao = $produto['aplicacao'];
            $descricaogoogle = $produto['descricaogoogle'];
           // $outro_cod = $produto['outro_cod'];
            $garantia = $produto['garantia'];
            $comprimento = $produto['comprimento'];
            $largura = $produto['largura'];
            $altura = $produto['altura'];
            $peso = $produto['peso'];
            $preco = $produto['preco'];
            $promocao = $produto['promocao'];
            $estoque = $produto['estoque'];
            $origem = $produto['origem'];
            $categoria = $produto['categoria'];
            $categoriainterm = $produto['categoriainterm'];
            $categoriafinal = $produto['categoriafinal'];
            $num_fabricante = $produto['num_fabricante'];
            $modelo = $produto['modelo'];
            $marca = $produto['marca'];
            $palavrasChave =$produto['palavraschave'];
            $ncm = $produto['ncm'];


         if( $produto['marca'] == null  || $produto['marca'] == 0 ){
                    return $this->response(false, 'O campo MARCA não foi atribuido'   );
                }
                if($produto['categoria']== null  || $produto['categoria'] == 0 ){
                    return $this->response(false, 'O campo CATEGORIA/GRUPO não foi atribuido'   );
                }
                if ($produto['peso'] == 0) {
                    return $this->response(false, 'O campo PESO não foi atribuido'   );
                }

                if ($produto['largura'] == 0) {
                    return $this->response(false, 'O campo LARGURA não foi atribuido'  );
                }
                
                if ($produto['altura'] == 0) {
                     return $this->response(false, 'O campo ALTURA não foi atribuido'  );
                }
                
                if ($produto['comprimento'] == 0) {
                      return $this->response(false, 'O campo COMPRIMENTO não foi atribuido'  );
                }


                $json = [];
                $json['product']['sku'] = null;
                $json['product']['name'] =   str_replace('"', ' ', $descricao )  ;
                $json['product']['shortName'] =   str_replace('"', ' ', $descricaocurta) ;
                $json['product']['description'] =  $aplicacao;  
                $json['product']['googleDescription'] = $descricaogoogle;
                $json['product']['status'] = 'enabled';
                $json['product']['wordKeys'] =  $palavrasChave ;
                $json['product']['price'] = floatval($preco);
                $json['product']['promotional_price'] = floatval($promocao);
                $json['product']['cost'] = floatval($preco);
                $json['product']['weight'] = !empty($peso) ? floatval($peso) : 0;
                $json['product']['width'] = !empty($largura) ? floatval($largura) : 0;
                $json['product']['height'] = !empty($altura) ? floatval($altura) : 0;
                $json['product']['length'] = !empty($comprimento) ? floatval($comprimento) : 0;
                $json['product']['brand'] = $marca;
                $json['product']['nbm'] = !empty($ncm) ? str_replace(".","",$ncm)  : '';
                $json['product']['model'] =   !empty($modelo) ? $this->removerAcentos($modelo) : null ; 
                $json['product']['gender'] = '';
                $json['product']['volumes'] = 0 ;
                $json['product']['warrantyTime'] = $garantia;
                $json['product']['category'] = !empty($categoria) ? $categoria : '';
                $json['product']['subcategory'] = !empty($categoriainterm) ?  $this->removerAcentos($categoriainterm) : '';
                $json['product']['endcategory'] = !empty($categoriafinal) ?  $this->removerAcentos($categoriafinal) : '';
                $json['product']['manufacturing']  =  $origem;
 

                $json['product']['attribute'] = [['key' => '', 'value' => '']];
                $json['product']['variations'] = [
                    [
                        'ref' => $codigo,
                        #'sku' => !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0,
                        #'sku' => '',
                        'qty' => !empty($estoque) ?  $estoque : 0  ,
                        'ean' => !empty($num_fabricante) ? $num_fabricante : null,
                        'images' => $fotos,
                        'specifications' => [
                            [
                                'key' => '', 
                                'value' => ''
                            ]
                        ]
                    ]
                ];
            
               
           if (empty($ini['conexao']['token'])) {
            return $this-> response(false,"Token da aplicação não fornecido no arquivo conexao.ini.");
        }

             $token = $ini['conexao']['token'];
             $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, 'https://www.replicade.com.br/api/v3/products');
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    curl_setopt($curl, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Basic ' . $token 
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

                                $codigo_site = isset($retorno['sku']) ? $retorno['sku'] : null; // Verifica se 'sku' existe
                                $data_recad = date('Y-m-d H:i:s');
                                $sql = "INSERT INTO produto_precode (codigo_site, codigo_bd, preco_site, data_recad) VALUES ('$codigo_site',  $codigo , $preco, '$data_recad')";
                                $envioPrecodeBase = $publico->Consulta($sql);
                                    //echo '<br>';
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
                                                WHERE CODIGO =  '$codigo' ";
                                                $envioPrecodeBase = $publico->Consulta($update_cad_prod);
                                    return $this->response(true, '  O produto foi enviado para a plataforma com sucesso!'  );
                                } else {
                                // echo '<strong>SQL:</strong> ' . htmlspecialchars($sql); // Mostra a query para debug
                                    return $this->response(true, ' o  Produto '.$codigo .' foi enviado para a plataforma <br> porém nao foi registrado na tabela de controle!  '  );
                                }
                            }else{
                                    return $this->response(false, ' solicitação recebida, porém a api não deu retorno, verifique se foi feita a inclusao do produto '.$codigo .' no precode  '  );
                            }

                     } else {
                        if (isset($retorno['message'])) {
                          return $this->response(false, ' Houve um erro no envio do produto, contate a plataforma. <br>  <strong>Mensagem de Erro:</strong> ' . htmlspecialchars(print_r($retorno['message'], true))  );
                        } else {
                          return $this->response(false, ' Houve um erro no envio do produto, <strong>Mensagem de Erro:</strong> Detalhes não fornecidos.');
                        }
                    }
                     
               
                 $publico->Desconecta();

              
            }
  
 function removerAcentos(string $string): string
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
 private function response(bool $success, string $message, $data = null): string {
        return json_encode([
            'success' => $success,
            'message' =>   $message,
            'data' => $data
        ]);
    }


    }

?>
 