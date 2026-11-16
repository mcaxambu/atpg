<?php

return [

    /*
     * Enquanto não houver chave, o portal continua controlando as
     * mensalidades à mão: a tela avisa, e nenhuma cobrança é emitida.
     */
    'habilitado' => env('ASAAS_HABILITADO', false),

    'ambiente' => env('ASAAS_AMBIENTE', 'sandbox'),

    'api_key' => env('ASAAS_API_KEY'),

    // Segredo conferido em todo webhook recebido.
    'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),

    'base_url' => [
        'sandbox' => 'https://api-sandbox.asaas.com/v3',
        'production' => 'https://api.asaas.com/v3',
    ],

    'timeout' => 20,

    /*
     * UNDEFINED deixa o associado escolher entre Pix e boleto na própria
     * tela do Asaas. Fixar um dos dois obrigaria a associação a decidir pelo
     * pagador, e Pix é o que a maioria usa sem precisar de instrução.
     */
    'forma' => env('ASAAS_FORMA', 'UNDEFINED'),

];
