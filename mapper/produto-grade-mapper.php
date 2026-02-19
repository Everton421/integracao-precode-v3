<?php
// utils/ProdutoGradeMapper.php

class ProdutoGradeMapper
{
    private $publico;
    private $vendas;
    private $estoque;
    private $dbPublicoName;
    private $dbVendasName;
    private $dbEstoqueName;
    private $setor;
    private $tabelaPreco;

    public function __construct($conexaoPublico, $conexaoVendas, $conexaoEstoque)
    {
        $this->publico = $conexaoPublico;
        $this->vendas = $conexaoVendas;
        $this->estoque = $conexaoEstoque;

        $this->dbPublicoName = $this->publico->getBase();
        $this->dbVendasName = $this->vendas->getBase();
        $this->dbEstoqueName = $this->estoque->getBase();

        // Carrega configurações do INI
        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);
        $this->setor = ($ini['conexao']['setor'] && !empty($ini['conexao']['setor'])) ? $ini['conexao']['setor'] : 1;
        $this->tabelaPreco = ($ini['conexao']['tabelaPreco'] && !empty($ini['conexao']['tabelaPreco'])) ? $ini['conexao']['tabelaPreco'] : 1;
    }

    /**
     * Busca todos os dados da grade e formata um array idêntico ao $_POST
     * que o controller espera.
     */
    public function obterDadosParaEnvio($codigoGrade)
    {
        // 1. Busca Dados Principais (Pai)
        $dadosPai = $this->buscarDadosPai($codigoGrade);

        if (!$dadosPai) {
            return null; // Produto não encontrado
        }

        // 2. Busca Variantes e Calcula Estoque
        $variantesData = $this->buscarVariantes($codigoGrade);
        

        $categoria = '';
        $categoria_interm = '';
        $categoria_final = '';
        $peso = $dadosPai['PESO'] > 0 ? $dadosPai['PESO'] : 0.22  ;

                    $comprimento  = $dadosPai['COMPRIMENTO'] > 0 ? $dadosPai['COMPRIMENTO'] : 17; 
                       $altura = $dadosPai['ALTURA'] > 0 ? $dadosPai['ALTURA'] :17  ;
                        $largura = $dadosPai['LARGURA'] > 0 ? $dadosPai['LARGURA'] : 17;


            if(!$dadosPai['CATEGORIA_MKTPLACE'] || $dadosPai['CATEGORIA_MKTPLACE'] === ''){
                $categoria= 'Acessórios para Veículos ';
            }
            if(!$dadosPai['INTERM_CATEGORIA_MKTPLACE'] || $dadosPai['INTERM_CATEGORIA_MKTPLACE'] === ''){
                $categoria_interm= 'Peças de Carros e Caminhonetes';
            }
             if(!$dadosPai['FINALCATEGORIA_MKTPLACE'] || $dadosPai['FINALCATEGORIA_MKTPLACE'] === ''){
                if( str_contains( $dadosPai['DESCRICAO'] , 'ANEL' ) ||  str_contains( $dadosPai['DESCRICAO'] , 'ANEIS' )  ){
                    $categoria_final= 'Anéis Segmento';
                
                }
                if( str_contains( $dadosPai['DESCRICAO'] , 'anel' ) ||  str_contains( $dadosPai['DESCRICAO'] , 'aneis') || str_contains( $dadosPai['DESCRICAO'] , 'anéis' )){
                    $categoria_final= 'Anéis Segmento';
                

                }
                 if( str_contains( $dadosPai['DESCRICAO'] , 'BRONZINA' ) ||  str_contains( $dadosPai['DESCRICAO'] , 'Bronzina' )){
                    $categoria_final= 'Bronzina de Mancal';
                    
                    }
                 if( str_contains( $dadosPai['DESCRICAO'] , 'BRONZINA' ) ||  str_contains( $dadosPai['DESCRICAO'] , 'Bronzina' )){
                    $categoria_final= 'Bronzina de Mancal';
                   
                }
            }
            

        // 3. Monta o array final simulando o POST do formulário
        $produtoFormatado = [
            'acao'            => 'enviar',
            'codigo'          => $this->utf8($dadosPai['GRADE']),
            'descricao'       => $this->utf8($dadosPai['DESCRICAO']),
            'descricaocurta'  => $this->utf8($dadosPai['DESCRICAO']), // No form original usa DESCRICAO para os dois
            'aplicacao'       => $this->utf8($dadosPai['APLICACAO']),
            'descricaogoogle' => $this->utf8($dadosPai['DESCR_CURTA_MKTPLACE']),
            'palavraschave'   => $this->utf8($dadosPai['DESCR_LONGA_MKTPLACE']),
            'preco'           => $dadosPai['PRECO'],
            'promocao'        => $dadosPai['PROMOCAO'] > 0 ? $dadosPai['PROMOCAO'] : $dadosPai['PRECO'] , // No form original usa PRECO se promocao nao existir, ajustar conforme logica
            'garantia'        => $this->utf8($dadosPai['GARANTIA']),
            'origem'          => $this->formatarOrigem($dadosPai['ORIGEM']),
            'ncm'             => $dadosPai['NCM'],
            
            // Dados físicos do pai (usados como padrão)
            'comprimento'     => $comprimento,
            'largura'         => $largura,
            'altura'          => $altura,
            'peso'            => $peso,
            
            // Categorização


            'categoria'       =>  $categoria ,
            'categoriainterm' =>  $categoria_interm ,
            'categoriafinal'  =>  $categoria_final  ,
            
            'modelo'          => $this->utf8($dadosPai['OUTRO_COD']),
            'marca'           => $this->utf8($dadosPai['MARCA']),
            'ult_custo'       => 0, // Campo não estava explícito na query SQL original, mas usado no serviço
            
            // Array de variantes (Grade)
            'grade'           => $variantesData['itens']
        ];

        // Se promoção estiver vazia ou zero, manda o preço normal (ajuste defensivo)
        if (empty($produtoFormatado['promocao'])) {
            $produtoFormatado['promocao'] = $produtoFormatado['preco'];
        }

        return $produtoFormatado;
    }

    private function buscarDadosPai($codigoGrade)
    {
        $sql =  "SELECT
        p.GRADE,
        p.CODIGO, 
        p.OUTRO_COD,
        p.DATA_RECAD, 
        p.SKU_MKTPLACE,
        p.DESCR_CURTA_MKTPLACE,
        p.DESCR_LONGA_MKTPLACE,
        g.DESCRICAO, 
        p.APLICACAO, 
        P.GARANTIA,
        p.COMPRIMENTO, 
        p.LARGURA, 
        p.ALTURA, 
        p.PESO,
        p.ORIGEM, 
        p.CATEGORIA_MKTPLACE, 
        p.INTERM_CATEGORIA_MKTPLACE,
        p.FINALCATEGORIA_MKTPLACE,
        p.MODELO_MKTPLACE,
        p.NUM_FABRICANTE,
        tp.PRECO,
        tp.PROMOCAO,
        m.descricao AS MARCA,
        cf.NCM
    FROM grades as g 
    LEFT JOIN cad_prod p on p.GRADE = g.codigo
    INNER JOIN prod_tabprecos tp ON p.CODIGO = tp.PRODUTO
    LEFT JOIN cad_pmar m ON m.codigo = p.marca
    LEFT JOIN class_fiscal cf ON cf.CODIGO = p.CLASS_FISCAL
    WHERE (p.NO_MKTP='S' AND p.ATIVO='S')  
    AND tp.tabela = {$this->tabelaPreco} 
    AND g.CODIGO = '$codigoGrade'
    ORDER BY LENGTH(p.APLICACAO) DESC
    LIMIT 1";
        
        $result = $this->publico->Consulta($sql);
        return mysqli_fetch_array($result, MYSQLI_ASSOC);
    }

    private function buscarVariantes($codigoGrade)
    {
        $sqlProdutosgrade = "
            SELECT  
                p.CODIGO,
                p.NUM_FABRICANTE,
                p.DESCRICAO,
                ig.CARAC as CODIGO_CARACTERISTICA,
                ig.VALOR as VALOR_CARACTERISTICA, 
                crg.DESCRICAO as DESCRICAO_CARACTERISTICA
            FROM  grades g
            JOIN   cad_prod p on p.GRADE = g.CODIGO
            JOIN   itens_grade ig on  p.CODIGO = ig.PRODUTO AND ig.GRADE = p.GRADE AND ig.VALOR <> '' AND ig.VALOR is NOT NULL
            JOIN   carac_grade crg on crg.CODIGO = ig.CARAC
            WHERE (p.NO_MKTP='S' AND p.ATIVO='S') AND g.CODIGO  = '$codigoGrade'
            GROUP BY p.CODIGO
        ";

        $result = $this->publico->Consulta($sqlProdutosgrade);
        
        $listaItens = [];
        $estoqueTotal = 0;

        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $estoqueReal = $this->calcularEstoque($row['CODIGO']);
            $estoqueTotal += $estoqueReal;

            // Formata estrutura para o Service (igual ao input hidden do HTML)
            // Nota: Não usamos htmlspecialchars aqui pois o json_encode cuidará disso depois
            $listaItens[] = [
                'codigo' => $this->utf8($row['CODIGO']),
                'descricao' => $this->utf8($row['DESCRICAO']),
                'valor_caracteristica' => $this->utf8($row['VALOR_CARACTERISTICA']),
                'descricao_caracteristica' => $this->utf8($row['DESCRICAO_CARACTERISTICA']),
                'num_fabricante' => $this->utf8($row['NUM_FABRICANTE']),
                'estoque' => $estoqueReal
            ];
        }
        return ['itens' => $listaItens, 'estoque_total' => $estoqueTotal];
    }

    private function calcularEstoque($codigoProduto)
    {
        $estoqueVariante = 0;
        
        $sqlEstoque = "SELECT
            est.CODIGO, 
            IF(est.estoque < 0, 0, est.estoque) AS ESTOQUE
        FROM
            (SELECT
            P.CODIGO,
            (SUM(PS.ESTOQUE) -
                (SELECT COALESCE(SUM((IF(PO.QTDE_SEPARADA > (PO.QUANTIDADE - PO.QTDE_MOV), PO.QTDE_SEPARADA, (PO.QUANTIDADE - PO.QTDE_MOV)) * PO.FATOR_QTDE) * IF(CO.TIPO = '5', -1, 1)), 0)
                FROM " . $this->dbVendasName . ".cad_orca AS CO
                LEFT OUTER JOIN " . $this->dbVendasName . ".pro_orca AS PO ON PO.ORCAMENTO = CO.CODIGO
                WHERE CO.SITUACAO IN ('AI', 'AP', 'FP')
                AND PO.PRODUTO = P.CODIGO)) AS estoque
            FROM " . $this->dbEstoqueName . ".prod_setor AS PS
            LEFT JOIN " . $this->dbPublicoName . ".cad_prod AS P ON P.CODIGO = PS.PRODUTO
            WHERE P.CODIGO = '$codigoProduto'
            AND PS.SETOR = '{$this->setor}'
            GROUP BY P.CODIGO) AS est";

        $buscaEstoque = $this->estoque->Consulta($sqlEstoque);

        if ($buscaEstoque && mysqli_num_rows($buscaEstoque) > 0) {
            while ($row_estoque = mysqli_fetch_array($buscaEstoque, MYSQLI_ASSOC)) {
                $estoqueVariante += $row_estoque['ESTOQUE'];
            }
        }

        return $estoqueVariante;
    }

    private function formatarOrigem($codigoOrigem)
    {
        $origemMap = [
            '0' => 'Nacional', '3' => 'Nacional', '4' => 'Nacional', 
            '5' => 'Nacional', '8' => 'Nacional',
            '1' => 'Importado', '2' => 'Importado', '6' => 'Importado', '7' => 'Importado'
        ];
        return isset($origemMap[$codigoOrigem]) ? $origemMap[$codigoOrigem] : 'Desconhecida';
    }

    // Função auxiliar para garantir UTF-8 (pois seu banco parece retornar ISO-8859-1)
    private function utf8($str) {
        return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    }
}
?>