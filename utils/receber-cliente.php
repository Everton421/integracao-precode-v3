<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');//
include_once(__DIR__.'/../database/conexao_publico.php');

class ReceberCliente{

    private $publico;
        private  $codigoVendedor = 1 ;

 public function cadastrarCliente($pedido){   
        $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);
			
        
        if( $ini['conexao']['vendedor_pedido'] && !empty($ini['conexao']['vendedor_pedido'])){
                         $this->codigoVendedor = $ini['conexao']['vendedor_pedido'];
                    }

        $this->publico = new CONEXAOPUBLICO();	


                $cpf = $this->formatCnpjCpf($pedido->dadosCliente->cpfCnpj);
                $tipo = $pedido->dadosCliente->tipo;
                $nome = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->nomeRazao)));
                $nome = substr($nome , 0, 99 );
                $apelido = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->fantasia)));
                $apelido = substr($apelido, 0 , 99 );

                $sexo = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->sexo)));
                $data_nasc = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->dataNascimento)));
                $email = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->email)));
                $endereco = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->dadosEntrega->endereco)));
                $numero = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->dadosEntrega->numero)));
                $complemento = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->dadosEntrega->complemento)));                
                $bairro = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->dadosEntrega->bairro)));
                $cidade = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->dadosEntrega->cidade)));
                $uf = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->dadosEntrega->uf)));
                $cep = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->dadosEntrega->cep)));
                $cep = substr($cep, 0, 8);
                
                $telefone_res = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->telefones->residencial)));
                $telefone_res = substr($telefone_res, 0 , 14);

                $telefone_com = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->telefones->comercial)));
                $telefone_com = substr($telefone_com, 0 ,14);                
                
                $telefone_cel = addslashes(strtoupper(utf8_decode($pedido->dadosCliente->telefones->celular)));
                $telefone_cel = substr($telefone_cel, 0 , 14 );
         
           
                
                $pega_dados = $this->publico->Consulta("SELECT * from cad_clie where CPF = '$cpf'");

                if(mysqli_num_rows($pega_dados) > 0){

                    echo '<div class="card-header alert alert-warning"> <h3 style="color: #B8860B;" align="center">Cliente"'.$nome.'" já cadastrado<br>';   
                    
                    $atualiza = '';	
                    if(!empty($nome)){
                        $atualiza = $atualiza."nome = '$nome'";
                    }
                    if(!empty($rua)){
                        $atualiza = $atualiza." ,endereco = '$endereco'";
                    } 
                    if(!empty($numero)){
                        $atualiza = $atualiza." ,numero = '$numero'";
                    } 
                    if(!empty($comentario)){
                        $atualiza = $atualiza." ,complemento = '$complemento'";
                    } 
                    if(!empty($bairro)){
                        $atualiza = $atualiza." ,bairro = '$bairro'";
                    } 
                    if(!empty($cidade)){
                        $atualiza = $atualiza." ,cidade = '$cidade'";
                    } 
                    if(!empty($telefone_res)){
                        $atualiza = $atualiza." ,telefone_res = '$telefone_res'";
                    }  
                    if(!empty($telefone_com)){
                        $atualiza = $atualiza." ,telefone_com = '$telefone_com'";
                    } 
                    if(!empty($telefone_cel)){
                        $atualiza = $atualiza." ,celular = '$telefone_cel'";
                    }
                    if(!empty($uf)){
                        $atualiza = $atualiza." ,estado = '$uf'";
                    } 
                    $sql = "UPDATE cad_clie set $atualiza where CPF = '$cpf'";
                    


                    if (mysqli_query($this->publico->link, $sql) === TRUE){ 
                      echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                      echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                      echo "<strong> </strong><br>Cliente :  $nome Atualizado com sucesso ! ";
                      print_r(date('d/m/Y h:i:s'));                    
                      echo '</div>';
                    }else{
                           echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao Atualizar Cliente "'.$nome.' - '.$cpf.'';   
                                 print_r(date('d/m/Y h:i:s'));                    
                                 echo '</div>';
                    }
                    echo "</main>"; 

               }else{
                $sql = "INSERT INTO cad_clie (
                    nome,
                    apelido,		
                    fis_jur, 
                    cpf, 
                    rg, 
                    email_fiscal, 
                    vendedor,
                    senha, 
                    observacoes, 
                    historico, 
                    bloq_motivo, 
                    obs_bancaria, 
                    obs_comercial1, 
                    obs_comercial2, 
                    obs_comercial3, 
                    obs_pessoal, 
                    endereco, 
                    numero, 
                    complemento, 
                    bairro, 
                    cidade, 
                    ESTADO, 
                    cep, 
                    telefone_com, 
                    telefone_res, 
                    celular,
                    data_cadastro, 
                    consumidor_final, 
                    ativo                     
                    )
                    VALUES (upper('$nome'), 
                            upper('$apelido'),
                            upper('$tipo'), 
                            '$cpf', 
                            '', 
                            upper('$email'), 
                            $this->codigoVendedor,
                            '',
                            '', 
                            '', 
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            upper('$endereco'),
                            '$numero',
                            upper('$complemento'),
                            upper('$bairro'),
                            upper('$cidade'),
                            upper('$uf'),
                            (SELECT INSERT('$cep', 6, 0, '-')),
                            '$telefone_com',
                            '$telefone_cel',
                            '$telefone_com',
                            now(),
                            'S',
                            'S')";                            
                            if (mysqli_query($this->publico->link, $sql) === TRUE){ 
                          
                                echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                                echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                                echo "<strong> </strong><br> Cliente $nome  -  $cpf   cadastrado! com sucesso ! ";
                                print_r(date('d/m/Y h:i:s'));                    
                                echo '</div>';                                     
                            }else{
                                echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao inserir Cliente "'.$nome.' - '.$cpf.'';   
                                print_r(date('d/m/Y h:i:s'));                    
                                echo '</div>';
                            }
                     
                    
               }

               $this->publico->Desconecta();
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