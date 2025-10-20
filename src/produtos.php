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

include(__DIR__.'/database/conexao_publico.php');
include(__DIR__.'/database/conexao_vendas.php');
include(__DIR__.'/database/conexao_estoque.php');


$curl;

$indice; 
$publico = new CONEXAOPUBLICO();

$ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

$tabelaDePreco = 1;
if($ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco']) ){
    $tabelaDePreco =$ini['conexao']['tabelaPreco']; 
}


if(empty($ini['conexao']['token'] )){
    echo 'token da aplicação não fornecido';
        exit();
}

$appToken = $ini['conexao']['token'];

echo "<main class='login-form'>";
echo '<div class="cotainer">';
echo '<div class="row justify-content-center">';
echo '<div class="col-md-8">';
echo '<div class="card">';
echo '<div class="card-header alert alert-info" align="center"><h3 style="color: #008080;""><b>Aguardando dados..</b></h3>';
echo '</div>';            

//verifica se o produto já foi cadastrado baseado na tabela produto precode
$codigoProduto = $_POST['codprod'];
 
if(!$appToken || empty($appToken)){
            echo '<div class="container">';
                echo '<div class="alert alert-danger" role="alert">';
                echo '<strong>Erro!</strong>token da aplicação não foi fornecido ';
                echo '<br>';
                echo '</div>';
            echo '</div>';
            exit();
      
        }
if(!$tabelaDePreco || empty($tabelaDePreco)){
            echo '<div class="container">';
                echo '<div class="alert alert-danger" role="alert">';
                echo '<strong>Erro!</strong>não foi fornecido o codigo da tabela de preço ';
                echo '<br>';
                echo '</div>';
            echo '</div>';
            exit();
     
        }


