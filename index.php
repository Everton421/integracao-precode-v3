<!DOCTYPE html>
<html lang="pt-BR">
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
    <title>Requisição</title>
    <style>
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

        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 18px;
            color: #f2f2f2;
            display: block;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #495057; /* Cor de fundo ao passar o mouse */
        }

        /* Estilos para o conteúdo principal */
        .content {
            margin-left: 250px; /* Largura da sidebar */
            padding: 20px;
        }

        /* Estilos para os itens da lista */
        .btn-list {
            margin-right: 10px;
        }

        /* Estilos para itens enviados */
        .enviado {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="src/atualizar-preco.php">Enviar Preços</a>
    <a href="src/atualizar-estoque.php">Enviar Estoque</a>
    <a href="src/receber-pedidos.php">Receber Pedidos</a>
</div>

<div class="content">
    <form method="post" action="src/produtos.php" id="formEnvia">
        <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">INTERSIG</a>
            <ul class="navbar-nav mr-auto">
                <li class="nav-item btn-list">
                    <button type="submit" class="btn btn-primary mt-3">Enviar produto selecionado</button>
                </li>
            </ul>
        </nav>

        <div class="container">
            <div class="form-group  " style="margin-top: 150px; ">
                <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar produto por nome ou código...">
            </div>

            <div class="card-body">
                <?php
                include(__DIR__ . '/src/database/conexao_publico.php');
                $publico = new CONEXAOPUBLICO();
                $result = $publico->Consulta("SELECT cp.CODIGO, cp.DESCRICAO, pp.CODIGO_SITE
                                                FROM cad_prod cp 
                                                LEFT JOIN produto_precode pp 
                                                ON pp.codigo_bd = cp.CODIGO
                                                WHERE cp.ATIVO='S' AND cp.NO_MKTP='S' 
                                                ORDER BY cp.CODIGO");
                $numRows = mysqli_num_rows($result);
                if ($numRows > 0) {
                    while ($list = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                        $codigo = $list['CODIGO'];
                        $descricao = $list['DESCRICAO'];
                        $codigo_site = $list['CODIGO_SITE'];
                        $classe_enviado = ($codigo_site != null && $codigo_site != '') ? 'enviado' : '';

                        echo "<div class='col-12 col-sm-6 mb-2 product-item $classe_enviado'>";
                        echo "<input type='checkbox' name='codprod' value='$codigo' class='mr-2'>";
                        echo "Cód: <span class='product-code'>$codigo</span>";
                        echo "<div class='fw-bold product-description'>$descricao</div>";
                        if ($classe_enviado != '') {
                            echo "<span>Enviado</span>";
                        }
                        echo "<hr>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const searchTerm = this.value.toLowerCase();
        const productItems = document.querySelectorAll('.product-item');

        productItems.forEach(function (item) {
            const productCode = item.querySelector('.product-code').textContent.toLowerCase();
            const productDescription = item.querySelector('.product-description').textContent.toLowerCase();

            if (productCode.includes(searchTerm) || productDescription.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>