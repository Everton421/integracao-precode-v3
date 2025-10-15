<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Dashboard</title>
    <style>
        .sidebar {
            height: 100%;
            width: 200px;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .sidebar a {
            padding: 6px 8px 6px 16px;
            text-decoration: none;
            font-size: 18px;
            color: #000;
            display: block;
        }
        .sidebar a:hover {
            color: #007bff;
        }
        .content {
            margin-left: 200px;
            padding: 15px;
        }
        .section {
            display: none;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="#" onclick="showSection('estoque')">Estoque Precode</a>
        <a href="#" onclick="showSection('envio')">Envio de Produto Precode</a>
    </div>
    <div class="content">
        <section id="estoque" class="section">
<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Estoque Precode</a>
    </nav>
    <div class="container">
        <div class="card-body">
            <form method="POST" action="" id="estoqueForm" onsubmit="return validarForm()">
                <div class="form-group">
                    <label for="exampleInputEmail1">C贸digo do Produto</label>
                    <hr>
                    <label >Enviar estoque zero</label>
                    <input type="checkbox" id="prodzero" name="prodzero">
                    <input type="text" class="form-control" name="codigoProd" id="codigoProd"  placeholder="C贸digo">
                    <small class="form-text text-muted">Coloque apenas n煤meros.</small>
                </div>
                <div class="form-group">
                    <label for="exampleFormControlSelect1">Selecione a Empresa</label>
                    <select class="form-control" id="empresa" name="empresa">
                        <option value="selecionar">Selecionar</option>
                        <option value="matriz">Estoque PR</option >
                        <option value="filialsc">Estoque SC</option>
                        
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Atualizar</button>
            </form>
        </div>
    </div>

        </section>
        <section id="envio" class="section">
          <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Envio de produto Precode</a>
    </nav>
    <div class="container">
        <div class="card-body">
            <form method="POST" action="class/produtos.php">
                <div class="form-group">
                    <label for="exampleInputEmail1">C贸digo do Produto</label>
                    <hr>
                    <input type="text" class="form-control" name="codigoProd" id="codigoProd" placeholder="C贸digo">
                    <small class="form-text text-muted">Coloque apenas n煤meros.</small>
                </div>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </form>
        </div>
    </div>


    <script>
        function validarForm() {
            var empresa = document.getElementById('empresa').value;
            
            if (empresa === 'selecionar') {
                alert("Por favor, selecione uma empresa.");
                return false; // Impede o envio do formul谩rio
            }
        }
        
        document.getElementById('empresa').addEventListener('change', function() {
            var formAction = '';
            if (this.value === 'matriz') {
                formAction = 'class/estoque2.php';
            } else if (this.value === 'filialsc') {
                formAction = 'class/estoque3.php';
            }
            document.getElementById('estoqueForm').action = formAction;
        });
    </script>

        </section>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.min.js"></script>
    <script>
        function showSection(sectionId) {
            var sections = document.getElementsByClassName('section');
            for (var i = 0; i < sections.length; i++) {
                sections[i].style.display = 'none';
            }
            document.getElementById(sectionId).style.display = 'block';
        }
        // Mostra a se玢o "estoque" por padr鉶
        showSection('estoque');
    </script>
</body>
</html>
