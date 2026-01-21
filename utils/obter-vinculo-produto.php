<?php
include_once(__DIR__ .'/../database/conexao_publico.php');

class ObterVinculo {

    function getVinculo(int $codigo) {
        set_time_limit(0);

        $publico = new CONEXAOPUBLICO();
        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

        

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

        if (!empty($result) && isset($result->produto->codigoAgrupador)) { // Verifica se $result e $result->produto existem
            $idPrecode = $result->produto->codigoAgrupador;
            $skuLoja =  $result->produto->atributos[0]->sku;
            $refLoja = $result->produto->atributos[0]->ref;
            $preco_site = $result->produto->precoAvista;

            $validationProduct = $publico->consulta("SELECT * FROM produto_precode WHERE CODIGO_SITE = '$idPrecode' AND CODIGO_BD = '$codigo'");

            if (mysqli_num_rows($validationProduct) == 0) {
                $insertResult = $publico->consulta("INSERT INTO produto_precode
                                                             (  
                                                             CODIGO_SITE,
                                                              CODIGO_BD,
                                                              SKU_LOJA,
                                                              PRECO_SITE,
                                                              REF_LOJA
                                                              ) VALUES ('$idPrecode', '$codigo', '$skuLoja',$preco_site, '$refLoja' )");

                if ($insertResult == 1) {
                    return $this-> response(true,"Vinculo obtido com sucesso para o produto: $codigo." );
                } else {
                    return $this-> response(false,"Erro ao inserir vínculo para o produto: $codigo." );
                 }

            } else {
                $row = mysqli_fetch_array($validationProduct, MYSQLI_ASSOC);
                $codigoPrecode = $row['CODIGO_SITE'];
                    return $this-> response(false,"Produto já possui um vínculo: ERP Cód: $codigo |    Cód Precode: $codigoPrecode" );
            }
        } else {
                    return $this-> response(false,"Resposta da API inválida para o produto: $codigo." );
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
}
?>