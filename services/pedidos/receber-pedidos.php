<?php
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 
date_default_timezone_set('America/Sao_Paulo');//
include(__DIR__.'/../../database/conexao_publico.php');
include(__DIR__.'/../../database/conexao_estoque.php'); 
include(__DIR__.'/../../database/conexao_vendas.php');
include(__DIR__.'/../../database/conexao_integracao.php');
include_once(__DIR__.'/receber-transportadora.php');
include_once(__DIR__.'/receber-cliente.php');
include_once(__DIR__."/../../utils/registrar-logs.php");
include_once(__DIR__.'/verificar-estoque-pedido.php');
include_once(__DIR__ . '/../../utils/pedido-sem-estoque.php');

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
    private $integracao;
    private $verificarEstoquePedido; 

    private $codigoVendedor = 1 ;
    private $codigoTipoRecebimento = 1 ;
    private $formaPagamento= 1;
    private $databaseVendas ;
    private $databaseIntegracao;
    private $pedidoSemEstoque;
    
    public function recebe(){
        $tentativas = 0;
		try {
         $this->pedidoSemEstoque = new PedidoSemEstoque();

			$this->publico = new CONEXAOPUBLICO();	
            $this->vendas = new CONEXAOVENDAS();
            $this->estoque = new CONEXAOESTOQUE();
            $this->integracao = new CONEXAOINTEGRACAO();
            $this->databaseIntegracao = $this->integracao->getBase(); 
            $this->verificarEstoquePedido = new VerificarEstoquePedido();

            $ini = parse_ini_file(__DIR__ .'/../../conexao.ini', true);
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

            // --- MELHORIA VISUAL: CSS INLINE PARA LOGS ---
            echo '<style>
                .log-box { border-radius: 5px; margin-bottom: 15px; padding: 15px; border: 1px solid transparent; }
                .log-info { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }
                .log-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
                .log-warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; }
                .log-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
                .log-code { background: #f8f9fa; border: 1px solid #ddd; padding: 10px; font-family: monospace; font-size: 0.9em; border-radius: 4px; overflow-x: auto; color: #333; margin-top: 10px;}
            </style>';

			echo '<div class="log-box log-info text-center"> 
                    <h3><i class="fas fa-sync-alt"></i> Recebendo Cliente e Pedido</h3>
                    <small>Início: '.date('d/m/Y H:i:s').'</small>
                  </div>';


            $this->recebePedidos();
            $this->publico->Desconecta();
			$this->vendas->Desconecta();
			$this->estoque->Desconecta();
             $this->integracao->Desconecta();


			echo '<div class="log-box log-info text-center"> 
                    <h3><i class="fas fa-check-double"></i> Fim do Recebimento</h3>
                    <small>Término: '.date('d/m/Y H:i:s').'</small>
                  </div>';

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
            set_time_limit(0);
                 

                $objReceberTransportadora = new ReceberTransportadora();
                $objReceberCliente = new ReceberCliente();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        
        //CURLOPT_URL => "https://www.replicade.com.br/api/v1/erp/nf/",
          
       
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

                for ($i = 0; $i < count($result->pedido); $i++){  
                       $objReceberCliente->cadastrarCliente($result->pedido[$i]);
                }

                for ($i = 0; $i < count($result->pedido); $i++){  
                    $codigoPedidoSite = $result->pedido[$i]->codigoPedido;


                    $pedidoItens = $result->pedido[$i]->itens;


                    $valorTotalCompra = $result->pedido[$i]->valorTotalCompra;
                    $totalGeral = $valorTotalCompra;
                    $valorTotalPed = $result->pedido[$i]->valorTotalCompra;
                    
                     $valorFrete = $result->pedido[$i]->valorFrete;

                    $cpf = $this->formatCnpjCpf($result->pedido[$i]->dadosCliente->cpfCnpj);
                    $pedidoStatus = $result->pedido[$i]->statusAtual; 
                    $marketplace = $result->pedido[$i]->nomeAfiliado;  
                    $dispositivo = $result->pedido[$i]->dispositivo; 
                    $valorDesconto = $result->pedido[$i]->valorTotalDesconto; 
                    $valorTotalProd = $valorTotalCompra - $valorFrete;

                    $val_total_prod_sem_desc = $valorTotalProd + $valorDesconto; //total dos produtos - frete - descontos; 
                    
                    $uf_cob = $result->pedido[$i]->dadosCliente->dadosEntrega->uf;
                    $cnpjTransport = $this->formatCnpjCpf($result-> pedido[$i]->dadosRastreio->CNPJfilial);
                    $PedidoMktplace = $result->pedido[$i]->pedidoParceiro;
    
                        $resultVerifyEstoque=  $this->verificarEstoquePedido->verify(
                                $this->publico,
                                $this->vendas,
                                $this->estoque,
                                $pedidoItens,
                                $codigoPedidoSite
                                );

                                $json = json_decode($resultVerifyEstoque);
                                print_r($json->message);
                                // pula para o proximo pedido caso nao tiver estoque
                            if(!$json->success){
                              $resultPutPedidoSemEstoque=  $this->pedidoSemEstoque->put($codigoPedidoSite);
                              echo '<br>';
                              print_r($resultPutPedidoSemEstoque);
                              echo '<br>';

                              continue;
                            }



                     

                    $filial_cd = $result->pedido[$i]->dadosRastreio->idCentroDistribuicao;
                    
                    $buscaPedido = $this->vendas->Consulta("SELECT * FROM cad_orca co inner join ".$this->databaseIntegracao.".pedido_precode pp on co.cod_site = pp.codigo_pedido_site where pp.codigo_pedido_site = '$codigoPedidoSite'");
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

                              $status = 0;
                        switch ( strtolower($marketplace) ){
                            case 'shopee':
                                $status = 1;
                                break;

                            case 'mercado livre':
                                $status = 2;
                                break;
                                default: 
                                $status = 0;
                        }
    
                    if(mysqli_num_rows($buscaPedido) > 0){
                        // --- MELHORIA VISUAL ---
                        echo '<div class="log-box log-warning text-center">';
                        echo '<h3><i class="fas fa-exclamation-triangle"></i> Pedido já cadastrado no ERP</h3>';
                        echo '<p>Site ID: <strong>' . $codigoPedidoSite . '</strong></p>';
                        echo '<small>' . date('d/m/Y H:i:s') . '</small>';
                        echo '</div>'; 
                        // Removido o echo </main> solto e excesso de divs
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
                            sit_separ,
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
                                VALUES ($status,
                                        '2',
                                        '$codigoPedidoSite',
                                        '$PedidoMktplace',
                                        '$codigoClienteBd', 			  	          
                                        '$val_total_prod_sem_desc',
                                        '$totalGeral',                
                                        now(),
                                        '$valorDesconto',
                                        '$valorFrete',
                                        'AI',
                                        'I',
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
                                        '1',
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
                                        Logs::registrar(
                                                    $this->integracao,
                                                    $this->databaseIntegracao,
                                                    'sucesso',
                                                    'registrar pedido',
                                                    "$sql",
                                                        '',
                                                    "pedido  registrado no cad_orca [ codigo precode: $codigoPedidoSite ] "
                                                    );
                                        
                                    if (mysqli_query($this->vendas->link, $sql) === TRUE){  
                                        // registrando log   
                                        Logs::registrar(
                                                    $this->integracao,
                                                    $this->databaseIntegracao,
                                                    'sucesso',
                                                    'registrar pedido',
                                                    "$sql",
                                                        '',
                                                    "pedido  registrado no cad_orca [ codigo precode: $codigoPedidoSite ] "
                                                    );
                                        
                                    $buscaCadOrca = $this->vendas->Consulta("SELECT * FROM cad_orca where cod_site = $codigoPedidoSite");
                                            while($row = mysqli_fetch_array($buscaCadOrca, MYSQLI_ASSOC)){
                                                $codigoOrcamento = $row['CODIGO'];                                   
                                            }

                                        $qtd_itens = count($pedidoItens);
                                            $freteAcumulado = 0; // Inicializa o acumulador

                                            for ($p = 0; $p < $qtd_itens; $p++) {
                                                 $sequencia = $p + 1;

                                                 $referenciaLoja = $pedidoItens[$p]->referenciaLoja;
                                                 $sku = $pedidoItens[$p]->sku;


                                                 $sql_busca_custo = "";
                                                 $sentence = 'KIT-';

                                                // verifica se o produto é um kit 
                                                    $productKit = str_contains($referenciaLoja, $sentence);
                                                    if ($productKit) {
                                                        $sql_busca_custo = "SELECT 
                                                                            pc.PRODUTO as CODIGO, 
                                                                            COALESCE(pc.ULT_CUSTO, 0 ) as ULT_CUSTO,
                                                                            COALESCE(pc.CUSTO_MEDIO, 0 ) as CUSTO_MEDIO,
                                                                            pt.PRECO,
                                                                            ip.QUANTIDADE
                                                                            FROM " . $this->databaseIntegracao . ".padronizados as pp  
                                                                            join cad_padr cpd on cpd.CODIGO = pp.CODIGO_PADR
                                                                            join ite_padr ip on ip.PADRONIZADO = cpd.CODIGO
                                                                        LEFT JOIN prod_custos pc ON ip.PROD_SERV = pc.PRODUTO
                                                                        LEFT JOIN prod_tabprecos pt on pt.PRODUTO = ip.PROD_SERV
                                                                        LEFT JOIN tab_precos tp on tp.CODIGO = pt.TABELA
                                                                            WHERE ( pt.TABELA = " . $this->tabelaprecopadrao . " OR tp.PADRAO = 'S') 
                                                                               AND cpd.PROD_SERV = 'P' 
                                                                                AND pp.CODIGO_KIT ='$referenciaLoja' ;";
                                                    } else {
                                                        $sql_busca_custo = "SELECT  
                                                                                p.CODIGO, 
                                                                                COALESCE(pc.ULT_CUSTO, 0 ) as ULT_CUSTO,
                                                                                COALESCE(pc.CUSTO_MEDIO, 0 ) as CUSTO_MEDIO 
                                                                            FROM cad_prod p 
                                                                            LEFT JOIN prod_custos pc ON p.CODIGO = pc.PRODUTO
                                                                            WHERE p.CODIGO = '$referenciaLoja'
                                                                            GROUP BY p.codigo;";
                                                    }
                                
                                                 $buscaCusto = $this->publico->Consulta($sql_busca_custo );

                                                 $retorno = mysqli_num_rows($buscaCusto);
                                                
                                                 if ($retorno > 0) {
                                                    // Inicializa variáveis para evitar erro de "undefined" caso o while falhe
                                                    $id_produto_bd = '';
                                                    $ultimo_custo = 0;
                                                    $custo_medio = 0;
                                                        
                                                    while ($row = mysqli_fetch_array($buscaCusto, MYSQLI_ASSOC)) {
                                                            $id_produto_bd = $row['CODIGO'];
                                                            $ultimo_custo = $row['ULT_CUSTO'];
                                                            $custo_medio = $row['CUSTO_MEDIO'];
                                                            $unitario_liquido = $pedidoItens[$p]->valorUnitarioLiquido; // Valor unitário liquido do produto, valor já com a dedução dos descontos.
                                                                    // valores  do produto do pedido
                                                                    $quantidade = $pedidoItens[$p]->quantidade;
                                                                    $valorUnitario = $pedidoItens[$p]->valorUnitario;
                                                                    $valorComDesconto = $pedidoItens[$p]->valorUnitarioLiquido;
                                                                    $descontoProd = $valorUnitario - $valorComDesconto;
                                                                    $valor_prod = $valorUnitario * $quantidade;
                                                                
                                                                    $percent_desc =   $descontoProd / $valor_prod ; //percentual de desconto

                                                                    
                                                                    $total_item_kit = 0;
                                                                    $frete_item_kit = $valorFrete; 

                                                                    if($productKit){
                                                                        $valorUnitario = $row['PRECO'];
                                                                        $percent_desc_unit =   $percent_desc / $row['QUANTIDADE'];    
                                                                        $quantidade = $quantidade  * $row['QUANTIDADE']; //quantidade a ser aplicada. Quantidade do kit * quantidade de um dos itens do kit.
                                                                        $valor_prod = $quantidade * $valorUnitario;   //valor total sem desconto        
                                                                        $descontoProd = $percent_desc_unit * $valor_prod;
                                                                        $valorComDesconto =   $total_item_kit - $descontoProd ;
                                                                        //  $frete_item_kit =  $valorFrete / $row['QUANTIDADE'];     
                                                                        }

                                                                    // --- CÁLCULO DO FRETE (Rateio) ---
                                                                    $frete_item = 0;

                                                                    if ($valorTotalProd > 0) {
                                                                        // Verifica se é o ÚLTIMO item do loop
                                                                        if ($p == ($qtd_itens - 1)) {
                                                                            // O último item pega a diferença (Total Frete - O que já foi distribuído)
                                                                            // Isso corrige o problema de sobrar ou faltar 1 centavo
                                                                            $frete_item = $frete_item_kit - $freteAcumulado;
                                                                        } else {
                                                                            // Calcula proporcionalmente para os itens do meio
                                                                            $fator = $valor_prod / $valorTotalProd;
                                                                            $frete_item = round($fator * $valorFrete, 2);
                                                                            
                                                                            // Soma ao acumulado para controle
                                                                            $freteAcumulado += $frete_item;
                                                                        }
                                                                    }

                                                            // Formata para garantir 2 casas decimais no banco (opcional, mas recomendado)
                                                            $frete_final_sql = number_format($frete_item, 2, '.', ''); 

                                                                // Importante: Assegure que as variáveis float usem ponto decimal no SQL
                                                           $sql = "INSERT INTO pro_orca (
                                                                    orcamento, sequencia, produto, grade, padronizado, complemento, unidade, item_unid, 
                                                                    just_ipi, just_icms, just_subst, qtde_separada, quantidade, unitario, tabela, 
                                                                    preco_tabela, total_liq, CUSTO_MEDIO, ULT_CUSTO, FRETE, DESCONTO
                                                                ) VALUES (
                                                                    '$codigoOrcamento',
                                                                    '$sequencia',
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
                                                                    '$quantidade',
                                                                    '$valorUnitario',
                                                                    '$this->tabelaprecopadrao',
                                                                    '$valor_prod',
                                                                    '$unitario_liquido',
                                                                    '$custo_medio',
                                                                    '$ultimo_custo',
                                                                    '$frete_final_sql', 
                                                                    '$descontoProd'
                                                                )";

                                                          if (mysqli_query($this->vendas->link, $sql) === TRUE) { 
                                                          $sequencia++;

                                                            Logs::registrar(
                                                                $this->integracao,
                                                                $this->databaseIntegracao,
                                                                'sucesso',
                                                                'registrar produto pedido ',
                                                                "$sql",
                                                                '',
                                                                "Produto [ $id_produto_bd ] registrado na tabela pro_orca "
                                                            );
                                                            
                                                            echo '<div class="log-box log-success">';
                                                            echo '<i class="fas fa-check-circle"></i> Produto <strong>'.$id_produto_bd.'</strong> inserido no orçamento <strong>'.$codigoOrcamento.'</strong>. (Frete rateado: R$ '.$frete_final_sql.')';
                                                            echo '</div>';                                      
                                                         } else {
                                                            // Se falhar o insert
                                                            $msgErro = isset($result->mensagem) ? $result->mensagem : mysqli_error($this->vendas->link);
                                                            
                                                            echo '<div class="log-box log-danger">';
                                                            echo '<h4 class="text-danger"><i class="fas fa-times-circle"></i> Falha ao inserir produto</h4>';
                                                            echo '<p>Produto: <strong>'.$id_produto_bd.'</strong> | Orçamento: <strong>'.$codigoOrcamento.'</strong></p>';
                                                            echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ' . $msgErro . '</div>';
                                                            echo '</div>';
                                                        } 
                                                }

                                            
                                                 } else {
                                                    // Produto não encontrado
                                                    Logs::registrar(
                                                        $this->integracao,
                                                        $this->databaseIntegracao,
                                                        'erro',
                                                        'registrar produto pedido ',
                                                        "",
                                                        '',
                                                        "Não foi encontrado o produto codigo: $sku, verifique os itens do pedido codigo: $PedidoMktplace no marketplace: $marketplace"
                                                    );
                                                    $sql = "UPDATE cad_orca SET DESTACAR = 'S' WHERE CODIGO = $codigoOrcamento";
                                                    $this->vendas->Consulta($sql);
                                                 }
                                            }
                                        if ($codigoOrcamento > 0){
                                    
                                            $sql = "INSERT INTO par_orca (orcamento, parcela, valor, vencimento, tipo_receb)
                                            VALUES ('$codigoOrcamento',
                                                    '1', 
                                                    '$valorTotalPed',
                                                    DATE_ADD(CURDATE(), INTERVAL 1 DAY),
                                                    $this->codigoTipoRecebimento                    
                                                    )";	
    
                                        if (mysqli_query($this->vendas->link, $sql) === TRUE){ 
                                                            Logs::registrar(
                                                                        $this->integracao,
                                                                        $this->databaseIntegracao,
                                                                        'sucesso',
                                                                        'registrar parcela do pedido ',
                                                                        "$sql",
                                                                            '',
                                                                        "parcela do pedido: [ $codigoOrcamento ]  registrada! "
                                                                        );
                                                            // --- MELHORIA VISUAL ---
                                                            echo '<div class="log-box log-success">';
                                                            echo '<h4><i class="fas fa-money-check-alt"></i> Forma de pagamento inserida no orçamento: '.$codigoOrcamento.'</h4>';
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
                                                                
                                                                // --- MELHORIA VISUAL ---
                                                                echo '<div class="log-box log-success">';
                                                                echo '<h3 class="text-success text-center"><i class="fas fa-thumbs-up"></i> Aceite confirmado!</h3>';
                                                                
                                                                // Formatando o JSON string para ficar legível
                                                                $jsonDebug = "
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
                                                                }";
                                                                echo '<div class="log-code"><pre>' . htmlspecialchars($jsonDebug) . '</pre></div>';
                                                                echo '</div>';
            
        
                                                                $data_atual = date('Y-m-d h:i:s');
                                                                $sql = "INSERT INTO pedido_precode (codigo_pedido_site, codigo_pedido_bd, data_inclusao, situacao)
                                                                VALUES ('$codigoPedidoSite',
                                                                        '$codigoOrcamento',               
                                                                        '$data_atual',             
                                                                        '$pedidoStatus')";
                        
                                                                if (mysqli_query($this->integracao->link, $sql) === TRUE){
                                                                    Logs::registrar(
                                                                        $this->integracao,
                                                                        $this->databaseIntegracao,
                                                                        'sucesso',
                                                                        'envio do aceite para precode ',
                                                                        "$sql",
                                                                            '',
                                                                        "Aceite confirmado e registrado  pedido: [ $codigoOrcamento ]  ! "
                                                                        ); 
                                                                    // --- MELHORIA VISUAL ---
                                                                    echo '<div class="log-box log-success">';
                                                                    echo '<i class="fas fa-database"></i> Orçamento <strong>'.$codigoOrcamento.'</strong> adicionado na tabela Precode com sucesso.';
                                                                    echo '</div>';      
                                                                }else{
                                                                    // --- MELHORIA VISUAL ---
                                                                    echo '<div class="log-box log-danger">';
                                                                    echo '<i class="fas fa-times-circle"></i> Falha ao inserir orçamento <strong>'.$codigoOrcamento.'</strong> na tabela Precode.';
                                                                    echo '</div>';
                                                                } 
                                                            }else{
                                                            
                                                                    Logs::registrar(
                                                                        $this->integracao,
                                                                        $this->databaseIntegracao,
                                                                        'sucesso',
                                                                        'Falha ao confirmar o aceite',
                                                                        " {
                                                                                \r\n\"codigoPedido\": $codigoPedidoSite,
                                                                                \r\n\"numeroPedidoERP\": $codigoOrcamento,
                                                                                \r\n\"numeroFilialFatura\": $filial_cd,
                                                                                \r\n\"numeroFilialSaldo\": $filial_cd\r\n
                                                                            }  ",
                                                                            '',
                                                                        "Falha ao confirmar o aceite do pedido: [ $codigoOrcamento ] ! "
                                                                        ); 
                                                                // --- MELHORIA VISUAL ---
                                                                echo '<div class="log-box log-danger">';
                                                                echo '<h3 class="text-danger text-center"><i class="fas fa-thumbs-down"></i> Falha ao confirmar o aceite!</h3>';
                                                                echo '</div>';
                                                            }
                                                        
                                                    }else{
                                                            // --- MELHORIA VISUAL ---
                                                            echo '<div class="log-box log-danger">';
                                                            echo '<h3 class="text-danger"><i class="fas fa-times"></i> Falha ao inserir forma de pagamento</h3>';
                                                            echo '<p>Orçamento: '.$codigoOrcamento.'</p>';
                                                            echo '</div>';
                                            }    
                                            
                                        }
                                    } else{
                                        Logs::registrar(
                                                            $this->integracao,
                                                            $this->databaseIntegracao,
                                                            'sucesso',
                                                            'registrar pedido ',
                                                            "$sql",
                                                                '',
                                                            "Falha ao inserir  orçamento  [ $id_produto_bd ]  "
                                                            );
                                        // --- MELHORIA VISUAL ---
                                        echo '<div class="log-box log-danger text-center">';
                                        echo '<h3 style="color: red;"><i class="fas fa-skull"></i> Falha Crítica ao inserir Orçamento</h3>';
                                        echo '<p>Canal Precode: <strong>'.$dispositivo.' - '.$marketplace.'</strong></p>';
                                        echo '<div class="log-code text-left"><pre>'.htmlspecialchars($sql).'</pre></div>';
                                        echo '<br><small>Data do erro: ' . date('d/m/Y H:i:s') . '</small>';
                                        echo '</div>'; 
                                        // Removido o echo </main> solto
                                    }   
                                        
                                            
                    }   
 
             }
                       
        } else {
            // --- MELHORIA VISUAL ---
			echo '<div class="log-box log-info text-center"> <h4><i class="fas fa-inbox"></i> Nenhum Pedido Novo</h4></div>';
			
		}
    }     
} 
?>