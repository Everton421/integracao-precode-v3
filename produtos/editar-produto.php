 <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <title>Editar Produto</title>
</head>
<body>
    <div class="container">
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
                p.ORIGEM,
                p.FINALCATEGORIA_MKTPLACE,
                p.MODELO_MKTPLACE,
                p.NUM_FABRICANTE,       
                tp.PRECO,       
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
            // Exibe um formulário para editar as informações do produto
            echo "<form method='post' action='salvar_produto.php'>";
          echo "<div class='row'>";
            mysqli_data_seek($resultFotosProd, 0);
            while($row_fotos = mysqli_fetch_array($resultFotosProd, MYSQLI_ASSOC)){
                $caminho_foto = $row_fotos['FOTO'];
                if (file_exists($caminho_foto)) {
                    echo "<div class='col-md-3'>";
                    echo "<img src='" . htmlspecialchars( $caminho_foto)  . "' alt='Imagem do Produto' class='img-thumbnail'>";
                    echo "</div>";
                } else {
                    echo "<div class='col-md-3'>";
                    echo "<p>Imagem não encontrada:  </p>";
                    echo "</div>";
                }
            }
            echo "</div>";
            
            echo "<input type='hidden' name='codigo' value='" . $produto['CODIGO'] . "'>";

            // Campo: Descrição
            echo "<div class='form-group'>";
            echo "<label for='descricao'>Descrição:</label>";
            echo "<input type='text' class='form-control' id='descricao' name='descricao' value='" . htmlspecialchars(mb_convert_encoding($produto['DESCRICAO'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Outro Código
            echo "<div class='form-group'>";
            echo "<label for='outro_cod'>Outro Código:</label>";
            echo "<input type='text' class='form-control' id='outro_cod' name='outro_cod' value='" . htmlspecialchars(mb_convert_encoding($produto['OUTRO_COD'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Data de Recebimento
            echo "<div class='form-group'>";
            echo "<label for='data_recad'>Data de Recebimento:</label>";
            echo "<input type='date' class='form-control' id='data_recad' name='data_recad' value='" . $produto['DATA_RECAD'] . "'>";
            echo "</div>";

            // Campo: SKU Marketplace
            echo "<div class='form-group'>";
            echo "<label for='sku_mktplace'>SKU Marketplace:</label>";
            echo "<input type='text' class='form-control' id='sku_mktplace' name='sku_mktplace' value='" . htmlspecialchars(mb_convert_encoding($produto['SKU_MKTPLACE'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Descrição Reduzida
            echo "<div class='form-group'>";
            echo "<label for='descr_reduz'>Descrição Reduzida:</label>";
            echo "<input type='text' class='form-control' id='descr_reduz' name='descr_reduz' value='" . htmlspecialchars(mb_convert_encoding($produto['DESCR_REDUZ'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Descrição Curta
            echo "<div class='form-group'>";
            echo "<label for='descr_curta'>Descrição Curta:</label>";
            echo "<input type='text' class='form-control' id='descr_curta' name='descr_curta' value='" . htmlspecialchars(mb_convert_encoding($produto['DESCR_CURTA'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Descrição Longa
            echo "<div class='form-group'>";
            echo "<label for='descr_longa'>Descrição Longa:</label>";
            echo "<textarea class='form-control' id='descr_longa' name='descr_longa'>" . htmlspecialchars(mb_convert_encoding($produto['DESCR_LONGA'], 'UTF-8', 'ISO-8859-1')) . "</textarea>";
            echo "</div>";

            // Campo: Aplicação
            echo "<div class='form-group'>";
            echo "<label for='aplicacao'>Aplicação:</label>";
            echo "<textarea class='form-control' id='aplicacao' name='aplicacao'>" . htmlspecialchars(mb_convert_encoding($produto['APLICACAO'], 'UTF-8', 'ISO-8859-1')) . "</textarea>";
            echo "</div>";

            // Campo: Garantia
            echo "<div class='form-group'>";
            echo "<label for='garantia'>Garantia:</label>";
            echo "<input type='text' class='form-control' id='garantia' name='garantia' value='" . htmlspecialchars(mb_convert_encoding($produto['GARANTIA'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Comprimento
            echo "<div class='form-group'>";
            echo "<label for='comprimento'>Comprimento:</label>";
            echo "<input type='text' class='form-control' id='comprimento' name='comprimento' value='" . htmlspecialchars(mb_convert_encoding($produto['COMPRIMENTO'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Largura
            echo "<div class='form-group'>";
            echo "<label for='largura'>Largura:</label>";
            echo "<input type='text' class='form-control' id='largura' name='largura' value='" . htmlspecialchars(mb_convert_encoding($produto['LARGURA'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Altura
            echo "<div class='form-group'>";
            echo "<label for='altura'>Altura:</label>";
            echo "<input type='text' class='form-control' id='altura' name='altura' value='" . htmlspecialchars(mb_convert_encoding($produto['ALTURA'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Peso
            echo "<div class='form-group'>";
            echo "<label for='peso'>Peso:</label>";
            echo "<input type='text' class='form-control' id='peso' name='peso' value='" . htmlspecialchars(mb_convert_encoding($produto['PESO'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Origem
            echo "<div class='form-group'>";
            echo "<label for='origem'>Origem:</label>";
            echo "<input type='text' class='form-control' id='origem' name='origem' value='" . htmlspecialchars(mb_convert_encoding($produto['ORIGEM'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Categoria Final Marketplace
            echo "<div class='form-group'>";
            echo "<label for='finalcategoria_mktplace'>Categoria Final Marketplace:</label>";
            echo "<input type='text' class='form-control' id='finalcategoria_mktplace' name='finalcategoria_mktplace' value='" . htmlspecialchars(mb_convert_encoding($produto['FINALCATEGORIA_MKTPLACE'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Modelo Marketplace
            echo "<div class='form-group'>";
            echo "<label for='modelo_mktplace'>Modelo Marketplace:</label>";
            echo "<input type='text' class='form-control' id='modelo_mktplace' name='modelo_mktplace' value='" . htmlspecialchars(mb_convert_encoding($produto['MODELO_MKTPLACE'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Número do Fabricante
            echo "<div class='form-group'>";
            echo "<label for='num_fabricante'>Número do Fabricante:</label>";
            echo "<input type='text' class='form-control' id='num_fabricante' name='num_fabricante' value='" . htmlspecialchars(mb_convert_encoding($produto['NUM_FABRICANTE'], 'UTF-8', 'ISO-8859-1')) . "'>";
            echo "</div>";

            // Campo: Preço
            echo "<div class='form-group'>";
            echo "<label for='preco'>Preço:</label>";
            echo "<input type='text' class='form-control' id='preco' name='preco' value='" . $produto['PRECO'] . "'>";
            echo "</div>";

            echo "<div class='form-group'>";
            echo "<label for='estoque'>Estoque:</label>";
            echo "<input type='text' class='form-control' id='estoque' name='estoque' value='" . $estoqueprod  . "'>";
            echo "</div>";

            // Botão de Salvar
            echo "<button type='submit' class='btn btn-primary'>Salvar</button>";
            echo "</form>";
        } else {
            echo "<p>Produto não encontrado.</p>";
        }

        $publico->Desconecta();
        $vendas->Desconecta();
        ?>
    </div>
</body>
</html>