<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <title>Salvar Produto</title>
</head>
<body>
    <div class="container">
        <h1>Dados Recebidos do Formulário</h1>

        <?php

    include_once(__DIR__.'/../utils/enviar-produto.php');

            if ($_SERVER["REQUEST_METHOD"] == "POST") {

                $objeEnv = new EnviarProduto();
                print_r(  $objeEnv->enviarProduto($_POST)) ;
                    

            /*
            $codigo = $_POST['codigo'];
            $descricao = $_POST['descricao'];
            $descricaocurta = $_POST['descricaocurta'];
            $aplicacao = $_POST['aplicacao'];
            $descricaogoogle = $_POST['descricaogoogle'];
            $outro_cod = $_POST['outro_cod'];
            $garantia = $_POST['garantia'];
            $comprimento = $_POST['comprimento'];
            $largura = $_POST['largura'];
            $altura = $_POST['altura'];
            $peso = $_POST['peso'];
            $preco = $_POST['preco'];
            $promocao = $_POST['promocao'];
            $estoque = $_POST['estoque'];
            $origem = $_POST['origem'];
            $categoria = $_POST['categoria'];
            $categoriainterm = $_POST['categoriainterm'];
            $categoriafinal = $_POST['categoriafinal'];
            $num_fabricante = $_POST['num_fabricante'];
            $modelo = $_POST['modelo'];
            $marca = $_POST['marca'];

             $imagens_base64 = [];

             
        

                $json = [];
               $fotos= [];
                $json['product']['sku'] = null;
                $json['product']['name'] =  mb_convert_encoding( str_replace('"', ' ', $descricao ), 'UTF-8', 'ISO-8859-1') ;
                $json['product']['shortName'] =  mb_convert_encoding( str_replace('"', ' ', $descricaocurta), 'UTF-8', 'ISO-8859-1') ;
                $json['product']['description'] = mb_convert_encoding($aplicacao, 'UTF-8', 'ISO-8859-1' ); //campo descricao detalhada do produto 
                $json['product']['googleDescription'] = mb_convert_encoding( $descricaogoogle, 'UTF-8', 'ISO-8859-1' ); //campo descricao detalhada do produto 
                $json['product']['status'] = 'enabled';
                $json['product']['price'] = floatval($preco);
                $json['product']['promotional_price'] = floatval($preco);
                $json['product']['cost'] = floatval($preco);
                $json['product']['weight'] = !empty($peso) ? floatval($peso) : 0;
                $json['product']['width'] = !empty($largura) ? floatval($largura) : 0;
                $json['product']['height'] = !empty($altura) ? floatval($altura) : 0;
                $json['product']['length'] = !empty($comprimento) ? floatval($comprimento) : 0;
                $json['product']['brand'] = $marca;
                $json['product']['nbm'] = !empty($ncm) ? str_replace(".","",$ncm)  : '';
                $json['product']['model'] =   !empty($modelo) ? removerAcentos($modelo) : null ; 
                $json['product']['gender'] = '';
                $json['product']['volumes'] = 0 ;
                $json['product']['warrantyTime'] = $garantia;
                $json['product']['category'] = !empty($categoria) ? $categoria : '';
                $json['product']['subcategory'] = !empty($categoriainterm) ?  removerAcentos($categoriainterm) : '';
                $json['product']['endcategory'] = !empty($categoriafinal) ?  removerAcentos($categoriafinal) : '';
                $json['product']['manufacturing']  =  $origem;
                $json['product']['attribute'] = [['key' => '', 'value' => '']];
                $json['product']['variations'] = [
                    [
                        'ref' => $outro_cod,
                        #'sku' => !empty($prod['SKU_MKTPLACE']) ?  floatval($prod['SKU_MKTPLACE']) : 0,
                        #'sku' => '',
                        'qty' => 0,
                        'ean' => !empty($num_fabricante) ? $num_fabricante : null,
                        'images' => $fotos,
                        'specifications' => [
                            [
                                'key' => '', 
                                'value' => ''
                            ]
                        ]
                    ]
                ];
            
              print_r($json);
              */
        }
        ?>

        <?php
 function removerAcentos(string $string): string
{
  $map = array(
    '/[áàãâä]/u' => 'a',
    '/[ÁÀÃÂÄ]/u' => 'A',
    '/[éèêë]/u' => 'e',
    '/[ÉÈÊË]/u' => 'E',
    '/[íìîï]/u' => 'i',
    '/[ÍÌÎÏ]/u' => 'I',
    '/[óòõôö]/u' => 'o',
    '/[ÓÒÕÔÖ]/u' => 'O',
    '/[úùûü]/u' => 'u',
    '/[ÚÙÛÜ]/u' => 'U',
    '/[ç]/u' => 'c',
    '/[Ç]/u' => 'C',
    '/[ñ]/u' => 'n',
    '/[Ñ]/u' => 'N',
  );

    return preg_replace(array_keys($map), array_values($map), $string);

}

?>

    </div>
</body>
</html>