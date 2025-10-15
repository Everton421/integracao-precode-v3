<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');//
include_once('conexao_publico_rec.php');
include_once('conexao_estoque_rec.php'); 
include_once('conexao_vendas_rec.php');

class recebePrecode{
    public $curl;    	
  //  public $key = 'a4a08c55ac9a2a0ba2d0b99a6813db35f4a7a45431d1ecab29fbe6f68be5634900cd1203bcfcf1df108a8848650876c4'; //chave token fixa
    // token syma
  //public $token = 'Basic dng0c29BenNKek9qSUFHQ0c6';
  
    public $tabelaprecopadrao = 4;
    public $indice; 
   
    public function recebe(){
        $tentativas = 0;
		try {
			$this->Obj_Conexao_publico = new CONEXAOPUBLICO();	
            $this->Obj_Conexao_vendas = new CONEXAOVENDAS();
            $this->Obj_Conexao_estoque = new CONEXAOESTOQUE();
			echo '<div class="card-header alert alert-information"> <h3 style="color: blue;" align="center"> Recebendo Cliente e Pedido '.date('d/m/Y h:i:s');   
            echo '</div>';
	        $this->cadastraCliente();
            $this->recebePedidos();
            $this->Obj_Conexao_publico->Desconecta();
			$this->Obj_Conexao_vendas->Desconecta();
			echo '<div class="card-header alert alert-information"> <h3 style="color: blue;" align="center"> Fim do Recebimento de Cliente e Pedido '.date('d/m/Y h:i:s');   
            echo '</div>';
		} catch (\Exception $e) {
			var_dump("ERRO:".$e->getMessage());
		} finally {
			
		}
        

    }
    function formatCnpjCpf($cpf){

    $cnpj_cpf = preg_replace("/\D/", '', $cpf);
    
    if (strlen($cnpj_cpf) === 11) {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
    } 
    
    return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }    
    public function cadastraCliente(){       
        //Recupera lista de pedidos aprovados. (pedidos prontos para serem faturados).
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/aprovado/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: ".$this->token
        ),
        ));
        $response = curl_exec($curl);
        $result = json_decode($response);       
        if(!empty($result)){
            for ($i = 0; $i < count($result->pedido); $i++) {
                $cpf = $this->formatCnpjCpf($result->pedido[$i]->dadosCliente->cpfCnpj);
                $tipo = $result->pedido[$i]->dadosCliente->tipo;
                $nome = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->nomeRazao)));
                $apelido = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->fantasia)));
                $sexo = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->sexo)));
                $data_nasc = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->dataNascimento)));
                $email = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->email)));
                $endereco = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->dadosEntrega->endereco)));
                $numero = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->dadosEntrega->numero)));
                $complemento = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->dadosEntrega->complemento)));                
                $bairro = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->dadosEntrega->bairro)));
                $cidade = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->dadosEntrega->cidade)));
                $uf = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->dadosEntrega->uf)));
                $cep = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->dadosEntrega->cep)));
                $telefone_res = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->telefones->residencial)));
                $telefone_com = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->telefones->comercial)));
                $telefone_cel = addslashes(strtoupper(utf8_decode($result->pedido[$i]->dadosCliente->telefones->celular)));
                
         


                echo "<main class='login-form'>";
                echo '<div class="cotainer">';
                echo '<div class="row justify-content-center">';
                echo '<div class="col-md-8">';
                echo '<div class="card">';
                echo '<div class="card-header alert alert-info" align="center"><h3 style="color: #008080;""><b>Cadastrando clientes</b></h3>';
                echo '</div>'; 
                
                $pega_dados = $this->Obj_Conexao_publico->Consulta("SELECT * from cad_clie where CPF = '$cpf'");

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
                    if(!empty($telefone_cel)){
                        $atualiza = $atualiza." ,celular = '$telefone_cel'";
                    }
                    if(!empty($uf)){
                        $atualiza = $atualiza." ,estado = '$uf'";
                    } 
                    $sql = "UPDATE cad_clie set $atualiza where CODIGO = '$cpf'";
                    
                    echo ' Atualizado com sucesso !<br>Cliente:"'.$nome.'"<br>CPF: '.$cpf.'';   
                    echo '</div>';                    
                    echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';                    
                    print_r(date('d/m/Y h:i:s'));                    
                    echo '</div></b>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>'; 
                    echo "</main>"; 

               }else{
                $sql = "INSERT INTO cad_clie (
                    nome,
                    apelido,		
                    fis_jur, 
                    cpf, 
                    rg, 
                    email_fiscal, 
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
                            '$telefone_cel',
                            '$telefone_com',
                            now(),
                            'S',
                            'S')";                            
                            if (mysqli_query($this->Obj_Conexao_publico->link, $sql) === TRUE){ 
                                echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> Cliente "'.$nome.' - '.$cpf.' cadastrado!"';   
                                echo '</div>';                                      
                            }else{
                                echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao inserir Cliente "'.$nome.' - '.$cpf.'';   
                                echo '</div>';
                            }

                    echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
                    print_r(date('d/m/Y h:i:s'));                    
                    echo '</div></b>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>'; 
                    echo "</main>";   
               }
            }

        } else {
			echo '<div class="card-header alert alert-information"> <h3 style="color: blue;" align="center"> Nenhum Cliente Novo </div>';
		}
        
    }
    public function recebePedidos(){
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/aprovado/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: ".$this->token
        ),
        ));
        $response = curl_exec($curl);
        $result = json_decode($response);    
        curl_close($curl);  
     
        if(!empty($result)){  
         
             echo "<br>";
                echo'Resultado consulta api :';
                print_r($result);
            echo'<br>';
            
            for ($i = 0; $i < count($result->pedido); $i++){                 
                $codigoPedidoSite = $result->pedido[$i]->codigoPedido;
                $valorTotalCompra = $result->pedido[$i]->valorTotalCompra;
                $totalGeral = $valorTotalCompra;
                $pedidoItens = $result->pedido[$i]->itens;
                $valorTotalPed = $result->pedido[$i]->valorTotalCompra;
                $valorFrete = $result->pedido[$i]->valorFrete;
                $cpf = $this->formatCnpjCpf($result->pedido[$i]->dadosCliente->cpfCnpj);
                $pedidoStatus = $result->pedido[$i]->statusAtual; 
                $marketplace = $result->pedido[$i]->nomeAfiliado;  
                $dispositivo = $result->pedido[$i]->dispositivo; 
                $valorDesconto = $result->pedido[$i]->valorTotalDesconto; 
                $valorTotalProd = $valorTotalCompra - $valorFrete;  

                $PedidoMktplace = $result->pedido[$i]->pedidoParceiro;

                            echo'<br>';
                            echo ' codigoMarketplace :', $PedidoMktplace;
                            echo'<br>';

            // Filial cd � aonde vem o campo do sistema precode com a id da filial dadosRastreio->idFilial
                    $filial_cd = $result->pedido[$i]->dadosRastreio->idCentroDistribuicao;
                    if($filial_cd == 1){
                        $setor = 421; // id setor
                        $filial = 2; // id da filial no intersig
                        $tabela = 4; // tabela de pre�o no intersig
                    }else if($filial_cd == 2){
                        $setor = 469; // setor intersig
                        $filial = 7; // filial id no intersig
                        $tabela = 11; // tabela de pre�os no intersig
                    }       
                $buscaPedido = $this->Obj_Conexao_vendas->Consulta("SELECT * FROM cad_orca co inner join pedido_precode pp on co.cod_site = pp.codigo_pedido_site where pp.codigo_pedido_site = '$codigoPedidoSite'");
                $buscaCliente = $this->Obj_Conexao_publico->Consulta("SELECT * from cad_clie where CPF = '$cpf'");                
                while($row1 = mysqli_fetch_array($buscaCliente, MYSQLI_ASSOC)){
                    $codigoClienteBd = $row1['CODIGO'];
                }         
                echo "<main class='login-form'>";
                echo '<div class="cotainer">';
                echo '<div class="row justify-content-center">';
                echo '<div class="col-md-8">';
                echo '<div class="card">';
                echo '<div class="card-header alert alert-info" align="center"><h3 style="color: #008080;""><b>Aguardando dados do pedido "'.$codigoPedidoSite.'"</b></h3>';
                echo '</div>'; 
                if(mysqli_num_rows($buscaPedido) > 0){
                    echo '<div class="card-header alert alert-warning"> <h3 style="color: #B8860B;" align="center"> Este pedido já foi cadastrado no ERP';   
                    echo '</div>';
                    echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
                    print_r(date('d/m/Y h:i:s'));                    
                    echo '</div></b>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>'; 
                    echo "</main>";  
                }else{
                    $sql = "INSERT INTO cad_orca (
                        status, 
                        tipo,
                        cod_site,
                        PEDIDO_MKTPLACE,
                        cliente, 
                        total_produtos, 
                        total_geral, 
                        data_pedido, 
                        DESC_PROD,
                        valor_frete, 
                        situacao, 
                        data_cadastro, 
                        hora_cadastro, 
                        data_inicio, 
                        hora_inicio, 
                        vendedor, 
                        contato, 
                        observacoes, 
                        observacoes2,  
                        NF_ENT_OS, 
                        RECEPTOR, 
                        VAL_PROD_MANIP, 
                        PERC_PROD_MANIP, 
                        PERC_SERV_MANIP, 
                        REVISAO_COMPLETA, 
                        DESTACAR, TABELA, 
                        QTDE_PARCELAS, 
                        FORMA_PAGAMENTO, 
                        ALIQ_ISSQN,
                        TRANSPORTADORA, 
                        OUTRAS_DESPESAS, 
                        PESO_LIQUIDO, 
                        BASE_ICMS_UF_DEST, 
                        MIDIA,
                        SETOR,
                        OPERACAO,
                        PARA_CONSUMO,
                        FILIAL
                        )
                            VALUES ('0',
                                    '2',
                                    '$codigoPedidoSite',
                                    '$PedidoMktplace',
                                    '$codigoClienteBd', 			  	          
                                    '$valorTotalProd',
                                    '$totalGeral',                
                                    now(),
                                    '$valorDesconto',
                                    '$valorFrete',
                                    'AI',
                                    now(),
                                    now(),
                                    now(),
                                    now(),
                                    '179',
                                    upper('PRECODE - $marketplace - $pedidoStatus'),
                                    '',
                                    '',
                                    '',
                                    '',
                                    '$valorTotalProd',
                                    '100',
                                    '100',
                                    'N',
                                    'N',
                                    '$tabela',
                                    '0',
                                    '2',
                                    '0.00',
                                    '0',
                                    '0',
                                    '0',
                                    '0.00',
                                    '51',
                                    '$setor',
                                    IF('$uf_cob'='PR','I','E'),
                                    'S',
                                    '$filial')";                                     
                                    
                                if (mysqli_query($this->Obj_Conexao_vendas->link, $sql) === TRUE){  
                                    for($p = 0; $p < count($pedidoItens); $p++){
                                        $referenciaLoja = $pedidoItens[$p]->referenciaLoja;
                                        $quantidade = $pedidoItens[$p]->quantidade;
                                        $valorUnitario =  $pedidoItens[$p]->valorUnitario;
                                        $valorComDesconto = $pedidoItens[$p]->valorUnitarioLiquido;
                                        $descontoProd = $valorUnitario - $valorComDesconto;
                                    
                                        $buscaCadOrca = $this->Obj_Conexao_vendas->Consulta("SELECT * FROM cad_orca where cod_site = $codigoPedidoSite");
                                        while($row = mysqli_fetch_array($buscaCadOrca, MYSQLI_ASSOC)){
                                            $codigoOrcamento = $row['CODIGO'];                                   
                                        }
                                        $buscaCusto = $this->Obj_Conexao_publico->Consulta("SELECT pc.produto CODIGO, if(pc.INDEXADO='S', (pc.ULT_CUSTO*pg.INDICE), pc.ULT_CUSTO) ULT_CUSTO, 
                                                                                            if(pc.INDEXADO='S', (pc.CUSTO_MEDIO*pg.INDICE), pc.CUSTO_MEDIO) CUSTO_MEDIO FROM prod_custos pc 
                                                                                            left outer join cad_prod p on p.codigo = pc.produto
                                                                                            left outer join mesquita_vendas.parametros pg on pg.id =1 
                                                                                            where pc.filial = $filial And pc.produto = $referenciaLoja");    
                                        $retorno = mysqli_num_rows($buscaCusto);
                                        if($retorno > 0 ){
                                            while($row = mysqli_fetch_array($buscaCusto, MYSQLI_ASSOC)){
                                                $id_produto_bd = $row['CODIGO'];
                                                $ultimo_custo = $row['ULT_CUSTO'];
                                                $custo_medio = $row['CUSTO_MEDIO'];
                                            }
                                        }
                                        $valor_prod = $valorUnitario * $quantidade;
                                        $sql = "INSERT INTO pro_orca (orcamento, sequencia, produto, grade, padronizado, complemento, unidade, item_unid, just_ipi, just_icms, just_subst, quantidade, unitario, tabela, preco_tabela, CUSTO_MEDIO, ULT_CUSTO, FRETE, DESCONTO)
                                            VALUES ('$codigoOrcamento',
                                            $p + 1,
                                            '$id_produto_bd',               
                                            '0',             
                                            '0',    
                                            '',           
                                            'UND',            
                                            '1',
                                            '0',
                                            '0',
                                            '0',
                                            '$quantidade',
                                            '$valorUnitario',
                                            '$tabela',
                                            '$valor_prod',
                                            '$custo_medio',
                                            '$ultimo_custo',
                                            '$valorFrete',
                                            '$descontoProd')";
                                            
                                            //print_r ($sql);
                                            if (mysqli_query($this->Obj_Conexao_vendas->link, $sql) === TRUE){ 
                                                echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> Produto "'.$id_produto_bd.'" inserido no orçamento "'.$codigoOrcamento.'"';   
                                                echo '</div>';                                        
                                            }else{
                                                echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao inserir produto "'.$id_produto_bd.'" no orçamento "'.$codigoOrcamento.'"';   
                                                echo '</div>';
                                            }                                            
                                    }
                                    if ($codigoOrcamento > 0){
                                        /*
                                            Prazo para a venda e forma de pagamento
                                        */
                                        if($marketplace == 'B2W V2'){
                                            $day = 30;
                                        }elseif($marketplace == 'Mercado Livre'){
                                            $day = 30;
                                        }elseif($marketplace == 'ViaVarejo'){
                                            $day = 30;
                                        }elseif($marketplace == 'Magazine Luiza'){
                                            $day = 30;                                           
                                        }else{
                                            $day = 30;
                                        }
                                        $sql = "INSERT INTO par_orca (orcamento, parcela, valor, vencimento, tipo_receb)
                                        VALUES ('$codigoOrcamento',
                                                '1', 
                                                '$valorTotalPed',
                                                (SELECT DATE_ADD(CURDATE(), INTERVAL $day DAY)),
                                                '101'                    
                                                )";	

                                        if (mysqli_query($this->Obj_Conexao_vendas->link, $sql) === TRUE){ 
                                            echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> Forma de pagamento inserida no orçamento "'.$codigoOrcamento.'"';   
                                            echo '</div>';     
                                            $curl = curl_init();
                                            curl_setopt_array($curl, array(
                                            CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/aceite",
                                            CURLOPT_RETURNTRANSFER => true,
                                            CURLOPT_ENCODING => "",
                                            CURLOPT_MAXREDIRS => 10,
                                            CURLOPT_TIMEOUT => 0,
                                            CURLOPT_FOLLOWLOCATION => true,
                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                            CURLOPT_CUSTOMREQUEST => "PUT",
                                            CURLOPT_POSTFIELDS =>"
                                            {
                                                \r\n\"pedido\": 
                                                [
                                                    \r\n
                                                    {
                                                        \r\n\"codigoPedido\": $codigoPedidoSite,
                                                        \r\n\"numeroPedidoERP\": $codigoOrcamento,
                                                        \r\n\"numeroFilialFatura\": $filial_cd,
                                                        \r\n\"numeroFilialSaldo\": $filial_cd\r\n
                                                    }
                                                    \r\n
                                                ]
                                                    
                                                \r\n
                                            }",
                                            CURLOPT_HTTPHEADER => array(
                                                "Authorization: ".$this->token
                                            ),
                                            ));
                                            $response = curl_exec($curl);
                                            curl_close($curl);
                                            //echo $response;
                                            if(!empty($response)){
                                                echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center">Aceite confirmado!';   
                                                echo '</div>'; 
                                                echo '<br><br>';
                                                print_r("
                                                {
                                                    \r\n\"pedido\": 
                                                    [
                                                        \r\n
                                                        {
                                                            \r\n\"codigoPedido\": $codigoPedidoSite,
                                                            \r\n\"numeroPedidoERP\": $codigoOrcamento,
                                                            \r\n\"numeroFilialFatura\": $filial_cd,
                                                            \r\n\"numeroFilialSaldo\": $filial_cd\r\n
                                                        }
                                                        \r\n
                                                    ]
                                                        
                                                    \r\n
                                                }");
                                                echo '<br><br>';


                                                $data_atual = date('Y-m-d h:i:s');
                                                $sql = "INSERT INTO pedido_precode (codigo_pedido_site, codigo_pedido_bd, data_inclusao, situacao)
                                                VALUES ('$codigoPedidoSite',
                                                        '$codigoOrcamento',               
                                                        '$data_atual',             
                                                        '$pedidoStatus')";
        
                                                if (mysqli_query($this->Obj_Conexao_vendas->link, $sql) === TRUE){ 
                                                    echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> Adicionando orçamento na tabela Precode "'.$codigoOrcamento.'"';   
                                                    echo '</div>';      
                                                }else{
                                                    echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao inserir orçamnto "'.$codigoOrcamento.'" na tabela Precode';  
                                                    echo '</div>';
                                                } 
                                            }else{
                                                echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao confirmar o aceite!';  
                                                echo '</div>';
                                            }
                                        }else{
                                            echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao inserir forma de pagamento no orçamento "'.$codigoOrcamento.'"';  
                                            echo '</div>';
                                        } 
                                    }
                                    
                                    echo '<div class="card-header alert alert-success"> <h3 style="color: green;" align="center"> Pedido nº "'.$codigoOrcamento.'" inserido com sucesso!<br> Dispositivo: "'.$dispositivo.' -' .$marketplace.'"';   
                                    echo '</div>';
                                    echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
                                    print_r(date('d/m/Y h:i:s'));                    
                                    echo '</div></b>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>'; 
                                    echo "</main>";  
                                    
                                } else{
                                    echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao inserir  orçamento"'.$codigoOrcamento.'" <br> Canal Precode:"'.$dispositivo.'' .$marketplace.'"'; 
                                    echo '</div>';
                                    echo '<div class="card-header alert alert-info" align="center"><b style="color: #008080;">';
                                    print_r(date('d/m/Y h:i:s'));                    
                                    echo '</div></b>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>'; 
                                    echo "</main>";                                        
                                }   
                                    
                                           
                }                 
            }            
        } else {
			echo '<div class="card-header alert alert-information"> <h3 style="color: blue;" align="center"> Nenhum Pedido Novo </div>';
			
		}
    }     
} 
?>

