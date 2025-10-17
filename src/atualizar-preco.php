<html>
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
    <title>precode</title>
</head>
<body>
</body>

<?php

  ini_set('mysql.connect_timeout','0');   
  ini_set('max_execution_time', '0'); 
  date_default_timezone_set('America/Sao_Paulo');
  include(__DIR__.'/database/conexao_publico.php');
  include(__DIR__.'/database/conexao_estoque.php'); 
  include(__DIR__.'/database/conexao_vendas.php');

  $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

  $tabela = 1;
  if($ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco']) ){
    $tabela =$ini['conexao']['tabelaPreco']; 
  }
$filial = 1;
  if($ini['conexao']['filial'] && !empty($ini['conexao']['filial']) ){
    $filial =$ini['conexao']['filial']; 
  }
  
if(empty($ini['conexao']['token'] )){
    echo 'token da aplicação não fornecido';
        exit();
}
 $appToken = $ini['conexao']['token'];


  $publico = new CONEXAOPUBLICO();	
  $vendas = new CONEXAOVENDAS();

  $hoje = date('Y-m-d');
//  $command = 'nohup /root/zap/sendZap_Bianca '; 
   $erro1 = '';  
   $erro2 = '';   
   $erro3 = '';
   $erro4 = '';

echo "<main class='login-form'>";
echo '<div class="cotainer"><div class="row justify-content-center"><div class="col-md-8"><div class="card">';
echo '<div class="card-header alert alert-info" align="center"><h3 style="color: #008080;""><b>Atualizando Preço</b></h3><br>'; //abrindo o header com informação
print_r(date('d/m/Y h:i:s'));
echo '</div>';

