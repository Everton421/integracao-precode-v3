<?php

class ObterEtiqueta {

    public function getEtiquetas(int $codigo) {
        

        set_time_limit(0);

        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

        if (empty($ini['conexao']['token'])) {
            return $this->response(false, "Token da aplicação não fornecido no arquivo conexao.ini.");
        }

        $token = $ini['conexao']['token'];
        $url = 'https://www.replicade.com.br/api/v1/labels/generateLabels?orders=' . urlencode($codigo) . '&responseType=pdf'; // Codifica a referência para a URL
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
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            // Logar o erro para debug
            error_log("Erro ao obter etiqueta: " . $error);
            echo "Erro ao obter a etiqueta. Por favor, tente novamente mais tarde.";
            return;  // Ou lançar uma exceção, dependendo da sua necessidade
        }

        if ($httpcode == 200) {
            // Define os cabeçalhos para exibir o PDF no navegador
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="etiqueta_' . $codigo . '.pdf"'); // 'inline' abre no navegador, 'attachment' força o download
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');

            // Imprime o PDF diretamente na saída
            echo $response;
        } else {
             $result = json_decode($response, true);
             if (isset($result['mensagem']) ) {
             return $this->response(false, " resposta precode: ".$result['mensagem'] );

             } else {
             return $this->response(false, "Erro desconhecido ao gerar a etiqueta.<br>" );

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