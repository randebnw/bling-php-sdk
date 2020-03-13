<?php
namespace Bling\Core;

/**
 *
 *
 * @package Bling\Core
 * @author Rande A. Moreira <rande@adok.com.br>
 * @version 1.0.0
 */
class Util {
	const PRODUTO_STATUS_ATIVO = 'Ativo';
	const PRODUTO_STATUS_INATIVO = 'Inativo';
	
	const CLIENTE_SEXO_FEMININO = 'feminino';
	const CLIENTE_SEXO_MASCULINO = 'masculino';
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 10 de mar de 2020
	 */
	public static function getUnidadesMedida() {
		return ['Metros', 'Centímetros', 'Milímetros'];
	}
}
