<?php
    include_once(__DIR__.'/../database/conexao_publico.php');


 Class ObterVinculo{

       function getVinculo( string $referencia , int $codigo ){
        
        $publico = new CONEXAOPUBLICO();
        $ini = parse_ini_file(__DIR__ .'/../../conexao.ini', true);

        if(empty($ini['conexao']['token'] )){
          echo 'token da aplicação não fornecido';
            return $this->response(false, 'token da aplicação não fornecido');
        }    

            $token = $ini['conexao']['token'];

          $curl = curl_init();
         curl_setopt_array($curl, array(
         CURLOPT_URL => "https://www.replicade.com.br/api/v3/products/query/".$referencia."/ref",
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
            $idPrecode = $result->produto->codigoAgrupador;
            $publico->consulta('INSERT INTO produto_precode ( CODIGO_SITE , CODIGO_BD )
            VALUES(
                    '$idPrecode',
                    '$codigo'
                )');


            /***
              
INSERT INTO products (id, name, quantity)
VALUES (123, 'Widget A', 50)
ON DUPLICATE KEY UPDATE
name = VALUES(name),
quantity = quantity + VALUES(quantity);


             */
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
