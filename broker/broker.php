<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once(__DIR__ . '/../services/evento-service.php');

include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_estoque.php'); 
include_once(__DIR__.'/../database/conexao_vendas.php');
include_once(__DIR__.'/../database/conexao_integracao.php');
include_once(__DIR__.'/../database/conexao_eventos.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;

$publico = new CONEXAOPUBLICO();
$estoque = new CONEXAOESTOQUE();
$vendas = new CONEXAOVENDAS();
$eventos = new CONEXAOEVENTOS();
$integracao = new CONEXAOINTEGRACAO();


$ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);

$url  = $ini['broker']['url'] ?? "localhost";
$queue = $ini['broker']['queue'] ?? "integracao_precode";
$port  = $ini['broker']['port'] ?? 5672;
$exchange  = $ini['broker']['exchange'] ?? "sistema";

    $service = new EventoService($publico, $estoque, $vendas,   $integracao);

try {
    $connection = new AMQPStreamConnection($url, $port, 'guest', 'guest');
    $channel = $connection->channel();

        // 1. declarar exchange 
      $channel->exchange_declare($exchange, 'fanout', false, true, false);

    // 2. Declarar fila
    $channel->queue_declare($queue, false, false, false, false);

      // 3. FAZER O BIND (Vincular a fila à exchange)
    // Parâmetros: nome_da_fila, nome_da_exchange, routing_key
    $channel->queue_bind($queue, $exchange, '');


     echo " [*] Sucesso: Fila '$queue' vinculada à Exchange '$exchange'\n";
    echo " [*] Aguardando mensagens. Para sair, pressione CTRL+C\n";

    $callback = function ($msg) use ($service) {
      //  echo ' [x] Recebido: ', $msg->body, "\n";
        if( $msg->body ){
                $payload = json_decode($msg->body);
                $tabela_origem = $payload->tabela_origem;
                   $service->processarMensagem($msg->body);
          //  $service->teste();
            
        }
    };

    $channel->basic_consume($queue, '', false, true, false, false, $callback);

    while (count($channel->callbacks)) {
        try {
            // Espera por 3 segundos. Se não chegar nada, ele repete o loop e mantém a conexão viva.
            $channel->wait(null, false, 3);
        } catch (AMQPTimeoutException $e) {
            // Se der timeout aqui, não é erro, é apenas o tempo de espera esgotado. 
            // O loop vai continuar e o heartbeat vai manter a conexão.
            continue;
        }
    }

} catch (\Exception $e) {
    echo "\n Erro: " . $e->getMessage() . "\n";
} finally {
    try {
        if (isset($channel) && $channel->is_open()) {
            $channel->close();
        }
        if (isset($connection) && $connection->is_connected()) {
            $connection->close();
        }
    } catch (\Exception $e) {
    }
}