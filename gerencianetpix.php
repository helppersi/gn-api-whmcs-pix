<?php

require 'gerencianetpix/gerencianet-sdk/autoload.php';

use WHMCS\Database\Capsule;
use Gerencianet\Gerencianet;
use Gerencianet\Exception\GerencianetException;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @see https://developers.whmcs.com/payment-gateways/configuration/
 *
 * @return array
 */
function gerencianetpix_config() {
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Gerencianet via Pix',
        ),
        'clientIdProd' => array(
            'FriendlyName' => 'Client_ID de Produção (obrigatório)',
            'Type' => 'text',
            'Size' => '250',
            'Default' => '',
            'Description' => '',
        ),
        'clientSecretProd' => array(
            'FriendlyName' => 'Client_Secret de Produção (obrigatório)',
            'Type' => 'text',
            'Size' => '250',
            'Default' => '',
            'Description' => '',
        ),
        'clientIdSandbox' => array(
            'FriendlyName' => 'Client_ID de Sandbox (obrigatório)',
            'Type' => 'text',
            'Size' => '250',
            'Default' => '',
            'Description' => '',
        ),
        'clientSecretSandbox' => array(
            'FriendlyName' => 'Client_Secret de Sandbox (obrigatório)',
            'Type' => 'text',
            'Size' => '250',
            'Default' => '',
            'Description' => '',
        ),
        'sandbox' => array(
            'FriendlyName' => 'Sandbox',
            'Type' => 'yesno',
            'Description' => 'Habilita o modo Sandbox da Gerencianet',
        ),
        'debug' => array(
            'FriendlyName' => 'Debug',
            'Type' => 'yesno',
            'Description' => 'Habilita o modo Debug',
        ),
        'pixKey' => array(
            'FriendlyName' => 'Chave Pix (obrigatório)',
            'Type' => 'text',
            'Size' => '250',
            'Default' => '',
            'Description' => 'Insira sua chave Pix padrão para recebimentos',
        ),
        'pixCert' => array(
            'FriendlyName' => 'Certificado Pix',
            'Type' => 'text',
            'Size' => '350',
            'Default' => '/var/certs/cert.pem',
            'Description' => 'Insira o caminho do seu certificado .pem',
        ),
        'pixDiscount' => array(
            'FriendlyName' => 'Desconto do Pix (porcentagem %)',
            'Type' => 'text',
            'Size' => '3',
            'Default' => '0%',
            'Description' => 'Preencha um valor caso queira dar um desconto para pagamentos via Pix',
        ),
        'pixDays' => array(
            'FriendlyName' => 'Validade da Cobrança em Dias',
            'Type' => 'text',
            'Size' => '3',
            'Default' => '1',
            'Description' => 'Tempo em dias de validade da cobrança',
        ),
        'mtls' => array(
            'FriendlyName' => 'Validar mTLS',
            'Type' => 'yesno',
            'Default' => true,
            'Description' => 'Entenda os riscos de não configurar o mTLS acessando o link https://gnetbr.com/rke4baDVyd',
        )
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function gerencianetpix_link($params) {
    //  Validate if required parameters are empty
    validateRequiredParams($params);

    // Getting API Instance
    $api_instance = getGerencianetApiInstance($params);

    // Creating 'tblgerencianetpix' table
    createGerencianetPixTable();

    // Verifying if exists a Pix Charge for current invoiceId
    $existingPixCharge = retrievePixInfo($params['invoiceid']);
    
    if (!isset($existingPixCharge)) {
        // Creating a new Pix Charge
        $newPixCharge = createImmediateCharge($api_instance, $params);
    
        if (isset($newPixCharge['txid'])) {
            // Storing Pix Charge Infos on 'tblgerencianetpix' table for later use
            storePixInfo($newPixCharge, $params);
    
            // Configurating Webhook
            configWebhook($api_instance, $params);
        }
    }

    // Generate QR Code
    $locId = $existingPixCharge ? $existingPixCharge['locid'] : $newPixCharge['loc']['id'];
    $qrcode = generateQRCode($api_instance, $locId);

    return generateQrCodeTemplate($qrcode);
}

/**
 * Create a Pix Charge
 * 
 * @param \Gerencianet\Endpoints $api_instance Gerencianet API Instance
 * @param array $params Payment Gateway Module Parameters
 * 
 * @return array Generated Pix Charge
 */
