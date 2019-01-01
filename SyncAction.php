<?php

/**
 * Sync's a page with other wikis
 *
 * @ingroup Actions
 */

use Nischayn22\MediaWikiApi;

class SyncAction extends FormAction {

	public function getName() {
		return 'sync';
	}

	protected function getDescription() {
		return '';
	}

	public function onSubmit( $data ) {
		global $wgSyncWikis, $wgUser;

		$title = $this->getTitle()->getFullText();
		$wikiPage = new WikiPage( $this->getTitle() );

		if ( class_exists( 'CommentStreams' ) ) {
			$comments = CommentStreams::singleton()->getComments( $this->getTitle()->getArticleID(), $wgUser );
			$child_comments = array();
			foreach( $comments as &$comment ) {
				if ( array_key_exists( 'children', $comment ) ) {
					foreach( $comment['children'] as &$child_comment) {
						$child_comment['parent_comment_title'] = WikiPage::newFromId( $child_comment['parentid'] )->getTitle()->getFullText();
					}

					$child_comments = array_merge( $child_comments, $comment['children'] );
					unset( $comment['children'] );
				}
			}
			$comments = array_merge( $comments, $child_comments );
			// Generate Export
			$exporter = new WikiExporter( wfGetDB( DB_MASTER ) );
			$comments_export = new DumpStringOutput;
			$exporter->setOutputSink( $comments_export );
			$exporter->openStream();
			foreach ( $comments as $comment_data ) {
				$exporter->pageByTitle( Title::newFromText( $comment_data['comment_page_title'] ) );
			}
			$exporter->closeStream();
		}
		
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				if ( class_exists( 'CommentStreams' ) ) {
					$syncWiki->importXML( $comments_export->__toString() );
				}
				if ( $wgSyncWiki['translate'] ) {
					$autoTranslate = new AutoTranslate( $wgSyncWiki['translate_to'] );
					$title = $autoTranslate->translateTitle( $wikiPage->getId() );
					$content = $autoTranslate->translate( $wikiPage->getId() );
				} else {
					$revision = $wikiPage->getRevision();
					$content = ContentHandler::getContentText( $revision->getContent( Revision::RAW ) );
				}
				$syncWiki->editPage( $title, $content );
			}
		}
		return true;
	}

	protected function checkCanExecute( User $user ) {
		// Must be logged in
		if ( !in_array( 'sysop', $user->getEffectiveGroups()) ) {
			throw new PermissionsError( 'sysop' );
		}
	}

	protected function usesOOUI() {
		return true;
	}

	protected function getFormFields() {
		return [
			'intro' => [
				'type' => 'info',
				'vertical-label' => true,
				'raw' => true,
				'default' => "Confirm Sync (this may take a while)?"
			]
		];
	}

	protected function alterForm( HTMLForm $form ) {
		$form->setSubmitText( 'Confirm' );
		$form->setTokenSalt( 'sync' );
	}

	public function onSuccess() {
		$this->getOutput()->addHtml( "Page has been synced!" );
	}

	public function doesWrites() {
		return false;
	}
}
