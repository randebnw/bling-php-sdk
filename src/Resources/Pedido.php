<?php
namespace Bling\Resources;

use Bling\Core\Bling;
/**
 *
 * Essa classe é resposavel por lidar com os pedidos dentro do Bling.
 *
 * @package Bling\Pedido
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=pedidos
 * @version 1.0.0
 */
class Pedido extends Bling {
	public function __construct($configurations) {
        parent::__construct($configurations);
    }

    /**
     *
     * Cria o produto dentro do Bling
     *
     * @param array $data
     * @return bool|array
     * @throws \Exception
     */
    public function createPedido(array $data) {
        try {
            $request = $this->configurations['guzzle']->post(
                'pedido/json/',
                ['query' => ['xml' => $data]]
            );
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response)) {
                return $response;
            }
            return false;
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
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
    public function getPedidos($dataEmissao, $situacao) {
        try {
        	if ($situacao) {
        		$situacao = (array) $situacao;
        	}
        	
            $request = $this->configurations['guzzle']->get(
                'pedidos/json/',
            	['query' => ['filters' => 'dataEmissao[' . $dataEmissao . ' TO ' . $dataEmissao . '];idSituacao[' . implode(',', $situacao) . ']']]
            );
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['pedidos'])) {
                $list = [];
            	foreach ($response['retorno']['pedidos'] as $item) {
            		$list[] = $item['pedido'];
            	}
                return $list;
            }
            return false;
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
    }

    /**
     *
     * Atualiza um pedido no Bling com novo array de dados buscando por seu numero
     *
     * @return bool|mixed|void
     * @throws \Exception
     */
    public function updatePedido($numero, $data) {
        try {
            $request = $this->configurations['guzzle']->put(
                'pedido/'. $numero .'/json/',
                ['query' => ['xml' => $data]]
            );
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
