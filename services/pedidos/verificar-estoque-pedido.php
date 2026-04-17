<?php
ini_set('mysql.connect_timeout', '0');
ini_set('max_execution_time', '0');
date_default_timezone_set('America/Sao_Paulo'); //
include_once(__DIR__ . '/../../database/conexao_publico.php');


class VerificarEstoquePedido
{
   public $curl;

   public $tabelaprecopadrao = 1;
   public $filial = 1;
   public $indice;

   /**
    * @param $publico, $vendas, $estoque connexoes do banco de dados 
    * @param $produtos_pedido produtos do pedido vindo da precode 
    * @param $codigo_pedido_precode codigo do peidido da precode 
    */
      function verify($publico, $vendas, $estoque, $produtos_pedido, $codigo_pedido_precode)
      {

         //$ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);
         $ini = parse_ini_file(__DIR__ . '/../../conexao.ini', true);

         if ($ini['conexao']['setor'] && !empty($ini['conexao']['setor'])) {
            $setor = $ini['conexao']['setor'];
         }
         $databaseVendas = $vendas->getBase();
         $databaseEstoque = $estoque->getBase();
         $databasePublico = $publico->getBase();

         $qtd_itens = count($produtos_pedido);
         if ($qtd_itens > 0) {
                                          
                           for ($p = 0; $p < $qtd_itens; $p++) {
                                                   $referenciaLoja = $produtos_pedido[$p]->referenciaLoja;
                                                   
                                                   $sql = "SELECT  
                                                         est.CODIGO, est.referencia,
                                                               IF(est.estoque < 0, 0, est.estoque) AS ESTOQUE,
                                                               est.DATA_RECAD
                                                         FROM 
                                                               (SELECT
                                                               P.CODIGO,P.OUTRO_COD as referencia,
                                                               PS.DATA_RECAD,
                                                               (SUM(PS.ESTOQUE) - 
                                                                  (SELECT COALESCE(SUM((IF(PO.QTDE_SEPARADA > (PO.QUANTIDADE - PO.QTDE_MOV), PO.QTDE_SEPARADA, (PO.QUANTIDADE - PO.QTDE_MOV)) * PO.FATOR_QTDE) * IF(CO.TIPO = '5', -1, 1)), 0)
                                                                  FROM " . $databaseVendas . ".cad_orca AS CO
                                                                  LEFT OUTER JOIN " . $databaseVendas . ".pro_orca AS PO ON PO.ORCAMENTO = CO.CODIGO
                                                                  WHERE CO.SITUACAO IN ('AI', 'AP', 'FP')
                                                                  AND PO.PRODUTO = P.CODIGO)) AS estoque
                                                               FROM " . $databaseEstoque . ".prod_setor AS PS
                                                               LEFT JOIN " . $databasePublico . ".cad_prod AS P ON P.CODIGO = PS.PRODUTO
                                                               INNER JOIN " . $databasePublico . ".cad_pgru AS G ON P.GRUPO = G.CODIGO
                                                               LEFT JOIN " . $databaseEstoque . ".setores AS S ON PS.SETOR = S.CODIGO
                                                         WHERE P.CODIGO = '$referenciaLoja'
                                                               AND PS.SETOR = '$setor'
                                                               GROUP BY P.CODIGO) AS est ";
                                                   $buscaEstoque = $estoque->Consulta($sql);
                              $retornoestoque = mysqli_num_rows($buscaEstoque);
                        
                              if($retornoestoque > 0 ){   
                                    while($row_estoque = mysqli_fetch_array($buscaEstoque, MYSQLI_ASSOC)){	
                                 $estoqueprod  = $row_estoque['ESTOQUE'];

                                 if($estoqueprod  > 0 ){
                                            return $this->response(false,'[X] saldo zerado para o Produto: '. $referenciaLoja. ' saldo [ '.$estoqueprod.' ] \n <br>' );

                                   }else{
                                            return $this->response(false,' [X] Estoque insuficiente. Produto:  '. $referenciaLoja. ' saldo [ '.$estoqueprod.' ] O pedido '.$codigo_pedido_precode.' nao será recebido \n <br>' );
                                     }
                                  }

                              }else{
                                    return $this->response(false,  ' [X] Produto: '.$referenciaLoja .'. Não foi encontrado nos registros do produto nos setores. \n <br>');
                              }

                  }
         
         }else{
                 return $this->response(false,' [X] Pedido '. $codigo_pedido_precode.' esta sem produtos. \n <br>');

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
