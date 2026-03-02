<?php
// utils/ProdutoGradeMapper.php

class ProdutoMapper
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

    public function obterDadosParaEnvio($codigo)
    {
        // 1. Busca Dados Principais (Pai)
        $dadosProduto = $this->consultaProduto($codigo);

        if (!$dadosProduto) {
            return null; // Produto não encontrado
        }

        $estoque = $this->calcularEstoque($codigo);



        $categoria = '';
        $categoria_interm = '';
        $categoria_final = '';
        $peso = $dadosProduto['PESO'] > 0 ? $dadosProduto['PESO'] : 0.22  ;

                    $comprimento  = $dadosProduto['COMPRIMENTO'] > 0 ? $dadosProduto['COMPRIMENTO'] : 17; 
                       $altura = $dadosProduto['ALTURA'] > 0 ? $dadosProduto['ALTURA'] :17  ;
                        $largura = $dadosProduto['LARGURA'] > 0 ? $dadosProduto['LARGURA'] : 17;

                    $categoria_interm= $dadosProduto['INTERM_CATEGORIA_MKTPLACE'];
                    $categoria_final= $dadosProduto['FINALCATEGORIA_MKTPLACE'] ;

                    if(!$dadosProduto['CATEGORIA_MKTPLACE'] || $dadosProduto['CATEGORIA_MKTPLACE'] === ''){
                        $categoria= 'Acessórios para Veículos ';
                    }
                    if(!$dadosProduto['INTERM_CATEGORIA_MKTPLACE'] || $dadosProduto['INTERM_CATEGORIA_MKTPLACE'] === ''){
                        $categoria_interm= 'Peças de Carros e Caminhonetes';
                    }

                    // --- MUDANÇA NA VALIDAÇÃO DA CATEGORIA FINAL ---
                    if(!$dadosProduto['FINALCATEGORIA_MKTPLACE'] || $dadosProduto['FINALCATEGORIA_MKTPLACE'] === ''){
                        
                        // Converte a descrição para maiúsculo para facilitar a comparação
                        $descricaoUpper = mb_strtoupper($dadosProduto['DESCRICAO'], 'UTF-8');

                        // Verifica Anéis (abrange ANEL, ANEIS, ANÉIS)
                        if( str_contains($descricaoUpper, 'ANEL') || str_contains($descricaoUpper, 'ANEIS') || str_contains($descricaoUpper, 'ANÉIS') ){
                            $categoria_final = 'Anéis Segmento';
                        }
                        // Verifica Bronzina
                        elseif( str_contains($descricaoUpper, 'BRONZINA') ){
                            $categoria_final = 'Bronzina de Mancal';
                        }
                    }
            $num_fabricante = $dadosProduto['NUM_FABRICANTE'];
            

        // 3. Monta o array final simulando o POST do formulário
        $produtoFormatado = [
            'acao'            => 'enviar',
            'codigo'          => $this->utf8($dadosProduto['CODIGO']),
            'descricao'       => $this->utf8($dadosProduto['DESCRICAO']),
            'descricaocurta'  => $this->utf8($dadosProduto['DESCRICAO']), // No form original usa DESCRICAO para os dois
            'aplicacao'       => $this->utf8($dadosProduto['APLICACAO']),
            'descricaogoogle' => $this->utf8($dadosProduto['DESCR_CURTA_MKTPLACE']),
            'palavraschave'   => $this->utf8($dadosProduto['DESCR_LONGA_MKTPLACE']),
            'preco'           => $dadosProduto['PRECO'],
            'promocao'        => $dadosProduto['PROMOCAO'] > 0 ? $dadosProduto['PROMOCAO'] : $dadosProduto['PRECO'] , // No form original usa PRECO se promocao nao existir, ajustar conforme logica
            'garantia'        => $this->utf8($dadosProduto['GARANTIA']),
            'origem'          => $this->formatarOrigem($dadosProduto['ORIGEM']),
            'ncm'             => $dadosProduto['NCM'],
            
            // Dados físicos do pai (usados como padrão)
            'comprimento'     => $comprimento,
            'largura'         => $largura,
            'altura'          => $altura,
            'peso'            => $peso,
            'estoque' => $estoque,
            'num_fabricante' => $num_fabricante,
            // Categorização


            'categoria'       =>  $categoria ,
            'categoriainterm' =>  $categoria_interm ,
            'categoriafinal'  =>  $categoria_final  ,
            
            'modelo'          => $this->utf8($dadosProduto['OUTRO_COD']),
            'marca'           => $this->utf8($dadosProduto['MARCA']),
            'ult_custo'       => 0, // Campo não estava explícito na query SQL original, mas usado no serviço
            
            // Array de variantes (Grade)
          //  'grade'           => $variantesData['itens']
        ];

        // Se promoção estiver vazia ou zero, manda o preço normal (ajuste defensivo)
        if (empty($produtoFormatado['promocao'])) {
            $produtoFormatado['promocao'] = $produtoFormatado['preco'];
        }

        return $produtoFormatado;
    }

    private function consultaProduto($codigo)
    {
        $sql =  "SELECT p.CODIGO,
                p.OUTRO_COD,
                p.DATA_RECAD,
                p.SKU_MKTPLACE,
                p.DESCR_CURTA_MKTPLACE,
                p.DESCR_LONGA_MKTPLACE,
                p.DESCR_CURTA_SITE,
                p.DESCRICAO,
                p.APLICACAO,
                p.GARANTIA,
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
                cf.NCM,
                sg.DESCRICAO AS SUBCATEGORIA,
                cg.NOME AS CATEGORIA
        
            FROM cad_prod p
            INNER JOIN prod_tabprecos tp ON p.CODIGO = tp.PRODUTO
            LEFT JOIN cad_pmar m ON m.codigo = p.marca
            LEFT JOIN class_fiscal cf ON cf.CODIGO = p.CLASS_FISCAL
            LEFT JOIN cad_pgru cg ON cg.CODIGO = p.GRUPO
            LEFT join subgrupos sg ON sg.CODIGO = p.SUBGRUPO
            WHERE   p.ATIVO='S'   AND tp.tabela = {$this->tabelaPreco} AND p.CODIGO = '$codigo' ";
        

        
        $result = $this->publico->Consulta($sql);
        return mysqli_fetch_array($result, MYSQLI_ASSOC);
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