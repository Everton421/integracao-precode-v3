<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Fontes e Estilos -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="../assets/css/fotos.css" type="text/css">
    <link rel="icon" href="Favicon.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <title>Requisição</title>
    <style>
        /* Estilos gerais */
        body { font-family: 'Raleway', sans-serif; background-color: #f8f9fa; }
        .sidebar { height: 100%; width: 250px; position: fixed; top: 0; left: 0; background-color: #343a40; padding-top: 60px; color: white; }
        .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 18px; color: #f2f2f2; display: block; transition: 0.3s; }
        .sidebar a:hover { background-color: #495057; }
        .content { margin-left: 250px; padding: 20px; }
        .navbar { background-color: #343a40 !important; color: white !important; }
        .navbar a { color: white !important; text-decoration: none; }
        .order-item { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: #fff; }
        .order-code { font-weight: normal; }
        .order-description { word-wrap: break-word; }
        .nota_enviada { background-color: #d4edda !important; color: #155724 !important; }
        .nota_enviada .order-code, .nota_enviada .client-name { font-weight: bold; }
        @media (max-width: 768px) {
            .content { margin-left: 0; padding: 10px; }
            .sidebar { width: 100%; position: static; height: auto; }
        }
        .card-filter { background-color: #fff; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <?php
    echo '<a href="./produtos-grade"><i class="fa-solid fa-cube"></i><span style="margin: 10px;">grades produtos<span></a>';
    echo '<a href="./produtos"><i class="fa-solid fa-cube"></i><span style="margin: 10px;">produtos<span></a>';
    echo '<a href="#"><i class="fa-regular fa-clipboard"></i><span style="margin: 10px;">Pedidos<span></a>';
    ?>
</div>

<div class="content">
    <nav class="navbar navbar-expand-md navbar-dark fixed-top">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="<?php echo __DIR__; ?>">INTERSIG</a>
        <a href="jobs/receber-pedidos.php">Receber Pedidos <i class="fa-solid fa-download"></i></a>
    </nav>

    <div class="container">
        <div class="form-group" style="margin-top: 60px;">
            <h2 style="font-weight: bold;"><i class="fa-regular fa-clipboard"></i> Pedidos</h2>
        </div>

        <!-- FILTRO DE DATA (Server Side) -->
        <?php
        // Define a data atual como padrão se não vier nada no GET
        $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d');
        $data_fim    = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
        ?>

        <div class="card-filter">
            <form method="GET" action="">
                <div class="form-row align-items-end">
                    <div class="col-md-5">
                        <label for="data_inicio">Data Inicial:</label>
                        <input type="date" class="form-control" name="data_inicio" id="data_inicio" 
                               value="<?php echo htmlspecialchars($data_inicio); ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="data_fim">Data Final:</label>
                        <input type="date" class="form-control" name="data_fim" id="data_fim" 
                               value="<?php echo htmlspecialchars($data_fim); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- PESQUISA RÁPIDA (Client Side / JS) -->
        <div class="form-group">
            <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar pedido na tela (Nome ou Código)...">
        </div>

        <div class="card-body">
            <?php
            include(__DIR__ . '/database/conexao_publico.php');
            include(__DIR__ . '/database/conexao_vendas.php');
            include(__DIR__ . '/database/conexao_integracao.php');

            $publico = new CONEXAOPUBLICO();
            $database_publico = $publico->getBase();

            $integracao = new CONEXAOINTEGRACAO();
            $database_integracao = $integracao->getBase();

            $vendas = new CONEXAOVENDAS();
            $database_vendas = $vendas->getBase();


            // Montagem da query
            $sql = "SELECT 
                        co.CODIGO,
                        co.COD_SITE,
                        pp.situacao AS SITUACAO,
                        cli.NOME,
                        concat(co.DATA_CADASTRO,' ',co.HORA_CADASTRO) as DATA_CADASTRO
                    FROM cad_orca co 
                    JOIN " . $database_publico . ".cad_clie cli ON cli.CODIGO = co.CLIENTE
                    JOIN ".$database_integracao.".pedido_precode pp ON pp.codigo_pedido_bd = co.CODIGO
                    WHERE 1=1 ";

            // Aplica o filtro de data (que sempre terá valor, padrão hoje ou o escolhido)
            if (!empty($data_inicio)) {
                $sql .= " AND co.DATA_CADASTRO >= '$data_inicio' ";
            }
            if (!empty($data_fim)) {
                $sql .= " AND co.DATA_CADASTRO <= '$data_fim' ";
            }
            
            $sql .= " ORDER BY co.DATA_CADASTRO DESC, co.HORA_CADASTRO DESC";

            $result = $vendas->Consulta($sql);
            
            if ($result) {
                $numRows = mysqli_num_rows($result);
                
                if ($numRows > 0) {
                    while ($list = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                        $codigo = $list['CODIGO'];
                        $codigo_site = $list['COD_SITE'];
                        $situacao = $list['SITUACAO'];
                        $nome = $list['NOME'];
                        
                        $dataExibicao = '';
                        if(!empty($list['DATA_CADASTRO'])){
                             try {
                                $dataObj = new DateTime($list['DATA_CADASTRO']);
                                $dataExibicao = $dataObj->format('d/m/Y H:i:s');
                             } catch (Exception $e) {
                                $dataExibicao = $list['DATA_CADASTRO'];
                             }
                        }

                        $classe_enviado = ($situacao == 'nota_enviada') ? 'nota_enviada' : '';

                        echo "<div class='order-item " . $classe_enviado . "'>";
                        echo "<form action='etiqueta-pedido.php' method='POST' id='formEnvia_$codigo'>"; 
                            echo "<div class='row'>";
                                echo "<div class='col-md-1'>";
                                    echo "<input type='checkbox' class='mr-2'>";
                                echo "</div>";
                                echo "<div class='col-md-11'>";
                                    echo "<div class='order-description'>";
                                    echo '<div class="d-flex justify-content-between align-itens-center">';
                                        echo "<span class='order-code'><strong>Cód Sistema:</strong> $codigo</span>";
                                        echo "<span class='product-code'> <strong>Cód Precode:</strong> $codigo_site</span>";
                                    echo "</div>";

                                        echo "<strong>Cliente:</strong> <span class='client-name'>$nome</span> ";
                                    echo "</div>";

                                    if ($classe_enviado == 'nota_enviada') {
                                        echo "<div>";
                                            echo "<span style='font-weight: bold; color: green;'> > " . $situacao;
                                            echo "<i class='fas fa-check-circle' style='margin-left:10px;'></i>";
                                            echo "</span>";
                                        echo "</div>";
                                    }
                                    echo "<div class='d-flex flex-row justify-content-between'>";
                                    echo "<button type='submit' class='btn btn-primary btn-sm' name='codpedido' value='$codigo_site'>";
                                        echo "Obter Etiquetas";
                                    echo "</button>";
                                        echo "<span class='client-name' style='margin-left:25px;'><strong>Data de Cadastro:</strong> $dataExibicao</span>";
                                    echo "</div>";
                                
                                echo "</div>";
                            echo "</div>";
                        echo "</form>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='alert alert-info'>Nenhum pedido encontrado para o período: " . date('d/m/Y', strtotime($data_inicio)) . " até " . date('d/m/Y', strtotime($data_fim)) . ".</div>";
                }
            }
            $publico->Desconecta();
            $vendas->Desconecta();
            $integracao->Desconecta();
            ?>
        </div>
    </div>
</div>

<script>
    // Filtro JS (Pesquisa na tela)
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const searchTerm = this.value.toLowerCase();
        const orderItems = document.querySelectorAll('.order-item');

        orderItems.forEach(function (item) {
            const orderCode = item.querySelector('.order-code').textContent.toLowerCase();
            const productCode = item.querySelector('.product-code').textContent.toLowerCase();
            const clientName = item.querySelector('.client-name').textContent.toLowerCase();

            // Verifica se o termo existe no cod sistema, cod precode ou nome cliente
            if (orderCode.includes(searchTerm) || clientName.includes(searchTerm) || productCode.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>