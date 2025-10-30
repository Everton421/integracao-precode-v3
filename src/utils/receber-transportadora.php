<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');//
include_once(__DIR__.'/../database/conexao_publico.php');

class ReceberTransportadora{

    private $token;    
    private $publico;

  /// informar os dados de rastreio vindo do objeto pedido da requisição       
public function receberTransportadora($pedido){
                $this->publico = new CONEXAOPUBLICO();
                $dadosRastreio = $pedido->dadosRastreio;
                $cnpjTransp = $this->formatCnpjCpf($dadosRastreio->CNPJfilial); //cnpj 
                $cidadeDistribuicao = addslashes(strtoupper($dadosRastreio->cidadeDistribuicao)); //  Cidade onde o produto saiu
                $ufCentroDistribuicao = addslashes(strtoupper( $dadosRastreio->ufCentroDistribuicao));//Estado onde o produto saiu
                $transportadora= addslashes(strtoupper( $dadosRastreio->transportadora));//Nome da transportadora que está transportando o produto

                $dadosTransportadoras = $this->publico->Consulta("SELECT * from cad_forn where CNPJ = '$cnpjTransp'");
                    if(empty($cnpjTransp)){
                         echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> nao informado o cnpj da transportdora do o pedido'.$pedido->codigoPedido .' ';   
                     $cnpjTransp='';
                        }
                    if(empty($cidadeDistribuicao)){
                         echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> nao informado a cidade da  transportdora do pedido'.$pedido->codigoPedido .' ';   
                    $cidadeDistribuicao ='';
                     }

                    if(empty($ufCentroDistribuicao)){
                         echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> nao informado a UF da  transportdora do pedido'.$pedido->codigoPedido .' ';   
                        $ufCentroDistribuicao ='';
                        }

                    if(empty($transportadora)){
                         echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> nao informado o nome da  transportdora do pedido'.$pedido->codigoPedido .' ';   
                        $transportadora='';
                    }



                if(mysqli_num_rows($dadosTransportadoras) > 0){
                    echo '<div class="card-header alert alert-warning"> <h3 style="color: #B8860B;" align="center">transportadora"'.$transportadora.'" já cadastrado<br>';   

                      $atualiza = '';	
                        if(!empty($cidadeDistribuicao)){
                            $atualiza = $atualiza."CIDADE = '$cidadeDistribuicao'";
                        }
                        if(!empty($ufCentroDistribuicao)){
                            $atualiza = $atualiza." ,ESTADO = '$ufCentroDistribuicao'";
                        } 
                       
                        if(!empty($transportadora)){
                            $atualiza = $atualiza." ,NOME_FANTASIA = '$transportadora'";
                        } 
                         if(!empty($transportadora)){
                            $atualiza = $atualiza." ,RAZAO_SOCIAL = '$transportadora'";
                        } 
                            $sql = "UPDATE cad_clie set $atualiza where CNPJ = '$cnpjTransp'";
                    
                    echo ' Atualizado com sucesso !<br>Cliente:"'.$transportadora.'"<br>CPF: '.$transportadora.'';   
                    echo '</div>';                    
                    echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';                    
                    print_r(date('d/m/Y h:i:s'));                    
                    echo '</div></b>';

                }else{


            $sql = "INSERT INTO cad_forn ( 
                            RAZAO_SOCIAL,
                            NOME_FANTASIA,
                            ATIV_EMPR,
                            CIDADE,
                            ESTADO,
                            DATA_CADASTRO,
                            CNPJ,
                            DATA_RECAD 
                    ) VALUES(
                        upper('$transportadora'),
                        upper('$transportadora'),
                        'p',
                        upper('$cidadeDistribuicao'),
                        upper('$ufCentroDistribuicao'),
                         NOW() ,
                        '$cnpjTransp',
                         NOW() 
                )";



                    if (mysqli_query($this->publico->link, $sql) === TRUE){ 
                                echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> transportadora '.$transportadora.' - '.$cnpjTransp.' cadastrada!';   
                                echo '</div>';
                            }else{
                                echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao inserir transportadora "'.$transportadora.' - '.$cnpjTransp.'';   
                                echo '</div>';
                            }
                }


}

  public function formatCnpjCpf($cpf){

        $cnpj_cpf = preg_replace("/\D/", '', $cpf);
        
        if (strlen($cnpj_cpf) === 11) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
        } 
        
     return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
  } 

}

?>