<?php
/*
 * Plugin Name: Oi Match Center
 * Description: Плагин для получения и вывода информации об игре с сайта odds.ru
 * Author: Isaenko Alexey
 * Version: 1.0
 * Author URI: http://oiplug.com/
 * Date: 27.07.18
 *
 * @author Isaenko Alexey <info@oiplug.com>
 */

Namespace oimatchcenter;

require 'config.php';

/**
 * Функция содержащая данные по умолчанию.
 *
 * @return array
 */
function vars() {
	return apply_filters( __NAMESPACE__ . '-vars', array(
		'host'        => 'https://odds.ru', // хост, с которого берется табличка с табами
		'params'      => array( // параметры ссылки для получения таблички
			'embed',
		),
		'api_url'     => MATCHCENTER_API_URL, // api url
		'credentials' => MATCHCENTER_CREDENTIALS, // данные для доступа к api
		'start'       => '-2 week', // диапазон проведения игр, начало и конец
		'end'         => '+3 month',
		'status'      => 'all', // статус игр(завершены/не завершены)
		'order_by'    => 'time', // сортировка по дате проведения
		'order_type'  => 'asc',
		'limit'       => 1000, // лимит количества получаемых записей
	) );
}

/**
 * Подключение стилей и скриптов в админке
 */
function enqueue_games_js() {
	$version = '20180730';
	wp_enqueue_style( 'oi-match-center', trailingslashit( plugins_url( '', __FILE__ ) ) . 'assets/style.css', array(), $version );
	wp_enqueue_script( 'oi-match-center', trailingslashit( plugins_url( '', __FILE__ ) ) . 'js/functions.js', array(), $version, true );
	wp_localize_script( 'oi-match-center', 'matchcenter', array(
		'is_user_logged_in' => is_user_logged_in(),
		'ajax_url'          => admin_url( 'admin-ajax.php' ),
	) );
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_games_js' );

/**
 * Функция загрузки шаблона с возможностью передачи в него массива с переменными.
 *
 * @param       $name
 * @param array $data
 *
 * @return string
 */
function get_template( $name, $data = array() ) {

	ob_start();
	include \plugin_dir_path( __FILE__ ) . '/templates/' . $name . '.php';

	return ob_get_clean();
}

/**
 * добавление кнопки и формы поиска в админке
 */
function add_media_element() {
	echo get_template( 'search' );
}

add_action( 'media_buttons', __NAMESPACE__ . '\add_media_element', 12 );

/**
 * Функция получения массива с данными о матчах.
 */
function get_games() {
	if ( ! empty( $_GET['search'] ) ) {
		$var                      = vars();
		$search_value             = $_GET['search'];
		$start                    = gmdate( 'Y-m-d_00:00:00', strtotime( $var['start'] ) );
		$end                      = gmdate( "Y-m-d_00:00:00", strtotime( $var['end'] ) );
		$headers['Authorization'] = 'Basic ' . base64_encode( $var['credentials'] );

		$defaults = [
			'status'     => $var['status'],
			'start'      => $start,
			'end'        => $end,
			'order_by'   => $var['order_by'],
			'order_type' => $var['order_type'],
			'limit'      => $var['limit']
		];
		$args     = [
			//'sr_ids' => implode(',', $srIds),
			'search'        => $search_value,
			'search_season' => $search_value,
		];
		$args     = array_merge( $defaults, $args );

		$transient_hash = md5( implode( '', $args ) );
		if ( ! $result = get_transient( $transient_hash ) ) {

			if ( isset( $args['ids'] ) && is_array( $args['ids'] ) ) {
				$args['ids'] = implode( ',', array_map( 'intval', $args['ids'] ) );
				unset( $args['start'], $args['end'] );
			}

			if ( isset( $args['sr_ids'] ) && is_array( $args['sr_ids'] ) ) {
				$args['sr_ids'] = implode( ',', array_map( 'intval', $args['sr_ids'] ) );
				unset( $args['start'], $args['end'] );
			}

			$request = new \WP_Http();
			$result  = $request->post( $var['api_url'], array(
				'sslverify' => false,
				'headers'   => $headers,
				'body'      => $args
			) );

			if ( ! is_wp_error( $result ) && $result['response']['code'] === 200 ) {
				$result = json_decode( $result['body'], true );
			}

			if ( ! $result || is_wp_error( $result ) ) {
				$result = array();
			}

			set_transient( $transient_hash, $result, HOUR_IN_SECONDS );
		}

		wp_send_json( $result );
	}
	wp_send_json( array( 'post' => $_POST, 'get' => $_GET, ) );
}

add_action( 'wp_ajax_' . __NAMESPACE__ . '-get_games', __NAMESPACE__ . '\get_games' );

/**
 * Шорткод для вывода информации об игре.
 *
 * @param $atts
 *
 * @return string
 */
function get_game_shortcode( $atts ) {
	$atts = wp_parse_args( $atts, array(
		'url'   => 0,
		'id'    => 0,
		'class' => apply_filters( __NAMESPACE__ . '-class', 'odds-embed-widget' ),
	) );

	// если url передан
	if ( ! empty( $atts['url'] ) ) {
		$var = vars();

		// если есть функция определения ARM версии сайта
		if ( function_exists( 'is_arm' ) && is_arm() ) {

			// в запрос добавляется параметр
			$var['params'][] = 'arm=1';
		}

		// если указаны какие-то параметры
		if ( ! empty( $var['params'] ) ) {
			$var['params'] = '?' . implode( '&', $var['params'] );
		} else {
			$var['params'] = '';
		}


		$atts['url'] = untrailingslashit( $var['host'] ) . trailingslashit( $atts['url'] ) . $var['params'];

		return get_template( 'frame', $atts );
	}

	return '';
}

add_shortcode( 'game', __NAMESPACE__ . '\get_game_shortcode' );


// eof
