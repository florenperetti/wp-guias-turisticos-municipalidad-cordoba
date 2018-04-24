<?php
/*
Plugin Name: Buscador de Gu&iacute;as Tur&iacute;sticos de la Municipalidad de C&oacute;rdoba
Plugin URI: https://github.com/ModernizacionMuniCBA/plugin-wordpress-guias-turisticos-municipales
Description: Este plugin a&ntilde;ade un shortcode que genera un buscador de los gu&iacute;as tur&iacute;sticos de la Municipalidad de C&oacute;rdoba.
Version: 1.1.4
Author: Florencia Peretti
Author URI: https://github.com/florenperetti/wp-guias-turisticos-municipalidad-cordoba
*/

add_action('plugins_loaded', array('GuiasTuristicosMuniCordoba', 'get_instancia'));

class GuiasTuristicosMuniCordoba
{
	public static $instancia = null;
	
	public static $array_idiomas = null;

	private static $URL_API_GOB = 'https://gobiernoabierto.cordoba.gob.ar/api/v2/guias-turisticos/guias-turisticos/';
	
	private static $URL_API_GOB_IDIOMAS = 'https://gobiernoabierto.cordoba.gob.ar/api/v2/guias-turisticos/idiomas/';

	public $nonce_busquedas = '';

	public static function get_instancia()
	{
		if (null == self::$instancia) {
			self::$instancia = new GuiasTuristicosMuniCordoba();
		} 
		return self::$instancia;
	}

	private function __construct()
	{
		add_action('wp_enqueue_scripts', array($this, 'cargar_assets'));

		add_action('wp_ajax_buscar_guias_turisticos', array($this, 'buscar_guias_turisticos')); 
		add_action('wp_ajax_nopriv_buscar_guias_turisticos', array($this, 'buscar_guias_turisticos'));
		
		add_action('wp_ajax_buscar_guias_turisticos_pagina', array($this, 'buscar_guias_turisticos_pagina')); 
		add_action('wp_ajax_nopriv_buscar_guias_turisticos_pagina', array($this, 'buscar_guias_turisticos_pagina'));
		
		add_shortcode('buscador_guias_turisticos_cba', array($this, 'render_shortcode_buscador_guias_turisticos'));

		add_action('init', array($this, 'boton_shortcode_buscador_guias_turisticos'));
	}

	public function render_shortcode_buscador_guias_turisticos($atributos = [], $content = null, $tag = '')
	{
	    $atributos = array_change_key_case((array)$atributos, CASE_LOWER);
	    $atr = shortcode_atts([
            'pag' => 12
        ], $atributos, $tag);

	    $cantidad_por_pagina = $atr['pag'] == 0 ? '' : '?page_size='.$atr['pag'];

	    $url = self::$URL_API_GOB.$cantidad_por_pagina;

    	$api_response = wp_remote_get($url);

    	$resultado = $this->chequear_respuesta($api_response, 'los gu&iacute;as', 'guias_turisticos_muni_cba');
		
		if (self::$array_idiomas == null) {
			$api_response = wp_remote_get(self::$URL_API_GOB_IDIOMAS);
			$res_idiomas = $this->chequear_respuesta($api_response, 'los idiomas', 'idiomas_guias_muni_cba');
			self::$array_idiomas = $res_idiomas['results'] ? $res_idiomas['results'] : [];
		}

		$html = '<div id="GTM">
	<form>
		<div class="filtros">
			<div class="filtros__columnas">
				<label class="filtros__label" for="idioma_id">Idioma</label>
				<select id="idioma_id" name="idioma_id"><option value="0">Todos</option>';
		foreach (self::$array_idiomas as $key => $idioma) {
			$html .= '<option value="'.$idioma['id'].'">'.$idioma['nombre'].'</option>';
		}
		$html .= '</select>
				<button id="filtros__buscar" type="submit">Buscar</button>
			</div>
			<div class="filtros__columnas">
				<button id="filtros__reset">Todos</button>
			</div>
		</div>
	</form>
	<div class="resultados">';
		$html .= $this->renderizar_resultados($resultado,$atr['pag']);
		$html .= '</div></div>';
		return $html;
	}
	
