<?php
namespace Bling\Resources;

use Bling\Core\Bling;
/**
 *
 * Essa classe é resposavel por lidar com os contatos dentro do Bling.
 *
 * @package Bling\Contato
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=produtos
 * @version 1.0.0
 */
class Contato extends Bling {
	const PESSOA_FISICA = 'F';
	const PESSOA_JURIDICA = 'J';
	const SEXO_M = 'masculino';
	const SEXO_F = 'feminino';
	const IE_ISENTO = 'ISENTO';
	const CONTRIBUINTE = 1;
	const CONTRIBUINTE_ISENTO = 2;
	const CONTRIBUINTE_NAO = 9;
	const CONTATO_CLIENTE = 'Cliente';
	
    public function __construct($configurations) {
        parent::__construct($configurations);
    }

    /**
     *
     * Cria o contato dentro do Bling
     *
     * @param array $data
     * @return bool|array
     * @throws \Exception
     */
    public function createContato(array $data) {
    	$id = 0;
        try {
        	$xml = \Bling\Util\ArrayToXml::convert($data, ['rootElementName' => 'contato'], true, 'UTF-8');
            $request = $this->configurations['guzzle']->post(
                'contato/json/',
                ['query' => ['xml' => $xml]]
            );
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['contatos']['contato']['id'])) {
                $id = $response['retorno']['contatos']['contato']['id'];
            }
        } catch (\Exception $e){
        	$this->ResponseException($e);
        }
        
    	if (!$id && isset($response['retorno']['erros'])) {
    		$error = $this->_getError($response);    		
    		throw new \Exception($error['message'], $error['code']);
    	}
    	
    	return $id;
    }
    
    /**
     *
     * Pega os dados do contato associado ao CPF/CNPJ passado como parametro.
     *
     * @param string $codigo
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getContato($cpfCnpj) {
        try {
        	$cpfCnpj = preg_replace('/\D/', '', $cpfCnpj);
            $request = $this->configurations['guzzle']->get(
                'contato/' . $cpfCnpj . '/json/'
            );
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['contatos'][0]['contato'])) {
                return $response['retorno']['contatos'][0]['contato'];
            }
            return false;
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
    }
}
