<?php
include_once(__DIR__ . '/../database/conexao_publico.php');
include_once(__DIR__ . '/../database/conexao_integracao.php');

class ObterVinculo {

    function getVinculo(int $codigo) {
        set_time_limit(0);

        $publico = new CONEXAOPUBLICO();
        $integracao = new CONEXAOINTEGRACAO();
        $databaseIntegracao = $integracao->getBase();

        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

        if (empty($ini['conexao']['token'])) {
            return $this->response(false, "Token da aplicação não fornecido no arquivo conexao.ini.");
        }

        $token = $ini['conexao']['token'];
        $url = 'https://www.replicade.com.br/api/v3/products/query/' . urlencode($codigo) . '/ref';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: " . $token
            ],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $result = json_decode($response);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return $this->response(false, "Erro de cURL! " . htmlspecialchars($error));
        }

        if ($httpcode != 200) {
            $msg = !empty($result->mensagem) ? $result->mensagem : "Status Code: $httpcode";
            return $this->response(false, "Erro API: $msg");
        }

        if (!empty($result) && isset($result->produto->codigoAgrupador)) {
            $id_produto_pai = $result->produto->codigoAgrupador;
            $preco_site = $result->produto->precoSite;
            $variants = $result->produto->atributos;

            // Variável para acumular a mensagem final
            $msgRetorno = "";
            $itensProcessados = 0;

            foreach ($variants as $variant) {
                $codigo_sistema = $variant->ref;
                $ean = $variant->ean;
                $sku_precode = $variant->sku;

                // Verifica se já existe o vínculo
                // IMPORTANTE: Adicionei aspas simples '$sku_precode' para evitar erro de SQL se for string
                $sqlCheck = "SELECT CODIGO_BD FROM produto_precode 
                             WHERE CODIGO_SITE = '$sku_precode' AND CODIGO_BD = '$codigo_sistema'";
                
                $validationProduct = $integracao->consulta($sqlCheck);

                if (mysqli_num_rows($validationProduct) == 0) {
                    
                    // Insere o novo vínculo
                    $sqlInsert = "INSERT INTO produto_precode (CODIGO_SITE, CODIGO_BD, PRECO_SITE, EAN, SKU_LOJA, REF_LOJA) 
                                  VALUES ('$sku_precode', '$codigo_sistema', '$preco_site', '$ean', '$sku_precode', '$codigo_sistema')";
                    
                    $insertResult = $integracao->consulta($sqlInsert);

                    if ($insertResult == 1) {
                        $itensProcessados++;
                        // Concatena sucesso da variante
                        $msgRetorno .= "[Var: $codigo_sistema OK]"; 

                        // Busca Grade na tabela de produtos
                        $consultaGrade = $publico->consulta("SELECT GRADE FROM cad_prod WHERE CODIGO = '$codigo_sistema'");

                        if ($row_prod = mysqli_fetch_array($consultaGrade, MYSQLI_ASSOC)) {
                            $cod_grade = $row_prod['GRADE'];

                            if ($cod_grade > 0) {
                                // Tenta vincular Grade
                                $sqlGrade = "INSERT INTO grade_precode SET CODIGO_SITE='$id_produto_pai', CODIGO_BD='$cod_grade' 
                                             ON DUPLICATE KEY UPDATE CODIGO_BD='$cod_grade'";
                                
                                $resultVincGrade = $integracao->Consulta($sqlGrade);
                                
                                if ($resultVincGrade == 1) {
                                    // Concatena sucesso da grade
                                    $msgRetorno .= " (Grade: $cod_grade Vinc.) ";
                                } else {
                                    $msgRetorno .= " (Erro Grade) ";
                                }
                            } else {
                                $msgRetorno .= " (S/ Grade) ";
                            }
                        }
                        
                        $msgRetorno .= " | "; // Separador entre produtos

                    } else {
                        $msgRetorno .= "[Erro Var: $codigo_sistema] | ";
                    }
                } else {
                     // Se quiser avisar que já existe, descomente a linha abaixo:
                     // $msgRetorno .= "[Var: $codigo_sistema Já existe] | ";
                }
            }
            
            $publico->Desconecta();
             $integracao->Desconecta();
             
            // Verifica se processou algo para dar a resposta correta
            if ($itensProcessados > 0) {
                return $this->response(true, "Processados: " . $msgRetorno);
            } else {
                return $this->response(true, "Nenhum novo vínculo necessário. Produtos já integrados.");
            }

        } else {
            return $this->response(false, "Resposta da API inválida para o produto: $codigo.");
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