	private function renderizar_resultados($datos,$pag = 12,$query='')
	{
		$html = '';
		
		if (count($datos['results']) > 0) {
			$html .= '<p class="cantidad-resultados"><small><a href="https://gobiernoabierto.cordoba.gob.ar/data/datos-abiertos/categoria/turismo/registro-de-guias-turisticos-habilitados/230" rel="noopener" target="_blank"><b>&#161;Descarg&aacute; toda la informaci&oacute;n&#33;</b></a></small>
				<small>Mostrando '.count($datos['results']).' de '.$datos['count'].' resultados</small></p>';
			$html .= '<div class="cargando" style="display:none;"><img alt="Cargando..." src="'.plugins_url('images/loading.gif', __FILE__).'"></div>';
			$html .= '<div class="resultados__container">';
			foreach ($datos['results'] as $key => $guia) {
				$nombre = $guia['apellido'].', '.$guia['nombre'];
				$idiomas = '';
				foreach ($guia['idiomas'] as $id => $idioma) {
					$idiomas .= '<li><strong>'.$idioma['idioma'].' </strong><small>('.$idioma['nivel'].')</small></li>';
				}
				$contacto = '';
				foreach ($guia['vias_comunicacion'] as $via) {
					$contacto .= '<li>'.$via.'</li>';
				}

				$html .= '<div class="resultado__container">
						<div class="resultado__cabecera"><span class="resultado__nombre">'.$nombre.'</span></div>
						<div class="resultado__foto">
							<a href="'.$guia['foto']['original'].'" target="_blank"><img src="'.$guia['foto']['thumbnail'].'" alt="'.$nombre.'" /></a>
						</div>
						<div class="resultado__info">
							<div>Idiomas:<br/>
							<ul>'.$idiomas.'</ul>
							</div>
						</div>
						<div class="resultado__contacto">
							<ul style="word-break: break-all;">'.$contacto.'</ul>
						</div>
					</div>';
			}
			$html .= '</div>';
			
			if ($datos['next'] != 'null' || $datos['previous'] != 'null') {
				$html .= $this->renderizar_paginacion($datos['previous'], $datos['next'], ($pag ? 12 : $pag), $datos['count'], $query);
			}
			
		} else {
			$html .= '<p class="resultados__mensaje">No hay resultados</p>';
		}
		
		return $html;
	}
	
	public function renderizar_paginacion($anterior, $siguiente, $tamanio, $total, $query)
	{
		$html = '<div class="paginacion">';
		
		$botones = $total % $tamanio == 0 ? $total / $tamanio : ($total / $tamanio) + 1;

		$actual = 1;
		if ($anterior != null) {
			$actual = $this->obtener_parametro($anterior,'page', 1) + 1;;
		} elseif ($siguiente != null) {
			$actual = $this->obtener_parametro($siguiente,'page', 1) - 1;
		}
		$query = $query ? '&nombre='.$query : '';
		for	($i = 1; $i <= $botones; $i++) {
			if ($i == $actual) {
				$html .= '<button type="button" class="paginacion__boton paginacion__boton--activo" disabled>'.$i.'</button>';
			} else {
				$html .= '<button type="button" class="paginacion__boton" data-pagina="'.self::$URL_API_GOB.'?page='.$i.'&page_size='.$tamanio.$query.'">'.$i.'</button>';
			}
		}
		
		$html .= '</div>';
		
		return $html;
	}

	public function boton_shortcode_buscador_guias_turisticos()
	{
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
			return;

		add_filter("mce_external_plugins", array($this, "registrar_tinymce_plugin")); 
		add_filter('mce_buttons', array($this, 'agregar_boton_tinymce_shortcode_buscador_guias_turisticos'));
	}

	public function registrar_tinymce_plugin($plugin_array)
	{
		$plugin_array['buscguiasturcba_button'] = $this->cargar_url_asset('/js/shortcodeGuiasTuristicos.js');
	    return $plugin_array;
	}

	public function agregar_boton_tinymce_shortcode_buscador_guias_turisticos($buttons)
	{
	    $buttons[] = "buscguiasturcba_button";
	    return $buttons;
	}

