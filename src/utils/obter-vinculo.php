<?php
    include_once(__DIR__.'/../database/conexao_publico.php');


 Class ObterVinculo{

       function getVinculo( int $codigoProduto ){
        
        $publico = new CONEXAOPUBLICO();
        $ini = parse_ini_file(__DIR__ .'/../../conexao.ini', true);

        if(empty($ini['conexao']['token'] )){
          echo 'token da aplicação não fornecido';
            return $this->response(false, 'token da aplicação não fornecido');
        }    

            $token = $ini['conexao']['token'];

          $curl = curl_init();
         curl_setopt_array($curl, array(
         CURLOPT_URL => "https://www.replicade.com.br/api/v3/products/query/".$codigoProduto."/ref",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: ".$token
        ),
        ));
        $response = curl_exec($curl);
        $result = json_decode($response);    
        curl_close($curl);  
     
        if(!empty($result)){  
            print_r($result);
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
