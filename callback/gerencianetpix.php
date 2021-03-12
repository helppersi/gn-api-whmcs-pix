<?php

require_once __DIR__ . '/../../../init.php';

use WHMCS\Database\Capsule;

App::load_function('gateway');
App::load_function('invoice');

// Detect module name from filename
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Hook data retrieving
@ob_clean();
$postdata = json_decode(file_get_contents('php://input'));

// Hook validation
if(isset($postdata->evento) && isset($postdata->data_criacao)) {
    header('HTTP/1.0 200 OK');

    exit();
}

// Hook manipulation
if (isset($postdata->pix)) {
	header('HTTP/1.0 200 OK');

    $success = false;
    $pixData = $postdata->pix;

    $txid = $pixData[0]->txid;

    $tableName = 'tblgerencianetpix';
    $invoiceId = Capsule::table($tableName)
                    ->where('txid', $txid)
                    ->value('invoiceid');

    Capsule::table($tableName)
        ->where('invoiceid', $invoiceId)
        ->update(
            [
                'e2eid' => $pixData[0]->endToEndId,
            ]
        );

    /**
     * Validate Callback Invoice ID.
     *
     * Checks invoice ID is a valid invoice number. Note it will count an
     * invoice in any status as valid.
     *
     * Performs a die upon encountering an invalid Invoice ID.
     *
     * Returns a normalised invoice ID.
     *
     * @param int $invoiceId Invoice ID
     * @param string $gatewayName Gateway Name
     */
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']); // VALIDAÇÃO DO INVOICE ID

    $paymentFee = '0.00';
    $paymentAmount = $pixData[0]->valor;

    $success = isset($invoiceId);

    if($success) {
        addInvoicePayment($invoiceId, $transactionId, $paymentAmount, $paymentFee, $gatewayModuleName);
    }
}