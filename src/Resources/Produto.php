<?php
namespace Bling\Resources;

use Bling\Core\Bling;
/**
 *
 * Essa classe é resposavel por lidar com os produtos dentro do Bling.
 *
 * @package Bling\Produto
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=produtos
 * @version 1.0.0
 */
class Produto extends Bling {
	const DEFAULT_PRICE = 'd';
	const CUSTOM_PRICE = 'c'; // multistore
	
	const TIPO_PRODUTO = 'P';
	const TIPO_SERVICO = 'S';
	
	const SITUACAO_ATIVO = 'Ativo';
	const SITUACAO_INATIVO = 'Inativo';
	
	const TIPO_DATA_INCLUSAO = 'dataInclusao';
	const TIPO_DATA_ALTERACAO = 'dataAlteracao';
	const TIPO_SKU_PRODUTO = 'codigo';
	
	const FILTRO_ATIVO = 'A';
	const FILTRO_INATIVO = 'I';
	const FILTRO_EXCLUIDO = 'E';
	
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
    public function createProduto(array $data) {
    	$success = false;
        try {
        	$xml = \Bling\Util\ArrayToXml::convert($data, ['rootElementName' => 'produto'], true, 'UTF-8');
            $request = $this->configurations['guzzle']->post(
                'produto/json/',
                ['body' => ['xml' => $xml]]
            );
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['produtos'][0][0]['produto']['codigo'])) {
                $success = true;
            }
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
        
