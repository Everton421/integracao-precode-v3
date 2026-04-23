<?php
class UpdateStatusOrder
{
    private $token;    

     /**
      * $codigo_pedido = codigo do pedido;
      * $pathUrlStatusOrder = caminho da url que determina o status do pedido, ex.: pedidosemestoque, aprovado;
      */
    function put($codigo_pedido,string $pathUrlStatusOrder)
    {

                  $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);
                $this->token = $ini['conexao']['token']; 


        $curl = curl_init();
        print_r("https://www.replicade.com.br/api/v1/erp/$pathUrlStatusOrder");
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/$pathUrlStatusOrder",
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

        if( $httpcode == 200 ){
                return $this->response(true, "status $httpcode ",  $json_result);
        }else{
                return $this->response(false,"Erro ao tentar atualizar status do pedido status $httpcode", $json_result);
        }
    }

    function confirmErpOrder($codigoPedidoSite, $codigoOrcamento, $filial_cd ){
                                  $curl = curl_init();
                                                            curl_setopt_array($curl, array(
                                                            CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/aceite",
                                                            CURLOPT_RETURNTRANSFER => true,
                                                            CURLOPT_ENCODING => "",
                                                            CURLOPT_MAXREDIRS => 10,
                                                            CURLOPT_TIMEOUT => 0,
                                                            CURLOPT_FOLLOWLOCATION => true,
                                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                            CURLOPT_CUSTOMREQUEST => "PUT",
                                                            CURLOPT_POSTFIELDS =>"
                                                            {
                                                                \r\n\"pedido\": 
                                                                [
                                                                    \r\n
                                                                    {
                                                                        \r\n\"codigoPedido\": $codigoPedidoSite,
                                                                        \r\n\"numeroPedidoERP\": $codigoOrcamento,
                                                                        \r\n\"numeroFilialFatura\": $filial_cd,
                                                                        \r\n\"numeroFilialSaldo\": $filial_cd\r\n
                                                                    }
                                                                    \r\n
                                                                ]
                                                                    
                                                                \r\n
                                                            }",
                                                            CURLOPT_HTTPHEADER => array(
                                                                "Authorization: ".$this->token
                                                            ),
                                                            ));
                                                            $response = curl_exec($curl);
                                                              $json_result = json_decode($response);
                                                            curl_close($curl);
                                                            //echo $response;
                                                            if(!empty($response)){
                                                                return $this->response(true, "Aceite confirmado!",  $json_result);

                                                            }else{
                                                                return $this->response(false, "  Falha ao confirmar o aceite do pedido: [ $codigoOrcamento ] !  ",  $json_result);

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
