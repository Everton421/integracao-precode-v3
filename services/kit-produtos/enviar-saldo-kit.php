<?php
date_default_timezone_set('America/Sao_Paulo');

// Includes necessários
include_once(__DIR__.'/../../database/conexao_publico.php');
include_once(__DIR__.'/../../database/conexao_estoque.php'); 
include_once(__DIR__.'/../../database/conexao_vendas.php');
include_once(__DIR__.'/../../database/conexao_integracao.php');

class EnviarSaldoKit {

    /**
     * Envia o saldo de um KIT para a API
     * @param int $idKitBanco ID interno do kit na tabela `kit`
     */
    public function postSaldoKit(int $idKitBanco, $publico, $estoque, $vendas, $integracao){
        set_time_limit(0);

        $ini = parse_ini_file(__DIR__ .'/../../conexao.ini', true);
        $setor = isset($ini['conexao']['setor']) ? $ini['conexao']['setor'] : 1;
        $appToken = isset($ini['conexao']['token']) ? $ini['conexao']['token'] : '';

        if(empty($appToken)){
            return $this->response(false,'Token da aplicação não fornecido');
        }

        $databaseVendas = $vendas->getBase();
        $databaseEstoque = $estoque->getBase();
        $databasePublico = $publico->getBase();
        $databaseIntegracao = $integracao->getBase();

        // 1. Busca dados do Kit Pai
        $sqlKit = "SELECT CODIGO_SITE, CODIGO_KIT, SALDO_ENVIADO FROM {$databaseIntegracao}.padronizados WHERE id = $idKitBanco";
        $resKit = $integracao->Consulta($sqlKit);

        if(mysqli_num_rows($resKit) == 0){
            return $this->response(false, "Kit ID $idKitBanco não encontrado na tabela de integração.");
        }

        $dadosKit = mysqli_fetch_assoc($resKit);
        $codigoSite = $dadosKit['CODIGO_SITE']; // ID no sistema externo
        $referenciaKit = $dadosKit['CODIGO_KIT']; // Ref (SKU) do Kit
        $saldoAnterior = $dadosKit['SALDO_ENVIADO'];

        if(empty($codigoSite)){
            return $this->response(false, "Kit $referenciaKit não possui CODIGO_SITE vinculado.");
        }

        // 2. Busca os Itens que compõem o Kit

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

        if(mysqli_num_rows($resItens) == 0){
            // Kit sem itens = Estoque Zero
            $estoqueFinalKit = 0;
        } else {
            // 3. Calcula o Estoque Virtual (Gargalo)
            $estoqueFinalKit = null; // null indica que ainda não calculamos

            while($item = mysqli_fetch_assoc($resItens)){
                $idItemFilho = $item['CODIGO_ITEM_SISTEMA'];
                $qtdNecessaria = floatval($item['QUANTIDADE']);

                if($qtdNecessaria <= 0) continue;

                // Consulta Estoque Real do Item Filho no ERP
                // (Query padrão complexa para descontar reservas)
                   $sqlEstoqueFilho = "SELECT  
                                                    est.CODIGO, est.referencia,
                                                            IF(est.estoque < 0, 0, est.estoque) AS ESTOQUE,
                                                            est.DATA_RECAD
                                                        FROM 
                                                            (SELECT
                                                            P.CODIGO,P.OUTRO_COD as referencia,
                                                            PS.DATA_RECAD,
                                                            (SUM(PS.ESTOQUE) - 
                                                                (SELECT COALESCE(SUM((IF(PO.QTDE_SEPARADA > (PO.QUANTIDADE - PO.QTDE_MOV), PO.QTDE_SEPARADA, (PO.QUANTIDADE - PO.QTDE_MOV)) * PO.FATOR_QTDE) * IF(CO.TIPO = '5', -1, 1)), 0)
                                                                FROM ".$databaseVendas.".cad_orca AS CO
                                                                LEFT OUTER JOIN ".$databaseVendas.".pro_orca AS PO ON PO.ORCAMENTO = CO.CODIGO
                                                                WHERE CO.SITUACAO IN ('AI', 'AP', 'FP')
                                                                AND PO.PRODUTO = P.CODIGO)) AS estoque
                                                            FROM ".$databaseEstoque.".prod_setor AS PS
                                                            LEFT JOIN ".$databasePublico.".cad_prod AS P ON P.CODIGO = PS.PRODUTO
                                                            INNER JOIN ".$databasePublico.".cad_pgru AS G ON P.GRUPO = G.CODIGO
                                                            LEFT JOIN ".$databaseEstoque.".setores AS S ON PS.SETOR = S.CODIGO
                                                        WHERE P.CODIGO = '$idItemFilho'
                                                            AND PS.SETOR = '$setor'
                                                            GROUP BY P.CODIGO) AS est ";

                                     

                $resEstFilho = $estoque->Consulta($sqlEstoqueFilho);
                $saldoFilho = 0;

                if($resEstFilho && mysqli_num_rows($resEstFilho) > 0){
                    $rowEst = mysqli_fetch_assoc($resEstFilho);
                    $saldoFilho = floatval($rowEst['ESTOQUE']);
                }
                if($saldoFilho < 0) $saldoFilho = 0;

                // Quantos kits este item permite montar?
                $capacidadeItem = floor($saldoFilho / $qtdNecessaria);

                // Lógica do menor valor (Gargalo)
                if($estoqueFinalKit === null || $capacidadeItem < $estoqueFinalKit){
                    $estoqueFinalKit = $capacidadeItem;
                }
            }

            // Se ainda for null (array vazio ou erro), zera
            if($estoqueFinalKit === null) $estoqueFinalKit = 0;
        }

        // 4. Verifica se precisa enviar (Se mudou o saldo)
        // Você pode adicionar a lógica de "forçar envio" aqui se quiser
        if($estoqueFinalKit != $saldoAnterior){
            
            // Monta JSON para API
            // OBS: Verifique se a API espera "IdReferencia" como o CODIGO_SITE ou a REFERENCIA (SKU)
            // No seu exemplo anterior você usava a REFERENCIA no campo IdReferencia.
            
            $payload = json_encode([
                "produto" => [
                    [
                        "IdReferencia" => $referenciaKit, // OU $codigoSite, dependendo da API
                        "sku" => 0, // Geralmente 0 ou o ID numérico
                        "estoque" => [
                            [
                                "filialSaldo" => 1,
                                "saldoReal" => $estoqueFinalKit,
                                "saldoDisponivel" => $estoqueFinalKit
                            ]
                        ]
                    ]
                ]
            ]);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://www.replicade.com.br/api/v1/produtoLoja/saldo",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic ".$appToken,
                    "Content-Type: application/json"
                ),
            ));

            $result = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $resultado = json_decode($result);
            $mensagem = isset($resultado->produto[0]->mensagem) ? $resultado->produto[0]->mensagem : 'Erro desconhecido API';
            $codMensagem = isset($resultado->produto[0]->idMensagem) ? $resultado->produto[0]->idMensagem : -1;

            if($codMensagem == '0'){
                // Atualiza tabela local
                $integracao->Consulta("UPDATE {$databaseIntegracao}.padronizados SET SALDO_ENVIADO = $estoqueFinalKit, DATA_RECAD_ESTOQUE = NOW() WHERE id = $idKitBanco");
                
                return $this->response(true, "Estoque Kit atualizado: $estoqueFinalKit. Msg: $mensagem");
            } else {
                return $this->response(false, "Erro API: $mensagem (HTTP $httpcode)");
            }

        } else {
            return $this->response(true, "Saldo inalterado ($estoqueFinalKit), envio ignorado.");
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