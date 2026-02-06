<?php


class ObterNotas{
    function getNotas(){
             set_time_limit(0);

        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);
    
        $token = $ini['conexao']['token'];
        $url = 'https://www.replicade.com.br/api/v1/invoiced/fulfillment'; // Codifica a referência para a URL

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

        print_r($result);
    }
}
?>