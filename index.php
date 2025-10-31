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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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
    <a href="atualizar-preco.php">Enviar Preços</a>
    <a href="atualizar-estoque.php">Enviar Estoque</a>
    <a href="receber-pedidos.php">Receber Pedidos</a>
</div>

<div class="content">
    <form method="post" action="produtos.php" id="formEnvia">
        <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">INTERSIG</a>
            <ul class="navbar-nav mr-auto">
                <li class="nav-item btn-list">
                    <button type="submit" class="btn btn-primary mt-3" name="acao" value="enviar">Enviar produto selecionado</button>
                </li>
                  <li class="nav-item btn-list">
                    <button type="submit" class="btn btn-primary mt-3" name="acao" value="vincular">Obter vinculo do produto selecionado</button>
                </li>
            </ul>
        </nav>

        <div class="container">
            <div class="form-group  " style="margin-top: 150px; ">
                <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar produto por nome ou código...">
            </div>

            <div class="card-body">
                <?php
                include(__DIR__ . '/database/conexao_publico.php');
                include(__DIR__ . '/database/conexao_vendas.php');

                $publico = new CONEXAOPUBLICO();
                $vendas = new CONEXAOVENDAS();
                $database_vendas = $vendas->getBase();
                $result = $publico->Consulta("SELECT cp.CODIGO, cp.OUTRO_COD ,cp.DESCRICAO, pp.CODIGO_SITE, fp.FOTO, pr.FOTOS as CAMINHO_FOTOS,
                                                pp.PRECO_SITE, pp.SALDO_ENVIADO, pp.DATA_RECAD_ESTOQUE
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
                        $classe_enviado = ($codigo_site != null && $codigo_site != '') ? 'enviado' : '';
                        $foto = ( $foto != null && $foto != '' ) ? $caminho.''.$foto : '';
                         $preco = $list['PRECO_SITE'];
                         $saldo = $list['SALDO_ENVIADO'];
                         $dataEstoque = $list['DATA_RECAD_ESTOQUE'];
                        echo "<div class='col-12 col-sm-6 mb-2 product-item $classe_enviado'>";
                         echo "<input type='checkbox' name='codprod[]' value='$codigo' class='mr-2'>";

                          echo "<div class='fw-bold product-description'> <strong> Cód:</strong> <span class='product-code'>$codigo <strong><br>  $descricao <br> </strong>  Referencia/outro_codigo:  <strong>$outro_cod </strong></span> </div>";

                              if ($classe_enviado != '') {
                                 echo "<span> > Enviado </span>";
                                    echo "<i class='fas fa-check-circle'></i>"; // Ícone de sucesso (Font Awesome)
                                  echo "<br><span> > Saldo Enviado: <strong>$saldo </strong>  |  Preço Enviado: <strong>$preco </strong>   </span>";
                                  echo "<br><span> > Último envio de estoque: <strong>$dataEstoque</strong>  </span>";

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