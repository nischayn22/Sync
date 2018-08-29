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
		global $wgSyncWikis;

		$dbr = wfGetDB( DB_SLAVE );
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			$pages = array();
			if( count( $wgSyncWiki['copy_ns'] ) > 0 ) {
				foreach( $wgSyncWiki['copy_ns'] as $namespaceId ) {
					$conds = [ 'page_namespace' => $namespaceId, 'page_is_redirect' => 0 ];
					$res = $dbr->select( 'page',
						[ 'page_title', 'page_id' ],
						$conds,
						__METHOD__
					);
					foreach( $res as $row ) {
						$pages[$row->page_id] = $row->page_title;
					}
				}
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			echo "Logging in to sync wiki\n";
			$syncWiki->logout();
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				echo "Successfully logged in\n";
			}

			$autoTranslate = new AutoTranslate( $wgSyncWiki['translate_to'] );

			foreach( $pages as $pageid => $pageName ) {
				if ( $wgSyncWiki['translate'] ) {
					$title = $autoTranslate->translateTitle( $pageid );
					$content = $autoTranslate->translate( $pageid );
				} else {
					$revision = Revision::newFromPageId( $pageid );
					$content = ContentHandler::getContentText( $revision->getContent( Revision::RAW ) );
					$title = $pageName;
				}
				$data = $syncWiki->editPage( $title, $content );
				if ( $data ) {
					echo "Synced $title\n";
				} else {
					echo "Could not sync $title\n";
				}
			}
		}
	}
}

require_once( DO_MAINTENANCE );
