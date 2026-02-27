  <?php
include_once(__DIR__ .'/../../database/conexao_publico.php');
include_once(__DIR__ . '/../../database/conexao_integracao.php');

    class EnviarKitProduto{
    public function enviarkit( $produto){
      set_time_limit(0);
      
          $integracao = new CONEXAOINTEGRACAO();
    

        $publico = new CONEXAOPUBLICO();
        $ini = parse_ini_file(__DIR__ . '/../../conexao.ini', true);
           $id_kit = $produto['codigo_kit'];

            $verifyKit = $integracao->Consulta(" SELECT id FROM padronizados WHERE CODIGO_KIT = '$id_kit' ;"); 
             $quantResultKit = mysqli_num_rows($verifyKit);
            if($quantResultKit >  0  ){
                    return $this->response(false, "O kit id: $id_kit já foi enviado!"  );
            }
                $fotos=[];        
            $produtos_kit =$produto['grade'];
             $codigo_padr = $produto['codigo_padr'];
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
          //  $num_fabricante = $produto['num_fabricante'];
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
                        'ref' => $id_kit,
                        #'sku' => !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0,
                        #'sku' => '',
                        'qty' => !empty($estoque) ?  $estoque : 0  ,
                       // 'ean' => !empty($num_fabricante) ? $num_fabricante : null,
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
            if (!empty($retorno)) {
                
                $mysqli = $integracao->link;
                if (!$mysqli) {
                    return $this->response(false, 'Erro: Conexão com banco de integração perdida.');
                }

                $codigo_site = isset($retorno['sku']) ? $retorno['sku'] : null;
                $data_recad = date('Y-m-d H:i:s');


                        $descricao = mb_convert_encoding($descricao, 'ISO-8859-1', 'UTF-8');
                        $descricaocurta = mb_convert_encoding($descricaocurta, 'ISO-8859-1', 'UTF-8');
                        $aplicacao = mb_convert_encoding($aplicacao, 'ISO-8859-1', 'UTF-8');
                        $descricaogoogle = mb_convert_encoding($descricaogoogle, 'ISO-8859-1', 'UTF-8');

                // 1. Inserção do KIT PAI na tabela 'kit'
                $sqlInsertKit = "INSERT INTO `integracao_precode`.`padronizados` 
                    (
                        `CODIGO_SITE`, 
                        `CODIGO_KIT`, 
                        `CODIGO_PADR`, 
                        `DESCRICAO`, 
                        `DESCRICAO_CURTA`, 
                        `APLICACAO`, 
                        `DESCRICAO_GOOGLE`, 
                        `GARANTIA`, 
                        `PRECO_SITE`, 
                        `DATA_RECAD_ESTOQUE`, 
                        `SALDO_ENVIADO`, 
                        `DATA_RECAD_PRECO`
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";

                if ($stmt = $mysqli->prepare($sqlInsertKit)) {
                    $stmt->bind_param(
                        "ssssssssdsds",
                        $codigo_site,
                        $id_kit,
                        $codigo_padr,
                        $descricao,
                        $descricaocurta,
                        $aplicacao,
                        $descricaogoogle,
                        $garantia,
                        $preco,
                        $data_recad,
                        $estoque,
                        $data_recad
                    );

                    if (!$stmt->execute()) {
                        $erroMsg = $stmt->error;
                        $stmt->close();
                        return $this->response(false, 'Erro ao salvar Kit no banco: ' . $erroMsg);
                    }
                    
                    // Recupera o ID auto-increment gerado para este Kit
                    $kit_id_banco = $mysqli->insert_id;
                    $stmt->close();

                    // 2. Inserção dos ITENS DO KIT na tabela 'itens_kit'
                    
                    /*if (!empty($produtos_kit) && is_array($produtos_kit)) {
                        
                        $sqlInsertItem = "INSERT INTO `integracao_precode`.`itens_padronizados` 
                            (`ID_KIT`, `CODIGO_BD`, `CODIGO_KIT`, `QUANTIDADE`) 
                            VALUES (?, ?, ?, ?)";

                        if ($stmtItem = $mysqli->prepare($sqlInsertItem)) {
                            
                            foreach ($produtos_kit as $item) {
                                // Ajuste as chaves conforme o array que vem do seu POST
                                // Exemplo esperado: $item = ['id' => 'COD123', 'qtd' => 2]
                                $codigo_filho_bd = $item['codigo']; 
                                $quantidade = floatval($item['QUANTIDADE']);
                                
                                $stmtItem->bind_param("issd", 
                                    $kit_id_banco,
                                    $codigo_filho_bd,
                                    $id_kit,
                                    $quantidade 
                                );
                                
                                if (!$stmtItem->execute()) {
                                }
                            }
                            $stmtItem->close();
                        } else {
                             return $this->response(false, 'Erro ao preparar query dos itens: ' . $mysqli->error);
                        }
                    }
                    */

                    return $this->response(true, 'Kit criado e enviado com sucesso!', ['id_banco' => $kit_id_banco]);

                } else {
                    return $this->response(false, 'Erro ao preparar query de integração: ' . $mysqli->error);
                }

            } else {
                return $this->response(false, 'Solicitação recebida, porém a API não retornou dados.');
            }

        } else {
            if (isset($retorno['message'])) {
                return $this->response(false, 'Erro na API: ' . htmlspecialchars(print_r($retorno['message'], true)));
            } else {
                return $this->response(false, 'Houve um erro no envio do produto. Detalhes não fornecidos.');
            }
        }

        $publico->Desconecta();
        $integracao->Desconecta();
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
 