    	if (!$success && isset($response['retorno']['erros'])) {
    		$error = $this->_getError($response);    		
    		throw new \Exception($error['message'], $error['code']);
    	}
    }
    
    /**
     *
     * Atualiza um produto no Bling com novo array de dados buscando por seu ID
     *
     * @return bool|mixed|void
     * @throws \Exception
     */
    public function updateProduto($codigo, $data) {
    	$success = false;
    	try {
    		$xml = \Bling\Util\ArrayToXml::convert($data, ['rootElementName' => 'produto'], true, 'UTF-8');
    		$request = $this->configurations['guzzle']->post(
    			'produto/'. $codigo .'/json/',
    			['body' => ['xml' => $xml]]
    		);
    		$response = \json_decode($request->getBody()->getContents(), true);
    		if ($response && is_array($response) && isset($response['retorno']['produtos'][0][0]['produto']['codigo'])) {
    			$success = true;
    		}
    	} catch (\Exception $e){
    		return $this->ResponseException($e);
    	}
    	
    	if (!$success && isset($response['retorno']['erros'])) {
    		$error = $this->_getError($response);    		
    		throw new \Exception($error['message'], $error['code']);
    	}
    }

	/**
     *
     * Pega os dados do produto associado ao codigo/SKU passado como parametro.
     *
     * @param string $codigo
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getProduto($codigo, $loja = '') {
        try {
            $query = ['imagem' => 'S', 'estoque' => 'S'];
            if ($loja) {
                $query['loja'] = $loja;
            }
            $request = $this->configurations['guzzle']->get(
                'produto/' . $codigo . '/json/',
            	['query' => $query]
            );
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['produtos'][0]['produto'])) {
                return $response['retorno']['produtos'][0]['produto'];
            }
            return false;
        } catch (\Exception $e){
            $this->ResponseException($e);
        }
    }
    
    public function getProdutos($loja, $situacao = '', $page = 1, $imagem = false) {
    	try {
    		$filters = '';
    		if ($situacao) {
    			$filters = 'situacao[' . $situacao . ']';
    		}
    		
    		$query = ['filters' => $filters, 'loja' => $loja, 'estoque' => 'S'];
    		if ($imagem) {
				$query['imagem'] = 'S';
			}
    		
    		$request = $this->configurations['guzzle']->get(
    			'produtos/page=' . (int)$page . '/json/',
    			['query' => $query]
    		);
    
    		$response = \json_decode($request->getBody()->getContents(), true);
    		if ($response && is_array($response) && isset($response['retorno']['produtos'])) {
    			$list = [];
    			foreach ($response['retorno']['produtos'] as $item) {
    				$list[] = $item['produto'];
    			}
    			return $list;
    		}
    
    		return false;
    	} catch (\Exception $e){
    		$this->ResponseException($e);
    	}
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 13 de jun de 2020
     * @param unknown $loja
     * @param unknown $codigo
     * @param unknown $data
     * @return mixed[]|boolean
     */
    public function inserirProdutoLoja($loja, $codigo, $data) {
    	$success = false;
    	try {
    		$xml = \Bling\Util\ArrayToXml::convert($data, ['rootElementName' => 'produtosLoja'], true, 'UTF-8');
            $request = $this->configurations['guzzle']->post(
                'produtoLoja/' . $loja . '/' . $codigo . '/json/',
                ['body' => ['xml' => $xml]]
            );
    
    		$response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['produtosLoja'][0][0]['produtosLoja']['idLojaVirtual'])) {
                $success = true;
            }
    	} catch (\Exception $e){
    		$this->ResponseException($e);
    	}
    	
    	if (!$success && isset($response['retorno']['erros'])) {
    		$error = $this->_getError($response);
    		throw new \Exception($error['message'], $error['code']);
    	}
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 13 de jun de 2020
     * @param unknown $loja
     * @param unknown $codigo
     * @param unknown $data
     * @throws \Exception
     */
    public function atualizarProdutoLoja($loja, $codigo, $data) {
    	$success = false;
    	try {
    		$xml = \Bling\Util\ArrayToXml::convert($data, ['rootElementName' => 'produtosLoja'], true, 'UTF-8');
            $request = $this->configurations['guzzle']->put(
                'produtoLoja/' . $loja . '/' . $codigo . '/json/',
                ['body' => ['xml' => $xml]]
            );
    
    		$response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['produtosLoja'][0][0]['produtosLoja']['idLojaVirtual'])) {
                $success = true;
            }
    	} catch (\Exception $e){
    		$this->ResponseException($e);
    	}
    	
    	if (!$success && isset($response['retorno']['erros'])) {
    		$error = $this->_getError($response);
    		throw new \Exception($error['message'], $error['code']);
    	}
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 13 de jun de 2020
     * @param unknown $dataInicio
     * @param unknown $dataFim
     * @param number $page
     * @param boolean $imagem
     * @return mixed|boolean
     */
    public function getProdutosPorDataInclusao($loja, $dataInicio, $dataFim, $page = 1, $imagem = false) {
    	return $this->_getProdutosPorData($loja, $dataInicio, $dataFim, self::TIPO_DATA_INCLUSAO, $page, $imagem);
    }
    
    public function getProdutosPorDataAlteracao($loja, $dataInicio, $dataFim, $page = 1, $imagem = false) {
    	return $this->_getProdutosPorData($loja, $dataInicio, $dataFim, self::TIPO_DATA_ALTERACAO, $page, $imagem);
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 13 de jun de 2020
     * @param unknown $loja
     * @param unknown $dataInicio
     * @param unknown $dataFim
     * @param unknown $tipoData
     * @param number $page
     * @param boolean $imagem
     * @return mixed[]|boolean
     */
	private function _getProdutosPorData($loja, $dataInicio, $dataFim, $tipoData, $page = 1, $imagem = false) {
        try {
			$list = [];
			
			// produtos ativos
			$query = [
				'loja' => $loja, 'estoque' => 'S', 
            	'filters' => $tipoData . '[' . $dataInicio . ' TO ' . $dataFim . ']; situacao[' . self::FILTRO_ATIVO . ']'
			];
			if ($imagem) {
				$query['imagem'] = 'S';
			}
			
        	$request = $this->configurations['guzzle']->get(
                'produtos/page=' . (int)$page . '/json/', ['query' => $query]
            );
            
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['produtos'])) {
            	foreach ($response['retorno']['produtos'] as $item) {
            		$list[] = $item['produto'];
            	}
            }
			
			// produtos inativos
            $query = [
            	'loja' => $loja, 'estoque' => 'S',
            	'filters' => $tipoData . '[' . $dataInicio . ' TO ' . $dataFim . ']; situacao[' . self::FILTRO_INATIVO . ']'
            ];
            if ($imagem) {
            	$query['imagem'] = 'S';
            }
			$request = $this->configurations['guzzle']->get(
                'produtos/page=' . (int)$page . '/json/', ['query' => $query]
            );
            
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['produtos'])) {
            	foreach ($response['retorno']['produtos'] as $item) {
            		$list[] = $item['produto'];
            	}
            }
            
            return $list ?: false;
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
    }
    
    /**
     * 
     * @param string $situacao
     * @return boolean
     */
    public static function isAtivo($situacao) {
    	return $situacao == self::SITUACAO_ATIVO;
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 22 de mai de 2020
     * @param unknown $tipo
     * @return boolean
     */
    public static function isServico($tipo) {
    	return $tipo == self::TIPO_SERVICO;
    }
}
