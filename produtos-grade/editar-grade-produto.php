<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <title>Editar Grade Produto</title>
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
            min-width: 150px;
        }

        .form-group-inline .form-group:last-child {
            margin-right: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        body {
            font-family: 'Raleway', sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #343a40;
            padding-top: 60px;
            color: white;
            z-index: 1000;
        }

        .sidebar a,
        .sidebar button {
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
            cursor: pointer;
        }

        .sidebar a:hover,
        .sidebar button:hover {
            background-color: #b3b3b3ff;
        }

        /* Ajuste para o conteúdo não ficar escondido atrás da sidebar */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>

<body>

    <!-- Sidebar fora do form container para evitar problemas de layout, mas o botão submit precisa estar dentro ou vinculado -->
    <!-- Neste caso, mantivemos a estrutura lógica, mas ajustamos o CSS do container -->

    <div class="sidebar">
        <!-- O botão de submit precisa estar dentro do form. 
             Como o form engloba a página, o botão funcionará se o form começar antes.
             Faremos o form englobar tudo abaixo. -->
        <button type="submit" form="formProduto" name="acao" value="enviar" class="btn-sidebar-submit"
            <?php
            $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);
            if (isset($ini['config']['envio_produtos'])) {
                $enviar_habilitado = filter_var($ini['config']['envio_produtos'], FILTER_VALIDATE_BOOLEAN);
                echo $enviar_habilitado ? '' : 'disabled';
            }
            ?>>
            Enviar Grade Produto <i class="fa-solid fa-arrow-up-from-bracket"></i>
        </button>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top" style="margin-left: 250px;">
                <a class="navbar-brand" href="index.php">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span style="margin: 10px;">INTERSIG</span>
                </a>
            </nav>

            <h1 style="margin-top: 60px;">Editar Grade Produto</h1>

            <form id="formProduto" method='post' action='controller.php' enctype='multipart/form-data'>
                <?php
                include_once(__DIR__ . '/../database/conexao_publico.php');
                include_once(__DIR__ . '/../database/conexao_vendas.php');
                include_once(__DIR__ . '/../database/conexao_estoque.php');

                $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);
                $setor = ($ini['conexao']['setor'] && !empty($ini['conexao']['setor'])) ? $ini['conexao']['setor'] : 1;
                $tabelaPreco = ($ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco'])) ? $ini['conexao']['tabelaPreco'] : 1;

                $publico = new CONEXAOPUBLICO();
                $vendas = new CONEXAOVENDAS();
                $estoque = new CONEXAOESTOQUE();

                $databaseEstoque = $estoque->getBase();
                $databasePublico = $publico->getBase();
                $databaseVendas =  $vendas->getBase();

                $codigo_grade = $_GET['codigo'];
                $tabelaDePreco = 1;

                // --- 1. BUSCAR VARIANTES E CALCULAR ESTOQUE TOTAL ---
                $estoqueTotal = 0;
                $listaVariantes = []; // Array para armazenar as variantes e não consultar o banco 2 vezes

                $sqlProdutosgrade = "
                SELECT  
                    p.CODIGO,
                    p.NUM_FABRICANTE,
                    p.ALTURA,
                    p.COMPRIMENTO,
                    p.DESCRICAO,
                    p.LARGURA,
                    p.PESO,
                    ig.CARAC as CODIGO_CARACTERISTICA,
                    ig.VALOR as VALOR_CARACTERISTICA, 
                    crg.DESCRICAO as DESCRICAO_CARACTERISTICA
                FROM  grades g
                JOIN   cad_prod p on p.GRADE = g.CODIGO
                JOIN   itens_grade ig on  p.CODIGO = ig.PRODUTO AND ig.GRADE = p.GRADE AND ig.VALOR <> '' AND ig.VALOR is NOT NULL
                JOIN   carac_grade crg on crg.CODIGO = ig.CARAC
                WHERE (p.NO_MKTP='S' AND p.ATIVO='S') AND g.CODIGO  = '$codigo_grade'
                GROUP BY p.CODIGO
            ";

                $resultProdutosGrade = $publico->Consulta($sqlProdutosgrade);

                while ($row = mysqli_fetch_array($resultProdutosGrade, MYSQLI_ASSOC)) {
                    $codigo_produto = $row['CODIGO'];
                    $estoqueVariante = 0;

                    // Query complexa de estoque
                    $sqlEstoque = "SELECT
                                    est.CODIGO, 
                                    IF(est.estoque < 0, 0, est.estoque) AS ESTOQUE
                                FROM
                                    (SELECT
                                    P.CODIGO,
                                    (SUM(PS.ESTOQUE) -
                                        (SELECT COALESCE(SUM((IF(PO.QTDE_SEPARADA > (PO.QUANTIDADE - PO.QTDE_MOV), PO.QTDE_SEPARADA, (PO.QUANTIDADE - PO.QTDE_MOV)) * PO.FATOR_QTDE) * IF(CO.TIPO = '5', -1, 1)), 0)
                                        FROM " . $databaseVendas . ".cad_orca AS CO
                                        LEFT OUTER JOIN " . $databaseVendas . ".pro_orca AS PO ON PO.ORCAMENTO = CO.CODIGO
                                        WHERE CO.SITUACAO IN ('AI', 'AP', 'FP')
                                        AND PO.PRODUTO = P.CODIGO)) AS estoque
                                    FROM " . $databaseEstoque . ".prod_setor AS PS
                                    LEFT JOIN " . $databasePublico . ".cad_prod AS P ON P.CODIGO = PS.PRODUTO
                                    WHERE P.CODIGO = '$codigo_produto'
                                    AND PS.SETOR = '$setor'
                                    GROUP BY P.CODIGO) AS est";

                    $buscaEstoque = $estoque->Consulta($sqlEstoque);

                    if (mysqli_num_rows($buscaEstoque) > 0) {
                        while ($row_estoque = mysqli_fetch_array($buscaEstoque, MYSQLI_ASSOC)) {
                            $estoqueVariante += $row_estoque['ESTOQUE'];
                        }
                    }

                    // Soma ao total geral
                    $estoqueTotal += $estoqueVariante;

                    // Armazena no array para usar na tabela do fim da página
                    $row['ESTOQUE_REAL'] = $estoqueVariante;
                    $listaVariantes[] = $row;
                }
                // --- FIM DO CÁLCULO DE ESTOQUE ---


                // --- 2. BUSCAR DADOS DO PRODUTO PAI/GRADE ---
                $result = $publico->Consulta("
                SELECT
                  p.GRADE ,
                  p.CODIGO,
                  p.OUTRO_COD,
                  p.DATA_RECAD, 
                  p.SKU_MKTPLACE,
                  p.DESCR_CURTA_MKTPLACE,
                  p.DESCR_LONGA_MKTPLACE,
                  p.DESCR_CURTA_SITE,
                  g.DESCRICAO,
                  g.APLICACAO,
                  P.GARANTIA,
                  p.COMPRIMENTO,
                  p.LARGURA,
                  p.ALTURA,
                  p.PESO,
                  p.ORIGEM,
                  p.CATEGORIA_MKTPLACE,
                  p.INTERM_CATEGORIA_MKTPLACE,
                  p.FINALCATEGORIA_MKTPLACE,
                  p.MODELO_MKTPLACE, 
                  p.NUM_FABRICANTE,
                  tp.PRECO,
                  tp.PROMOCAO,
                  m.descricao AS MARCA,
                  cf.NCM,
                  g.OUTRO_COD AS OUTRO_COD_GRADE
                FROM grades as g 
                LEFT JOIN cad_prod p on p.GRADE = g.codigo
                INNER JOIN prod_tabprecos tp ON p.CODIGO = tp.PRODUTO
                LEFT JOIN cad_pmar m ON m.codigo = p.marca
                LEFT JOIN class_fiscal cf ON cf.CODIGO = p.CLASS_FISCAL
                LEFT JOIN cad_pgru cg ON cg.CODIGO = p.GRUPO
                LEFT join subgrupos sg ON sg.CODIGO = p.SUBGRUPO
                WHERE (p.NO_MKTP='S' AND p.ATIVO='S')  
                AND tp.tabela = $tabelaDePreco AND g.CODIGO = '$codigo_grade'");

                $produto = mysqli_fetch_array($result, MYSQLI_ASSOC);

                if ($produto) {
                    $origemFormatada = 'Desconhecida';
                    $origemMap = [
                        '0' => 'Nacional',
                        '3' => 'Nacional',
                        '4' => 'Nacional',
                        '5' => 'Nacional',
                        '8' => 'Nacional',
                        '1' => 'Importado',
                        '2' => 'Importado',
                        '6' => 'Importado',
                        '7' => 'Importado'
                    ];
                    if (array_key_exists($produto['ORIGEM'], $origemMap)) {
                        $origemFormatada = $origemMap[$produto['ORIGEM']];
                    }


                    // --- CAMPOS DO FORMULÁRIO ---
                    echo "<div class='form-group'>";
                    echo "<label>GRADE: ". htmlspecialchars(mb_convert_encoding($produto['GRADE'], 'UTF-8', 'ISO-8859-1')) ."</label>";
                    echo "<input type='hidden' class='form-control' name='codigo'   value= '" . htmlspecialchars(mb_convert_encoding($produto['GRADE'], 'UTF-8', 'ISO-8859-1')) . "' >  ";
                    echo "</div>";

                    echo "<div class='form-group'>";
                    echo "<label>Descrição/titulo:</label>";
                    echo "<textarea class='form-control' name='descricao'>" . htmlspecialchars(mb_convert_encoding($produto['DESCRICAO'], 'UTF-8', 'ISO-8859-1')) .  "</textarea>";
                    echo "</div>";

                    echo "<div class='form-group'>";
                    echo "<label>Descrição curta/titulo curto:</label>";
                    echo "<textarea class='form-control' name='descricaocurta'>" . htmlspecialchars(mb_convert_encoding($produto['DESCRICAO'], 'UTF-8', 'ISO-8859-1')) .  "</textarea>";
                    echo "</div>";

                    echo "<div class='form-group'>";
                    echo "<label>Aplicação:</label>";
                    echo "<textarea class='form-control' name='aplicacao'>" . htmlspecialchars(mb_convert_encoding($produto['APLICACAO'], 'UTF-8', 'ISO-8859-1')) . "</textarea>";
                    echo "</div>";

                    echo "<div class='form-group'>";
                    echo "<label>Descrição Google:</label>";
                    echo "<textarea class='form-control' name='descricaogoogle'>" . htmlspecialchars(mb_convert_encoding($produto['DESCR_CURTA_MKTPLACE'], 'UTF-8', 'ISO-8859-1'))  . "</textarea>";
                    echo "</div>";

                    echo "<div class='form-group'>";
                    echo "<label>Palavras chave:</label>";
                    echo "<textarea class='form-control' name='palavraschave'>" . htmlspecialchars(mb_convert_encoding($produto['DESCR_LONGA_MKTPLACE'], 'UTF-8', 'ISO-8859-1'))  . "</textarea>";
                    echo "</div>";

                      echo "<div class='form-group-inline'>";
                    echo "<div class='form-group'><label>Preço:</label><input type='text' class='form-control' name='preco' value='" . $produto['PRECO'] . "'></div>";
                    echo "<div class='form-group'><label>Promoção:</label><input type='text' class='form-control' name='promocao' value='" . $produto['PRECO'] . "'></div>";
                    // AQUI ESTÁ A MUDANÇA: Exibindo a soma das variantes
                    echo "<div class='form-group'><label>Estoque Total (Soma Variantes):</label><input type='text' class='form-control' name='estoque' value='" . $estoqueTotal . "' readonly></div>";
                    echo "</div>";

                    echo "<div class='form-group-inline'>";
                    echo "<div class='form-group'><label>Garantia:</label><input type='text' class='form-control' name='garantia' value='" . htmlspecialchars(mb_convert_encoding($produto['GARANTIA'], 'UTF-8', 'ISO-8859-1')) . "'></div>";

                    echo "<div class='form-group'><label>Origem:</label><input type='text' class='form-control' name='origem' value='" . htmlspecialchars(mb_convert_encoding($origemFormatada, 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>NCM:</label><input type='text' class='form-control' name='ncm' value='" . htmlspecialchars(mb_convert_encoding($produto['NCM'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "</div>";


                    echo '<hr>';
                    
                    echo "<h4 class='mb-3'>Dados Variantes do Produto (Grade)</h4>";

                    echo "<div class='form-group-inline'>";
                    echo "<div class='form-group'><label>Comprimento:</label><input type='text' class='form-control' name='comprimento' value='" . htmlspecialchars(mb_convert_encoding($produto['COMPRIMENTO'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Largura:</label><input type='text' class='form-control' name='largura' value='" . htmlspecialchars(mb_convert_encoding($produto['LARGURA'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Altura:</label><input type='text' class='form-control' name='altura' value='" . htmlspecialchars(mb_convert_encoding($produto['ALTURA'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Peso:</label><input type='text' class='form-control' name='peso' value='" . htmlspecialchars(mb_convert_encoding($produto['PESO'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "</div>";

                  

                    echo "<div class='form-group-inline'>";
                    echo "<div class='form-group'><label>Categoria:</label><input type='text' class='form-control' name='categoria' value='" . htmlspecialchars(mb_convert_encoding($produto['CATEGORIA_MKTPLACE'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Cat. Interm:</label><input type='text' class='form-control' name='categoriainterm' value='" . htmlspecialchars(mb_convert_encoding($produto['INTERM_CATEGORIA_MKTPLACE'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Cat. Final:</label><input type='text' class='form-control' name='categoriafinal' value='" . htmlspecialchars(mb_convert_encoding($produto['FINALCATEGORIA_MKTPLACE'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "</div>";

                    echo "<div class='form-group-inline'>";
                    echo "<div class='form-group'><label>Outro còd/Modelo Marketplace:</label><input type='text' class='form-control' name='modelo' value='" . htmlspecialchars(mb_convert_encoding($produto['OUTRO_COD'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Marca:</label><input type='text' class='form-control' name='marca' value='" . htmlspecialchars(mb_convert_encoding($produto['MARCA'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "</div>";

                    echo "<hr>";

                    // --- 3. EXIBIR TABELA DE VARIANTES (USANDO O ARRAY POPULADO NO INÍCIO) ---

                    if (count($listaVariantes) > 0) {
                        echo "<div class='table-responsive'>";
                        echo "<table class='table table-bordered table-striped table-hover table-sm bg-white'>";
                        echo "<thead class='thead-dark'>";
                        echo "<tr>";
                        echo "<th>Cód. Variante</th>";
                        echo "<th>Descrição</th>";
                        echo "<th>Característica</th>";
                        echo "<th>Núm. Fabricante</th>";
                        echo "<th>Estoque</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";

                        $contador = 0; // Inicializa contador para organizar o array no POST

                        foreach ($listaVariantes as $var) {
                            $codVar = htmlspecialchars(mb_convert_encoding($var['CODIGO'], 'UTF-8', 'ISO-8859-1'));
                            $descVar = htmlspecialchars(mb_convert_encoding($var['DESCRICAO'], 'UTF-8', 'ISO-8859-1'));
                            $tipoCarac = htmlspecialchars(mb_convert_encoding($var['DESCRICAO_CARACTERISTICA'], 'UTF-8', 'ISO-8859-1'));
                            $valorCarac = htmlspecialchars(mb_convert_encoding($var['VALOR_CARACTERISTICA'], 'UTF-8', 'ISO-8859-1'));
                            $numFab = htmlspecialchars(mb_convert_encoding($var['NUM_FABRICANTE'], 'UTF-8', 'ISO-8859-1'));
                            $estqVar = $var['ESTOQUE_REAL'];

                            // PARTE VISUAL (Tabela)
                            echo "<tr>";
                            echo "<td><strong>{$codVar}</strong></td>";
                            echo "<td>{$descVar}</td>";
                            echo "<td><span class='badge badge-info'>{$tipoCarac}</span> {$valorCarac}</td>";
                            echo "<td>{$numFab}</td>";
                            echo "<td><strong>{$estqVar}</strong></td>";
                            echo "</tr>";

                            // PARTE FUNCIONAL (Inputs Ocultos para enviar ao controller.php)
                            // O usuário não vê e não edita, mas o PHP recebe
                            echo "<input type='hidden' name='grade[$contador][codigo]' value='{$var['CODIGO']}'>";
                            echo "<input type='hidden' name='grade[$contador][descricao]' value='{$var['DESCRICAO']}'>"; // Envia sem encoding HTML para salvar correto no banco
                            echo "<input type='hidden' name='grade[$contador][valor_caracteristica]' value='{$var['VALOR_CARACTERISTICA']}'>";
                            echo "<input type='hidden' name='grade[$contador][descricao_caracteristica]' value='{$var['DESCRICAO_CARACTERISTICA']}'>";
                            echo "<input type='hidden' name='grade[$contador][num_fabricante]' value='{$var['NUM_FABRICANTE']}'>";
                            echo "<input type='hidden' name='grade[$contador][estoque]' value='{$estqVar}'>";

                            $contador++;
                        }

                        echo "</tbody>";
                        echo "</table>";
                        echo "</div>";
                    } else {
                        echo "<div class='alert alert-warning'>Nenhuma variante encontrada.</div>";
                    }
                } else {
                    echo "<p class='alert alert-danger'>Produto não encontrado.</p>";
                }

                $publico->Desconecta();
                $vendas->Desconecta();
                ?>
            </form>
        </div>
    </div>

</body>

</html>