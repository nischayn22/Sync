<?php

if ( getenv('MW_INSTALL_PATH') ) {
	require_once( getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php' );
} else {
	require_once( dirname( __FILE__ ) . '/../../../maintenance/Maintenance.php' );
}
$maintClass = "ImportWiki";

use Nischayn22\MediaWikiApi;

class ImportWiki extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = "Bulk Imports this wiki to other wikis.";
	}

	public function execute() {
		global $wgSyncWikis, $wgSyncReadUser, $wgSyncGoogleTranslateProjectId, $wgServer, $wgScriptPath;

		$sourceWiki = new MediaWikiApi( $wgServer . $wgScriptPath );
		$sourceWiki->login( $wgSyncReadUser['username'], $wgSyncReadUser['password'] );

		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			$pages = array();
			if( count( $wgSyncWiki['copy_ns'] ) > 0 ) {
				foreach( $wgSyncWiki['copy_ns'] as $namespaceId ) {
					$result = $sourceWiki->listPageInNamespace( $namespaceId );
					foreach( $result as $page ) {
						$pages[] = (string)$page['title'];
					}
				}
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			echo "Logging in to sync wiki\n";
			$syncWiki->logout();
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				echo "Successfully logged in\n";
			}

			foreach( $pages as $pageName ) {
				$content = $sourceWiki->readPage( $pageName );
				if ( $wgSyncWiki['translate'] ) {
					$syncWiki->setTranslateSettings( $wgSyncGoogleTranslateProjectId, $wgSyncWiki['translate_to'] );
					$content = $syncWiki->translateWikiText( $content );
				}
				$data = $syncWiki->editPage( $pageName, $content );
				if ( $data ) {
					echo "Synced $pageName\n";
				} else {
					echo "Could not sync $pageName\n";
				}
			}
		}
	}
}

require_once( DO_MAINTENANCE );
