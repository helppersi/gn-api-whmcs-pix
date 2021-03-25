<?php

if(!defined('WHMCS')){die();}

require_once ROOTDIR . '/modules/gateways/gerencianetpix/gerencianetpix_lib/handler/exception_handler.php';

use WHMCS\Database\Capsule;

// defina o método de pagamento 
define('PAYMENT_METHOD', 'gerencianetpix' );

add_hook('CartTotalAdjustment', 1, function($vars) { 

    $cart_adjustments = [];

    if($vars['paymentmethod'] == PAYMENT_METHOD) {

        // Busca o id da moeda BRL
        $result = Capsule::table('tblcurrencies') 
                -> where('code', '=', 'BRL' ) 
                -> get() -> first();
        $idBRL = $result->id;

        if (!empty($idBRL)) {
            // Carrega os valores das configurações do Gateway
            $paramsGateway = getGatewayVariables(PAYMENT_METHOD);
            $pixDiscount = str_replace('%', '', $paramsGateway['pixDiscount']);
            
            $products = [];
            foreach ($vars['products'] as $product) {
                $billingcycle = $product['billingcycle'];
                $price = Capsule::table('tblpricing') 
                    -> where('type', '=', 'product' ) 
                    -> where('relid', '=', $product['pid'])
                    -> where('currency', '=', $idBRL)
                    -> first($billingcycle);
    
                $products[] = (float)$price->$billingcycle;    
            }

            // Soma o valor total dos itens
            $invoice_total = array_sum($products);
            // Valor do desconto
            $discountValue = (float)(($invoice_total) * $pixDiscount) /100;

            $cart_adjustments = [
                "description" => "Desconto de $pixDiscount% no pagamento com PIX",
                "amount" => $discountValue * -1,// Valor do desconto a ser subtraido
                "taxed" => false,
            ];
        } else {
            showException('Exception', array('A Gerencianet processa apenas transações na moeda brasileira, o Real (código BRL)'));
        }
    }

    return $cart_adjustments;
});