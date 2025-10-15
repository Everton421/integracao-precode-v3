<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="assets/css/page.css">
    <link rel="icon" href="Favicon.png">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <title>API PRECODE</title>

</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light navbar-laravel">
        <div class="container">
            <a class="navbar-brand" href="#">API PRECODE</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <main class="login-form">
        <div class="cotainer">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">  
                        <div class="card-header badge badge-primary" >
                            <h3>Enviar</h3>
                        </div>
                        <div class="card-body">                                                 
                        </div>
                        <hr>
                        <div class="card-body">
                            <form method="POST" action="produtos.php">
                            <div class="container">    
                                <div class="row">
                                    <div class="col-6 col-sm-3">
                                        <span class="badge badge-success col-8 col-sm-12"><h5>Início</h5></span>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <span class="badge badge-danger col-8 col-sm-12"><h5>Fim</h5></span>
                                    </div>                                   

                                    <span class="badge badge-info col-12 col-sm-6"><h5>Categoria</h5></span>
                                </div>

                                <div class="row">
                                    <div class="col-6 col-sm-3">    
                                        <input type="date" name="data1" id="data1" class="form-control-sm col-8 col-sm-12">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <input type="date" name="data2" id="data2" class="form-control-sm col-8 col-sm-12">
                                    </div>
                                    <select name="categoria" class="form-control-sm col-6 col-sm-6 font-weight-bold font-italic">                                     
                                        <option value=''>*-Enviar todos os produtos -*</option>    
                                        <?php 
                                            include_once('../connections/conexao_publico_rec.php');
                                            $Obj_Conexao_publico = new CONEXAOPUBLICO();											
                                        //Chamar a tua conexao
                                            $busca = $Obj_Conexao_publico->Consulta("select CODIGO, NOME from cad_pgru where NO_SITE='S'");
                                            $retorno = mysqli_num_rows($busca);

                                            if($retorno > 0 ){
                                                
                                                while($lista = mysqli_fetch_array($busca, MYSQLI_ASSOC)){	
                                                    $codigo = utf8_encode($lista['CODIGO']);
                                                    $descricao = utf8_encode($lista['NOME']);
                                                    echo"<option value='$codigo'>Cód: $codigo - $descricao</option>";
                                                    //echo"<option value='$codigo'>$descricao</option>"; 
                                                }
                                            }
                                            
                                        ?>
                                    </select>
                                </div>                                          
                                <div class="row card-body">
                                    <span class="badge badge-warning col-md-8 offset-md-2"><h5>Selecione os itens para atualizar/cadastrar</h5></span>  
                                    <div class="card-body col-12 col-sm-12">
                                        <button type="submit" class="btn button col-md-4 offset-md-4">
                                            Enviar
                                        </button>                                
                                    </div>                 
                                </div>
                            </form>   
                            </div>                                   
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
