<?php

$endpoint = 'https://github.com/aleduca/aula-domingo-slim';


//iniciar sessão
$cURL = curl_init();

//processar 

//Qual URL fazer a requisição
curl_setopt($cURL, CURLOPT_URL, $endpoint);

//Habilitar o retorno do conteúdo
curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);

//Habilitar o cabeçalho
curl_setopt($cURL, CURLOPT_USERAGENT, 'cURL Test');

//Executar a requisição
$response = curl_exec($cURL);

if (curl_errno($cURL)) {
    echo 'Erro: ' . curl_error($cURL);
} else {
    //Exibir o conteúdo retornado
    print $response;
}

//Fechar a sessão
curl_close($cURL);
