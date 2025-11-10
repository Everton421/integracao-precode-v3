 <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="../assets/css/fotos.css" type="text/css">
    <link rel="icon" href="Favicon.png">
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
            background-color: #343a40;
            padding-top: 60px;
            color: white;
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
            background-color: #495057;
        }

        /* Estilos para o conteúdo principal */
        .content {
            margin-left: 250px;
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

        /* Estilos para os itens de pedido */
        .order-item {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
        }

        .order-code {
            font-weight: normal; /* Remove negrito do código */
        }

        .order-description {
            word-wrap: break-word; /* Quebra palavras longas */
        }

        /* Responsividade (opcional) */
        @media (max-width: 768px) {
            .content {
                margin-left: 0; /* Remove a margem para telas menores */
                padding: 10px;
            }
            .sidebar {
                width: 100%;
                position: static; /* Remove o posicionamento fixo */
                height: auto;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <?php
    echo '<a href="./produtos">';
    echo '<i class="fa-solid fa-cube"></i>';
    echo '<span style="margin: 10px;">';
    echo 'produtos';
    echo '<span>';
    echo '</a>';


        echo '<a href="#">';
    echo '<i class="fa-regular fa-clipboard"></i>';
    echo '<span style="margin: 10px;">';
    echo 'Pedidos';
    echo '<span>';
    echo '</a>';
                    


    ?>
</div>

<div class="content">
    <form   id="formEnvia">
        <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
            <?php
            echo ' <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="'.__DIR__.'">INTERSIG</a>';
            ?>
            <button >
                <a href="jobs/receber-pedidos.php" style="color: #495057;font-weight: bold;">
                    Receber Pedidos
                    <i class="fa-solid fa-download"></i>
                </a>
            </button>
        </nav>
    </form>

        <div class="container">
            <div class="form-group" style="margin-top: 60px; ">
                <h2 style="font-weight: bold;" >
                    <i class="fa-regular fa-clipboard"></i>
                    Pedidos
                </h2>
            </div>
            <div class="form-group" style="margin-top: 30px; ">
                <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar pedido por nome do cliente ou código...">
            </div>

            <div class="card-body">
                <?php
                include(__DIR__ . '/database/conexao_publico.php');
                include(__DIR__ . '/database/conexao_vendas.php');

                $publico = new CONEXAOPUBLICO();
                $vendas = new CONEXAOVENDAS();
                $database_vendas = $vendas->getBase();
                $database_publico= $publico->getBase();

                $result = $vendas->Consulta("SELECT 
                                                co.CODIGO,
                                                co.COD_SITE,
                                                pp.situacao AS SITUACAO,
                                                cli.NOME
                                                FROM cad_orca co 
                                                JOIN ".$database_publico.".cad_clie cli ON cli.CODIGO = co.CLIENTE
                                                JOIN pedido_precode pp ON pp.codigo_pedido_bd = co.cod_site
                                                
                                                ");
                $numRows = mysqli_num_rows($result);
                if ($numRows > 0) {
                    while ($list = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                        $codigo = $list['CODIGO'];
                        $codigo_site = $list['COD_SITE'];
                        $situacao = $list['SITUACAO'];
                        $nome= $list['NOME'];
                        $classe_enviado =  $situacao != '' ? $situacao : '';

                        echo "<div class='order-item'>";
                        echo"<form action='jobs/obter-etiquetas-pedido.php' method='POST'>";
                            echo "<div class='row'>"; // Inicia uma linha (row) do Bootstrap
                                echo "<div class='col-md-1'>"; // Coluna para o checkbox
                                    echo "<input type='checkbox' name='codprod[]' value='$codigo' class='mr-2'>";
                                echo "</div>";
                                echo "<div class='col-md-11'>"; // Coluna para os detalhes do produto
                                    echo "<div class='order-description'>";
                                      echo "<strong>Cód Sistema:</strong> <span class='order-code'>$codigo</span> | <strong>Cód Precode:</strong> <span class='product-code'>$codigo_site</span><br>";
                                      echo "<strong>Nome:</strong> <span class='client-name'>$nome</span>";
                                    echo "</div>";
                                    
                                    if ($classe_enviado != '' && $classe_enviado == 'nota_enviada') {
                                        echo "<div >";
                                            echo "<span style='font-weight: bold ;color: green; '> >  " . $classe_enviado;
                                            echo "<i class='fas fa-check-circle' style='margin-left:10px;'></i>";
                                            echo "</span>";
                                        echo "</div>";
                                    }
                                    echo "<button type='submit' >";
                                            echo "teste";
                                   echo "</button>";

                                echo "</div>";
                            echo "</div>"; // Fecha a linha (row)
                          //  echo "<hr>";
                        echo"</form>";

                        echo "</div>";
                    }
                    $publico->Desconecta();
                    $vendas->Desconecta();
                }
                ?>
            </div>
        </div>
</div>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const searchTerm = this.value.toLowerCase();
        const orderItems = document.querySelectorAll('.order-item');

        orderItems.forEach(function (item) {
            const orderCode = item.querySelector('.order-code').textContent.toLowerCase();
            const clientName = item.querySelector('.client-name').textContent.toLowerCase();

            if (orderCode.includes(searchTerm) || clientName.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>