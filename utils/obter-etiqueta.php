<?php
 
class ObterEtiqueta{

    public function getEtiquetas( int $codigo){

          set_time_limit(0);

        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

        if (empty($ini['conexao']['token'])) {
            return $this-> response(false,"Token da aplicação não fornecido no arquivo conexao.ini.");
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
        $result = json_decode($response);
        $error = curl_error($curl);

        curl_close($curl);

        print_r($response);
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