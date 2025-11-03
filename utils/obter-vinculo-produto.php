<?php
include_once(__DIR__ . '/../database/conexao_publico.php');

class ObterVinculo {

    function getVinculo(int $codigo) {
        $publico = new CONEXAOPUBLICO();
        $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

        // Consulta o produto no banco de dados interno
        $resultQueryProd = $publico->consulta("SELECT * FROM cad_prod WHERE CODIGO = $codigo");

        if (mysqli_num_rows($resultQueryProd) == 0) {
            echo "<div class='mensagem-container mensagem-erro' role='alert'>";
            echo "<i class='fas fa-exclamation-triangle'></i> <strong>Erro!</strong> Produto com código $codigo não encontrado no banco de dados.";
            echo "</div>";
            return;
        }

        $row = mysqli_fetch_array($resultQueryProd, MYSQLI_ASSOC);
        $referencia = trim($row['OUTRO_COD']); // Remove espaços em branco

        if (empty($referencia)) {
            echo "<div class='mensagem-container mensagem-erro' role='alert'>";
            echo "<i class='fas fa-exclamation-triangle'></i> <strong>Erro!</strong> Produto com código $codigo não possui referência (OUTRO_COD).";
            echo "</div>";
            return;
        }

        if (empty($ini['conexao']['token'])) {
            echo "<div class='mensagem-container mensagem-erro' role='alert'>";
            echo "<i class='fas fa-exclamation-triangle'></i> <strong>Erro!</strong> Token da aplicação não fornecido no arquivo conexao.ini.";
            echo "</div>";
            return;
        }

        $token = $ini['conexao']['token'];
        $url = 'https://www.replicade.com.br/api/v3/products/query/' . urlencode($referencia) . '/ref'; // Codifica a referência para a URL

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,  // Aumenta o timeout
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: " . $token
            ],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $result = json_decode($response);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            echo "<div class='mensagem-container mensagem-erro' role='alert'>";
            echo "<i class='fas fa-exclamation-triangle'></i> <strong>Erro de cURL!</strong> " . htmlspecialchars($error);
            echo "</div>";
            return;
        }

        if ($httpcode != 200) {
            echo "<div class='mensagem-container mensagem-erro' role='alert'>";
            echo "<i class='fas fa-exclamation-triangle'></i> <strong>Erro na API!</strong> ";

            if (!empty($result->mensagem)) {
                echo htmlspecialchars($result->mensagem);
            } else {
                echo "Código HTTP: " . $httpcode;
            }

            echo "<br>Produto (código: $codigo, referência: $referencia) não encontrado na API.";
            echo "</div>";
            return;
        }

        if (!empty($result) && isset($result->produto->codigoAgrupador)) { // Verifica se $result e $result->produto existem
            $idPrecode = $result->produto->codigoAgrupador;

            $validationProduct = $publico->consulta("SELECT * FROM produto_precode WHERE CODIGO_SITE = '$idPrecode' AND CODIGO_BD = '$codigo'");

            if (mysqli_num_rows($validationProduct) == 0) {
                $insertResult = $publico->consulta("INSERT INTO produto_precode (CODIGO_SITE, CODIGO_BD) VALUES ('$idPrecode', '$codigo')");

                if ($insertResult == 1) {
                    echo "<div class='mensagem-container mensagem-sucesso' role='alert'>";
                    echo "<i class='fas fa-check-circle'></i> Vinculo obtido com sucesso para o produto: $codigo.";
                    echo "</div>";
                } else {
                     echo "<div class='mensagem-container mensagem-erro' role='alert'>";
                     echo "<i class='fas fa-exclamation-triangle'></i> Erro ao inserir vínculo para o produto: $codigo.";
                     echo "</div>";
                }

            } else {
                $row = mysqli_fetch_array($validationProduct, MYSQLI_ASSOC);
                $codigoPrecode = $row['CODIGO_SITE'];

                echo "<div class='mensagem-container mensagem-sucesso' role='alert'>";
                echo "<i class='fas fa-check-circle'></i> Produto já possui um vínculo: ERP Cód: $codigo | Referência: $referencia | Cód Precode: $codigoPrecode";
                echo "</div>";
            }
        } else {
            echo "<div class='mensagem-container mensagem-erro' role='alert'>";
            echo "<i class='fas fa-exclamation-triangle'></i> Resposta da API inválida para o produto: $codigo.";
            echo "</div>";
        }
    }

    private function response(bool $success, string $message, $data = null): string {
        return json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
    }
}
?>