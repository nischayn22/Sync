# Sync
A MediaWiki extension that selective sync's your wiki with multiple other wikis.


# Installation

	Download this repo on your extensions folder
	Add the following on your LocalSettings.php: wfLoadExtension( 'Sync' );
    Run the following command on your main directory: "composer require google/cloud-translate"
    Run the following command on your main directory: "composer require nischayn22/mediawiki-api:dev-master"
	Set environment variables for the Cloud Translate API. See https://cloud.google.com/translate/docs/reference/libraries#client-libraries-install-php

# Configuration

    // Add any such lines for multiple wikis
    $wgSyncWikis[] = array(
      'api_path' => "http://localhost/test",
      'username' => "Nischayn22",
      'password' => "Password",
      'copy_ns' => array( 0 ),
      'live_edit' => true,
      'live_create' => true,
      'live_move' => true,
      'live_delete' => true,
      'translate' => true,
      'translate_to' => 'hi'
    );