	public function cargar_assets()
	{
		$urlJSBuscador = $this->cargar_url_asset('/js/buscadorGuiasTuristicos.js');
		$urlCSSBuscador = $this->cargar_url_asset('/css/shortcodeGuiasTuristicos.css');
		
		wp_register_style('buscador_guias_turisticos_cba.css', $urlCSSBuscador);
		wp_register_script('buscador_guias_turisticos_cba.js', $urlJSBuscador);
		
		global $post;
	    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'buscador_guias_turisticos_cba') ) {
			wp_enqueue_script(
				'buscar_guias_turisticos_ajax', 
				$urlJSBuscador, 
				array('jquery'), 
				'1.0.0',
				TRUE
			);
			wp_enqueue_style('buscador_guias_turisticos.css', $this->cargar_url_asset('/css/shortcodeGuiasTuristicos.css'));
			
			$nonce_busquedas = wp_create_nonce("buscar_guia_turistico_nonce");
			
			wp_localize_script(
				'buscar_guias_turisticos_ajax', 
				'buscarGuiasTuristicos', 
				array(
					'url'   => admin_url('admin-ajax.php'),
					'nonce' => $nonce_busquedas
				)
			);
		}
	}
	
	public function buscar_guias_turisticos()
	{
		$idioma = $_REQUEST['idioma_id'];
		check_ajax_referer('buscar_guia_turistico_nonce', 'nonce');
	
		$idioma = $idioma == 0? '' : '&idioma_id='.$idioma;
		if(true && $nombre !== '') {
			$api_response = wp_remote_get(self::$URL_API_GOB.'?page_size=12'.$idioma);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			wp_send_json_success($this->renderizar_resultados($api_data,12,$nombre.$idioma));
		} else {
			wp_send_json_error($api_data);
		}
		
		die();
	}
	
	public function buscar_guias_turisticos_pagina()
	{
		$pagina = $_REQUEST['pagina'];
		$idioma = $_REQUEST['idioma_id'];
		check_ajax_referer('buscar_guia_turistico_nonce', 'nonce');

		$idioma = $idioma == 0? '' : '&idioma_id='.$idioma;
		if(true && $pagina !== '') {
			$api_response = wp_remote_get($pagina.$idioma);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			
			wp_send_json_success($this->renderizar_resultados($api_data,12,($nombre ? $nombre : '')));
		} else {
			wp_send_json_error($api_data);
		}
		
		die();
	}

	/*
	* Mira si la respuesta es un error, si no lo es, cachea por una hora el resultado.
	*/
	private function chequear_respuesta($api_response, $tipoObjeto)
	{
		if (is_null($api_response)) {
			return [ 'results' => [] ];
		} else if (is_wp_error($api_response)) {
			$mensaje = WP_DEBUG ? ' '.$this->mostrar_error($api_response) : '';
			return [ 'results' => [], 'error' => 'Ocurri&oacute; un error al cargar '.$tipoObjeto.'.'.$mensaje];
		} else {
			return json_decode(wp_remote_retrieve_body($api_response), true);
		}
	}


	/* Funciones de utilidad */

	private function mostrar_error($error)
	{
		if (WP_DEBUG === true) {
			return $error->get_error_message();
		}
	}

	private function formatear_fecha($original)
	{
		return date("d/m/Y", strtotime($original));
	}

	private function cargar_url_asset($ruta_archivo)
	{
		return plugins_url($this->minified($ruta_archivo), __FILE__);
	}

	// Se usan archivos minificados en producción.
	private function minified($ruta_archivo)
	{
		if (WP_DEBUG === true) {
			return $ruta_archivo;
		} else {
			$extension = strrchr($ruta_archivo, '.');
			return substr_replace($ruta_archivo, '.min'.$extension, strrpos($ruta_archivo, $extension), strlen($extension));
		}
	}
	
	private function obtener_parametro($url, $param, $fallback)
	{
		$partes = parse_url($url);
		parse_str($partes['query'], $query);
		$resultado = $query[$param] ? $query[$param] : $fallback;
		return $resultado;
	}
}