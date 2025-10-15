<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="../assets/css/fotos.css" type="text/css">
    <link rel="icon" href="Favicon.png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <title>Precode</title>
</head>
<body>
</body>
<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');
include_once('databases.php');
$curl;
/**
 * CHAVE DE ENVIO HOMOLOGAÇÃO / BASETESTES
 */
// $appToken = 'SDBkSFVzOHJWNE94MkhaS3U6'; 
/**
 * 
 * CHAVE DE ENVIO EM PRODUÇÃO SYMA  
*/
//  $appToken = 'dng0c29BenNKek9qSUFHQ0c6';


   // token teste everton
  
 $appToken = 'H0dHUs8rV4Ox2HZKu';
$indice; 
$publico = new Database();

echo "<main class='login-form'>";
echo '<div class="cotainer">';
echo '<div class="row justify-content-center">';
echo '<div class="col-md-8">';
echo '<div class="card">';
echo '<div class="card-header alert alert-info" align="center"><h3 style="color: #008080;""><b>Aguardando dados..</b></h3>';
echo '</div>';            

//verifica se o produto já foi cadastrado baseado na tabela produto precode
$codigoProduto = $_POST['codigoProd'];

if ($codigoProduto == '' || $codigoProduto == 0) {
   echo 'O Código do produto não foi preenchido';
} else {
    //verifica se o produto já foi incluido na tabela precode anteriormente
    $produtoPrecode = $publico->query("SELECT * FROM produto_precode where codigo_bd = $codigoProduto");

    if ((mysqli_num_rows($produtoPrecode)) == 0) {
        echo 'Buscando o produto para ser enviado <br>';

        $sqlProdutoIntersig = $publico->query("SELECT p.CODIGO,
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
        WHERE (p.NO_MKTP='S'AND p.ATIVO='S')  AND tp.tabela = 3 AND p.CODIGO = $codigoProduto");
        
        //montagem de produto no intersig
        $prod = mysqli_fetch_array($sqlProdutoIntersig, MYSQLI_ASSOC);

        if ($prod == null || empty($prod)) {
            echo 'Produto não encontrado <br>';
            exit();
        }

        if ($prod['PESO'] == 0) {
            echo 'O campo peso não foi atribuido <br>';
            exit();
        }

        if ($prod['LARGURA'] == 0) {
            echo 'O campo peso não foi atribuido <br>';
            exit();
        }
        
        if ($prod['ALTURA'] == 0) {
            echo 'O campo peso não foi atribuido <br>';
            exit();
        }

        if ($prod['COMPRIMENTO'] == 0) {
            echo 'O campo peso não foi atribuido <br>';
            exit();
        }

        $json = [];

        $json['product']['sku'] = null;
        //$json['product']['name'] = utf8_encode(str_replace('"', ' ', substr($prod['DESCRICAO'], 4, 10)));
        $json['product']['name'] = utf8_encode(str_replace('"', ' ', $prod['DESCRICAO']));
        $json['product']['description'] = utf8_encode($prod['DESCRICAO']);
        $json['product']['status'] = 'enabled';
        $json['product']['price'] = floatval($prod['PRECO']);
        $json['product']['promotional_price'] = floatval($prod['PRECO']);
        $json['product']['cost'] = floatval($prod['PRECO']);
        $json['product']['weight'] = !empty($prod['PESO']) ? floatval($prod['PESO']) : 0;
        $json['product']['width'] = !empty($prod['LARGURA']) ? floatval($prod['LARGURA']) : 0;
        $json['product']['height'] = !empty($prod['ALTURA']) ? floatval($prod['ALTURA']) : 0;
        $json['product']['length'] = !empty($prod['COMPRIMENTO']) ? floatval($prod['COMPRIMENTO']) : 0;
        $json['product']['brand'] = $prod['MARCA'];
        $json['product']['nbm'] = null;
        $json['product']['model'] = null;
        $json['product']['gender'] = '';
        $json['product']['volumes'] = 0;
        $json['product']['warrantyTime'] = $prod['GARANTIA'];
        $json['product']['category'] = '';
        $json['product']['subcategory'] = '';
        $json['product']['endcategory'] = '';
        $json['product']['attribute'] = [['key' => '', 'value' => '']];
        $json['product']['variations'] = [
            [
                'ref' => $prod['CODIGO'],
                'sku' => '',
                'qty' => 0,
                'ean' => !empty($prod['EAN']) ? $prod['EAN'] : null,
                'images' => [''],
                'specifications' => [
                    [
                        'key' => '', 
                        'value' => ''
                    ]
                ]
            ]
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://www.replicade.com.br/api/v3/products');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . $appToken 
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode == 200 || $httpCode == 201) {
            echo '<br>O produto foi enviado para plataforma com sucesso! <br><br>';  
            /**
             * tratamento do retorno, está vindo como bool e decodifica para ARRAY
             */
            $retorno = json_decode($response, true);
            $codigo_bd = $prod['CODIGO']; //produto precisa ser defino antes da query ser executada
            $preco_site = $prod['PRECO']; //produto precisa ser defino antes da query ser executada
            $codigo_site = $retorno['sku'];
            $data_recad = date('Y-m-d H:i:s');
            $sql = "INSERT INTO produto_precode (codigo_site, codigo_bd, preco_site, data_recad) VALUES ('$codigo_site', $codigo_bd, $preco_site, '$data_recad')";
        
            /**
             * EXECUTA A QUERY PARA ENVIO PARA A TABELA PRODUTO_PRECODE
             */
            $envioPrecodeBase = $publico->query($sql);        
            if ($envioPrecodeBase) {	
                echo "Produto inserido na tabela com sucesso!";
            } else {
                echo "Erro ao inserir o produto na tabela: " . $publico->link->error;
            }
        } else {
            echo 'Houve um erro no envio do produto, contate a plataforma <br>';
            var_dump($httpCode);
        }
    } else {
        echo 'Produto já foi enviado para a plataforma! <br>';
    } 
    
}
?>