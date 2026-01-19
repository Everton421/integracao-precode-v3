  <?php
  

  Class Logs{
   
  public static function registrar(
    $vendasConnection ,
    $databaseName,
    $status = 'sucesso',
    string | null $acao = null,
    string | null $dados = null,
    string | null $referencia = null,
    string | null $mensagem = null
  ){

    $sql = "INSERT INTO `$databaseName`.`log_precode` SET `acao`='$acao',`dados`= "."\"$dados\"".", `status`='$status',`referencia`='$referencia', `mensagem`='$mensagem' ;";

    $result =  $vendasConnection->Consulta($sql);

        return $result;
  }
 
  }
    ?>