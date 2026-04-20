<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once(__DIR__ . '/../services/evento-service.php');

include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_estoque.php'); 
include_once(__DIR__.'/../database/conexao_vendas.php');
include_once(__DIR__.'/../database/conexao_integracao.php');
include_once(__DIR__.'/../database/conexao_eventos.php');
    include_once(__DIR__."/../services/enviar-notas.php");

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

   $service = new EventoService($publico, $estoque, $vendas, $integracao);

try {
    $connection = new AMQPStreamConnection($url, $port, 'guest', 'guest');
    $channel = $connection->channel();

    $channel->exchange_declare($exchange, 'fanout', false, true, false);
    $channel->queue_declare($queue, false, false, false, false);
    $channel->queue_bind($queue, $exchange, '');

    /**
     * AJUSTE 1: Configuração do Prefetch (QoS)
     * O segundo parâmetro (10) diz ao RabbitMQ para não enviar mais de 10 mensagens
     * simultâneas para este worker antes que ele envie um ACK.
     */
    $channel->basic_qos(null, 10, null);

    echo " [*] Sucesso: Fila '$queue' vinculada à Exchange '$exchange'\n";
    echo " [*] Aguardando mensagens (Limite: 10 por vez). Para sair, pressione CTRL+C\n";

    $callback = function ($msg) use ($service, $publico, $vendas, $integracao) {
        try {
            if ($msg->body) {
                $payload = json_decode($msg->body);
                $tabela_origem = $payload->tabela_origem ?? null;
                
                $service->processarMensagem($msg->body);
                
                if ($tabela_origem == 'cad_nf') {
                    $serviceEnviarNotas = new EnviarNota();
                    $serviceEnviarNotas->enviar($publico, $vendas, $integracao);
                }
            }

            /**
             * AJUSTE 2: Confirmação Manual (ACK)
             * Avisamos ao RabbitMQ que a mensagem foi processada com sucesso e pode ser removida da fila.
             */
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            
        } catch (\Exception $e) {
            echo "Erro ao processar mensagem: " . $e->getMessage() . "\n";
            // Em caso de erro, você pode decidir se rejeita a mensagem ou tenta novamente
            // basic_nack(tag, multiple, requeue)
            $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
        }
    };

    /**
     * AJUSTE 3: Desativar o auto_ack
     * O quarto parâmetro mudou de 'true' para 'false'. 
     * Agora o RabbitMQ espera o nosso comando 'basic_ack' dentro do callback.
     */
  $consuming = true;
    $channel->basic_consume($queue, '', false, false, false, false, $callback);
    while ($consuming) {
        try {
            $channel->wait(null, false, 3);
        } catch (AMQPTimeoutException $e) {
            continue;
        }
    }

} catch (\Exception $e) {
    echo "\n Erro: " . $e->getMessage() . "\n";
} finally {
    try {
      
        if (isset($connection) && $connection->is_connected()) {
            $connection->close();
        }
    } catch (\Exception $e) {
    }

       $vendas->Desconecta();
        $publico->Desconecta();
        $integracao->Desconecta();
}