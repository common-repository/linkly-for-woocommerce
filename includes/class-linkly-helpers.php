<?php

use GuzzleHttp\Exception\ConnectException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Linkly\OAuth2\Client\Helpers\LinklyOrderHelper;
use Linkly\OAuth2\Client\Helpers\LinklySsoHelper;
use Linkly\OAuth2\Client\Provider\LinklyProvider;
use function Linkly\OAuth2\Client\Helpers\dd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class LinklyHelpers {
	/**
	 * @var LinklyHelpers singleton instance
	 */
	protected static $instance;

	/**
	 * @var LinklyProvider
	 */
	private LinklyProvider $linklyProvider;

	/**
	 * @var LinklySsoHelper
	 */
	private LinklySsoHelper $linklySsoHelper;

	/**
	 * @var LinklyOrderHelper
	 */
	private LinklyOrderHelper $linklyOrderHelper;

	protected function __construct() {
		$this->linklyProvider = new LinklyProvider( [
			'clientId'     => get_option( 'linkly_settings_app_key' ), // 'test-wp-plugin'
			'clientSecret' => get_option( 'linkly_settings_app_secret' ), // 'secret',
			'redirectUri'  => rtrim( get_site_url() . '?linkly_callback' ),
			'environment'  => get_option( 'linkly_settings_environment' ) // options are "prod", "beta", "local"
		] );

		$this->linklySsoHelper   = new LinklySsoHelper( $this->linklyProvider );
		$this->linklyOrderHelper = new LinklyOrderHelper( $this->linklyProvider );
	}

	public static function instance(): LinklyHelpers {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return LinklySsoHelper
	 */
	public function getSsoHelper(): LinklySsoHelper {
		return $this->linklySsoHelper;
	}

	/**
	 * @return LinklyOrderHelper
	 */
	public function getOrderHelper(): LinklyOrderHelper {
		return $this->linklyOrderHelper;
	}

	/**
	 * @return LinklyProvider
	 */
	public function getLinklyProvider(): LinklyProvider {
		return $this->linklyProvider;
	}

	/**
	 * @return bool
	 */
	public function isConnectedWithVerification(): bool {
		try {
			$clientKey    = get_option( 'linkly_settings_app_key' );
			$clientSecret = get_option( 'linkly_settings_app_secret' );

			if ( ! $clientKey || ! $clientSecret ) {
				return false;
			}

			$this->getSsoHelper()->verifyClientCredentials();
			update_option( 'linkly_settings_app_connected', true );

			return true;
		} catch ( IdentityProviderException $e ) {
			update_option( 'linkly_settings_app_connected', false );

			return false;
		} catch ( ConnectException $e ) {
			return false;
		}
	}


	/**
	 * @return bool
	 */
	public function isConnected(): bool {
		return !!get_option( 'linkly_settings_app_connected' );
	}
}