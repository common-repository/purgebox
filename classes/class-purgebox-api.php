<?php
/**
 * PurgeBox base class
 */
require_once dirname( __FILE__ ). '/class-purgebox-plugin.php';

/**
 * RedBox API.
 * @package RedBox
 * @author ShoheiTai
 * @copyright 2016 REDBOX All Rights Reserved.
 */
class PurgeBox_API extends PurgeBox_Plugin {

	/**
	 * API base url.
	 * @var array
	 */
	protected $_base_url = null;

	/**
	 * API key.
	 * @var string
	 */
	protected $_api_key = null;

	/**
	 * Purge group name.
	 * @var string
	 */
	protected $_group_name = null;

	/**
	 * API version.
	 * @var string
	 */
	protected $_version = null;

	/**
	 * Default constructor.
	 * @params string $version Url of the API server.
	 * @params string $api_key RedBox API key.
	 * @params string $group_name User group name.
	 */
	public function __construct( $version, $api_key, $group_name ) {
		// API url
		$url = array(
			'2' => 'https://api.cdnw.net/v2.1/'
		);
		if( !$this->_is_enabled_curl() ) {
			error_log( '['. self::PLUGIN_NAME. ']: you must install "php5-curl" for '. self::PLUGIN_NAME. 'to work.' );
		} elseif( !isset( $url[$version] ) ) {
			error_log( '['. self::PLUGIN_NAME. ']: Unknown version.' );
		}
		$this->_api_key = $api_key;
		$this->_group_name = $group_name;
		$this->_version = $version;
		$this->_base_url = isset( $url[$version] ) ? $url[$version] : null;
	}

	/**
	 * Sends a purge request.
	 * @params array|string $paths URL to purge.
	 */
	public function purge( $paths ) {
		foreach( (array)$paths as $path ) {
			$headers = $this->_get_base_header( $path );
		       	$method = 'POST';
			$headers['X-TYPE'] = 'SINGLE';
			$this->_request( $this->_base_url, array(
				'headers' => $headers,
				'method' => $method,
				'blocking' => false
			) );
		}
	}

	/**
	 * Sends a purge all request.
	 */
	public function purge_all() {
		$url = '/*';
		$home = parse_url( home_url() );
		if( !empty( $home['path'] ) ) {
			$url = rtrim($home['path'], '/'). $url;
		}
		$headers = $this->_get_base_header( $url );
		$method = 'POST';
		$headers['X-TYPE'] = 'REGEX';
		$this->_request( $this->_base_url, array(
			'headers' => $headers,
			'method' => $method
		) );
	}

	/**
	 * Check the curl support.
	 * @return boolean
	 */
	protected function _is_enabled_curl() {
		return function_exists( 'curl_version' );
	}

	/**
	 * Sends a Http request.
	 * @params string $url Request url.
	 * @params array $params Option argument.
	 * @return array|null
	 */
	protected function _request( $url, $params ) {
		if( !$this->_is_enabled_curl() ) {
			return false;
		}
		defined('WP_DEBUG') && WP_DEBUG && error_log(json_encode($params));
		$response = wp_remote_request( $url, $params );
		if( is_wp_error( $response ) ) {
			error_log( '['. self::PLUGIN_NAME. ']: '. $response->get_error_message() );
			$response = null;
		}
		defined('WP_DEBUG') && WP_DEBUG  && error_log(json_encode($response['response']));
		return $response;
	}

	/**
	 * Get the default header by version.
	 * @param string $url URL
	 * @return array
	 */
	protected function _get_base_header( $url ) {
		$headers = array();
		$headers['X-KEY'] = $this->_api_key;
		$headers['X-GROUP'] = $this->_group_name;
		$headers['X-PATH'] = $this->_get_path( $url );
		return $headers;
	}

	/**
	 * Get path from URL.
	 * @params string $url URL string.
	 * @return string
	 */
	protected function _get_path( $url ) {
		$url = parse_url( $url );
		$path = isset( $url['path'] ) ? $url['path'] : '';
		if( !empty( $url['query'] ) ) {
			$path .=  '?'. $url['query'];
		}
		return $path;
	}
}

