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
     <?php
       echo '<a href="./produtos">';
                echo '<i class="fa-solid fa-cube"></i>';
            echo '<span style="margin: 10px;">';
                 echo 'produtos';
            echo '<span>';
            echo '</a>';

     ?>
</div>

<div class="content">
    <form method="post" action="produtos.php" id="formEnvia">
        <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
            <?php
           echo ' <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="'.__DIR__.'">INTERSIG</a>';
          ?>
            <button >
                <a href="receber-pedidos.php" style="color: #495057;font-weight: bold;">
                        Receber Pedidos
                        <i class="fa-solid fa-download"></i>
                    </a>
            </button>
            

        </nav>

        <div class="container">
           
            <div class="form-group" style="margin-top: 60px; ">
             <h2 style="font-weight: bold;" > 
                <i class="fa-regular fa-clipboard"></i>
                Pedidos
            </h2>   
            </div>
            <div class="form-group" style="margin-top: 30px; ">
            <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar produto por nome ou código...">
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
                                                LEFT JOIN pedido_precode pp ON pp.codigo_pedido_bd = co.cod_site
                                                
                                                ");
                $numRows = mysqli_num_rows($result);
                if ($numRows > 0) {
                    while ($list = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                        $codigo = $list['CODIGO'];
                        $codigo_site = $list['COD_SITE'];
                        $situacao = $list['SITUACAO'];
                        $nome= $list['NOME'];
                        $classe_enviado =  $situacao != '' ? $situacao : '';

                        echo "<div class=' mb-2 product-item  '>";
                         echo "<input type='checkbox' name='codprod[]' value='$codigo' class='mr-2'>";

                          echo "<div class='fw-bold product-description'> <strong> Cód Sistema: <span class='product-code'>$codigo  |  Cód Precode: $codigo_site  </strong> </div>";
                          echo "<div class='fw-bold product-description'> <strong> Nome:</strong> <span class='product-code'>$nome <strong>  </div>";

                              if ($classe_enviado != '') {
                                //    echo "<div class='d-flex justify-content-center'>";
                                      //  echo '<div>';
                                        echo "<span> > ".$classe_enviado ;
                                            echo "<i class='fas fa-check-circle'></i>"; // Ícone de sucesso (Font Awesome)
                                        echo '</span>';
                                      //  echo '</div>';

                                     //  echo '<button> teste</button>';
                                  //  echo "</div>";

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