$buscaProdutos =$publico->Consulta("SELECT pp.codigo_site, pp.codigo_bd, pp.preco_site, cp.no_mktp, pp.site_desbloquear_preco as desbloqueio from produto_precode pp 
left join cad_prod cp on cp.codigo = pp.codigo_bd where no_mktp = 'S'");
  while($row = mysqli_fetch_array($buscaProdutos, MYSQLI_ASSOC)){
    $produtoSite = $row['codigo_site'];
    $produtoBd = $row['codigo_bd'];
    $ultimoPreco = $row['preco_site'];    
    $desbloqueio = $row['desbloqueio'];  
    echo '<div class="card-header alert alert-warning" align="center"><h3 style="color: #DAA520;""><b>Verificando alterações de preço no produto "'.$produtoBd.'" </b></h3><br>'; //abrindo o header com informação
    print_r(date('d/m/Y h:i:s'));
    echo '</div>';
    
    // obtem preço do produto 
    $buscaPreco =$publico->Consulta("SELECT tabela, produto, preco, promocao, valid_prom FROM prod_tabprecos WHERE tabela = $tabela and produto = $produtoBd");

    while($row1 = mysqli_fetch_array($buscaPreco, MYSQLI_ASSOC)){

      $verificaIndexado =$publico->Consulta("SELECT p.codigo, p.grupo, p.descricao, pc.indexado FROM cad_prod p
                                                          Left Outer Join prod_custos pc on (pc.PRODUTO = p.CODIGO) And (pc.FILIAL = $filial)
                                                          WHERE p.codigo = $produtoBd");
      //print_r("SELECT codigo, grupo, descricao, indexado FROM cad_prod WHERE codigo = $produtoBd");
      
      while($resposta = mysqli_fetch_array($verificaIndexado, MYSQLI_ASSOC)){	
        $indexado = $resposta['indexado'];   
      }  
      $valorProduto = $row1['preco'];
      $valorPromocional = $row1['promocao'];
      if($indexado == 'S'){
        $valorProduto = $row1['preco'];
        $valorPromocional = $row1['promocao'];
        $parametro =$vendas->Consulta("SELECT indice FROM parametros"); 
        $resultado = $parametro->fetch_array(MYSQLI_ASSOC);
        $indice = $resultado['indice'];
        $preco = $valorProduto * $indice;
        $promocao = $valorPromocional * $indice;         
      }else{        
        $preco = $valorProduto; 
        $promocao = $valorPromocional; 
      }
      $validade = $row1['valid_prom'];
      if($validade >= $hoje){
        $precoVenda = $promocao;
        $situacao = 1;
      }else if($preco != $ultimoPreco){
        $precoVenda = $preco;
        $situacao = 2;
      }else{
        $precoVenda = $ultimoPreco;
        $situacao = 3;
      }      
    }
    # condições
    if($situacao == 1){ 
      #condição para promoção 
      $calc  = (($ultimoPreco - $precoVenda) * 100) /  $ultimoPreco;      
      $calc = number_format($calc , 3, '.', '');
      #campo de desbloqueio
      if($desbloqueio == 'S'){
        #campo de promoção com valor acima de 30%
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.replicade.com.br/api/v1/produtoLoja/preco",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "PUT",
          CURLOPT_POSTFIELDS => "
          {
            \r\n\"produto\":\r\n   
              [\r\n      
                {
                  \r\n\"IdReferencia\": \"$produtoBd\",
                  \r\n\"sku\": 0,
                  \r\n\"precoDe\": $ultimoPreco,
                  \r\n\"precoVenda\": $precoVenda,
                  \r\n\"precoSite\": $precoVenda\r\n     
                }\r\n   
              ]\r\n
          }",
          CURLOPT_HTTPHEADER => array(
            "authorization:$appToken",
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        
        curl_close($curl); 
        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          $inserePrecode =$publico->Consulta("UPDATE produto_precode SET preco_site = $precoVenda, data_recad = now() where codigo_bd = '$produtoBd'");
          //print_r("UPDATE produto_precode SET preco_site = $precoVenda, data_recad = now() where codigo_bd = '$produtoBd'");  
          if($inserePrecode == 1){
            echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Tabela produto_precode atualizada!</b></h3>';	
            echo '<br>';
            echo '</div>'; 
            $removeDesbloqueio =$publico->Consulta("UPDATE produto_precode SET site_desbloquear_preco = 'N' where codigo = '$produtoBd'");
            if($removeDesbloqueio == 1){
              echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Desbloqueio do produto removido com sucesso!</b></h3>';	
              echo '<br>';
              echo '</div>'; 	
              $insereLog =$publico->Consulta("INSERT INTO log_precode (produto, alteracao, data_modificacao) VALUES('$produtoBd', 'PROMOCAO INSERIDA ATRAVES DO DESBLOQUEI DO ERP', now())");
              if($insereLog == 1){
                echo 'Log informado com sucesso!';	
                echo '<br>'; 	
                }else{
                  echo 'falha ao inserir log';	
                  echo '<br>';
                }
              }else{
                echo '<div class="card-header alert alert-danger" align="center"><h3 style="color:red;"><b>falha ao remover desbloqueio do produto</b></h3>';	
                echo '<br>';
                echo '</div>';
              }	
              $erro4 = $erro4.'+'.$produtoBd;
          } else {
            echo '<div class="card-header alert alert-danger" align="center"><h3 style="color:red;"><b>falha ao atualizar tabela produto_precode</b></h3>';	
            echo '<br>';
            echo '</div>'; 	            
          }
          echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Código: '.$produtoBd.'<br>Desbloqueada alteração do produto!</b></h3><br>'; //abrindo o header com informação
          echo 'Ultimo valor é : '.$ultimoPreco.'<br>'; 
          echo 'Valor promocional atualizado: '.$precoVenda.'<br>';  
          echo 'Valor Indexado: '.$indexado.'<br>'; 

          echo "<br>";
          print_r(date('d/m/Y h:i:s'));    
          echo '</div>';           
          //echo $response;
        }
      }elseif($calc > 30){
        echo '<div class="card-header alert alert-danger" align="center"><h3 style="color: red;"><b>Código: '.$produtoBd.'<br>Valor do produto em promoção não alterado pois o produto está com alteração superior a 30% comparado ao valor anterior.</b></h3><br>'; //abrindo o header com informação
        echo 'Ultimo valor é : '.$ultimoPreco.'<br>'; 
        echo 'Valor promocional: '.$precoVenda.'<br>';      
        //echo 'Falha no produto: '.$falhaProd.'<br>';
        echo 'Valor Indexado: '.$indexado.'<br>'; 
        echo 'para poder alterar este valor, marque a opção no ERP!<br>'; 
        print_r(date('d/m/Y h:i:s'));    
        echo '</div>';       
        //$falhaProd = $produtoBd;
        $erro1 = $erro1.'+'.$produtoBd; 
        $insereLog =$publico->Consulta("INSERT INTO log_precode (produto, alteracao, data_modificacao) VALUES('$produtoBd', 'Valor do produto em promocao nao alterado pois o produto esta com alteracao superior a 30% comparado ao valor anterior', now())");
          if($insereLog == 1){
            echo 'atualizado no log';
            echo '<br>';
          }else{
            echo 'falha ao atualizar no log';
            echo '<br>';
          } 
        
      }else if($precoVenda == $ultimoPreco){
        echo '<div class="card-header alert alert-info" align="center"><h3 style="color: secondary;"><b>Código: '.$produtoBd.'<br>Esta promoção não foi alterada!</b></h3><br>'; //abrindo o header com informação
        echo 'Valor atual: '.$precoVenda.'<br>'; 
        echo 'Valor Indexado: '.$indexado.'<br>'; 
        //print_r("SELECT tabela, produto, preco, promocao, valid_prom FROM prod_tabprecos WHERE tabela = $tabela and produto = $produtoBd");
        //echo '<br>';
        print_r(date('d/m/Y h:i:s'));    
        echo '</div>';               
      }else{        
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.replicade.com.br/api/v1/produtoLoja/preco",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "PUT",
          CURLOPT_POSTFIELDS => "
          {
            \r\n\"produto\":\r\n   
              [\r\n      
                {
                  \r\n\"IdReferencia\": \"$produtoBd\",
                  \r\n\"sku\": 0,
                  \r\n\"precoDe\": $ultimoPreco,
                  \r\n\"precoVenda\": $precoVenda,
                  \r\n\"precoSite\": $precoVenda\r\n     
                }\r\n   
              ]\r\n
          }",
          CURLOPT_HTTPHEADER => array(
            "authorization:$appToken",
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        
        curl_close($curl); 
        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          $inserePrecode =$publico->Consulta("UPDATE produto_precode SET preco_site = $precoVenda, data_recad = now() where codigo_bd = '$produtoBd'");
          //print_r("UPDATE produto_precode SET preco_site = $precoVenda, data_recad = now() where codigo_bd = '$produtoBd'");  
          if($inserePrecode == 1){
            echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Tabela produto_precode atualizada!</b></h3>';	
            echo '<br>';
            echo '</div>'; 
            $insereLog =$publico->Consulta("INSERT INTO log_precode (produto, alteracao, data_modificacao) VALUES('$produtoBd', 'Produto em promocao alterado!', now())");
            if($insereLog == 1){
              echo 'atualizado no log';
              echo '<br>';
            }else{
              echo 'falha ao atualizar no log';
              echo '<br>';
            } 	
          } else {
            echo '<div class="card-header alert alert-danger" align="center"><h3 style="color:red;"><b>falha ao atualizar tabela produto_precode</b></h3>';	
            echo '<br>';
            echo '</div>'; 	
          }
          echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Código: '.$produtoBd.'<br>Produto em promoção alterado!</b></h3><br>'; //abrindo o header com informação
          echo 'Ultimo valor é : '.$ultimoPreco.'<br>'; 
          echo 'Valor promocional atualizado: '.$precoVenda.'<br>';  
          echo 'Valor Indexado: '.$indexado.'<br>'; 
          echo "<br>";
          print_r(date('d/m/Y h:i:s'));    
          echo '</div>';           
          //echo $response;
        }
      }
    }elseif($situacao == 2){
      $calc2 = 0;    
      if($precoVenda < $ultimoPreco ){
        $calc2  = (($ultimoPreco - $precoVenda) * 100) /  $ultimoPreco;
        $calc2 = number_format($calc2 , 3, '.', '');
      }
      //print_r($calc2);
      if($desbloqueio == 'S'){
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.replicade.com.br/api/v1/produtoLoja/preco",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "PUT",
          CURLOPT_POSTFIELDS => "
          {
            \r\n\"produto\":\r\n   
              [\r\n      
                {
                  \r\n\"IdReferencia\": \"$produtoBd\",
                  \r\n\"sku\": 0,
                  \r\n\"precoDe\": $ultimoPreco,
                  \r\n\"precoVenda\": $precoVenda,
                  \r\n\"precoSite\": $precoVenda\r\n     
                }\r\n   
              ]\r\n
          }",
          CURLOPT_HTTPHEADER => array(
            "authorization:$appToken",
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        
        curl_close($curl); 
        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          $inserePrecode =$publico->Consulta("UPDATE produto_precode SET preco_site = $precoVenda, data_recad = now() where codigo_bd = '$produtoBd'");
          //print_r("UPDATE produto_precode SET preco_site = $precoVenda, data_recad = now() where codigo_bd = '$produtoBd'");  
          if($inserePrecode == 1){
            echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Tabela produto_precode atualizada!</b></h3>';	
            echo '<br>';
            echo '</div>'; 
            $removeDesbloqueio =$publico->Consulta("UPDATE produto_precode SET site_desbloquear_preco = 'N' where codigo = '$produtoBd'");
            if($removeDesbloqueio == 1){
              echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Desbloqueio do produto removido com sucesso!</b></h3>';	
              echo '<br>';
              echo '</div>'; 	
              $insereLog =$publico->Consulta("INSERT INTO log_precode (produto, alteracao, data_modificacao) VALUES('$produtoBd', 'VALOR DO PRODUTO INSERIDO ATRAVES DO DESBLOQUEI DO ERP', now())");
              if($insereLog == 1){
                echo 'Log informado com sucesso!';	
                echo '<br>';	
                }else{
                  echo '<div class="card-header alert alert-danger" align="center"><h3 style="color:red;"><b>falha ao inserir log/b></h3>';	
                  echo '<br>';
                  echo '</div>';
                }
              }else{
                echo '<div class="card-header alert alert-danger" align="center"><h3 style="color:red;"><b>falha ao remover desbloqueio do produto</b></h3>';	
                echo '<br>';
                echo '</div>';
              }	
              $erro4 = $erro4.'+'.$produtoBd;
          } else {
            echo '<div class="card-header alert alert-danger" align="center"><h3 style="color:red;"><b>falha ao atualizar tabela produto_precode</b></h3>';	
            echo '<br>';
            echo '</div>'; 	            
          }
          echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Código: '.$produtoBd.'<br>Desbloqueada alteração do produto!</b></h3><br>'; //abrindo o header com informação
          echo 'Ultimo valor é : '.$ultimoPreco.'<br>'; 
          echo 'Valor promocional atualizado: '.$precoVenda.'<br>';  
          echo 'Valor Indexado: '.$indexado.'<br>'; 

          echo "<br>";
          print_r(date('d/m/Y h:i:s'));    
          echo '</div>';           
          //echo $response;
        }
      }elseif($calc2 > 30){
        echo '<div class="card-header alert alert-danger" align="center"><h3 style="color: red;"><b>Código: '.$produtoBd.'<br>Este produto esta com diferença acima de 30% do valor anterior!</b></h3><br>'; //abrindo o header com informação
        echo 'Ultimo preço inserido: '.$ultimoPreco.'<br>'; 
        echo 'Valor a ser alterado: '.$precoVenda.'<br>'; 
        echo 'Valor Indexado: '.$indexado.'<br>'; 
        echo 'para poder alterar este valor, marque a opção no ERP!<br>'; 
        $erro2 = $erro2.'+'.$produtoBd;
        //print_r("SELECT tabela, produto, preco, promocao, valid_prom FROM prod_tabprecos WHERE tabela = $tabela and produto = $produtoBd");
        //echo '<br>';
        $insereLog =$publico->Consulta("INSERT INTO log_precode (produto, alteracao, data_modificacao) VALUES('$produtoBd', 'Este produto esta com diferenca acima de 30% do valor anterior', now())");
        if($insereLog == 1){
          echo 'atualizado no log';
          echo '<br>';
        }else{
          echo 'falha ao atualizar no log';
          echo '<br>';
        } 
        print_r(date('d/m/Y h:i:s'));    
        echo '</div>'; 
      }else{
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.replicade.com.br/api/v1/produtoLoja/preco",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "PUT",
          CURLOPT_POSTFIELDS => "
          {
            \r\n\"produto\":\r\n   
              [\r\n      
                {
                  \r\n\"IdReferencia\": \"$produtoBd\",
                  \r\n\"sku\": 0,
                  \r\n\"precoDe\": $ultimoPreco,
                  \r\n\"precoVenda\": $precoVenda,
                  \r\n\"precoSite\": $preco\r\n     
                }\r\n   
              ]\r\n
          }",
          CURLOPT_HTTPHEADER => array(
            "authorization:$appToken",
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl); 
        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          $inserePrecode =$publico->Consulta("UPDATE produto_precode SET preco_site = $precoVenda, data_recad = now() where codigo_bd = '$produtoBd'");
          //print_r("UPDATE produto_precode SET preco_site = $precoVenda, data_recad = now() where codigo_bd = '$produtoBd'");  
          if($inserePrecode == 1){
            echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Tabela produto_precode atualizada!</b></h3>';	
            echo '<br>';
            echo '</div>'; 	
            $insereLog =$publico->Consulta("INSERT INTO log_precode (produto, alteracao, data_modificacao) VALUES('$produtoBd', 'Este produto esta com valor alterado e sera atualizado', now())");
            if($insereLog == 1){
              echo 'atualizado no log';
              echo '<br>';
            }else{
              echo 'falha ao atualizar no log';
              echo '<br>';
            } 
          } else {
            echo '<div class="card-header alert alert-success" align="center"><h3 style="color:red;"><b>falha ao atualizar tabela produto_precode</b></h3>';	
            echo '<br>';
            echo '</div>'; 	
          }
          echo '<div class="card-header alert alert-success" align="center"><h3 style="color: green;"><b>Código: '.$produtoBd.'<br>Este produto está com valor alterado e será atualizado!</b></h3><br>';
          echo 'Ultimo preço inserido: '.$ultimoPreco.'<br>'; 
          echo 'Valor a ser alterado: '.$precoVenda.'<br>';  
          echo 'Valor Indexado: '.$indexado.'<br>'; 
          print_r(date('d/m/Y h:i:s'));    
          echo '</div>'; 
        }        
      } 
    }else{
      echo '<div class="card-header alert alert-info" align="center"><h3 style="color: secondary;"><b>Código: '.$produtoBd.'<br>Este produto não foi alterado!</b></h3><br>';
      echo 'Valor atual: '.$precoVenda.'<br>';  
      echo 'Valor Indexado: '.$indexado.'<br>'; 
      //print_r("SELECT tabela, produto, preco, promocao, valid_prom FROM prod_tabprecos WHERE tabela = $tabela and produto = $produtoBd");
      //echo '<br>';
      print_r(date('d/m/Y h:i:s'));    
      echo '</div>'; 
    }
  }  
  /*  
    executa o comando e envia uma msg no whatsapp para o administrador com preços fora do comum
  
  $text1 = 'Valor+destes+produtos+em+promoção+não+alterado+acimda+de+30%+';
  $text2 = 'Estes+produtos+estão+com+diferença+acima+de+30%';
  $text3 = 'Estes+produtos+foram+atualizados+com+diferença+acima+de+30%+na+promocao';
  $text4 = 'Estes+produtos+foram+atualizados+com+diferença+acima+de+30%';
  $msg1 = $command.'+'.$text1.'+'.$erro1;
  $msg2 = $command.'+'.$text2.'+'.$erro2;
  $msg3 = $command.'+'.$text3.'+'.$erro3;
  $msg4 = $command.'+'.$text4.'+'.$erro4;
  exec($msg1 ,$op);
  exec($msg2 ,$op);
  exec($msg3 ,$op);
  exec($msg4 ,$op);
  */
  //$pid = (int)$op[0];
 $vendas->Desconecta();
 $publico->Desconecta();
  echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
  //echo 'falhas '.$falhaProd;
  print_r(date('d/m/Y h:i:s'));
  echo '</div></b>';
  echo '</div>';
  echo '</div>';
  echo '</div>';
  echo '</div>'; 
  echo "</main>"; 						
  echo '<br>';

?>




