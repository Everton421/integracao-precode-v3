<?php
class PedidoSemEstoque
{
    private $token;    

    function put($codigo_pedido)
    {

                  $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);
                $this->token = $ini['conexao']['token']; 


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/pedidosemestoque",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => "
                                                            {
                                                                \r\n\"pedido\": 
                                                                [
                                                                    \r\n
                                                                    {
                                                                        \r\n\"codigoPedido\": $codigo_pedido 
                                                                    }
                                                                    \r\n
                                                                ]
                                                                    
                                                                \r\n
                                                            }",
            CURLOPT_HTTPHEADER => array(
                "Authorization: " . $this->token,
                 "Content-Type: application/json"
            ),
        ));
        $response = curl_exec($curl);
        $json_result = json_decode($response);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE); 

        curl_close($curl);

        print_r( $httpcode);
        print_r($json_result);
        if(!empty($response) && $httpcode == 200 ){
            
                return $this->response(true,'');
        }else{
                return $this->response(false,'Erro ao tentar atualizar status do pedido');
        }
    }

     private function response(bool $success, string $message, $data = null): string {
        return json_encode([
            'success' => $success,
            'message' =>   $message,
            'data' => $data
        ]);
    }

}
