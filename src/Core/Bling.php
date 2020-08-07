<?php
namespace Bling\Core;

/**
 * abstract Bling class
 *
 * Essa classe abstrai todos os metodos das
 * classes registradas na função getBundles, e permite
 * a qualquer classe que a extenda utilizar qualquer função
 * dos bundles registados, podendo assim se criar a classe
 * Bling\BlingClient que realiza todas as operações do pacote.
 *
 * @package Bling
 * @version 1.0.0
 */
abstract class Bling
{
    /** @var $configurations */
    public $configurations;
    /** @var $namespace */
    private $namespace;
    
    private $total_requests = 0;

    public function __construct($configurations)
    {
        $this->configurations = $configurations;
        $this->namespace = __NAMESPACE__ . '\\';
    }

    /**
     * function getBundles
     *
     * Registra todas as classes da biblioteca
     * em um array para serem listados para utilização
     *
     * @return array
     */
    private function getBundles()
    {
        return [
            \Bling\Resources\Contato::class,
        	\Bling\Resources\Deposito::class,
        	\Bling\Resources\FormaPagamento::class,
       		\Bling\Resources\Produto::class,
       		\Bling\Resources\Pedido::class,
        	\Bling\Resources\Situacao::class
        ];
    }

    /**
     * checkBundlesRepeat function
     *
     * Lista todos os bundles registados para observar se não existem funções
     * que se sobrescrevem
     *
     * @return array
     */
    public function checkBundlesRepeat()
    {
        $bundles = $this->getAllBundle();
        unset($bundles['binary']);
        return $bundles;
    }

    /**
     * getBundle function
     *
     * Pega o noem do Bundle dono da função
     * que foi requisitada
     *
     * @param array $bundles
     * @param string $function
     * @return string|bool
     */
    private function getBundle(array $bundles, $function)
    {
        unset($bundles['binary']);

        foreach ($bundles as $bundleKey => $bundleMethods) {
            if(\in_array($function, $bundleMethods)){
                return $bundleKey;
            }
        }
        return false;
    }

    /**
     * getAllBundle function
     *
     * Lista todos os metodos que estão dentro dos Bundles
     * registados e lista todos os bundles também para serem
     * reutilizados.
     *
     * @return array
     */
    private function getAllBundle()
    {
        $bundlesArray = array('binary' => array());
        $bundles = $this->getBundles();
        foreach ($bundles as $bundle) {
            if(!isset($bundlesArray[$bundle])){
               $bundlesArray[$bundle] = array();
            }
        }
        foreach ($bundlesArray as $bundleKey => $bundle) {
            $bundleMethods = \get_class_methods($bundleKey);
            if(is_array($bundleMethods) && !empty($bundleMethods)){
                foreach ($bundleMethods as $method) {
                    if($method != '__construct'
                    && $method != '__call'
                    && $method != 'hookBundle'
                    && $method != 'getAllBundle'
                    && $method != 'getBundle'
                    && $method != 'getBundles'
                    && $method != 'ResponseException'
                    ){
                        $bundlesArray[$bundleKey][] = $method;
                        $bundlesArray['binary'][] = $method;
                    }
                }
            }
        }
        return $bundlesArray;
    }

    /**
     * hookBundle function
     *
     * @param string $class
     * @param string $method
     * @param $params
     * @return mixed|bool
     */
    private function hookBundle($class, $method, $params)
    {
        $metodos = \get_class_methods($class);
        if(in_array($method, $metodos)){
            return call_user_func_array(array(new $class($this->configurations), $method), $params);
        }
        return false;
    }

    /**
     * gunction __call
     *
     * Pega metodos que estão fora dessa classe
     * porem que pertencem ao namespace e estão
     * registados no registro de bundles acima
     * e utiliza de funções especiais para habiliar
     * esses metodos fora dessas classes
     *
     * @param [type] $name
     * @param [type] $arguments
     * @return bool|mixed
     */
    public function __call($name, $arguments)
    {
        $bundles = $this->getAllBundle();
        if(!in_array($name, $bundles['binary'])){
            return false;
        }
        $bundle = $this->getBundle($bundles, $name);
        if(!$bundle){
            return false;
        }
        
        $this->total_requests++;
        return $this->hookBundle($bundle, $name, $arguments);
    }

	public function ResponseException(\Exception $e) {
    	if (!in_array('getResponse', \get_class_methods($e)) || is_null($e->getResponse())){
            throw new \Exception($e->getMessage(), 1);
        }
        
        $response = \json_decode($e->getResponse()->getBody()->getContents(), true);
        if (isset($response['retorno']['erros'])) {
        	$error = $this->_getError($response);
        	throw new \Exception($error['message'], $error['code']);
        }
        
        throw new \Exception(\json_encode(\json_decode($e->getResponse()->getBody()->getContents(), true)), 1);
    }
    
    public function getTotalRequests() {
    	return $this->total_requests;
    }
    
	protected function _getError($response) {
    	$code = -1;
    	$message = print_r($response, true);
    	if (isset($response['retorno']['erros'])) {
    		$error_copy = $response['retorno']['erros'];
    		$first = array_shift($response['retorno']['erros']);
    		
    		if (isset($first['erro']['cod'], $first['erro']['msg'])) {
    			// trata um dos formatos de retorno de erro
    			$code = $first['erro']['cod'];
    			$message = $first['erro']['msg'];
    		} else if (isset($first['cod'], $first['msg'])) {
    			// trata outro formato de erro
    			$code = $first['cod'];
    			$message = $first['msg'];
    		} else {
    			// trata o outro formato de retorno de erro
    			$keys = array_keys($error_copy);
    			$code = (int) $keys[0];
    			if (isset($error_copy[$code])) {
    				if (is_array($error_copy[$code])) {
    					$message = array_shift($error_copy[$code]);
    				} else {
    					$message = (string) $error_copy[$code];
    				}
    			}
    		}
    	}
    	
    	return compact('message', 'code');
    }
}
