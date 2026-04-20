<?php
 

class EnviarPrecoKit
{
    /**
     * @param int $idKitBanco ID da tabela kit (integração)
     * @param object $publico Instância conexão publico
     * @param object $integracao Instância conexão integração
     */
    public function postPrecoKit(int $idKitBanco, $publico, $integracao)
    {
        $ini = parse_ini_file(__DIR__ . '/../../conexao.ini', true);

        $forcar_envio_preco = false;
        if (isset($ini['config']['forcar_envio_preco'])) {
            $forcar_envio_preco = filter_var($ini['config']['forcar_envio_preco'], FILTER_VALIDATE_BOOLEAN);
        }

        set_time_limit(0);
        ini_set('mysql.connect_timeout', '0');
        ini_set('max_execution_time', '0');
        date_default_timezone_set('America/Sao_Paulo');

        $databaseIntegracao = $integracao->getBase();

        $tabela = 1;
        if (isset($ini['conexao']['tabelaPreco']) && !empty($ini['conexao']['tabelaPreco'])) {
            $tabela = $ini['conexao']['tabelaPreco'];
        }

        if (empty($ini['conexao']['token'])) {
            return $this->response(false, "Token da aplicação não fornecido");
        }
        $appToken = $ini['conexao']['token'];

        // Lógica para forçar envio (data antiga)
        $dataUltimoEnvio = '2000-01-01 00:00:00'; // Valor padrão
        if ($forcar_envio_preco == true) {
            $dataUltimoEnvio = '1999-01-01 00:00:00';
        }

        // 1. Busca dados do Kit Pai  
        $sqlItensKit = "SELECT CODIGO_KIT, PRECO_SITE, DATA_RECAD_PRECO 
                        FROM {$databaseIntegracao}.padronizados  p
                        WHERE p.id = '$idKitBanco'";

        $resKit = $integracao->Consulta($sqlItensKit);

        if (mysqli_num_rows($resKit) == 0) {
            return $this->response(false, "Kit ID $idKitBanco não encontrado na tabela de integração.");
        }

        $dadosKit = mysqli_fetch_assoc($resKit);
        $referenciaKit = $dadosKit['CODIGO_KIT'];
        $ultimoPrecoEnviado = floatval($dadosKit['PRECO_SITE']);
        
        if(!empty($dadosKit['DATA_RECAD_PRECO']) && !$forcar_envio_preco){
            $dataUltimoEnvio = $dadosKit['DATA_RECAD_PRECO'];
        }

        // 2. Consulta os itens que compõem o kit
        
        $sqlItens = "SELECT 
                    cpd.CODIGO as CODIGO_PADRONIZADO_SISTEMA,
                    pp.CODIGO_SITE,
                    pp.SALDO_ENVIADO,
                    ip.PROD_SERV as CODIGO_ITEM_SISTEMA,
                    ip.QUANTIDADE
            FROM {$databaseIntegracao}.padronizados as pp  
                JOIN cad_padr cpd on cpd.CODIGO = pp.CODIGO_PADR
                JOIN ite_padr ip on ip.PADRONIZADO = cpd.CODIGO
                WHERE cpd.PROD_SERV = 'P' AND pp.id = $idKitBanco ";
        $resItens = $publico->Consulta($sqlItens);

        $preco_total = 0;
        $maiorDataRecadErp = '1900-01-01 00:00:00';

        if (mysqli_num_rows($resItens) > 0) {
            while ($item = mysqli_fetch_assoc($resItens)) {
                $codigo_bd = $item['CODIGO_ITEM_SISTEMA'];
                $quantidade_item = floatval($item['QUANTIDADE']);

                // Busca preço unitário no ERP
                $resultPrice = $publico->consulta("SELECT p.PRECO, p.DATA_RECAD
                                                    FROM prod_tabprecos p
                                                    WHERE p.PRODUTO = '$codigo_bd' 
                                                    AND p.TABELA = $tabela
                                                    LIMIT 1");

                if(mysqli_num_rows($resultPrice) > 0){
                    $rowErp = mysqli_fetch_assoc($resultPrice);
                    $novoPreco = floatval($rowErp['PRECO']);
                    $dataRecadItem = $rowErp['DATA_RECAD'];
                    
                    // Soma ao total do kit
                    $preco_total += ($novoPreco * $quantidade_item);

                    // Verifica qual a data mais recente de alteração entre os itens
                    if($dataRecadItem > $maiorDataRecadErp){
                        $maiorDataRecadErp = $dataRecadItem;
                    }
                }
            }

            // 3. Verifica se precisa enviar (Preço mudou OU data de alteração no ERP é mais nova que o último envio)
            if (abs($preco_total - $ultimoPrecoEnviado) > 0.001 || $maiorDataRecadErp > $dataUltimoEnvio) {

                $payload = json_encode([
                    "produto" => [
                        [
                            "IdReferencia" => $referenciaKit,
                            "sku" => 0,
                            "precoDe" => $preco_total,
                            "precoVenda" => $preco_total,
                            "precoSite" => $preco_total
                        ]
                    ]
                ]);

                $curl = curl_init();
                $url = 'https://www.replicade.com.br/api/v1/produtoLoja/preco';

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "PUT",
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Basic $appToken",
                        "Content-Type: application/json"
                    ),
                ));

                $result = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    return $this->response(false, "cURL Error: " . $err);
                }

                $resultado = json_decode($result);

                if (!isset($resultado->produto[0])) {
                    return $this->response(false, "Erro retorno API: " . print_r($result, true));
                }

                $codMensagem = isset($resultado->produto[0]->idMensagem) ? $resultado->produto[0]->idMensagem : -1;
                $mensagem = isset($resultado->produto[0]->mensagem) ? $resultado->produto[0]->mensagem : 'Sem mensagem';

                if ($codMensagem == 0) { // Sucesso

                    $dataHoje = date('Y-m-d H:i:s');
                    $sqlUpdate = "UPDATE {$databaseIntegracao}.padronizados 
                                  SET PRECO_SITE = $preco_total, 
                                      DATA_RECAD_PRECO = '$dataHoje'  
                                  WHERE id = $idKitBanco";

                    $integracao->Consulta($sqlUpdate);

                    return $this->response(true, "$mensagem | Preço Kit atualizado: R$ $preco_total");
                } else {
                    return $this->response(false, "Erro API Kit: $mensagem (Cód: $codMensagem)");
                }

            } else {
                return $this->response(true, "Preço Kit $referenciaKit inalterado (R$ $preco_total).");
            }
        } else {
            return $this->response(false, "Kit $idKitBanco não possui itens vinculados.");
        }
    }

    private function response(bool $success, string $message, $data = null): string
    {
        return json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
    }
}