if ($codigoProduto == '' || $codigoProduto == 0) {
    
      echo '<div class="container">';
                echo '<div class="alert alert-danger" role="alert">';
                echo '<strong>Erro!</strong>O Código do produto não foi preenchido ';
                echo '<br>';
                echo '</div>';
            echo '</div>';

} else {
     
    //verifica se o produto já foi incluido na tabela precode anteriormente
    $produtoPrecode = $publico->Consulta("SELECT * FROM produto_precode where codigo_bd = $codigoProduto");

    if ((mysqli_num_rows($produtoPrecode)) == 0) {
     //   echo 'Buscando o produto para ser enviado <br>';

        $sqlProdutoIntersig = $publico->Consulta("SELECT p.CODIGO,
            p.DATA_RECAD,
            p.SKU_MKTPLACE,
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
        WHERE (p.NO_MKTP='S'AND p.ATIVO='S')  AND tp.tabela = $tabelaDePreco AND p.CODIGO = $codigoProduto");
        
        //montagem de produto no intersig
        $prod = mysqli_fetch_array($sqlProdutoIntersig, MYSQLI_ASSOC);

        if ($prod == null || empty($prod)) {
            echo 'Produto não encontrado <br>';
            exit();
        }

        if ($prod['PESO'] == 0) {
               echo '<div class="container">';
                echo '<div class="alert alert-danger" role="alert">';
                echo '<strong>Erro!</strong>O campo PESO não foi atribuido ';
                echo '<br>';
                echo '</div>';
            echo '</div>';
            exit();
        }

        if ($prod['LARGURA'] == 0) {
              echo '<div class="container">';
              echo '<div class="alert alert-danger" role="alert">';
            echo '<strong>Erro!</strong>O campo LARGURA não foi atribuido ';
            echo '<br>';
              echo '</div>';
            echo '</div>';
            exit();
        }
        
        if ($prod['ALTURA'] == 0) {
             echo '<div class="container">';
              echo '<div class="alert alert-danger" role="alert">';
            echo '<strong>Erro!</strong>O campo ALTURA não foi atribuido ';
            echo '<br>';
              echo '</div>';
            echo '</div>';
            exit();
        }

        if ($prod['COMPRIMENTO'] == 0) {
             echo '<div class="container">';
              echo '<div class="alert alert-danger" role="alert">';
            echo '<strong>Erro!</strong>O campo COMPRIMENTO não foi atribuido ';
            echo '<br>';
              echo '</div>';
            echo '</div>';
            exit();
        }

        print_r($prod['SKU_MKTPLACE']);
        
        $json = [];

#        $json['product']['sku'] = !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0;

        $json['product']['sku'] = null;
        $json['product']['name'] = mb_convert_encoding( str_replace('"', ' ', $prod['DESCRICAO']), 'UTF-8', 'ISO-8859-1');
        $json['product']['description'] = mb_convert_encoding($prod['DESCRICAO'], 'UTF-8', 'ISO-8859-1' );
        $json['product']['status'] = 'enabled';
        $json['product']['price'] = floatval($prod['PRECO']);
        $json['product']['promotional_price'] = floatval($prod['PRECO']);
        $json['product']['cost'] = floatval($prod['PRECO']);
        $json['product']['weight'] = !empty($prod['PESO']) ? floatval($prod['PESO']) : 0;
        $json['product']['width'] = !empty($prod['LARGURA']) ? floatval($prod['LARGURA']) : 0;
        $json['product']['height'] = !empty($prod['ALTURA']) ? floatval($prod['ALTURA']) : 0;
        $json['product']['length'] = !empty($prod['COMPRIMENTO']) ? floatval($prod['COMPRIMENTO']) : 0;
        $json['product']['brand'] = $prod['MARCA'];
        $json['product']['nbm'] = !empty($prod['NCM']) ? $prod['NCM']  : '';
        $json['product']['model'] = null;
        $json['product']['gender'] = '';
        $json['product']['volumes'] = !empty($prod['VOLUMES']) ? $prod['VOLUMES'] : 0 ;
        $json['product']['warrantyTime'] = $prod['GARANTIA'];
        $json['product']['category'] = !empty($prod['CATEGORIA']) ? $prod['CATEGORIA'] : '';
        $json['product']['subcategory'] = '';
        $json['product']['endcategory'] = '';
        $json['product']['attribute'] = [['key' => '', 'value' => '']];
        $json['product']['variations'] = [
            [
                'ref' => $prod['CODIGO'],
                'sku' => !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0,
                #'sku' => '',
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
            $retorno = json_decode($response, true);

        if ($httpCode == 200 || $httpCode == 201) {
               echo '<div class="container">';
                echo '<div class="alert alert-success" role="alert">';
                echo '<strong>Sucesso!</strong> O produto foi enviado para a plataforma com sucesso!';
                echo '</div>';

                $codigo_bd = $prod['CODIGO'];
                $preco_site = $prod['PRECO'];
                $codigo_site = isset($retorno['sku']) ? $retorno['sku'] : null; // Verifica se 'sku' existe
                $data_recad = date('Y-m-d H:i:s');
                $sql = "INSERT INTO produto_precode (codigo_site, codigo_bd, preco_site, data_recad) VALUES ('" . htmlspecialchars($codigo_site) . "', " . intval($codigo_bd) . ", " . floatval($preco_site) . ", '" . htmlspecialchars($data_recad) . "')";

                $envioPrecodeBase = $publico->Consulta($sql);

                if ($envioPrecodeBase) {
                    echo '<div class="alert alert-info" role="alert">';
                    echo '<strong>Informação:</strong> Produto inserido na tabela com sucesso!';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-warning" role="alert">';
                    echo '<strong>Atenção!</strong> Erro ao inserir o produto na tabela.';
                    echo '<br>';
                    echo '<strong>SQL:</strong> ' . htmlspecialchars($sql); // Mostra a query para debug
                    echo '</div>';
                }
    echo '</div>'; // Fecha o container
        } else {
             echo '<div class="container">';
            echo '<div class="alert alert-danger" role="alert">';
            echo '<strong>Erro!</strong> Houve um erro no envio do produto, contate a plataforma.';
            echo '<br>';
            echo '<strong>HTTP Code:</strong> ' . htmlspecialchars($httpCode) . '<br>';
            if (isset($retorno['message'])) {
                echo '<strong>Mensagem de Erro:</strong> ' . htmlspecialchars(print_r($retorno['message'], true));
            } else {
                echo '<strong>Mensagem de Erro:</strong> Detalhes não fornecidos.';
            }
            echo '</div>';
            echo '</div>';
        }
         
    } else {
        echo 'Produto já foi enviado para a plataforma! <br>';
    } 
    
}
?>