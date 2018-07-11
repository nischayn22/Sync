<?php

use Nischayn22\MediaWikiApi;

class SyncHooks {

	public static function onPageContentSaveComplete( &$wikiPage, &$user, $content, $summary, $isMinor, $isWatch, $section, &$flags, $revision, &$status, $baseRevId, $undidRevId ) {
		global $wgSyncWikis, $wgSyncGoogleTranslateProjectId;
		$title = $wikiPage->getTitle();
		$revision = $wikiPage->getRevision();
		$content = ContentHandler::getContentText( $revision->getContent( Revision::RAW ) );
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			if ( !$wgSyncWiki['live_edit'] ) {
				continue;
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				if ( $wgSyncWiki['translate'] ) {
					$syncWiki->setTranslateSettings( $wgSyncGoogleTranslateProjectId, $wgSyncWiki['translate_to'] );
					$content = $syncWiki->translateWikiText( $content );
				}
				$syncWiki->editPage( $title->getPrefixedText(), $content );
			}
		}
		return true;
	}

	public static function onPageContentInsertComplete( &$wikiPage, User &$user, $content, $summary, $isMinor, $isWatch, $section, &$flags, Revision $revision ) {
		global $wgSyncWikis, $wgSyncGoogleTranslateProjectId;
		$title = $wikiPage->getTitle();
		$revision = $wikiPage->getRevision();
		$content = $revision->getContent( Revision::RAW );
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			if ( !$wgSyncWiki['live_create'] ) {
				continue;
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				if ( $wgSyncWiki['translate'] ) {
					$syncWiki->setTranslateSettings( $wgSyncGoogleTranslateProjectId, $wgSyncWiki['translate_to'] );
					$content = $syncWiki->translateWikiText( $content );
				}
				$syncWiki->editPage( $title->getPrefixedText(), $content );
			}
		}
		return true;
	}

	public static function onTitleMoveComplete( Title &$title, Title &$newTitle, User $user, $oldid, $newid, $reason, Revision $revision ) {
		global $wgSyncWikis, $wgSyncGoogleTranslateProjectId;
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			if ( !$wgSyncWiki['live_move'] ) {
				continue;
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				$rev_id = $title->getLatestRevID();
				$content = ContentHandler::getContentText( Revision::newFromId( $rev_id )->getContent( Revision::RAW ) );
				if ( $wgSyncWiki['translate'] ) {
					$syncWiki->setTranslateSettings( $wgSyncGoogleTranslateProjectId, $wgSyncWiki['translate_to'] );
					$content = $syncWiki->translateWikiText( $content );
				}
				$syncWiki->editPage( $title->getPrefixedText(), $content );

				$rev_id = $newTitle->getLatestRevID();
				$content = ContentHandler::getContentText( Revision::newFromId( $rev_id )->getContent( Revision::RAW ) );
				if ( $wgSyncWiki['translate'] ) {
					$syncWiki->setTranslateSettings( $wgSyncGoogleTranslateProjectId, $wgSyncWiki['translate_to'] );
					$content = $syncWiki->translateWikiText( $content );
				}
				$syncWiki->editPage( $newTitle->getPrefixedText(), $content );
			}
		}
		return true;
	}

	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id, $content, LogEntry $logEntry ) {
		global $wgSyncWikis;
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			if ( !$wgSyncWiki['live_delete'] ) {
				continue;
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				$syncWiki->deleteById( $id );
			}
		}
		return true;
	}
}