function createImmediateCharge($api_instance, $params) {
    // Pix Parameters
    $pixKey         = $params['pixKey'];
    $pixDays        = $params['pixDays'];
    $pixDescription = $params['description'];

    if (empty($pixKey)) {
        showException('Exception', array('Chave Pix não informada. Verificar as configurações do Portal de Pagamento.'));
    }

    // Calculating discount value
    $pixDiscount = str_replace('%', '', $params['pixDiscount']) / 100;

    // Calculating pix amount with discount
    $pixAmount = $params['amount'] * (1 - $pixDiscount);

    $requestBody = [
        'calendario' => [
            'expiracao' => $pixDays * 86400 // Multiplying by 86400 (1 day seconds) because the API expects to receive a value in seconds
        ],
        'valor' => [
            'original' => strval($pixAmount) // String value from amount
        ],
        'chave' => $pixKey,
        'solicitacaoPagador' => $pixDescription
    ];

    // Pix Charge Creation Request
    try {        
        $pixCharge = $api_instance->pixCreateImmediateCharge([], $requestBody);

        return $pixCharge;

    } catch (GerencianetException $e) {
        showException('Gerencianet Exception', array($e));

    } catch (Exception $e) {
        showException('Exception', array($e));
    }

}

/**
 * Create 'tblgerencianetpix' table
 */
function createGerencianetPixTable() {
    if(!Capsule::schema()->hasTable('tblgerencianetpix')) {
        try {
            Capsule::schema()->create(
                'tblgerencianetpix',
                function ($table) {
                    $table->increments('id');
                    $table->integer('invoiceid');
                    $table->string('txid');
                    $table->string('e2eid');
                    $table->integer('locid');
                }
            );
    
        } catch (\Exception $e) {
            showException('DataBase Exception', array($e->getMessage()));
        }
    }
}

/**
 * Generate QR Code for a Pix Charge
 * 
 * @param \Gerencianet\Endpoints $api_instance Gerencianet API Instance
 * @param int $locId Location ID to generate QR Code
 * 
 * @return array Generated QR Code
 */
function generateQRCode($api_instance, $locId) {
    $requestParams = [
        'id' => $locId
    ];

    // QR Code Generation Request
    try {
        $qrcode = $api_instance->pixGenerateQRCode($requestParams);

        return $qrcode;

    } catch (GerencianetException $e) {
        showException('Gerencianet Exception', array($e));

    } catch (Exception $e) {
        showException('Exception', array($e));
    }
}

/**
 * Configure WebhookUrl for a Pix Charge
 * 
 * @param \Gerencianet\Endpoints $api_instance Gerencianet API Instance
 * @param array $params Payment Gateway Module Parameters
 * 
 */
function configWebhook($api_instance, $params) {
    // System Parameters
    $moduleName  = $params['paymentmethod'];
    $systemUrl   = $params['systemurl'];
    $callbackUrl = $systemUrl . 'modules/gateways/callback/' . $moduleName . '.php';

    $requestParams = [
        'chave' => $params['pixKey']
    ];

    $requestBody = [
        'webhookUrl' => $callbackUrl
    ];

    // Pix Webhook Config Request
    try {
        $api_instance->pixConfigWebhook($requestParams, $requestBody);

    } catch (GerencianetException $e) {
        showException('Gerencianet Exception', array($e));

    } catch (Exception $e) {
        showException('Exception', array($e));
    }
}

/**
 * Refund transaction
 *
 * Called when a refund is requested for a previously successful transaction
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/refunds/
 *
 * @return array Transaction response status
 */
function gerencianetpix_refund($params) {
    //  Validate if required parameters are empty
    validateRequiredParams($params);

    // Getting API Instance
    $api_instance = getGerencianetApiInstance($params);

    $requestParams = [
        'e2eId' => retrievePixInfo($params['invoiceid'])['e2eid'],
        'id' => uniqid()
    ];

    $requestBody = [
        'valor' => $params['amount']
    ];

    // Pix Devolution Request
    try {
        $responseData = $api_instance->pixDevolution($requestParams, $requestBody);

    } catch (GerencianetException $e) {
        showException('Gerencianet Exception', array($e));

    } catch (Exception $e) {
        showException('Exception', array($e));
    }

    return array(
        'status' => $responseData['rtrId'] ? 'success' : 'error',
        'rawdata' => $responseData,
        'transid' => $responseData['rtrId'],
    );
}

