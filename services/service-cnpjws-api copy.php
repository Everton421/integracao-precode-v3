<?php

class ConsultaCnpj {

    function verify($cpnj, string $sigla) {
        // Remove caracteres não numéricos
        $cpnj_format = preg_replace("/\D/", '', $cpnj);

        if (strlen($cpnj_format) > 11) {
            
            // Tenta obter os dados
            $result_api = $this->get($cpnj_format);

            // Verifica se o resultado é válido e se tem o estabelecimento
            if (isset($result_api->estabelecimento)) {
                
                $inscricoes = $result_api->estabelecimento->inscricoes_estaduais;
                $sigla = strtoupper($sigla);

                // Filtra conforme sua lógica anterior
                $arr_insc = array_filter($inscricoes, function($i) use($sigla) {
                    return ($i->estado->sigla == $sigla && $i->ativo == true);
                });

                // Reseta os índices array para pegar sempre o [0] se existir
                $arr_insc = array_values($arr_insc);

                if(!empty($arr_insc)){
                     print_r($arr_insc[0]);
                } else {
                     echo "Nenhuma inscrição ativa encontrada para a UF $sigla.\n";
                }

            } else {
                // Caso não tenha estabelecimento, verifica erro ou imprime o retorno cru
                if (isset($result_api->status)) {
                    echo "Erro retornado: " . $result_api->title . "\n";
                }
            }

        } else {
            print_r("CNPJ invalido ...\n");
            return;
        }
    }

    function get($cpnj_format) {
        $url = "https://publica.cnpj.ws/cnpj/$cpnj_format";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        // Timeout para não travar o script se a API cair
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); 

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Pega o código HTTP real
        curl_close($curl);

        $result_json = json_decode($resp);

        // --- LÓGICA DE RETRY (RECURSIVA) ---
        // Se o código for 429 (Too Many Requests)
        if ($httpCode == 429 || (isset($result_json->status) && $result_json->status == 429)) {
            
            echo "Limite de requisições atingido (429). Aguardando 61 segundos...\n";
            
            // CORREÇÃO: sleep usa SEGUNDOS.
            // Colocamos 61 para garantir que o minuto vire e o limite zere.
            sleep(61); 
            
            echo "Tentando novamente...\n";
            
            // Chama a própria função get novamente (Recursividade)
            return $this->get($cpnj_format); 
        }

        return $result_json;
    }
}

$obj = new ConsultaCnpj();
// Teste
$obj->verify('81491763000184' , "PR");

?>