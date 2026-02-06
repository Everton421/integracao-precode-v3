<?php
include_once(__DIR__ .'/../database/conexao_publico.php');

class ObterInformacoesProduto {

    function getInfo(int $codigo) {
        set_time_limit(0);

        $publico = new CONEXAOPUBLICO();
        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

        // Consulta o produto no banco de dados interno
        $resultQueryProd = $publico->consulta("SELECT * FROM cad_prod WHERE CODIGO = $codigo");

        if (mysqli_num_rows($resultQueryProd) == 0) {
            return $this-> response(false, "Produto com código $codigo não encontrado no banco de dados.");
        }

        $row = mysqli_fetch_array($resultQueryProd, MYSQLI_ASSOC);

        $referencia = trim($row['OUTRO_COD']); // Remove espaços em branco

       // if (empty($referencia)) {
       //         return $this-> response(false," Produto com código $codigo não possui referência (OUTRO_COD).");
       // }

        if (empty($ini['conexao']['token'])) {
            return $this-> response(false,"Token da aplicação não fornecido no arquivo conexao.ini.");
        }

        $token = $ini['conexao']['token'];
        $url = 'https://www.replicade.com.br/api/v3/products/query/' . urlencode($codigo) . '/ref'; // Codifica a referência para a URL

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,  // Aumenta o timeout
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
            return $this->response(false, "Erro de cURL!  ".htmlspecialchars($error) );
        }

        if ($httpcode != 200) {
            if (!empty($result->mensagem)) {
                return $this->response(false, "Status Code: $httpcode  ".$result->mensagem);
            } 
                return $this-> response(false,"Erro inesperado ao tentar consumir a api do precode Status code: $httpcode");
        }
print_r($result);


        /*if (!empty($result) && isset($result->produto->codigoAgrupador)) { // Verifica se $result e $result->produto existem
            $produto = $result->produto ;
            $descricao =  mb_convert_encoding($produto->descricao, 'ISO-8859-1', 'UTF-8') ;
            $titulo = mb_convert_encoding($produto->titulo, 'ISO-8859-1', 'UTF-8');
            $descr_curta_site  = mb_convert_encoding($produto->Descricao240, 'ISO-8859-1', 'UTF-8');
            $descr_longa_site = mb_convert_encoding($produto->PalavrasChaves, 'ISO-8859-1', 'UTF-8'); 
            $aplicacao_site = mb_convert_encoding($produto->PalavrasChaves, 'ISO-8859-1', 'UTF-8');
            $peso = $produto->peso;
            $altura = $produto->altura_cm;
            $largura = $produto->largura_cm;
            $garantia = $produto->garantia;
            
             $marca = mb_convert_encoding( $produto->marca, 'ISO-8859-1', 'UTF-8'); 
                
             //// 
                    $verifyPmar = $publico->consulta("SELECT * FROM cad_pmar where DESCRICAO = '$marca' ;");
               if (mysqli_num_rows($verifyPmar) == 0) {
                        $sql = "INSERT INTO cad_pmar  
                        SET  DESCRICAO ='$marca',
                         DATA_CADASTRO ='1899-12-30',
                         NO_SITE ='N',
                         IMPORTADO_SITE ='N',
                          ALTERADO_SITE ='N' 
                         ;";
                          $publico->consulta( $sql );
                  }


                    $verifyPmar2 = $publico->consulta("SELECT * FROM cad_pmar where DESCRICAO = '$marca' ;");
                 $rowPmar = mysqli_fetch_array($verifyPmar2, MYSQLI_ASSOC);
                    $codigo_marca  = $rowPmar['CODIGO'];
             ///
            
                $insertResult = $publico->consulta("UPDATE  cad_prod
                                                            SET    

                                                                DESCRICAO ='$titulo',
                                                                APLICACAO ='$descricao',
                                                                DESCR_CURTA_SITE = '$descr_curta_site',
                                                                DESCR_LONGA_SITE =  '$descr_longa_site',
                                                                APLICACAO_SITE =  '$aplicacao_site',
                                                                GARANTIA = $garantia,
                                                                LARGURA = $largura, 
                                                                PESO= $peso,
                                                                ALTURA= $altura,
                                                                MARCA = $codigo_marca 
                                                                WHERE CODIGO = $codigo
                                                               "
                                                               );

                if ($insertResult == 1) {
                    return $this-> response(true," produto: $codigo atualizado com sucesso" );
                } else {
                    return $this-> response(false,"Erro ao atualizar o produto: $codigo." );
                 }
                
          

        } else {
                    return $this-> response(false,"Resposta da API inválida para o produto: $codigo." );
        }
        */
         $publico->Desconecta();
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