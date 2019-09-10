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
		$this->addOption(
			'pagelist',
			'Comma-separated list of pages to be imported',
			false,
			true
		);
	}

	public function execute() {
		global $wgSyncWikis;

		$dbr = wfGetDB( DB_SLAVE );
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			$pages = array();
			if ( $this->hasOption( 'pagelist' ) ) {
				$pagelist = $this->getOption( 'pagelist' );
				if ( $pagelist !== '' ) {
					$pagelist = explode( ',', $pagelist );
				}
				foreach( $pagelist as $page ) {
					$page_id = WikiPage::factory( Title::newFromText( $page ) )->getId();
					if ( is_null( $page_id ) ) {
						$this->error( "Could not find page_id for " . $page );
						return;
					}
					$pages[$page_id] = $page;
				}
			} else {
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
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			echo "Logging in to sync wiki: " . $wgSyncWiki['api_path'] . "\n";
			$syncWiki->logout();
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				echo "Successfully logged in\n";
			}

			if ($wgSyncWiki['translate']) {
				$autoTranslate = new AutoTranslate( $wgSyncWiki['translate_to'] );
			}

			foreach( $pages as $pageid => $pageName ) {
				if ( $wgSyncWiki['translate'] ) {
					echo "Translating title " . $pageName . " to ". $wgSyncWiki['translate_to'] ."\n";
					$title = $autoTranslate->translateTitle( $pageid );
					$content = $autoTranslate->translate( $pageid );
				} else {
					$revision = Revision::newFromPageId( $pageid );
					$content = ContentHandler::getContentText( $revision->getContent( Revision::RAW ) );
					$title = $pageName;
				}
				echo "Syncing title " . $pageName . "\n";
				$data = $syncWiki->editPage( $title, $content );
				if ( $data ) {
					echo "Synced $pageName as $title\n";
				} else {
					echo "Could not sync $pageName\n";
				}
			}
		}
	}
}

require_once( DO_MAINTENANCE );
