<?php
namespace Bling\Resources;

use Bling\Core\Bling;
/**
 *
 * Essa classe é resposavel por lidar com os depositos dentro do Bling.
 *
 * @package Bling\Deposito
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=depositos
 * @version 1.0.0
 */
class Deposito extends Bling
{
    public function __construct($configurations) {
        parent::__construct($configurations);
    }
    
	/**
     *
     * Pega os dados do produto associado ao codigo/SKU passado como parametro.
     *
     * @param date $dataEmissao
     * @param int|array $situacao
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getDepositos() {
        try {
        	$request = $this->configurations['guzzle']->get('/pedidos/json/');
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response)) {
                return $response;
            }
            return false;
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
    }
}
