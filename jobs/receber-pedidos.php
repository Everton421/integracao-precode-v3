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

 <div class="content"> 
     <nav class="navbar navbar-expand-md navbar-dark fixed-top "> 
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="../index.php" >INTERSIG</a>
    </nav>  

 </div> 
<?php
    include_once(__DIR__."/../services/pedidos/receber-pedidos.php");
	$dadosEnvio = new recebePrecode();	
	$dadosEnvio->recebe();
	
?>
