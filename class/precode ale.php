<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('connections/databases.php');
require_once('connections/databases_estoque.php');

class Precode {
    const INTEGRATION_KEY = '';

    //const APP_TOKEN = 'SDBkSFVzOHJWNE94MkhaS3U6';
      // token teste everton
    const APP_TOKEN ='H0dHUs8rV4Ox2HZKu';  
  
    const URL = 'https://www.replicade.com.br/api';
    const VERSION_1 = '/v1';
    const VERSION_2 = '/v2';
    const VERSION_3 = '/v3';
    public $tabelaPadrao = 1;
    

    /**
     * @param string $productId ID do produto inserido no formulario
     * @param string $categoryId ID da categoria do produto se houver
     */
    public function sendProduct (string $productId, string $categoryId = ''): array {
        $hasProductPrecode = $this->verifyProductPrecode($productId);

        if ($hasProductPrecode) {
            echo 'produto já inserido na precode';
        }

        $api = new Database();
        $addSql = !empty($categoryId) ? $categoryId : '';        
        $tabela = $this->tabelaPadrao;

        $productsIntersig = $api->query("SELECT p.CODIGO,
        p.DATA_RECAD,
        p.DESCR_REDUZ,
        p.DESCR_CURTA,
        p.DESCR_LONGA,
        p.DESCRICAO,
        p.APLICACAO,
        p.GARANTIA,
        p.COMPRIMENTO,
        p.LARGURA,
        p.ALTURA,         
        p.PESO,       
        tp.PRECO,       
        m.descricao AS MARCA
        FROM cad_prod p
        INNER JOIN prod_tabprecos tp ON p.CODIGO = tp.PRODUTO
        LEFT JOIN cad_pmar m ON m.codigo = p.marca
        WHERE (p.NO_SITE='S'AND p.ATIVO='S') AND p.CODIGO = $productId AND p.GRADE = 0 AND tp.tabela = $tabela $addSql" ); 
        
        if ($productsIntersig) {
            $arrayProductsIntersig = mysqli_fetch_all($productsIntersig, MYSQLI_ASSOC);

            $packProduct = $this->packProduct($arrayProductsIntersig);

            if ($packProduct['error'] == true) {
                return [
                    'sendProduct' => false,
                    'httpCode' => 400,
                    'msg' => $packProduct['msg']
                ];
            }

            return [
                'sendProduct' => true,
                'httpCode' => $packProduct['httpCode'],
                'response' => $packProduct['response']
            ];
        }
        
        return [
            'sendProduct' => false,
            'msg' => 'Erro ao buscar produto, verifique se este item está marcado "NO_SITE" para a busca e se está "ATIVO".'        
        ];       
    }

    private function verifyProductPrecode (string $productId): bool {
        $api = new Database();
        $getProduct = $api->query("SELECT * FROM produto_precode where codigo_bd = $productId");

        $hasProduct = $getProduct->num_rows > 0 ? true : false;

        return $hasProduct;        
    }

    private function packProduct (array $productsIntersig): array {        
        if (empty($productsIntersig)) {
            return [
                'error' => true,
                'msg' => 'Erro: Produto vazio ou não existente!".'   
            ];  
        }
        foreach ($productsIntersig as $prod) {
            $arr = [];
            //$stockProd = $this->getStockProduct($prod['CODIGO']);
            $arr = [
                'product' => [
                    'sku' => null,
                    'name' => utf8_encode(str_replace('"', ' ', substr($prod['DESCRICAO'], 4, 10))),
                    'description' => utf8_encode($prod['DESCRICAO']),
                    'status' => 'enabled',
                    'price' => number_format($prod['PRECO'], 2, '.', ''),
                    'promotional_price' => number_format($prod['PRECO'], 2, '.', ''),
                    'cost' => number_format($prod['PRECO'], 2, '.', ''),
                    'weight' => !empty($prod['PESO']) ? number_format($prod['PESO'], 2, '.', '') : 0,
                    'width' => !empty($prod['LARGURA']) ? number_format($prod['LARGURA'], 2, '.', '') : 0,
                    'height' => !empty($prod['ALTURA']) ? number_format($prod['ALTURA'], 2, '.', '') : 0,
                    'length' => !empty($prod['COMPRIMENTO']) ? number_format($prod['COMPRIMENTO'], 2, '.', '') : 0,
                    'brand' => $prod['MARCA'],
                    'nbm' => null,
                    'model' => null,
                    'gender' => null,
                    'volumes' => 0,
                    'warrantyTime' => $prod['GARANTIA'],
                    'category' => '',
                    'subcategory' => '',
                    'endcategory' => '',
                    'attribute' => [
                        [
                            'key' => '',
                            'value' => ''
                        ]
                    ],
                    'variations' => [
                        [
                            'ref' => $prod['CODIGO'],
                            'sku' => null,
                            'qty' => 0,
                            'ean' => !empty($prod['EAN']) ? $prod['EAN'] : '',
                            'images' => [
                                ''
                            ],
                            'specifications' => [
                                [
                                    'key' => '',
                                    'value' => ''
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
        }

        //var_dump($arr);

        if ($arr['weight'] == 0) {
            return [
                'error' => true,
                'msg' => 'Erro: Peso não pode ser nulo ou vazio!".'   
            ];  
        }

        if ($arr['width'] == 0) {
            return [
                'error' => true,
                'msg' => 'Erro: Largura não pode ser nulo ou vazio!".'      
            ];
        }
        
        if ($arr['height'] == 0) {
            return [
                'error' => true,
                'msg' => 'Erro: Altura não pode ser nulo ou vazio!".'       
            ];
        }

        if ($arr['length'] == 0) {
            return [
                'error' => true,
                'msg' => 'Erro: Comprimento não pode ser nulo ou vazio!".'       
            ];
        }

        $json_string = json_encode($arr, JSON_UNESCAPED_UNICODE);    
           
        return $this->sendProductToPrecodeCurl($json_string);
    }

    private function sendProductToPrecodeCurl ($json): array {
        //print_r($json);
        //echo '<br> ';
        $url = self::URL . self::VERSION_3;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url . '/products');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . self::APP_TOKEN 
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [
            'error' => false,
            'httpCode' => $httpCode,
            'response' => $response
        ];
    }

    private function getStockProduct(string $productId): string {
        $apiStock = new DatabaseStock;
        
        $stock = $apiStock->query("SELECT
        P.CODIGO,
        P.DESCRICAO,
        FORMAT(COALESCE(SUM(PS.ESTOQUE) - COALESCE(SUM(PO.QTDE_SEPARADA), 0) * PO.FATOR_QTDE, 0), 0) AS ESTOQUE
        FROM
            hiper_estoque.prod_setor AS PS
            LEFT JOIN hiper_publico.cad_prod AS P ON P.CODIGO = PS.PRODUTO
            LEFT JOIN hiper_publico.cad_pgru AS G ON P.GRUPO = G.CODIGO
            LEFT JOIN hiper_vendas.empresas_setor AS S ON PS.SETOR = S.SETOR AND S.FILIAL = 2
            LEFT JOIN (
            SELECT
                CO.CODIGO,
                COALESCE(SUM(IF(PO.QTDE_SEPARADA > (PO.QUANTIDADE - PO.QTDE_MOV), PO.QTDE_SEPARADA, (PO.QUANTIDADE - PO.QTDE_MOV))) * IF(CO.TIPO = '5', -1, 1), 0) AS QTDE_SEPARADA,
                PO.FATOR_QTDE
            FROM
                hiper_vendas.cad_orca AS CO
                LEFT JOIN hiper_vendas.pro_orca AS PO ON PO.ORCAMENTO = CO.CODIGO
                LEFT JOIN hiper_vendas.empresas AS E ON E.FILIAL = 2
            WHERE
                CO.SITUACAO IN ('AI', 'AP', 'FP')
                AND (
                (E.MONT_FATUR <> 'S' AND PO.APROVADO IN ('A', 'C', 'G', 'P'))
                OR
                (E.MONT_FATUR = 'S' AND PO.APROVADO IN ('A', 'C', 'G'))
                )
            GROUP BY CO.CODIGO
            ) AS PO ON PO.CODIGO = P.CODIGO
        WHERE
            S.EST_ATUAL = 'X'
            AND P.ATIVO = 'S'
            AND P.CODIGO = $productId
            AND PS.SETOR NOT IN (469, 471)
        GROUP BY
            P.CODIGO,
            P.DESCRICAO;");

        if (mysqli_num_rows($stock) > 0) {
            $retorno = mysqli_fetch_all($stock, MYSQLI_ASSOC);
            return $retorno[0]['ESTOQUE'];
        } else {
            return 0;
        }  
    } 
}


?>
