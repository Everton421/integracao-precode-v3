<?php

class ConsultaCnpj {

    function verify( $cpnj, string $sigla ){

        
          if (strlen($cpnj) > 11  ) {
                $cpnj_format = preg_replace("/\D/", '', $cpnj);
           
            $result_api = $this->get($cpnj_format);

            if($result_api->status && $result_api->status == 429 ){
                $minutes = 3;
                $seconds = ($minutes * 60) * 1000 ;
                sleep($seconds);
                    print_r("Aguaradando $minutes minutos...");
             $result_api = $this->get($cpnj_format);
                    
            }

            if($result_api->status && $result_api->status == 429){
                print_r("Erro status 400   ".$result_api);    
                return; 
            }

            if($result_api->estabelecimento){
                print_r($result_api->estabelecimento);
            }
             //print_r($result_api->estabelecimento );
               

            //    $inscricoes = $result_json->estabelecimento->inscricoes_estaduais;
//
            //    $sigla = strtoupper($sigla);
            //    $arr_insc = array_filter( $inscricoes, function( $i ) use($sigla){
            //            return ( $i->estado->sigla == $sigla && $i->ativo == true  );
            //    });
//
//
            //    print_r($arr_insc[0]);
    } else{
                    print_r("CNPJ invalido ...");
                    return;
                }

    }


     function get($cpnj_format){
    $url = "https://publica.cnpj.ws/cnpj/$cpnj_format";
                
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                //for debug only!
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                $resp = curl_exec($curl);
                curl_close($curl);
                //var_dump($resp);
                    $result_json = json_decode($resp); 
            return $result_json;
       }

}

$obj = new ConsultaCnpj();
 $obj->verify('81491763000184' , "PR");



?>