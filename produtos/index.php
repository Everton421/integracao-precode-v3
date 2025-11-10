<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">
    <link rel="icon" href="Favicon.png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <title>Requisição</title>
    <style>
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

        .sidebar hr {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
       
        /* Estilos para o conteúdo principal */
        .content {
            margin-left: 250px; /* Largura da sidebar */
            padding: 20px;
        }

        /* Estilos para a navbar */
        .navbar {
            background-color: #343a40 !important; /* Cor de fundo escura */
        }

        .navbar-brand {
            color: white !important; /* Cor do texto */
        }

        /* Estilos para a tabela */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }

        .table tbody + tbody {
            border-top: 2px solid #dee2e6;
        }

        .table .table-light,
        .table .table-light > th,
        .table .table-light > td {
            background-color: #fdfdfe;
        }

        .table .table-hover tbody tr:hover {
            color: #212529;
            background-color: rgba(0, 0, 0, 0.075);
        }

        /* Estilos para itens enviados */
        .enviado {
            background-color: #d4edda !important; /* Cor de fundo verde claro */
            color: #155724; /* Cor do texto verde escuro */
        }

        .enviado td {
            font-weight: bold; /* Texto em negrito */
        }
    </style>
</head>
<body>
  <form method="post" action="controller.php" id="formEnvia">

    <div class="sidebar">
        
        <a href="../jobs/atualizar-preco.php">
            Enviar Preços
            <i class="fa-solid fa-arrow-up-from-bracket"></i>
        </a>
        <a href="../jobs/atualizar-estoque.php">
            Enviar Estoque
            <i class="fa-solid fa-arrow-up-from-bracket"></i>
        </a>
        <hr>
     
    <button type="submit" name="acao" value="vincular">
        Obter vínculo do produto selecionado
        <i class="fa-solid fa-paperclip"></i>
    </button>

    <button type="submit" name="acao" value="vincularTodos">
        Obter vínculo de todos os produtos possíveis 
        <i class="fa-solid fa-paperclip"></i>
    </button>
    <hr>
    <?php
        $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);
        $enviar = false;
        
        if( isset($ini['config']['envio_produtos'])   ){
            $enviar_habilitado = filter_var($ini['config']['envio_produtos'], FILTER_VALIDATE_BOOLEAN);
        }
        $disabled_attribute = $enviar_habilitado ? '' : 'disabled';
        
        echo '<button type="submit" name="acao" value="enviar" '.$disabled_attribute.'>';
        echo 'Enviar produto selecionado';
        echo '<i class="fa-solid fa-arrow-up-from-bracket"></i>';
        echo '</button>';
    ?>

    </div>

    <div class="content">
        <nav class="navbar navbar-expand-md navbar-dark fixed-top">
            <?php
                echo '<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="../index.php">';
                echo '<i class="fa-solid fa-arrow-left"></i>';
                echo '<span style="margin: 10px;">';
                echo 'INTERSIG';
                echo '<span>';
                echo '</a>';
            ?>
        </nav>

        <div class="container-fluid">
            <div class="form-group" style="margin-top: 60px;">
                <h2 style="font-weight: bold;"> 
                    <i class="fa-solid fa-cube"></i> 
                    Produtos
                </h2>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar produto por nome ou código...">
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Código ERP</th>
                            <th>Referência/Outro Código</th>
                            <th>Código Agrupador Precode</th>
                            <th>SKU Loja Precode</th>
                            <th>Descrição</th>
                            <th>Saldo Enviado</th>
                            <th>Preço Enviado</th>
                            <th>Último Envio de Estoque</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            include_once(__DIR__ . '/../database/conexao_publico.php');
                            include_once(__DIR__ . '/../database/conexao_vendas.php');

                            $publico = new CONEXAOPUBLICO();
                            $vendas = new CONEXAOVENDAS();
                            $database_vendas = $vendas->getBase();
                            $result = $publico->Consulta("SELECT cp.CODIGO, cp.OUTRO_COD ,cp.DESCRICAO,
                                                                fp.FOTO, pr.FOTOS as CAMINHO_FOTOS,
                                                                pp.PRECO_SITE , pp.REF_LOJA ,pp.SKU_LOJA ,pp.CODIGO_SITE, pp.SALDO_ENVIADO, pp.DATA_RECAD_ESTOQUE
                                                            FROM cad_prod cp
                                                            LEFT JOIN produto_precode pp ON pp.codigo_bd = cp.CODIGO
                                                            LEFT JOIN fotos_prod_precode fp ON fp.PRODUTO = cp.CODIGO
                                                            JOIN ".$database_vendas.".parametros pr on pr.id = 1
                                                            WHERE cp.ATIVO='S' AND cp.NO_MKTP='S'
                                                        GROUP BY cp.CODIGO
                                                            ORDER BY cp.CODIGO
                                                            ");
                            $numRows = mysqli_num_rows($result);
                            if ($numRows > 0) {
                                while ($list = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                    $codigo = $list['CODIGO'];
                                    $descricao = $list['DESCRICAO'];
                                    $codigo_site = $list['CODIGO_SITE'];
                                    $outro_cod= $list['OUTRO_COD'];
                                    $foto = $list['FOTO'];
                                    $caminho = $list['CAMINHO_FOTOS'];
                                    $preco = $list['PRECO_SITE'];
                                    $saldo = $list['SALDO_ENVIADO'];
                                    $dataEstoque = $list['DATA_RECAD_ESTOQUE'];
                                    $skuLoja = $list['SKU_LOJA'];
                                    $refLoja = $list['REF_LOJA'];

                                    // Verifica se o produto foi enviado
                                    $classe_enviado = ($codigo_site != null && $codigo_site != '') ? 'enviado' : '';

                                    echo "<tr class='$classe_enviado'>";
                                        echo "<td><input type='checkbox' name='codprod[]' value='$codigo'></td>";
                                        echo "<td>$codigo</td>";
                                        echo "<td>$outro_cod</td>";
                                        echo "<td>$codigo_site</td>";
                                        echo "<td>$skuLoja</td>";
                                        echo "<td>$descricao</td>";
                                        echo "<td>$saldo</td>";
                                        echo "<td>$preco</td>";
                                        echo "<td>$dataEstoque</td>";
                                        echo "<td>";
                                        echo "<a href='editar-produto.php?codigo=$codigo' class='btn btn-primary btn-sm'>Editar</a>";
                                        echo "</td>";
                                    echo "</tr>";
                                }
                                $publico->Desconecta();
                                $vendas->Desconecta();
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
   </form>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const searchTerm = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('.table tbody tr');

        tableRows.forEach(function (row) {
            const productCode = row.querySelector('td:nth-child(2)').textContent.toLowerCase(); // Código ERP
            const productDescription = row.querySelector('td:nth-child(6)').textContent.toLowerCase(); // Descrição

            if (productCode.includes(searchTerm) || productDescription.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>