<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet">

    <title>Novo Kit</title>
    <style>
        body { font-family: 'Raleway', sans-serif; background-color: #f8f9fa; }
        .sidebar { height: 100%; width: 250px; position: fixed; top: 0; left: 0; background-color: #343a40; padding-top: 60px; color: white; z-index: 100; }
        .sidebar button { padding: 15px 25px; text-decoration: none; font-size: 16px; color: #f2f2f2; display: block; transition: 0.3s; text-align: left; border: none; background-color: transparent; width: 100%; cursor: pointer; }
        .sidebar button:hover { background-color: #28a745; color: white; }
        .sidebar button:disabled { color: #6c757d; cursor: not-allowed; }
        
        .content { margin-left: 250px; padding: 20px; }
        .navbar { background-color: #343a40 !important; }
        .navbar-brand { color: white !important; }

        /* Estilos dos Inputs */
        .form-group-inline { display: flex; flex-wrap: wrap; align-items: center; }
        .form-group-inline .form-group { flex: 1; margin-right: 10px; margin-bottom: 10px; min-width: 150px; }
        .form-group-inline .form-group:last-child { margin-right: 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }

       /* Garante que a lista apareça sobre tudo */
#resultado_pesquisa_kit {
    position: absolute; /* Flutua */
    top: 100%;          /* Fica logo abaixo do input */
    left: 0;
    width: 100%;        /* Largura igual ao input */
    z-index: 1050;      /* Alto z-index para ficar em cima de tabelas e cards */
    background-color: #fff; /* Fundo branco é essencial */
    border: 1px solid #ced4da;
    border-radius: 0 0 5px 5px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-height: 250px;
    overflow-y: auto;
    display: none;      /* Controlado pelo JS */
}
/* Ajuste visual dos itens */
#resultado_pesquisa_kit .list-group-item {
    cursor: pointer;
    border-left: none;
    border-right: none;
}
#resultado_pesquisa_kit .list-group-item:hover {
    background-color: #f8f9fa;
    font-weight: bold;
}

        /* Área oculta inicialmente */
        #area-dados-kit { display: none; border-top: 2px solid #28a745; padding-top: 20px; margin-top: 30px; }
    </style>
</head>
<body>

<form method="post" action="controller.php" enctype="multipart/form-data" id="formKit">
    <input type="hidden" name="acao" value="criar_kit">

    <div class="sidebar">
        <!-- Botão de Envio (Só habilita se tudo estiver ok) -->
        <button type="submit" id="btn-enviar-final" >
            <i class="fa-solid fa-save"></i> Salvar Kit
        </button>
    </div>

    <div class="content">
        <nav class="navbar navbar-expand-md navbar-dark fixed-top">
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">
                <i class="fa-solid fa-arrow-left"></i> INTERSIG
            </a>
        </nav>

        <div class="container-fluid" style="margin-top: 60px;">
            
            <!-- PASSO 1: SELEÇÃO DE ITENS -->
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <h5><i class="fa-solid fa-list-check"></i> 1. Composição do Kit</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label>Adicionar Produtos ao Kit:</label>
                            <div class="input-group mb-3" style="position: relative;">
            
                                <input type="text" class="form-control" id="termo_busca_kit" placeholder="Digite código ou nome (min 3 letras)..." autocomplete="off">
                                
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" onclick="buscarProdutosKit()">
                                        <i class="fa-solid fa-search"></i>
                                    </button>
                                </div>
                            <div id="resultado_pesquisa_kit" class="list-group"></div>

                            </div>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped mt-3">
                        <thead class="thead-light">
                            <tr>
                                <th>Cód. Item</th>
                                <th>Descrição</th>
                                <th width="120">Qtd.</th>
                                <th width="80">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="lista_itens_kit">
                            <!-- Itens adicionados via JS -->
                        </tbody>
                    </table>

                    <div class="text-right">
                        <button type="button" class="btn btn-success btn-lg" onclick="avancarParaDados()">
                            <i class="fa-solid fa-arrow-down"></i> Preencher Dados do Kit (Baseado no 1º Item)
                        </button>
                    </div>
                </div>
            </div>

            <!-- PASSO 2: DADOS DO KIT (Inicialmente Oculto) -->
            <div id="area-dados-kit">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fa-solid fa-box-open"></i> 2. Detalhes do Novo Kit</h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- Inputs Idênticos ao seu arquivo de edição -->
                        <div class="form-group-inline">
                             
                             <div class="form-group">
                                <label for="id_kit">Novo Código do Kit:</label>
                                <input type="text"
                                 style="background-color: #e9ecef; cursor: not-allowed;"
                                class="form-control" id="id_kit" name="id_kit" readonly placeholder="Ex: KIT-001">
                            </div>   

                            <div class="form-group">
                                <label for="marca">Marca:</label>
                                <input type="text" class="form-control" id="marca" name="marca">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descricao">Descrição/Título:</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="2"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="descricaocurta">Descrição Curta:</label>
                            <textarea class="form-control" id="descricaocurta" name="descricaocurta" rows="1"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="aplicacao">Aplicação / Detalhes:</label>
                            <textarea class="form-control" id="aplicacao" name="aplicacao" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="descricaogoogle">Descrição Google / Marketplace:</label>
                            <textarea class="form-control" id="descricaogoogle" name="descricaogoogle"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="palavraschave">Palavras-chave:</label>
                            <textarea class="form-control" id="palavraschave" name="palavraschave"></textarea>
                        </div>

                        <div class="form-group-inline">
                            <div class="form-group">
                                <label for="comprimento">Comprimento (cm):</label>
                                <input type="text" class="form-control" id="comprimento" name="comprimento">
                            </div>
                            <div class="form-group">
                                <label for="largura">Largura (cm):</label>
                                <input type="text" class="form-control" id="largura" name="largura">
                            </div>
                            <div class="form-group">
                                <label for="altura">Altura (cm):</label>
                                <input type="text" class="form-control" id="altura" name="altura">
                            </div>
                            <div class="form-group">
                                <label for="peso">Peso (kg):</label>
                                <input type="text" class="form-control" id="peso" name="peso">
                            </div>
                        </div>

                        <div class="form-group-inline">
                            <div class="form-group">
                                <label for="preco">Preço de Venda:</label>
                                <input type="text" class="form-control" id="preco" name="preco">
                            </div>
                            <div class="form-group">
                                <label for="promocao">Preço Promoção:</label>
                                <input type="text" class="form-control" id="promocao" name="promocao">
                            </div>
                            <div class="form-group">
                                <label for="estoque">Estoque Inicial:</label>
                                <input type="text" class="form-control" id="estoque" name="estoque">
                            </div>
                        </div>

                        <div class="form-group-inline">
                            <div class="form-group">
                                <label for="origem">Origem:</label>
                                <input type="text" class="form-control" id="origem" name="origem">
                            </div>
                            <div class="form-group">
                                <label for="ncm">NCM:</label>
                                <input type="text" class="form-control" id="ncm" name="ncm">
                            </div>
                            <div class="form-group">
                                <label for="garantia">Garantia:</label>
                                <input type="text" class="form-control" id="garantia" name="garantia">
                            </div>
                        </div>

                        <div class="form-group-inline">
                            <div class="form-group">
                                <label for="categoria">Categoria:</label>
                                <input type="text" class="form-control" id="categoria" name="categoria">
                            </div>
                            <div class="form-group">
                                <label for="categoriainterm">Categoria Interm.:</label>
                                <input type="text" class="form-control" id="categoriainterm" name="categoriainterm">
                            </div>
                            <div class="form-group">
                                <label for="categoriafinal">Categoria Final:</label>
                                <input type="text" class="form-control" id="categoriafinal" name="categoriafinal">
                            </div>
                        </div>

                        <div class="form-group-inline">
                            <div class="form-group">
                                <label for="num_fabricante">Núm. Fabricante/GTIN:</label>
                                <input type="text" class="form-control" id="num_fabricante" name="num_fabricante">
                            </div>
                            <div class="form-group">
                                <label for="modelo">Outro Cód / Modelo:</label>
                                <input type="text" class="form-control" id="modelo" name="modelo">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>

<script>
    // 1. Busca Simples para preencher a lista
   function buscarProdutosKit() {
    const termo = document.getElementById('termo_busca_kit').value;
    const resultadosDiv = document.getElementById('resultado_pesquisa_kit');

    if (termo.length < 3) {
        // Oculta se limpar o campo
        resultadosDiv.style.display = 'none';
        return;
    }

    // Mostra mensagem de carregando
    resultadosDiv.innerHTML = '<div class="list-group-item text-muted">Buscando...</div>';
    resultadosDiv.style.display = 'block'; // <--- FORÇA A EXIBIÇÃO AQUI

    fetch('ajax_busca_produtos.php?termo=' + encodeURIComponent(termo))
        .then(response => response.json())
        .then(data => {
            console.log("Dados recebidos:", data); // Verifique isso no console

            resultadosDiv.innerHTML = ''; // Limpa o "Buscando..."

            if (!data || data.length === 0) {
                resultadosDiv.innerHTML = '<div class="list-group-item text-danger">Nenhum produto encontrado.</div>';
            } else {
                data.forEach(prod => {
                    // Cria o elemento
                    const item = document.createElement('a');
                    item.href = '#'; // Previne navegação
                    item.className = 'list-group-item list-group-item-action';
                    
                    // Conteúdo visual
                    item.innerHTML = `<span class="text-primary font-weight-bold">${prod.codigo}</span> - ${prod.descricao}`;
                    
                    // Evento de clique
                    item.onclick = function(e) {
                        e.preventDefault(); // Impede a tela de pular para o topo
                        adicionarAoKit(prod.codigo, prod.descricao);
                        
                        // Limpa e esconde após selecionar
                        resultadosDiv.style.display = 'none';
                        document.getElementById('termo_busca_kit').value = '';
                    };
                    
                    resultadosDiv.appendChild(item);
                });
            }
            // Garante que continue visível após carregar
            resultadosDiv.style.display = 'block';
        })
        .catch(error => {
            console.error('Erro JS:', error);
            resultadosDiv.innerHTML = '<div class="list-group-item text-danger">Erro ao buscar.</div>';
        });
}

    // 2. Adicionar linha na tabela
    function adicionarAoKit(codigo, descricao) {
        if (document.getElementById('row_' + codigo)) { alert('Item já adicionado.'); return; }
        
        const tbody = document.getElementById('lista_itens_kit');
        const tr = document.createElement('tr');
        tr.id = 'row_' + codigo;
        // Marca se é o primeiro item para lógica visual (opcional)
        tr.className = 'item-kit'; 
        
        tr.innerHTML = `
            <td>${codigo} <input type="hidden" name="produtos_kit[${codigo}][id]" value="${codigo}" class="kit-id"></td>
            <td>${descricao}</td>
            <td><input type="number" name="produtos_kit[${codigo}][qtd]" value="1" min="1" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
    }

    // 3. Lógica Principal: Pegar o 1º item, buscar detalhes completos e mostrar formulário
    function avancarParaDados() {
        const itens = document.querySelectorAll('.kit-id');
        if (itens.length === 0) {
            alert('Adicione pelo menos um produto na tabela antes de prosseguir.');
            return;
        }

        const primeiroCodigo = itens[0].value;
        const btn = document.querySelector('button[onclick="avancarParaDados()"]');
        const textoOriginal = btn.innerHTML;
        
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Buscando dados...';
        btn.disabled = true;

        // Chama o PHP que retorna TODOS os detalhes do produto (peso, ncm, medidas...)
        fetch('busca_detalhes_produto.php?codigo=' + primeiroCodigo)
            .then(res => res.json())
            .then(data => {
                if (data && !data.erro) {
                    // Preenche os inputs do formulário com os dados do 1º item
                    //id do kit a ser cadastrado
                    document.getElementById('id_kit').value = data.id_kit || '';

                    document.getElementById('descricao').value = data.DESCRICAO || '';
                    document.getElementById('descricaocurta').value = data.DESCRICAO || '';
                    document.getElementById('aplicacao').value = data.APLICACAO || '';
                    document.getElementById('descricaogoogle').value = data.DESCR_CURTA_MKTPLACE || '';
                    document.getElementById('palavraschave').value = data.DESCR_LONGA_MKTPLACE || '';
                    
                    document.getElementById('comprimento').value = data.COMPRIMENTO || '';
                    document.getElementById('largura').value = data.LARGURA || '';
                    document.getElementById('altura').value = data.ALTURA || '';
                    document.getElementById('peso').value = data.PESO || '';
                    
                    document.getElementById('preco').value = data.PRECO || '';
                    document.getElementById('promocao').value = data.PRECO || ''; // Padrão
                    document.getElementById('estoque').value = data.ESTOQUE_REAL || '0';
                    
                    document.getElementById('origem').value = data.ORIGEM_TEXTO || '';
                    document.getElementById('ncm').value = data.NCM || '';
                    document.getElementById('garantia').value = data.GARANTIA || '';
                    
                    document.getElementById('categoria').value = data.CATEGORIA_MKTPLACE || '';
                    document.getElementById('categoriainterm').value = data.INTERM_CATEGORIA_MKTPLACE || '';
                    document.getElementById('categoriafinal').value = data.FINALCATEGORIA_MKTPLACE || '';
                    
                    document.getElementById('marca').value = data.MARCA || '';
                    document.getElementById('num_fabricante').value = data.NUM_FABRICANTE || '';
                    document.getElementById('modelo').value = data.MODELO_MKTPLACE || '';

                    // Mostra a área do formulário com efeito de deslize
                    $('#area-dados-kit').slideDown();
                    
                    // Habilita o botão de salvar final
                    document.getElementById('btn-enviar-final').disabled = false;
                    
                    // Foca no campo de código novo
                     document.getElementById('marca').focus();

                } else {
                    alert('Erro ao buscar dados do produto base.');
                }
            })
            .catch(err => {
                console.error("Erro: " , err);
                alert('Erro de comunicação. ', err);
            })
            .finally(() => {
                btn.innerHTML = textoOriginal;
                btn.disabled = false;
            });
    }

    // Fechar busca ao clicar fora
    document.addEventListener('click', function(e) {
        const container = document.getElementById('resultado_pesquisa_kit');
        const input = document.getElementById('termo_busca_kit');
        if (e.target !== container && e.target !== input) {
            container.style.display = 'none';
        }
    });
</script>

</body>
</html>