/**
 * Validate if required parameters are empty
 * 
 * @param array $params Payment Gateway Module Parameters
 */
function validateRequiredParams($params) {
    $requiredParams = array(
        'clientIdProd'        => $params['clientIdProd'],
        'clientSecretProd'    => $params['clientSecretProd'],
        'clientIdSandbox'     => $params['clientIdSandbox'],
        'clientSecretSandbox' => $params['clientSecretSandbox'],
    );

    $errors = array();

    foreach ($requiredParams as $key => $value) {
        if (empty($value)) {
            $errors[] = $key . ' é um campo obrigatório. Verificar as configurações do Portal de Pagamento.';
        };
    };

    if (!empty($errors)) {
        showException('Exception', $errors);
    }
}

/**
 * Store Pix Info
 * 
 * @param array $pix Pix Charge
 * @param array $params Payment Gateway Module Parameters
 */
function storePixInfo($pix, $params) {
    $txId      = $pix['txid'];
    $locId     = $pix['loc']['id'];
    $invoiceId = $params['invoiceid'];

    try {
        Capsule::table('tblgerencianetpix')
            ->insert(
                [
                    'invoiceid' => $invoiceId,
                    'txid' => $txId,
                    'locid' => $locId,
                ]
            );

    } catch (\Exception $e) {    
        showException('DataBase Exception', array($e->getMessage()));
    }
}

/**
 * Retrieve Pix Charge Infos
 * 
 * @param int $invoiceId Invoice ID
 * 
 * @return array Pix Charge Infos
 */
function retrievePixInfo($invoiceId) {
    try {
        $data = Capsule::table('tblgerencianetpix')
            ->where('invoiceid', $invoiceId)
            ->first();

        return json_decode(json_encode($data), true);

    } catch (\Exception $e) {
        showException('DataBase Exception', array($e->getMessage()));
    }
}

/**
 * Show Exceptions
 * 
 * @param string $type Exception type
 * @param array $exceptions Array of exceptions to show
 */
function showException($type, $exceptions) {
    $errorPage = new \WHMCS\View\HtmlErrorPage();

    // Append error to template body
    foreach($exceptions as $exception) {
        $errorPage->body .= "<li style=\"padding: 10px 0px;\"><b>{$type}: </b>{$exception}</li>";
    }

    $html = $errorPage->getHtmlErrorPage();

    die($html);
}

/**
 * Retrieve Genrencianet API Instance
 * 
 * @param array $params Payment Gateway Module Parameters
 * 
 * @return \Gerencianet\Endpoints $api_instance Gerencianet API Instance
 */
function getGerencianetApiInstance($params) {    
    $mtls    = ($params['mtls'] == 'on');
    $debug   = ($params['debug'] == 'on');
    $sandbox = ($params['sandbox'] == 'on');

    // Getting API Instance
    $api_instance = Gerencianet::getInstance(
        array(
            'client_id' => $sandbox ? $params['clientIdSandbox'] : $params['clientIdProd'],
            'client_secret' => $sandbox ? $params['clientSecretSandbox'] : $params['clientSecretProd'],
            'pix_cert' => $params['pixCert'],
            'sandbox' => $sandbox,
            'debug' => $debug,
            'headers' => [
                'x-skip-mtls-checking' => $mtls ? 'true' : 'false' // Needs to be string
            ]
        )
    );

    return $api_instance;
}

/**
 * Generate QR Code Template including copy button
 * 
 * @param array $qrcode QR Code to generate template from
 * 
 * @return string $template Template
 */
function generateQrCodeTemplate($qrcode) {
    // QR Code image
    $qrcodeImage = "<img src=\"{$qrcode['imagemQrcode']}\" />\n";

    // Copy button 
    $copyButton = "<button class=\"btn btn-default\" id=\"copyButton\" onclick=\"copyQrCode('{$qrcode['qrcode']}')\">Copiar QR Code</button>\n";

    // Script for Copy action
    $script = "<script type=\"text/javascript\" src=\"/whmcs/modules/gateways/gerencianetpix/gerencianetpix_lib/scripts/js/copyQrCode.js\"></script>";

    $template = $qrcodeImage.$copyButton.$script;

    return $template;
}