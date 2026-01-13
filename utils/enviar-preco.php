<?php
include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_estoque.php'); 
include_once(__DIR__.'/../database/conexao_vendas.php');

class EnviarPreco {

  public function postPreco( int $codigo){

     $forcar_envio_preco = false;
            $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

        if( isset($ini['config']['forcar_envio_preco']) ){
            $forcar_envio_preco  = filter_var($ini['config']['forcar_envio_preco'], FILTER_VALIDATE_BOOLEAN );
        }
        
      set_time_limit(0);
      $publico = new CONEXAOPUBLICO();	

      ini_set('mysql.connect_timeout','0');   
      ini_set('max_execution_time', '0'); 
      date_default_timezone_set('America/Sao_Paulo');
    

        $tabela = 1;
        if( $ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco']) ){
          $tabela =$ini['conexao']['tabelaPreco']; 
        }
        
        if(empty($ini['conexao']['token'] )){
            return $this->response(false,"token da aplicação não fornecido  ");
        }
      $appToken = $ini['conexao']['token'];

        $resultPrice = $publico->consulta(" SELECT 
                                                 cp.OUTRO_COD ,
                                                 pp.PRECO_SITE,
                                                 p.PRECO,
                                                 p.DATA_RECAD,
                                                 COALESCE(pp.DATA_RECAD_PRECO, '2001-01-01 00:00:00') AS DATA_RECAD_PRECO
                                              FROM  cad_prod cp
                                              JOIN prod_tabprecos p ON cp.CODIGO = p.PRODUTO
                                              JOIN  produto_precode pp ON pp.CODIGO_BD = cp.CODIGO
                                               WHERE p.PRODUTO = $codigo AND p.TABELA = $tabela");

        while($row = mysqli_fetch_array($resultPrice, MYSQLI_ASSOC)){
            $valorProduto = $row['PRECO'];
            $referencia = $row['OUTRO_COD'];
            $ultimoPreco = $row['PRECO_SITE'];
            $dataUltiEnvi= $row['DATA_RECAD_PRECO']; // data ultimo envio
            $dataRecadPrecoErp = $row['DATA_RECAD']; //data recadastro do sistema
        }

        if($forcar_envio_preco == true ){
           $dataUltiEnvi = '2001-01-01 00:00:00';

        }

        // Lê o delta máximo de configuração (ex.: 0.30 = 30%)
        $maxDelta = 0.30;
        if( isset($ini['config']['max_delta_preco']) ){
            $maxDelta = floatval($ini['config']['max_delta_preco']);
        }

        // Envia se for forçado, se o preço desejado for diferente do preço no site, ou se houve recadastramento no ERP desde o último envio
        if( $forcar_envio_preco == true || floatval($valorProduto) != floatval($ultimoPreco) || $dataRecadPrecoErp >  $dataUltiEnvi ){

            $currentSitePrice = floatval($ultimoPreco);
            $targetPrice = floatval($valorProduto);
            $final = false;

            if($currentSitePrice <= 0){
                // Não é possível calcular delta relativo com preço zero ou inválido: enviamos o alvo (mas registramos aviso para revisão manual)
                $sendPrice = round($targetPrice, 2);
                error_log("[enviar-preco] aviso: preco_site invalido ($currentSitePrice) para codigo $codigo. Enviando alvo $sendPrice");
            } else {
                $diff = ($targetPrice - $currentSitePrice) / $currentSitePrice;
                if( abs($diff) <= $maxDelta ){
                    $sendPrice = round($targetPrice, 2);
                    $final = true;
                } elseif ( $diff > $maxDelta ){
                    $sendPrice = round($currentSitePrice * (1 + $maxDelta), 2);
                } else {
                    $sendPrice = round($currentSitePrice * (1 - $maxDelta), 2);
                }
            }

            $payload = [
                'produto' => [
                    [
                        'IdReferencia' => $referencia,
                        'sku' => 0,
                        'precoDe' => floatval(number_format($currentSitePrice,2,'.','')),
                        'precoVenda' => floatval(number_format($sendPrice,2,'.','')),
                        'precoSite' => floatval(number_format($sendPrice,2,'.',''))
                    ]
                ]
            ];

            $curl = curl_init();
              $url = 'https://www.replicade.com.br/api/v1/produtoLoja/preco' ; // Codifica a referência para a URL

              curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => "
                        \r\n\"IdReferencia\": \"$referencia\",
                        \r\n\"sku\": 0,
                        \r\n\"precoDe\": $ultimoPreco,
                        \r\n\"precoVenda\": $valorProduto,
                        \r\n\"precoSite\": $valorProduto\r\n     
                      }\r\n   
                    ]\r\n
                }",
                CURLOPT_HTTPHEADER => array(
                  "authorization:$appToken",
                  "cache-control: no-cache",
                  "content-type: application/json"
                ),
              ));
          
              $result = curl_exec($curl);
              $err = curl_error($curl);
              $resultado = json_decode($result);
              $codMensagem = $resultado->produto[0]->idMensagem;   
              $mensagem = $resultado->produto[0]->mensagem;   
      
              curl_close($curl); 
              if ($err) {
                echo "cURL Error #:" . $err;
                return $this->response(false, $err);
              } else {
                  if($codMensagem != 0){
                        return $this->response(false, $mensagem);
                    }
                    if($codMensagem == 0 ){
                      $resultUpdateProduct =$publico->Consulta("UPDATE produto_precode SET preco_site = $sendPrice, data_recad = now(),data_recad_preco = now()  where codigo_bd = '$codigo'");
                        if($resultUpdateProduct != 1 ){
                          return $this->response(false,'Ocorreu um erro ao tentar atualizar a data de envio do estoque do produto '.$codigo.'na tabela produto_recode!');
                        }
                        if($resultUpdateProduct == 1 ){
                          if($final){
                            return $this->response(true,"$mensagem | preco final enviado para o produto $codigo | enviado: $sendPrice | codigo mensagem: $codMensagem");
                          } else {
                            return $this->response(true,"$mensagem | preco parcial enviado: $sendPrice | alvo: $targetPrice | codigo mensagem: $codMensagem");
                          }
                        }
                    }
              }   
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