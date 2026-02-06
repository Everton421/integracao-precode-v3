 <?php


 include_once(__DIR__."/../database/conexao_publico.php");
 include_once(__DIR__."/../utils/obter-informacoes-produto.php");
 include_once(__DIR__."/../utils/obter-vinculo-produto.php");

        $publico = new CONEXAOPUBLICO();

  // $obterInfo = new  ObterInformacoesProduto();
  // $obterInfo->getInfo(797)

       $obterVinc = new ObterVinculo();
       $obterVinc->getVinculo(801);

          /*    while( true ){
                     sleep(5);
                     
                      $produtos = $publico->Consulta("SELECT * from lps_eventos_sistema.eventos_produtos_sistema where status = 'PENDENTE';");

                 while($row1 = mysqli_fetch_array($produtos, MYSQLI_ASSOC)){
                                 print_r($row1);       
                     }
              } */


  
 ?> 