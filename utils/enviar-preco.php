<?php


set_time_limit(0);
  ini_set('mysql.connect_timeout','0');   
  ini_set('max_execution_time', '0'); 
  date_default_timezone_set('America/Sao_Paulo');
  include(__DIR__.'/../database/conexao_publico.php');
  include(__DIR__.'/../database/conexao_estoque.php'); 
  include(__DIR__.'/../database/conexao_vendas.php');

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

$buscaProdutos =$publico->Consulta("SELECT pp.codigo_site,cp.outro_cod, pp.codigo_bd, pp.preco_site, cp.no_mktp, pp.site_desbloquear_preco as desbloqueio from produto_precode pp 
                                    left join cad_prod cp on cp.codigo = pp.codigo_bd where no_mktp = 'S'");
   
    while($row1 = mysqli_fetch_array($buscaPreco, MYSQLI_ASSOC)){



        $referencia = $row1['outro_cod'];

        $valorProduto = $row1['preco'];
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
                  \r\n\"IdReferencia\": \"$referencia\",
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
        }     
    }


?>