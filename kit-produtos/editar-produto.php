<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <title>Editar Produto Padronizado</title>
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

    <!-- Sidebar fora do form container para evitar problemas de layout -->
    <div class="sidebar">
        <button type="submit" form="formProduto" name="acao" value="enviar" class="btn-sidebar-submit"
            <?php
            $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);
            if (isset($ini['config']['envio_produtos'])) {
                $enviar_habilitado = filter_var($ini['config']['envio_produtos'], FILTER_VALIDATE_BOOLEAN);
                echo $enviar_habilitado ? '' : 'disabled';
            }
            ?>>
            Enviar Kit <i class="fa-solid fa-arrow-up-from-bracket"></i>
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

            <h1 style="margin-top: 60px;">Editar Produto Padronizado</h1>

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
                $listaVariantes = []; 

                $sqlProdutosgrade = "
                SELECT  
                    p.CODIGO,
                    p.NUM_FABRICANTE,
                    p.ALTURA,
                    p.COMPRIMENTO,
                    pdr.DESCRICAO,
                    p.LARGURA,
                    p.PESO,
                    pp.PRECO,
                    ipd.QUANTIDADE
                FROM cad_prod p 
                join ite_padr ipd on ipd.PROD_SERV = p.CODIGO
                join cad_padr pdr on pdr.CODIGO = ipd.PADRONIZADO
                join prod_tabprecos pp on pp.PRODUTO = p.CODIGO
                WHERE pdr.CODIGO = '$codigo_grade'
                group by p.codigo
                ;
                ";

                $resultProdutos = $publico->Consulta($sqlProdutosgrade);
                $estoqueFinalKit = null; // null indica que ainda não calculamos
                $price = 0;
                
                while ($row = mysqli_fetch_array($resultProdutos, MYSQLI_ASSOC)) {
                    $codigo_produto = $row['CODIGO'];
                    $estoqueVariante = 0;
                    $qtdNecessaria = floatval($row['QUANTIDADE']);

                    $price += $row['PRECO'];

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
                    $saldoFilho = 0;

                    if (mysqli_num_rows($buscaEstoque) > 0) {
                        $rowEst = mysqli_fetch_assoc($buscaEstoque);
                        $saldoFilho = floatval($rowEst['ESTOQUE']);
                    }
                    if($saldoFilho < 0) $saldoFilho = 0;

                    $estoqueTotal += $estoqueVariante;
                    $capacidadeItem = floor($saldoFilho / $qtdNecessaria);

                    // Lógica do menor valor (Gargalo)
                    if($estoqueFinalKit === null || $capacidadeItem < $estoqueFinalKit){
                        $estoqueFinalKit = $capacidadeItem;
                    }
                    $listaVariantes[] = $row;
                }
                // --- FIM DO CÁLCULO DE ESTOQUE ---


                // --- 2. BUSCAR DADOS DO PRODUTO PAI/GRADE ---
                $result = $publico->Consulta( 
                 "SELECT
                        pdr.CODIGO AS PADRONIZADO,
                        p.CODIGO, 
                        p.OUTRO_COD,
                        p.DATA_RECAD, 
                        p.SKU_MKTPLACE,
                        pdr.DESCRICAO AS DESCR_CURTA_MKTPLACE,
                        pdr.DESCRICAO AS DESCR_LONGA_MKTPLACE,
                        pdr.DESCRICAO,
                        p.APLICACAO, 
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
                        pp.PRECO,
                        pp.PROMOCAO,
                        m.descricao AS MARCA,
                        cf.NCM
                   FROM cad_prod p 
                join ite_padr ipd on ipd.PROD_SERV = p.CODIGO
                join cad_padr pdr on pdr.CODIGO = ipd.PADRONIZADO
                join prod_tabprecos pp on pp.PRODUTO = p.CODIGO
                left join cad_pmar m on m.CODIGO = p.MARCA 
                LEFT JOIN class_fiscal cf ON cf.CODIGO = p.CLASS_FISCAL
                WHERE pdr.CODIGO = '$codigo_grade'
                    ORDER BY LENGTH(p.APLICACAO) DESC
                    LIMIT 1;
                 ");

                $produto = mysqli_fetch_array($result, MYSQLI_ASSOC);

                if ($produto) {
                    $origemFormatada = 'Desconhecida';
                    $origemMap = [
                        '0' => 'Nacional', '3' => 'Nacional', '4' => 'Nacional',
                        '5' => 'Nacional', '8' => 'Nacional',
                        '1' => 'Importado', '2' => 'Importado',
                        '6' => 'Importado', '7' => 'Importado'
                    ];
                    if (array_key_exists($produto['ORIGEM'], $origemMap)) {
                        $origemFormatada = $origemMap[$produto['ORIGEM']];
                    }

                    $categoria=  $produto['CATEGORIA_MKTPLACE'] ;
                    $categoria_interm= $produto['INTERM_CATEGORIA_MKTPLACE'];
                    $categoria_final= $produto['FINALCATEGORIA_MKTPLACE'] ;

                    if(!$produto['CATEGORIA_MKTPLACE'] || $produto['CATEGORIA_MKTPLACE'] === ''){
                        $categoria= 'Acessórios para Veículos ';
                    }
                    if(!$produto['INTERM_CATEGORIA_MKTPLACE'] || $produto['INTERM_CATEGORIA_MKTPLACE'] === ''){
                        $categoria_interm= 'Peças de Carros e Caminhonetes';
                    }

                    // --- MUDANÇA NA VALIDAÇÃO DA CATEGORIA FINAL ---
                    if(!$produto['FINALCATEGORIA_MKTPLACE'] || $produto['FINALCATEGORIA_MKTPLACE'] === ''){
                        
                        // Converte a descrição para maiúsculo para facilitar a comparação
                        $descricaoUpper = mb_strtoupper($produto['DESCRICAO'], 'UTF-8');

                        // Verifica Anéis (abrange ANEL, ANEIS, ANÉIS)
                        if( str_contains($descricaoUpper, 'ANEL') || str_contains($descricaoUpper, 'ANEIS') || str_contains($descricaoUpper, 'ANÉIS') ){
                            $categoria_final = 'Anéis Segmento';
                        }
                        // Verifica Bronzina
                        elseif( str_contains($descricaoUpper, 'BRONZINA') ){
                            $categoria_final = 'Bronzina de Mancal';
                        }
                    }
                    // --- FIM DA MUDANÇA ---

                    $codigo_kit = "KIT-".$produto['PADRONIZADO'] ;

                    // --- CAMPOS DO FORMULÁRIO ---
                    echo "<div class='form-group'>";
                    echo "<label>Padronizado: ". htmlspecialchars(mb_convert_encoding( $codigo_kit , 'UTF-8', 'ISO-8859-1')) ."</label>";
                    echo "<input type='hidden' class='form-control' name='codigo_kit'   value= '" . htmlspecialchars(mb_convert_encoding($codigo_kit, 'UTF-8', 'ISO-8859-1')) . "' >  ";
                    echo "</div>";
                    echo "<div class='form-group'>";
                    echo "<label>Padronizado sistema: ". htmlspecialchars(mb_convert_encoding( $produto['PADRONIZADO'] , 'UTF-8', 'ISO-8859-1')) ."</label>";
                    echo "<input type='hidden' class='form-control' name='codigo_padr'   value= '" . htmlspecialchars(mb_convert_encoding($produto['PADRONIZADO'], 'UTF-8', 'ISO-8859-1')) . "' >  ";
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
                    echo "<div class='form-group'><label>Preço:</label><input type='text' class='form-control' name='preco' value='" . $price . "'></div>";
                    echo "<div class='form-group'><label>Promoção:</label><input type='text' class='form-control' name='promocao' value='" . $price . "'></div>";
                    echo "<div class='form-group'><label>Estoque disponivel Kit:</label><input type='text' class='form-control' name='estoque' value='" . $estoqueFinalKit . "' readonly></div>";
                    echo "</div>";

                    echo "<div class='form-group-inline'>";
                    echo "<div class='form-group'><label>Garantia:</label><input type='text' class='form-control' name='garantia' value='" . htmlspecialchars(mb_convert_encoding($produto['GARANTIA'], 'UTF-8', 'ISO-8859-1')) . "'></div>";

                    echo "<div class='form-group'><label>Origem:</label><input type='text' class='form-control' name='origem' value='" . htmlspecialchars(mb_convert_encoding($origemFormatada, 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>NCM:</label><input type='text' class='form-control' name='ncm' value='" . htmlspecialchars(mb_convert_encoding($produto['NCM'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "</div>";


                    echo '<hr>';
                    
                    echo "<div class='form-group-inline'>";
                    echo "<div class='form-group'><label>Comprimento:</label><input type='text' class='form-control' name='comprimento' value='" . htmlspecialchars(mb_convert_encoding($produto['COMPRIMENTO'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Largura:</label><input type='text' class='form-control' name='largura' value='" . htmlspecialchars(mb_convert_encoding($produto['LARGURA'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Altura:</label><input type='text' class='form-control' name='altura' value='" . htmlspecialchars(mb_convert_encoding($produto['ALTURA'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Peso:</label><input type='text' class='form-control' name='peso' value='" . htmlspecialchars(mb_convert_encoding($produto['PESO'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "</div>";

                    echo "<div class='form-group-inline'>";
                    echo "<div class='form-group'><label>Categoria:</label><input type='text' class='form-control' name='categoria' value='" . htmlspecialchars( $categoria) . "'></div>";
                    echo "<div class='form-group'><label>Cat. Interm:</label><input type='text' class='form-control' name='categoriainterm' value='" . htmlspecialchars(  $categoria_interm ) . "'></div>";
                    echo "<div class='form-group'><label>Cat. Final:</label><input type='text' class='form-control' name='categoriafinal' value='" . htmlspecialchars( $categoria_final  ) . "'></div>";
                    echo "</div>";

                    echo "<div class='form-group-inline'>";
                    echo "<div class='form-group'><label>Outro còd/Modelo Marketplace:</label><input type='text' class='form-control' name='modelo' value='" . htmlspecialchars(mb_convert_encoding($produto['OUTRO_COD'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "<div class='form-group'><label>Marca:</label><input type='text' class='form-control' name='marca' value='" . htmlspecialchars(mb_convert_encoding($produto['MARCA'], 'UTF-8', 'ISO-8859-1')) . "'></div>";
                    echo "</div>";

                    echo "<hr>";

                    // --- 3. EXIBIR TABELA DE VARIANTES ---

                    if (count($listaVariantes) > 0) {
                        echo "<div class='table-responsive'>";
                        echo "<h4 class='mb-3'>Produtos do Kit</h4>";

                        echo "<table class='table table-bordered table-striped table-hover table-sm bg-white'>";
                        echo "<thead class='thead-dark'>";
                        echo "<tr>";
                        echo "<th>Cód. Variante</th>";
                        echo "<th>Descrição</th>";
                        echo "<th>Núm. Fabricante</th>";
                        echo "<th>Quantidade</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";

                        $contador = 0; 

                        foreach ($listaVariantes as $var) {
                            $codVar = htmlspecialchars(mb_convert_encoding($var['CODIGO'], 'UTF-8', 'ISO-8859-1'));
                            $descVar = htmlspecialchars(mb_convert_encoding($var['DESCRICAO'], 'UTF-8', 'ISO-8859-1'));
                            $numFab = htmlspecialchars(mb_convert_encoding($var['NUM_FABRICANTE'], 'UTF-8', 'ISO-8859-1'));
                            $estqVar = $var['QUANTIDADE'];

                            echo "<tr>";
                            echo "<td><strong>{$codVar}</strong></td>";
                            echo "<td>{$descVar}</td>";
                            echo "<td>{$numFab}</td>";
                            echo "<td><strong>{$estqVar}</strong></td>";
                            echo "</tr>";

                            echo "<input type='hidden' name='grade[$contador][codigo]' value='{$var['CODIGO']}'>";
                            echo "<input type='hidden' name='grade[$contador][descricao]' value='{$var['DESCRICAO']}'>"; 
                            echo "<input type='hidden' name='grade[$contador][num_fabricante]' value='{$var['NUM_FABRICANTE']}'>";
                            echo "<input type='hidden' name='grade[$contador][QUANTIDADE]' value='{$estqVar}'>";

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