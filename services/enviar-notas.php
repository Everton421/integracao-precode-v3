<?php
 
class EnviarNota
{
    function enviar($vendas, $integracao )
    {

        ini_set('max_execution_time', '0');
        date_default_timezone_set('America/Sao_Paulo');

        set_time_limit(0);


        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

        if (empty($ini['conexao']['token'])) {
            echo 'token da aplicação não fornecido';
            return;
        }

        $appToken =  $ini['conexao']['token'];
 

        $databaseIntegracao = $integracao->getBase();


        echo '<div class="card-header alert alert-info" align="center"><h3 style="color: #008080;""><b>Buscando informações fiscais..</b></h3>'; //abrindo o header com informação
        echo '</div>';

        $sqlInvoices = "SELECT pid.situacao, co.COD_SITE, cnf.CHAVE_NFE, cnf.NUMERO_NF, cnf.DATA_EMISSAO, cnf.SERIE, xf.XML_NFE
                                                     FROM cad_orca co 
                                                            inner join ".$databaseIntegracao.".pedido_precode pid on co.codigo = pid.codigo_pedido_bd
                                                            inner join cad_nf cnf on cnf.pedido = co.codigo
                                                            inner join xml_fatur xf on xf.FATUR = cnf.CODIGO
                                                            where cnf.CHAVE_NFE != ''
                                                            and pid.situacao = 'aprovado';";

        $busca_nf = $vendas->Consulta($sqlInvoices);
        $retorno = mysqli_num_rows($busca_nf);


        if ($retorno > 0) {
            while ($row = mysqli_fetch_array($busca_nf, MYSQLI_ASSOC)) {
                $id_pedido  = $row['COD_SITE'];
                $chave_nf  = $row['CHAVE_NFE'];
                $xml  = base64_encode($row['XML_NFE']);

                $object_json_put = "
                    {
                        \r\n\"pedido\": 
                        [
                            \r\n
                            {
                                \r\n\"codigoPedido\": $id_pedido,
                                \r\n\"chaveNF\": \"$chave_nf\",
                                \r\n\"xml\": \"$xml\"\r\n        
                            }
                            \r\n    
                        ]
                        \r\n
                    }"; 

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/faturamento",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "PUT",
                    CURLOPT_POSTFIELDS => $object_json_put,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: $appToken",
                        "Content-Type: application/json"
                    ),
                ));
                print_r($curl);
                $response = curl_exec($curl);
                print_r($response);
                curl_close($curl);
                $decode = json_decode($response);
                $codMensagem = $decode->pedido[0]->idRetorno;
                $mensagem_nf_err = $decode->pedido[0]->mensagem;
                $numeroPedido = $decode->pedido[0]->numeroPedido;
                $busca_status = $integracao->Consulta("select * from pedido_precode where codigo_pedido_site = '$id_pedido' and situacao = 'aprovado'");
                $retorno2 = mysqli_num_rows($busca_status);

                if ($codMensagem == 0) {
                    $sql = "update pedido_precode set situacao = 'nota_enviada' where codigo_pedido_site = '$id_pedido'";
                    if (mysqli_query($integracao->link, $sql) === TRUE) {
                         return $this->response(false,'XML da nota inserida com sucesso!'  );

                    } else {
                         return $this->response(false,'Falha ao inserir XML da  nota fiscal'  );
                    }
                } else {
                 return $this->response(false,'Falha ao inserir nota fiscal <br>Cód:' . $numeroPedido . '<br>' . $mensagem_nf_err);
                }
            }
        } else {
            // Não há notas/XML pendentes
                 return $this->response(false,' [X] Não há notas/XML pendentes. \n <br>');

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
