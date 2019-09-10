<?php

use Nischayn22\MediaWikiApi;

class SyncHooks {

	public static function onTranslationsApproved( $pageId, $target_lang ) {
		global $wgSyncWikis;

		$title = '';
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			if ( $wgSyncWiki['translate_to'] != $target_lang ) {
				continue;
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				if ( $wgSyncWiki['translate'] ) {
					$autoTranslate = new AutoTranslate( $target_lang );
					$title = $autoTranslate->translateTitle( $pageId );
					$content = $autoTranslate->translate( $pageId );
				} else {
					$revision = $wikiPage->getRevision();
					$content = ContentHandler::getContentText( $revision->getContent( Revision::RAW ) );
					$title = Title::newFromId( $pageId )->getFullText();
				}
				$syncWiki->editPage( $title, $content );
			}
		}
		return true;
	}

	public static function onPageContentSaveComplete( &$wikiPage, &$user, $content, $summary, $isMinor, $isWatch, $section, &$flags, $revision, &$status, $baseRevId, $undidRevId ) {
		global $wgSyncWikis;

		$title = '';
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			if ( !$wgSyncWiki['live_edit'] ) {
				continue;
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				if ( $wgSyncWiki['translate'] ) {
					$autoTranslate = new AutoTranslate( $wgSyncWiki['translate_to'] );
					$title = $autoTranslate->translateTitle( $wikiPage->getId() );
					$syncWiki->editPage( $title, $autoTranslate->translate( $wikiPage->getId() ) );
				} else {
					$title = $wikiPage->getTitle()->getFullText();
					$syncWiki->editPage( $title, $content->getNativeData() );
				}
			}
		}
		return true;
	}

	public static function onPageContentInsertComplete( &$wikiPage, User &$user, $content, $summary, $isMinor, $isWatch, $section, &$flags, Revision $revision ) {
		global $wgSyncWikis, $wgSyncGoogleTranslateProjectId;
		$title = $wikiPage->getTitle()->getFullText();
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			if ( !$wgSyncWiki['live_create'] ) {
				continue;
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
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

	public static function onTitleMoveComplete( Title &$title, Title &$newTitle, User $user, $oldid, $newid, $reason, Revision $revision ) {
		global $wgSyncWikis;
		foreach ( $wgSyncWikis as $wgSyncWiki ) {
			if ( !$wgSyncWiki['live_move'] ) {
				continue;
			}
			$syncWiki = new MediaWikiApi( $wgSyncWiki['api_path'] );
			if ( $syncWiki->login( $wgSyncWiki['username'], $wgSyncWiki['password'] ) ) {
				$rev_id = $title->getLatestRevID();
				$old_revision = Revision::newFromId( $rev_id );
				$title = $title->getFullText();
				if ( $wgSyncWiki['translate'] ) {
					$autoTranslate = new AutoTranslate( $wgSyncWiki['translate_to'] );
					$title = $autoTranslate->translateTitle( $old_revision->getPage() );
					$content = $autoTranslate->translate( $old_revision->getPage() );
				} else {
					$content = ContentHandler::getContentText( $old_revision->getContent( Revision::RAW ) );
				}
				$syncWiki->editPage( $title->getPrefixedText(), $content );

				$rev_id = $newTitle->getLatestRevID();
				$new_revision = Revision::newFromId( $rev_id );
				$title = $newTitle->getFullText();
				if ( $wgSyncWiki['translate'] ) {
					$autoTranslate = new AutoTranslate( $wgSyncWiki['translate_to'] );
					$title = $autoTranslate->translateTitle( $new_revision->getPage() );
					$content = $autoTranslate->translate( $new_revision->getPage() );
				} else {
					$content = ContentHandler::getContentText( $new_revision->getContent( Revision::RAW ) );
				}
				$syncWiki->editPage( $title, $content );
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

	public static function onSkinTemplateNavigation( SkinTemplate &$skinTemplate, array &$links ) {
		global $wgUser;
		if ( !in_array( 'sysop', $wgUser->getEffectiveGroups()) ) {
			return true;
		}
		$request = $skinTemplate->getRequest();
		$action = $request->getText( 'action' );
		$links['actions']['sync'] = array(
			'class' => ( $action == 'sync') ? 'selected' : false,
			'text' => "Sync",
			'href' => $skinTemplate->makeArticleUrlDetails(
				$skinTemplate->getTitle()->getFullText(), 'action=sync' )['href']
		);
		return true;
	}
}