<?php

return [
    /*
    |
    |Como usar: Config::get('boletofacil.variável')
    |
    |-----------------------------------------------------------
    | Token Privado
    |-----------------------------------------------------------
    | Token para ser utilizado na integração gerado a partir
    | do site BoletoFácil.
    |
    */
    'token' => 'SeuTokenBoletoFacil',
    /*
    |-----------------------------------------------------------
    | URL
    |-----------------------------------------------------------
    | Endereço do servidor utilizado (sandbox/produção)
    |
    */
    // Geração de cobrança
    'url'               => 'https://sandbox.boletobancario.com/boletofacil/integration/api/v1/issue-charge',
    'url_fetch_payment' => 'https://sandbox.boletobancario.com/boletofacil/integration/api/v1/fetch-payment-details',
    'url_listcarges'    => 'https://sandbox.boletobancario.com/boletofacil/integration/api/v1/list-charges',
    'url_fetch-balance' => 'https://sandbox.boletobancario.com/boletofacil/integration/api/fetch-balance',
    'cancel-charge'     => 'https://sandbox.boletobancario.com/boletofacil/integration/api/cancel-charge',
    'daysBeforeSend'    => '7',
    'interval'          => '1',

    'responseType'  => 'JSON',
];