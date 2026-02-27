<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet">

    <title>Montar Kit</title>
    <style>
        /* Estilos copiados e adaptados do seu arquivo original */
        body { font-family: 'Raleway', sans-serif; background-color: #f8f9fa; }
        
        .form-group-inline { display: flex; flex-wrap: wrap; align-items: center; }
        .form-group-inline .form-group { flex: 1; margin-right: 10px; margin-bottom: 10px; min-width: 150px; }
        .form-group-inline .form-group:last-child { margin-right: 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }

        /* Sidebar */
        .sidebar { height: 100%; width: 250px; position: fixed; top: 0; left: 0; background-color: #343a40; padding-top: 60px; color: white; z-index: 100; }
        .sidebar button { padding: 15px 25px; text-decoration: none; font-size: 16px; color: #f2f2f2; display: block; transition: 0.3s; text-align: left; border: none; background-color: transparent; width: 100%; cursor: pointer; }
        .sidebar button:hover { background-color: #28a745; color: white; } /* Verde para destacar o botão montar */
        
        /* Content */
        .content { margin-left: 250px; padding: 20px; }
        .navbar { background-color: #343a40 !important; }
        .navbar-brand { color: white !important; }

        /* Modal Customization */
        .modal-header { background-color: #343a40; color: white; }
        .modal-lg { max-width: 90%; } /* Modal mais largo para caber os campos */
        a {
                text-decoration: none; /* Remove o sublinhado */
                color: inherit;        /* Faz o link herdar a cor do texto ao redor (ou use 'black', 'blue', etc.) */
            }
    </style>
</head>
<body>

<!-- O FORM WRAPPER ENGLOBA TUDO (Tabela e Modal) -->
<form method="post" action="controller.php" enctype="multipart/form-data" id="formKit">
    <!-- Campo oculto para identificar a ação no controller -->
    <input type="hidden" name="acao" value="criar_kit">

    <div class="sidebar">
        <!-- Botão que aciona a verificação e abre o modal -->
        <button type="button" >
            <a href="montar-kit.php" style="text-decoration: none;">
                <i class="fa-solid fa-boxes-stacked"></i> CRIAR NOVO KIT
            </a>
        </button>

         <button type="submit" name="acao" value="atualizarPreco">
             Enviar Preço do produto selecionado
            <i class="fa-solid fa-arrow-up-from-bracket"></i>
             </button>

         <button type="submit" name="acao" value="atualizarEstoque">
            Enviar Estoque do produto selecionados
            <i class="fa-solid fa-arrow-up-from-bracket"></i>
        </button>
    </div>

    <div class="content">
        <nav class="navbar navbar-expand-md navbar-dark fixed-top">
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">
                <i class="fa-solid fa-arrow-left"></i> INTERSIG
            </a>
        </nav>

        <div class="container-fluid">
            <div class="form-group" style="margin-top: 60px;">
                <h2 style="font-weight: bold;"> 
                    <i class="fa-solid fa-list-check"></i>  Kits enviados
                </h2>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar produto por nome ou código...">
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover bg-white">
                    <thead class="thead-dark">
                        <tr>
                            <th width="50">#</th>
                            <th>id</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            include_once(__DIR__ . '/../database/conexao_integracao.php');
                            $integracao = new CONEXAOINTEGRACAO();
                            
                            // Busca produtos ativos para compor o kit
                            $sql = "SELECT id, DESCRICAO 
                                    FROM kit 
                                    ORDER BY id ASC LIMIT 500"; 
                            
                            $result = $integracao->Consulta($sql);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($list = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                    $id = $list['id'];
                                    $descricao = mb_convert_encoding($list['DESCRICAO'], 'UTF-8', 'ISO-8859-1');

                                    echo "<tr>";
                                        echo "<td><input type='checkbox' name='itens_selecionados[]' value='$id' data-desc='".htmlspecialchars($descricao, ENT_QUOTES)."'></td>";
                                        echo "<td>$id</td>";
                                        echo "<td>$descricao</td>";
                                    echo "</tr>";
                                }
                            }
                            $integracao->Desconecta();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

 
    <!-- FIM DO MODAL -->

</form>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>

<script>
    // Função de Pesquisa
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const searchTerm = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('.table tbody tr');

        tableRows.forEach(function (row) {
            const productCode = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const productOtherCode = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const productDescription = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

            if (productCode.includes(searchTerm) || productDescription.includes(searchTerm) || productOtherCode.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

   
</script>

</body>
</html>