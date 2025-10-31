<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');//
include(__DIR__.'/database/conexao_publico.php');
include(__DIR__.'/database/conexao_estoque.php'); 
include(__DIR__.'/database/conexao_vendas.php');
include_once(__DIR__.'/utils/receber-transportadora.php');
include_once(__DIR__.'/utils/receber-cliente.php');

class recebePrecode{
    public $curl;    	
 
    public $tabelaprecopadrao = 1 ;
    public $filial = 1 ;
    public $indice; 

    private $token;    
    private $setor;
    private $publico;
    private $vendas;
    private $estoque;

    private $codigoVendedor = 1 ;
    private $codigoTipoRecebimento = 1 ;
    private $formaPagamento= 1;
    private $databaseVendas ;
   
    public function recebe(){
        $tentativas = 0;
		try {
			$this->publico = new CONEXAOPUBLICO();	
            $this->vendas = new CONEXAOVENDAS();
            $this->estoque = new CONEXAOESTOQUE();
            $ini = parse_ini_file(__DIR__ .'/conexao.ini', true);
                if($ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco']) ){
                    $this->tabelaprecopadrao = $ini['conexao']['tabelaPreco']; 
                }
                $this->filial= $ini['conexao']['filial'];        
                $this->databaseVendas = $this->vendas->getBase();
                $this->setor = $ini['conexao']['setor']; 
                $this->token = $ini['conexao']['token']; 
                    if( $ini['conexao']['vendedor_pedido'] && !empty($ini['conexao']['vendedor_pedido'])){
                         $this->codigoVendedor = $ini['conexao']['vendedor_pedido'];
                    }
                    if( $ini['conexao']['tipo_recebimento_pedido'] && !empty($ini['conexao']['tipo_recebimento_pedido'])){
                        $this->codigoTipoRecebimento = $ini['conexao']['tipo_recebimento_pedido'];
                    }

                    if( $ini['conexao']['forma_pagamento'] && !empty($ini['conexao']['forma_pagamento'])){
                        $this->formaPagamento = $ini['conexao']['forma_pagamento'];
                    }


			echo '<div class="card-header alert alert-information"> <h3 style="color: blue;" align="center"> Recebendo Cliente e Pedido '.date('d/m/Y h:i:s');   
            echo '</div>';


            $this->recebePedidos();
            $this->publico->Desconecta();
			$this->vendas->Desconecta();
			$this->estoque->Desconecta();
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
    
    public function recebePedidos(){
                $objReceberTransportadora = new ReceberTransportadora();
                $objReceberCliente = new ReceberCliente();
        $curl = curl_init();
        curl_setopt_array($curl, array(
//         CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/aprovado/",
         CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/status/2",
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
         
                ////
            for ($i = 0; $i < count($result->pedido); $i++){                 
                    $objReceberCliente->cadastrarCliente($result->pedido[$i]);
                }
                ///
            
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
                $uf_cob = $result->pedido[$i]->dadosCliente->dadosEntrega->uf;
                $cnpjTransport = $this->formatCnpjCpf($result-> pedido[$i]->dadosRastreio->CNPJfilial);
                $PedidoMktplace = $result->pedido[$i]->pedidoParceiro;


            // Filial cd � aonde vem o campo do sistema precode com a id da filial dadosRastreio->idFilial
                    $filial_cd = $result->pedido[$i]->dadosRastreio->idCentroDistribuicao;
                  
                $buscaPedido = $this->vendas->Consulta("SELECT * FROM cad_orca co inner join pedido_precode pp on co.cod_site = pp.codigo_pedido_site where pp.codigo_pedido_site = '$codigoPedidoSite'");
                $buscaCliente = $this->publico->Consulta("SELECT * from cad_clie where CPF = '$cpf'");
                $buscaTransport = $this->publico->Consulta("SELECT * from cad_forn where CNPJ = '$cnpjTransport' ");
                
                while($rowTr = mysqli_fetch_array($buscaTransport, MYSQLI_ASSOC)){
                    $codigoTransport = $rowTr['CODIGO'];
                }

                //// se nao encontrar tenta cadastrar e buscar novamente
                if(empty($codigoTransport)){
                    $objReceberTransportadora->receberTransportadora($result->pedido[$i]);
                  $buscaTransport = $this->publico->Consulta("SELECT * from cad_forn where CNPJ = '$cnpjTransport' ");

                   while($rowTr = mysqli_fetch_array($buscaTransport, MYSQLI_ASSOC)){
                    $codigoTransport = $rowTr['CODIGO'];
                 }
                }
                //
                    
                while($row1 = mysqli_fetch_array($buscaCliente, MYSQLI_ASSOC)){
                    $codigoClienteBd = $row1['CODIGO'];
                }       

                //// se nao encontrar cliente, tenta cadastrar e buscar novamente
                   if(empty($codigoClienteBd)){
                      $objReceberCliente->cadastrarCliente($result->pedido[$i]);
                    $buscaCliente = $this->publico->Consulta("SELECT * from cad_clie where CPF = '$cpf'");
                        while($row1 = mysqli_fetch_array($buscaCliente, MYSQLI_ASSOC)){
                                            $codigoClienteBd = $row1['CODIGO'];
                                 }  
                    }
 
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
                                    $this->codigoVendedor,
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
                                    '$this->tabelaprecopadrao',
                                    '0',
                                    $this->formaPagamento,  # forma pagamento
                                    '0.00',
                                    '$codigoTransport',
                                    '0',
                                    '0',
                                    '0.00',
                                    '0',
                                    '$this->setor',
                                    IF('$uf_cob'='PR','I','E'),
                                    'S',
                                    '$this->filial')";    
                                    
                                if (mysqli_query($this->vendas->link, $sql) === TRUE){  
                                    for($p = 0; $p < count($pedidoItens); $p++){
                                        $referenciaLoja = $pedidoItens[$p]->referenciaLoja;
                                        $sku = $pedidoItens[$p]->sku;
                                        $quantidade = $pedidoItens[$p]->quantidade;
                                        $valorUnitario =  $pedidoItens[$p]->valorUnitario;
                                        $valorComDesconto = $pedidoItens[$p]->valorUnitarioLiquido;
                                        $descontoProd = $valorUnitario - $valorComDesconto;
                                    
                                        $buscaCadOrca = $this->vendas->Consulta("SELECT * FROM cad_orca where cod_site = $codigoPedidoSite");
                                        while($row = mysqli_fetch_array($buscaCadOrca, MYSQLI_ASSOC)){
                                            $codigoOrcamento = $row['CODIGO'];                                   
                                        }
                                        

                                           $buscaCusto = $this->publico->Consulta(    " SELECT pc.produto CODIGO, 
                                                                                           if(pc.INDEXADO='S', (pc.ULT_CUSTO*pg.INDICE), pc.ULT_CUSTO) ULT_CUSTO, 
                                                                                                   if(pc.INDEXADO='S', (pc.CUSTO_MEDIO*pg.INDICE), pc.CUSTO_MEDIO) CUSTO_MEDIO FROM   prod_custos pc 
                                                                                                   left outer join cad_prod p on p.codigo = pc.produto
                                                                                                   left outer join   ".$this->databaseVendas.".parametros pg on pg.id =1 
                                                                                                   where  p.outro_cod = '$referenciaLoja'
                                                                                                   group by p.codigo " 
                                                                                                 ); 

                                                                              ///******* Select com validação de custo por filial   
                                                                        // $buscaCusto = $this->publico->Consulta(    " SELECT pc.produto CODIGO, 
                                                                        //                if(pc.INDEXADO='S', (pc.ULT_CUSTO*pg.INDICE), pc.ULT_CUSTO) ULT_CUSTO, 
                                                                        //                        if(pc.INDEXADO='S', (pc.CUSTO_MEDIO*pg.INDICE), pc.CUSTO_MEDIO) CUSTO_MEDIO FROM   prod_custos pc 
                                                                        //                        left outer join cad_prod p on p.codigo = pc.produto
                                                                        //                        left outer join   ".$this->databaseVendas.".parametros pg on pg.id =1 
                                                                        //                        where if( pg.CUSTO_FILIAL = 'S', ( pc.FILIAL = ".$this->filial." ), pc.FILIAL = 0 )
                                                                        //                        And pc.produto = $referenciaLoja"  ); 

                                                                                          
                                        $retorno = mysqli_num_rows($buscaCusto);
                                        if($retorno > 0 ){
                                            while($row = mysqli_fetch_array($buscaCusto, MYSQLI_ASSOC)){
                                                $id_produto_bd = $row['CODIGO'];
                                                $ultimo_custo = $row['ULT_CUSTO'];
                                                $custo_medio = $row['CUSTO_MEDIO'];


                                                  // 
                                                 if(empty($id_produto_bd)){
                                                    $id_produto_bd = 166; 
                                                 }
                                                 if(empty($ultimo_custo)){
                                                     $ultimo_custo =  1;
                                                 }

                                                 if(empty($custo_medio)){
                                                    $custo_medio=1;
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
                                            '$this->tabelaprecopadrao',
                                            '$valor_prod',
                                            '$custo_medio',
                                            '$ultimo_custo',
                                            '$valorFrete',
                                            '$descontoProd')";
                                            
                                            //print_r ($sql);
                                            if (mysqli_query($this->vendas->link, $sql) === TRUE){ 
                                             
                                                  echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                                                    echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                                                    echo "<strong> </strong><br>Produto  $id_produto_bd  inserido no orçamento  $codigoOrcamento. ";
                                                    echo '</div>';                                      
                                            }else{
                                                echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao inserir produto "'.$id_produto_bd.'" no orçamento "'.$codigoOrcamento.'"';   
                                                echo '</div>';
                                                     echo '<div class="mensagem-container mensagem-erro" role="alert">';
                                                    echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
                                                    echo "<strong>Atenção!</strong> " .   $result->mensagem;
                                                    echo "<br><strong> Produto: </strong> Falha ao inserir produto  $id_produto_bd  no orçamento  $codigoOrcamento " ;
                                                    echo '</div>';
                                            } 
                                        }else{
                                                         
                                                echo '<br>';     
                                                  echo '<div class="container">';
                                                    echo '<div class="alert alert-warning " role="alert" >';
                                                    echo ' <h3 style="color:red;" align="center"> <strong>Atenção!</strong> ';
                                                    echo '<br> Não foi encontrado o produto codigo:  '.$sku ;
                                                    echo '<br>  verifique os itens do pedido codigo: '.$PedidoMktplace. ' no marketplace: '. $marketplace      ;
                                                    echo '<br>   verifique os itens do pedido codigo:  '. $codigoPedidoSite.' no precode </h3> ';
                                                      
                                                $resultDeleteOrder =  $this->vendas->Consulta("DELETE FROM cad_orca where CODIGO = '$codigoOrcamento'");
                                                if($resultDeleteOrder == 1){
                                                    echo '<h3 style="color:red;" align="center">  pedido nao registrado no sistema   </h3> ';
                                                }
                                                 echo '</div>';    
                                                 echo '</div>'; 
                                        }
                                                                                 
                                    }
                                    if ($codigoOrcamento > 0){
                                 

                                        $sql = "INSERT INTO par_orca (orcamento, parcela, valor, vencimento, tipo_receb)
                                        VALUES ('$codigoOrcamento',
                                                '1', 
                                                '$valorTotalPed',
                                                ( CURDATE()  ),
                                                 $this->codigoTipoRecebimento                    
                                                )";	

                                        if (mysqli_query($this->vendas->link, $sql) === TRUE){ 
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
        
                                                if (mysqli_query($this->vendas->link, $sql) === TRUE){ 
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
                                    
                                
                                    
                                } else{
                                    echo '<div class="card-header alert alert-danger"> <h3 style="color: red;" align="center"> Falha ao inserir  orçamento  <br> Canal Precode:"'.$dispositivo.'' .$marketplace.'"'; 
                                    echo '</div>';
                                    print_r( $sql);
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

