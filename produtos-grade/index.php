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
            Enviar Preços de todos produtos
            <i class="fa-solid fa-arrow-up-from-bracket"></i>
        </a>
        <a href="../jobs/atualizar-estoque.php">
            Enviar Estoque de todos produtos
            <i class="fa-solid fa-arrow-up-from-bracket"></i>
        </a>
        <hr>

     <!-- <button type="submit" name="acao" value="enviar">
        Enviar Grade selecionada
            <i class="fa-solid fa-arrow-up-from-bracket"></i>
     </button>
     --->
   
    <hr>
    <?php
        $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);
        $enviar = false;
        
       
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
                    Grade Produtos
                </h2>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <input
                 type="text" class="form-control" id="searchInput" placeholder="Pesquisar produto por nome ou código..."
                                        
                >
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Código ERP</th>
                            <th>Descrição</th>
                            <th>Referência/Outro Código</th>
                            <th>Código Agrupador Precode</th>
                            <th>SKU Loja Precode</th>
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
                            $result = $publico->Consulta(" SELECT g.CODIGO, g.OUTRO_COD , g.DESCRICAO,
                                                                COALESCE( gp.CODIGO_SITE,0 ) AS CODIGO_SITE 
                                                            FROM grades as g
                                                            LEFT JOIN grade_precode gp ON gp.codigo_bd = g.CODIGO
                                                             JOIN ".$database_vendas.".parametros pr on pr.id = 1
                                                            WHERE g.ATIVO='S'  
                                                        GROUP BY g.CODIGO
                                                            ORDER BY g.CODIGO  
                                                            ");
                            $numRows = mysqli_num_rows($result);
                            if ($numRows > 0) {
                                while ($list = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                    $codigo = $list['CODIGO'];
                                    $descricao = $list['DESCRICAO'];
                                    $codigo_site = $list['CODIGO_SITE'];
                                    $outro_cod= $list['OUTRO_COD'];

                             //   $dataEstoque = new DateTime($list['DATA_RECAD_ESTOQUE']);
                           // $dataEstoque = $dataEstoque->format('d/m/Y H:i'); 
                                    // Verifica se o produto foi enviado
                                    $classe_enviado = ($codigo_site != null && $codigo_site != '' && $codigo_site !=  0) ? 'enviado' : '';

                                    echo "<tr class='$classe_enviado'>";
                                        echo "<td><input type='checkbox' name='codprod[]' value='$codigo'></td>";
                                        echo "<td>$codigo</td>";
                                           echo "<td> ". htmlspecialchars(mb_convert_encoding($descricao, 'UTF-8', 'ISO-8859-1'))." </td>";
                                        echo "<td>$outro_cod</td>";
                                        echo "<td>$codigo_site</td>";
                                        echo "<td>  0  </td>";
                                        echo "<td>";
                                        if( $codigo_site == null ||  $codigo_site == '' ||  $codigo_site == 0){ 
                                        echo "<a href='editar-grade-produto.php?codigo=$codigo' class='btn btn-primary btn-sm'>Editar e enviar</a>";
                                        } 
                                        echo "<a></a>" ; 
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
            const productOtherCode = row.querySelector('td:nth-child(3)').textContent.toLowerCase(); // Código ERP
            const productDescription = row.querySelector('td:nth-child(6)').textContent.toLowerCase(); // Descrição

            if (productCode.includes(searchTerm) || productDescription.includes(searchTerm) || productOtherCode.includes(searchTerm))  {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>