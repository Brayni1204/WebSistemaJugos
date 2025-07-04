<?php
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$host = "0.0.0.0";
$port = 8090;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, $host, $port);
socket_listen($socket);

$clients = [$socket];

echo "✅ Servidor WebSocket iniciado en ws://$host:$port\n";

while (true) {
    $read = $clients;
    socket_select($read, $write, $except, 0);

    foreach ($read as $client) {
        if ($client === $socket) {
            // Nueva conexión
            $newClient = socket_accept($socket);
            $clients[] = $newClient;
            handshake($newClient);
            echo "🟢 Nuevo cliente conectado\n";
        } else {
            $data = socket_read($client, 1024);

            // Si el cliente se ha desconectado, lo eliminamos
            if ($data === false || $data === "") {
                echo "🔴 Cliente desconectado\n";
                socket_close($client);
                unset($clients[array_search($client, $clients)]);
                continue;
            }

            $decodedData = unmask($data);
            echo "📩 Mensaje recibido: $decodedData\n";

            // Enviar mensaje a todos los clientes activos
            foreach ($clients as $sendClient) {
                if ($sendClient !== $socket && $sendClient !== $client) {
                    $mensajeUTF8 = mb_convert_encoding($decodedData, 'UTF-8', 'UTF-8');
                    $maskedMessage = mask($mensajeUTF8);

                    // Intentamos enviar el mensaje
                    $result = @socket_write($sendClient, $maskedMessage, strlen($maskedMessage));

                    // Si no se pudo enviar, eliminamos al cliente
                    if ($result === false) {
                        echo "⚠️ Cliente desconectado, eliminándolo de la lista\n";
                        socket_close($sendClient);
                        unset($clients[array_search($sendClient, $clients)]);
                    }
                }
            }
        }
    }
}

// Función de handshake con el cliente
function handshake($client)
{
    $headers = socket_read($client, 1024);
    if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $matches)) {
        $key = trim($matches[1]);
        $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
        socket_write($client, $response, strlen($response));
    }
}

// Decodificar mensajes WebSocket
function unmask($payload)
{
    $length = ord($payload[1]) & 127;
    if ($length == 126) {
        $masks = substr($payload, 4, 4);
        $data = substr($payload, 8);
    } elseif ($length == 127) {
        $masks = substr($payload, 10, 4);
        $data = substr($payload, 14);
    } else {
        $masks = substr($payload, 2, 4);
        $data = substr($payload, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}

// Codificar mensajes WebSocket
function mask($text)
{
    $b1 = 0x81;
    $length = strlen($text);
    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length <= 65535) {
        $header = pack('CCn', $b1, 126, $length);
    } else {
        $header = pack('CCNN', $b1, 127, 0, $length);
    }
    return $header . $text;
}
