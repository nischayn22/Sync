# Sync
A MediaWiki extension that selective sync's your wiki with multiple other wikis.


## Installation

Add the following to your wiki's composer.json's require:

    "google/cloud-translate": "^1.2",
    "nischayn22/mediawiki-api": "dev-master"
Now run:

    composer update

## Configuration

    // Only required if login is required to read
    $wgSyncReadUser = array(
      'username' => "Nischayn22",
      'password' => "Password"
    );

    $wgSyncGoogleTranslateProjectId = 'xyz'; // Only needed if you want to enable translate

    // Add any such lines for multiple wikis
    $wgSyncWikis[] = array(
      'api_path' => "http://localhost/test",
      'username' => "Nischayn22",
      'password' => "Password",
      'copy_ns' => array( 0 ),
      'translate' => false,
      'translate_to' => 'hi'
    );
