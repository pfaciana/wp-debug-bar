<?php

namespace DebugBar\Traits;

trait VersionsTrait
{
	protected $transientKey = 'gutenberg_version';
	protected $gutenbergVersions = [
		'6.1.0'  => '13.1-14.1',
		'6.0.3'  => '12.0-13.0',
		'6.0.2'  => '12.0-13.0',
		'6.0.1'  => '12.0-13.0',
		'6.0.0'  => '12.0-13.0',
		'5.9.3'  => '10.8-11.9',
		'5.9.2'  => '10.8-11.9',
		'5.9.1'  => '10.8-11.9',
		'5.9.0'  => '10.8-11.9',
		'5.8.3'  => '10.0-10.7',
		'5.8.2'  => '10.0-10.7',
		'5.8.1'  => '10.0-10.7',
		'5.8.0'  => '10.0-10.7',
		'5.7.1'  => '9.3-9.9',
		'5.7.0'  => '9.3-9.9',
		'5.6.1'  => '8.6-9.2',
		'5.6.0'  => '8.6-9.2',
		'5.5.3'  => '7.6-8.5',
		'5.5.2'  => '7.6-8.5',
		'5.5.1'  => '7.6-8.5',
		'5.5.0'  => '7.6-8.5',
		'5.4.2'  => '6.6-7.5',
		'5.4.0'  => '6.6-7.5',
		'5.3.4'  => '5.5-6.5',
		'5.3.3'  => '5.5-6.5',
		'5.3.2'  => '5.5-6.5',
		'5.3.1'  => '5.5-6.5',
		'5.3.0'  => '5.5-6.5',
		'5.2.7'  => '4.9-5.4',
		'5.2.6'  => '4.9-5.4',
		'5.2.5'  => '4.9-5.4',
		'5.2.4'  => '4.9-5.4',
		'5.2.3'  => '4.9-5.4',
		'5.2.2'  => '4.9-5.4',
		'5.2.1'  => '4.9-5.4',
		'5.2.0'  => '4.9-5.4',
		'5.1.6'  => '4.8',
		'5.1.5'  => '4.8',
		'5.1.4'  => '4.8',
		'5.1.3'  => '4.8',
		'5.1.2'  => '4.8',
		'5.1.1'  => '4.8',
		'5.1.0'  => '4.8',
		'5.0.10' => '4.7.1',
		'5.0.9'  => '4.7.1',
		'5.0.8'  => '4.7.1',
		'5.0.7'  => '4.7.1',
		'5.0.6'  => '4.7.1',
		'5.0.5'  => '4.7.1',
		'5.0.4'  => '4.7.1',
		'5.0.3'  => '4.7.1',
		'5.0.2'  => '4.7.0',
		'5.0.1'  => '4.6.1',
		'5.0.0'  => '4.6.1',
	];

	protected function expandVersion ( $version, $minSize = 3 )
	{
		return implode( '.', array_pad( explode( '.', trim( $version ) ), $minSize, 0 ) );
	}

	protected function getWordPressVersion ()
	{
		return get_bloginfo( 'version' );
	}

	protected function getGutenbergVersion ()
	{
		if ( defined( 'GUTENBERG_VERSION' ) ) {
			return GUTENBERG_VERSION;
		}

		if ( ( $wp_version = $this->expandVersion( $this->getWordPressVersion() ) )[0] < 5 ) {
			return FALSE;
		}

		if ( array_key_exists( $wp_version, ( $gb_versions = $this->gutenbergVersions ) ) ) {
			return $gb_versions[$wp_version];
		}

		if ( !empty( $gb_version = get_transient( $this->transientKey ) ) ) {
			return $gb_version;
		}

		$response = wp_remote_request( 'https://developer.wordpress.org/block-editor/contributors/versions-in-wordpress/' );

		if ( is_wp_error( $response ) || !is_array( $response ) || !array_key_exists( 'response', $response ) //
			|| !array_key_exists( 'code', $response['response'] ) || $response['response']['code'] >= 400 //
			|| !array_key_exists( 'body', $response ) || empty( $content = $response['body'] ) ) {
			return $this->getLastestVersionKnown();
		}

		$doc = new \DOMDocument;
		$doc->loadHTML( $content );
		$xpath = new \DOMXPath( $doc );

		if ( !empty( ( $rows = $xpath->query( './/main/table/tbody/tr' ) )->count() ) ) {
			foreach ( $rows as $row ) {
				if ( ( $tds = $xpath->query( 'td', $row ) )->count() == 2 ) {
					$row_gb_version = trim( $tds->item( 0 )->textContent );
					$row_wp_version = $this->expandVersion( $tds->item( 1 )->textContent );
					if ( $row_wp_version === $wp_version ) {
						set_transient( $this->transientKey, $row_gb_version, WEEK_IN_SECONDS );

						return $row_gb_version;
					}
					$gb_versions[$row_wp_version] = $row_gb_version;
				}
			}
		}

		$gb_version = $this->getLastestVersionKnown( $gb_versions );

		set_transient( $this->transientKey, $gb_version, WEEK_IN_SECONDS );

		return $gb_version;
	}

	protected function getLastestVersionKnown ( $gb_versions = [] )
	{
		$gb_versions = $gb_versions ?: $this->gutenbergVersions;
		$wp_versions = array_keys( $gb_versions );
		usort( $wp_versions, 'version_compare' );

		return $gb_versions[array_pop( $wp_versions )] . ' or higher';
	}
}