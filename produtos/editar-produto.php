<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <title>Editar Produto</title>
    <style>
        .form-group-inline {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .form-group-inline .form-group {
            flex: 1;
            margin-right: 10px;
            margin-bottom: 10px;
            min-width: 150px; /* Ajuste conforme necessário */
        }

        .form-group-inline .form-group:last-child {
            margin-right: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        /* Estilos gerais */
        body {
            font-family: 'Raleway', sans-serif;
            background-color: #f8f9fa; /* Cor de fundo leve */
        }

        /* Estilos para a sidebar */
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #343a40; /* Cor de fundo escura */
            padding-top: 60px;
            color: white; /* Cor do texto */
        }

        .sidebar a, .sidebar button {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 16px;
            color: #f2f2f2;
            display: block;
            transition: 0.3s;
            text-align: left;
            border: none;
            background-color: transparent;
            width: 100%;
        }

        .sidebar a:hover, .sidebar button:hover {
            background-color: #b3b3b3ff; /* Cor de fundo ao passar o mouse */
        }

    </style>
</head>
<body>
    
    <div class="container">
      
     <form method='post' action='controller.php' enctype='multipart/form-data'>

        <div class="sidebar">
              <?php
        $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

                if( isset($ini['config']['envio_produtos'])   ){
                     $enviar_habilitado = filter_var($ini['config']['envio_produtos'], FILTER_VALIDATE_BOOLEAN);
                  }
                    $disabled_attribute = $enviar_habilitado ? '' : 'disabled';
            echo '<a  >';
                echo '<Button type="submit" name="acao" value="enviar" '.$disabled_attribute.'>';
               ?>
                    Enviar Produto
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                </Button>
            </a>
        </div>
          <nav class="navbar navbar-expand-md navbar-dark fixed-top">
            <?php
                echo '<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">';
                echo '<i class="fa-solid fa-arrow-left"></i>';
                echo '<span style="margin: 10px;">';
                echo 'INTERSIG';
                echo '<span>';
                echo '</a>';
            ?>
        </nav>
        <h1>Editar Produto</h1>
        <?php
        include_once(__DIR__ . '/../database/conexao_publico.php');
        include_once(__DIR__ . '/../database/conexao_vendas.php');
        include_once(__DIR__ . '/../database/conexao_estoque.php');


        $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

           $setor=1;

            if($ini['conexao']['setor'] && !empty($ini['conexao']['setor']) ){
                $setor =$ini['conexao']['setor'];
            }

           $tabela = 1;
        if( $ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco']) ){
          $tabela =$ini['conexao']['tabelaPreco'];
        }


        $publico = new CONEXAOPUBLICO();
        $vendas = new CONEXAOVENDAS();
        $estoque = new CONEXAOESTOQUE();
        $databaseEstoque = $estoque->getBase();
        $databasePublico = $publico->getBase();
        $databaseVendas =  $vendas->getBase();
        // Obtém o código do produto da URL
        $codigoProduto = $_GET['codigo'];
        $tabelaDePreco = 1;


 $buscaEstoque = $estoque->Consulta(  "  SELECT
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
                                                        WHERE P.CODIGO = '$codigoProduto'
                                                            AND PS.SETOR = '$setor'
                                                            GROUP BY P.CODIGO) AS est " );

                    $retornoestoque = mysqli_num_rows($buscaEstoque);

                    if($retornoestoque > 0 ){
                        while($row_estoque = mysqli_fetch_array($buscaEstoque, MYSQLI_ASSOC)){
                            $estoqueprod  = $row_estoque['ESTOQUE'];
                        }
                    }

                    $resultFotosProd = $publico->consulta(" SELECT
                                                                CONCAT(vpar.FOTOS, fp.FOTO) AS FOTO
                                                                FROM
                                                                fotos_prod fp
                                                                JOIN ".$databaseVendas.".parametros vpar on vpar.id = 1
                                                                WHERE PRODUTO = $codigoProduto");

        // Consulta o banco de dados para obter as informações do produto
        $result = $publico->Consulta("
            SELECT p.CODIGO,
                p.OUTRO_COD,
                p.DATA_RECAD,
                p.SKU_MKTPLACE,
                p.DESCR_CURTA_MKTPLACE,
                p.DESCR_LONGA_MKTPLACE,
                p.DESCRICAO,
                p.APLICACAO,
                p.GARANTIA,
                p.COMPRIMENTO,
                p.LARGURA,
                p.ALTURA,
                p.PESO,
                p.ORIGEM,
                p.FINALCATEGORIA_MKTPLACE,
                p.MODELO_MKTPLACE,
                p.NUM_FABRICANTE,
                tp.PRECO,
                tp.PROMOCAO,
                m.descricao AS MARCA,
                cf.NCM,
                sg.DESCRICAO AS SUBCATEGORIA,
                cg.NOME AS CATEGORIA

            FROM cad_prod p
            INNER JOIN prod_tabprecos tp ON p.CODIGO = tp.PRODUTO
            LEFT JOIN cad_pmar m ON m.codigo = p.marca
            LEFT JOIN class_fiscal cf ON cf.CODIGO = p.CLASS_FISCAL
            LEFT JOIN cad_pgru cg ON cg.CODIGO = p.GRUPO
            LEFT join subgrupos sg ON sg.CODIGO = p.SUBGRUPO
            WHERE (p.NO_MKTP='S' AND p.ATIVO='S')  AND tp.tabela = $tabelaDePreco AND p.CODIGO = '$codigoProduto'");

        $produto = mysqli_fetch_array($result, MYSQLI_ASSOC);

        if ($produto) {


        $origemValor = $produto['ORIGEM'];
            $origemFormatada = '';

            switch ($origemValor) {
                  case '0':
                    $origemFormatada = 'Nacional';
                    break;
                case '1':
                    $origemFormatada = 'Importado';
                    break;
                case '2':
                    $origemFormatada = 'Importado'; // Sim, intencionalmente igual a 1
                    break;
                   case '3':
                    $origemFormatada = 'Nacional';
                    break;
                case '4':
                    $origemFormatada = 'Nacional';
                   break;
                case '5':
                    $origemFormatada = 'Nacional';
                   break;
                 case '6':
                    $origemFormatada = 'Importado';
                   break;
                 case '7':
                    $origemFormatada = 'Importado';
                  break;
                 case '8':
                    $origemFormatada = 'Nacional';
                   break;
                default:
                    $origemFormatada = 'Desconhecida'; // Ou qualquer outro valor padrão
                    break;
            }



            // Exibe um formulário para editar as informações do produto
                echo "<div class='row'>";
                mysqli_data_seek($resultFotosProd, 0);
                while($row_fotos = mysqli_fetch_array($resultFotosProd, MYSQLI_ASSOC)){
                $caminho_foto = $row_fotos['FOTO'];
                if (file_exists($caminho_foto)) {
                $imgData = file_get_contents($caminho_foto);
                $img = base64_encode($imgData);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $caminho_foto);
                finfo_close($finfo);
                 echo "<div class='col-md-3'>";
                  echo "<img src='data:" . $mimeType . ";base64," . $img . "' alt='Imagem do Produto' class='img-thumbnail'>";
                echo "</div>";
                } else {
                 echo "<div class='col-md-3'>";
                  echo "<p>Imagem não encontrada: </p>";
                  echo "</div>";
              }
            }
            echo "</div>";
 

            echo "<input type='hidden' name='codigo' value='" . $produto['CODIGO'] . "'>";

        echo "<div class='form-group'>";
        echo "<label for='descricao'>Descrição/titulo:</label>";
        echo "<textarea type='text' class='form-control' id='descricao' name='descricao' >" . htmlspecialchars(mb_convert_encoding($produto['DESCRICAO'], 'UTF-8', 'ISO-8859-1')).  "</textarea>";
        echo "</div>";

    echo "<div class='form-group'>";
        echo "<label for='descricaocurta'>Descrição curta/titulo curto:</label>";
        echo "<textarea type='text' class='form-control' id='descricaocurta' name='descricaocurta' >" . htmlspecialchars(mb_convert_encoding($produto['DESCRICAO'], 'UTF-8', 'ISO-8859-1')) .  "</textarea>";
        echo "</div>";
    echo "<div class='form-group'>";
        echo "<label for='aplicacao'>Aplicação/ descricao Informações sobre o produto (benefícios, dimensões, peso, garantia, etc.):</label>";
        echo "<textarea type='text' class='form-control' id='aplicacao' name='aplicacao' >". htmlspecialchars(mb_convert_encoding($produto['APLICACAO'], 'UTF-8', 'ISO-8859-1')). "</textarea>";
        echo "</div>";

        echo "<div class='form-group'>";
        echo "<label for='descricaogoogle'>Descrição curta mktplace/ descrição google :</label>";
        echo "<textarea type='text' class='form-control' id='descricaogoogle' name='descricaogoogle'  >" . htmlspecialchars(mb_convert_encoding($produto['DESCR_CURTA_MKTPLACE'], 'UTF-8', 'ISO-8859-1'))  . "</textarea>";
        echo "</div>";

          echo "<div class='form-group'>";
        echo "<label for='palavraschave'>Descrição longa mktplace/ Palavras chave :</label>";
        echo "<textarea type='text' class='form-control' id='palavraschave' name='palavraschave'  >" . htmlspecialchars(mb_convert_encoding($produto['DESCR_LONGA_MKTPLACE'], 'UTF-8', 'ISO-8859-1'))  . "</textarea>";
        echo "</div>";


        echo "<div class='form-group-inline'>";
              echo "<div class='form-group'>";
                echo "<label for='outro_cod'>Outro Código/referencia:</label>";
                echo "<input type='text' class='form-control' id='outro_cod' name='outro_cod' value='" . htmlspecialchars(mb_convert_encoding($produto['OUTRO_COD'], 'UTF-8', 'ISO-8859-1')) . "'>";
              echo "</div>";

              
                 echo "<div class='form-group'>";
                    echo "<label for='garantia'>Garantia:</label>";
                    echo "<input type='text' class='form-control' id='garantia' name='garantia' value='" . htmlspecialchars(mb_convert_encoding($produto['GARANTIA'], 'UTF-8', 'ISO-8859-1')) . "'>";
                 echo "</div>";

            echo "</div>"; // Fecha form-group-inline


            echo "<div class='form-group-inline'>";
                echo "<div class='form-group'>";
                    echo "<label for='comprimento'>Comprimento:</label>";
                    echo "<input type='text' class='form-control' id='comprimento' name='comprimento' value='" . htmlspecialchars(mb_convert_encoding($produto['COMPRIMENTO'], 'UTF-8', 'ISO-8859-1')) . "'>";
                echo "</div>";

                echo "<div class='form-group'>";
                    echo "<label for='largura'>Largura:</label>";
                    echo "<input type='text' class='form-control' id='largura' name='largura' value='" . htmlspecialchars(mb_convert_encoding($produto['LARGURA'], 'UTF-8', 'ISO-8859-1')) . "'>";
                echo "</div>";

                echo "<div class='form-group'>";
                    echo "<label for='altura'>Altura:</label>";
                    echo "<input type='text' class='form-control' id='altura' name='altura' value='" . htmlspecialchars(mb_convert_encoding($produto['ALTURA'], 'UTF-8', 'ISO-8859-1')) . "'>";
                echo "</div>";

                    echo "<div class='form-group'>";
                    echo "<label for='peso'>Peso:</label>";
                    echo "<input type='text' class='form-control' id='peso' name='peso' value='" . htmlspecialchars(mb_convert_encoding($produto['PESO'], 'UTF-8', 'ISO-8859-1')) . "'>";
                    echo "</div>";
                    echo "</div>";
                    echo "<div class='form-group-inline'>";
                echo "<div class='form-group'>";
                    echo "<label for='preco'>Preço:</label>";
                    echo "<input type='text' class='form-control' id='preco' name='preco' value='" . $produto['PRECO'] . "'>";
                echo "</div>";
                 echo "<div class='form-group'>";
                    echo "<label for='promocao'>Promoção:</label>";
                    echo "<input type='text' class='form-control' id='promocao' name='promocao' value='" . $produto['PROMOCAO'] . "'>";
                echo "</div>";

                    echo "<div class='form-group'>";
                        echo "<label for='estoque'>Estoque:</label>";
                        echo "<input type='text' class='form-control' id='estoque' name='estoque' value='" . $estoqueprod  . "'>";
                    echo "</div>";
                echo "</div>";
            
        echo "<div class='form-group-inline'>";
            echo "<div class='form-group'>";
            echo "<label for='origem'>Origem:</label>";
            echo "<input type='text' class='form-control' id='origem' name='origem' value='" . htmlspecialchars(mb_convert_encoding($origemFormatada, 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

          echo "<div class='form-group'>";
                echo "<label for='ncm'>NCM:</label>";
                echo "<input type='text' class='form-control' id='ncm' name='ncm' value='" . htmlspecialchars(mb_convert_encoding($produto['NCM'], 'UTF-8', 'ISO-8859-1')) . "'>";
                echo "</div>";
           echo "</div>";

        echo "<div class='form-group-inline'>";
            

    echo "<div class='form-group'>";
                  echo "<label for='categoria'>Categoria :</label>";
                  echo "<input type='text' class='form-control' id='categoria' name='categoria' value='" . htmlspecialchars(mb_convert_encoding($produto['CATEGORIA'], 'UTF-8', 'ISO-8859-1')) . "'>";
                echo "</div>";

                  echo "<div class='form-group'>";
                    echo "<label for='categoriainterm'>Categoria interm. :</label>";
                    echo "<input type='text' class='form-control' id='categoriainterm' name='categoriainterm' value='" . htmlspecialchars(mb_convert_encoding($produto['CATEGORIA'], 'UTF-8', 'ISO-8859-1')) . "'>";
                   echo "</div>";

                    echo "<div class='form-group'>";
                  echo "<label for='categoriafinal'>Categoria Final:</label>";
                  echo "<input type='text' class='form-control' id='categoriafinal' name='categoriafinal' value='" . htmlspecialchars(mb_convert_encoding($produto['FINALCATEGORIA_MKTPLACE'], 'UTF-8', 'ISO-8859-1')) . "'>";
                echo "</div>";
          
         echo "</div>";


             echo "<div class='form-group-inline'>";
                echo "<div class='form-group'>";
                echo "<label for='num_fabricante'>Núm.Fabricante/GTIN:</label>";
                echo "<input type='text' class='form-control' id='num_fabricante' name='num_fabricante' value='" . htmlspecialchars(mb_convert_encoding($produto['NUM_FABRICANTE'], 'UTF-8', 'ISO-8859-1')) . "'>";
                echo "</div>";
           
       echo "<div class='form-group'>";
                echo "<label for='modelo'>Modelo Marketplace:</label>";
                echo "<input type='text' class='form-control' id='modelo' name='modelo' value='" . htmlspecialchars(mb_convert_encoding($produto['MODELO_MKTPLACE'], 'UTF-8', 'ISO-8859-1')) . "'>";
              echo "</div>";

                   echo "<div class='form-group'>";
                    echo "<label for='marca'>Marca:</label>";
                    echo "<input type='text' class='form-control' id='marca' name='marca' value='" . htmlspecialchars(mb_convert_encoding($produto['MARCA'], 'UTF-8', 'ISO-8859-1')) . "'>";
                    echo "</div>";
                 echo "</div>";


         
            echo "</form>";
        } else {
            echo "<p>Produto não encontrado.</p>";
        }

        $publico->Desconecta();
        $vendas->Desconecta();
        ?>
    </div>

    <script> 
 /* document.getElementById('formFile').addEventListener('change', function(event) {
    const files = event.target.files; // Lista de arquivos selecionados
    const imagensPreview = document.getElementById('imagensPreview');
    imagensPreview.innerHTML = ''; // Limpa previews anteriores

    if (files && files.length > 0) {
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const reader = new FileReader();
 
        reader.onload = function(e) {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.style.maxWidth = '100px'; // Ajuste o tamanho conforme necessário
          img.style.marginRight = '5px';
          imagensPreview.appendChild(img);
        }

        reader.readAsDataURL(file); // Converte o arquivo para uma URL de dados (base64)
      }
    }
  }); 
*/

</script>

</body>
</html>
