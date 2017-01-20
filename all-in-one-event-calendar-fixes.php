<?php
/**
 * Plugin Name: All-in-One Event Calendar Fixes
 * Description: All-in-One Event Calendar Fixes
 * Author: charliecek
 * Author URI: http://charliecek.eu/
 * Version: 0.1
 */

define( "ATTACHMENT_COUNT_NUMBER_LIMIT", 10 );
define( "ATTACHMENT_COUNT_NUMBER_LIMIT_TIMEOUT", 2*60 );

class AI1EC_Fixes {
  /**
    * Constructor
    */
  public function __construct() {
    add_filter( 'ai1ec_pre_init_event_from_feed', array( $this, 'ai1ecf_filter_pre_init_event_from_feed' ), 10, 3 );
    add_filter( 'ai1ec_contact_url', array( $this, 'ai1ecf_filter_contact_url' ) );
    add_action( 'ai1ec_ics_event_saved', array( $this, 'ai1ecf_action_ics_event_saved' ), 10, 2 );
    add_action( 'init', array( $this, 'ai1ecf_translations_load') );
    add_action( 'ai1ec_pre_save_event', array( $this, 'ai1ecf_action_pre_save_event' ), 10, 2 );
//     add_action( 'ai1ec_event_saved', array( $this, 'ai1ecf_action_event_saved' ), 10, 3 );
    
//     add_action( 'admin_menu', array( $this, "ai1ecf_add_options_page" ) );
  
    add_action( 'ai1ecf_add_missing_featured_images', array( $this, 'ai1ecf_add_missing_featured_images' ) );
  }

  public static function ai1ecf_activate() {
    if ( !wp_next_scheduled( 'ai1ecf_add_missing_featured_images' ) ) {
        wp_schedule_event( time(), 'hourly', 'ai1ecf_add_missing_featured_images');
    }
  }
  
  public static function ai1ecf_deactivate() {
    wp_clear_scheduled_hook('ai1ecf_add_missing_featured_images');
  }

  public function ai1ecf_translations_load() {
    load_plugin_textdomain('ai1ecf', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
  }
  
  public function ai1ecf_add_options_page() {
    add_options_page(
      "All-in-One Event Calendar Fixes",
      "All-in-One Event Calendar Fixes",
      "manage_options",
      "ai1ecf_ai1ec_fixes",
      array( $this, "ai1ecf_options_page" )
    );
  }
  
  public function ai1ecf_options_page() {
    echo "<h1>All-in-One Event Calendar Fixes</h1>";
    
    
    echo "<pre>";
    $aPostsWithoutFI = $this->ai1ecf_get_events_with_no_featured_image();
    var_dump($aPostsWithoutFI);
//     var_dump($aPostsWithoutFI[0]->ID);
//     var_dump(maybe_unserialize(get_option('kkdebug_e222456421507885@facebook.com_aData')));
//     var_dump(maybe_unserialize(get_option('kkdebug_e222456421507885@facebook.com_oEvent')));
//     var_dump(maybe_unserialize(get_option('kkdebug_e222456421507885@facebook.com_oFeed')));
    echo "</pre>";
  }
  
  public function ai1ecf_filter_pre_init_event_from_feed( $aData, $oEvent, $oFeed ) {
    $aFields = array("contact_name", "contact_email");
    foreach ($aFields as $strField) {
      // $strField = "contact_name";
      if (!empty($aData[$strField])) {
        $aData[$strField] = str_ireplace('mailto:', '', $aData[$strField]);
        $aData[$strField] = str_ireplace('noreply@facebookmail.com', '', $aData[$strField]);
      }
      if (empty($aData['contact_url'])) {
        $aData[$strField] = '';
      }
    }
    if (!empty($aData['contact_phone']) && !preg_match( '/^[\d()+\s-]*$/', $aData['contact_phone'] )) {
      $aData['contact_url'] .= ";" . $aData['contact_phone'];
      $aData['contact_phone'] = '';
    }
    $aData['post']['post_author'] = 2;
    $aData['post']['post_content'] = $this->ai1ecf_auto_link_text($aData['post']['post_content'], true);
    $aEventUrl = explode('_-_-_', $aData['ical_source_url']);
    $aData['ical_source_url'] = $aEventUrl[0];
    if (isset($aEventUrl[1])) {
      $aData['post']['post_content'] .= "<!-- thumbnail-" . $aEventUrl[1] . "-thumbnail";
      if (isset($aEventUrl[2])) {
        $aData['post']['post_content'] .= "-new=".$aEventUrl[2];
      }
      $aData['post']['post_content'] .=  " -->";
    }
    // file_put_contents(__DIR__.'/debug.kk', var_export($aEventUrl, true)."\n", FILE_APPEND | LOCK_EX);

    $this->ai1ecf_add_debug_log( "ai1ecf_filter_pre_init_event_from_feed" );
//     if ($aData['post']['post_title'] == 'Mistrovství ČR v tancích Salsa a Bachata') {
//       $this->ai1ecf_add_debug_log( var_export($aData, true)  ."\n". var_export($oEvent->dtstart)."\n". var_export($oEvent->dtend), false, "debug-filter.kk" );
//     }
    
    return $aData;
  }

  public function ai1ecf_filter_contact_url ($strString) {
    return __( 'Organizer website', "ai1ecf" );
  }
  
  public function ai1ecf_action_ics_event_saved( $oEvent, $oFeed ) {
    $iPostID = $oEvent->get( 'post_id' );
    $oPost = $oEvent->get( 'post' );
    
    $this->ai1ecf_add_debug_log( "ai1ecf_action_ics_event_saved" );
    
    $GLOBALS['ai1ecf_event_save'] = true;
    $GLOBALS['ai1ecf_event_fname'] = 'save-debug-log-'. $iPostID . '.kk';
    $GLOBALS['ai1ecf_event_fpath'] = __DIR__ . '/' . $GLOBALS['ai1ecf_event_fname'];
    $res = $oEvent->save( true );
    if (false === $res) {
      wp_trash_post($iPostID);
      $this->ai1ecf_add_debug_log( "ai1ecf_action_ics_event_saved: trashed post number ".$iPostID );
      return;
    } else {
      if (file_exists($GLOBALS['ai1ecf_event_fpath'])) {
        // rename( $GLOBALS['ai1ecf_event_fpath'], $GLOBALS['ai1ecf_event_fpath'].'.ok' );
        unlink($GLOBALS['ai1ecf_event_fpath']);
      }
    }
    $GLOBALS['ai1ecf_event_save'] = false;

    $this->ai1ecf_parse_and_add_featured_image( $oPost, $iPostID );
  }
  
  private function ai1ecf_parse_and_add_featured_image( $oPost, $iPostID = -1 ) {
    if ($iPostID === -1) {
      $iPostID = $oPost->ID;
    }

    $strPostContent = $oPost->post_content;
    $aMatches = array();
    $strPattern =
        preg_quote("<!-- thumbnail-")
        .'(.*)'
        .preg_quote("-thumbnail")
        .'('
        .preg_quote('-new=')
        .'[0-1])?'
        .preg_quote(" -->");
    preg_match('/'.$strPattern.'/', $strPostContent, $aMatches);
    if (!empty($aMatches)) {
      $strPostThumbnailUrl = str_replace('&amp;', '&', $aMatches[1]);
      $bIsNew = false;
      if (isset($aMatches[2])) {
        $bIsNew = ($aMatches[2] === '-new=1' );
      }
      // file_put_contents(__DIR__.'/debug.kk', var_export($iPostID, true)."\n", FILE_APPEND | LOCK_EX);
      // file_put_contents(__DIR__.'/debug.kk', var_export($oPost, true)."\n", FILE_APPEND | LOCK_EX);
      // file_put_contents(__DIR__.'/debug.kk', var_export($aMatches, true)."\n", FILE_APPEND | LOCK_EX);
      // file_put_contents(__DIR__.'/debug.kk', var_export($strPostThumbnailUrl, true)."\n", FILE_APPEND | LOCK_EX);
      $this->ai1ecf_add_debug_log( "ai1ecf_parse_and_add_featured_image" );
      
      $this->ai1ecf_maybe_set_featured_image( $iPostID, $strPostThumbnailUrl, $bIsNew );
    }
  }

  private function ai1ecf_maybe_set_featured_image( $iPostID, $strImageUrl, $bIsNew = false ) {
    $bHasPostThumbnail = has_post_thumbnail($iPostID);
    if ((!$bIsNew && $bHasPostThumbnail) || !isset($strImageUrl) || empty($strImageUrl)) {
      return;
    }
    
    // make sure the events are imported even if there are too many featured images to import
    $aAttachmentCounter = get_option('ai1ecf_attachment_counter');
    if (false === $aAttachmentCounter) {
      $aAttachmentCounter = array(
        'time' => time(),
        'counter' => 1
      );
    } else {
      if (isset($aAttachmentCounter['time'], $aAttachmentCounter['counter']) && time() - $aAttachmentCounter['time'] < ATTACHMENT_COUNT_NUMBER_LIMIT_TIMEOUT) {
        $aAttachmentCounter['counter']++;
      } else {
        $aAttachmentCounter['time'] = time();
        $aAttachmentCounter['counter'] = 1;
      }
    }
    update_option('ai1ecf_attachment_counter', $aAttachmentCounter, true);
    
    if ($aAttachmentCounter['counter']>ATTACHMENT_COUNT_NUMBER_LIMIT) {
      return;
    }

    // get old attachment IDs so we're not setting one of those as featured
    $aArgs = array(
      'post_type' => 'attachment',
      'posts_per_page' => -1,
      'post_status' => 'any',
      'post_parent' => $iPostID
    );
    $aOldAttachments = get_posts($aArgs);
    $aOldAttachmentIDs = array();
    if(isset($attachments) && is_array($attachments)){
      foreach($attachments as $attachment){
        $aOldAttachmentIDs[] = $attachment->ID;
      }
    }
    if ($bHasPostThumbnail) {
      delete_post_thumbnail($iPostID);
    }
    
    // load necessary files //
    if (defined('ABSPATH') && !empty(ABSPATH)) {
      $strPathToWPAdmin = ABSPATH . "wp-admin";
    } else {
      $strPathToWPAdmin = dirname( dirname( dirname( __DIR__ ) ) ) . "/wp-admin";
    }
    require_once($strPathToWPAdmin . '/includes/image.php');
    require_once($strPathToWPAdmin . '/includes/file.php');
    require_once($strPathToWPAdmin . '/includes/media.php');
    
    // magic sideload image returns an HTML image, not an ID
    $media = media_sideload_image( $strImageUrl, $iPostID, null, 'src' );

    // therefore we must find it so we can set it as featured ID
    if(!empty($media) && !is_wp_error($media)){
      $this->ai1ecf_add_debug_log("successfully added image (" .$media. ") to post " . $iPostID . " via media_sideload_image()", false);
      
      $aArgs['exclude'] = $aOldAttachmentIDs;

      // reference new image to set as featured
      $aAttachments = get_posts($aArgs);

      if(isset($aAttachments) && is_array($aAttachments)){
        foreach($aAttachments as $attachment){
          // grab source of full size images (so no 300x150 nonsense in path)
          $image = wp_get_attachment_image_src($attachment->ID, 'full');
          // determine if in the $media image we created, the string of the URL exists
          if (strpos($media, $image[0]) !== false){
            // if so, we found our image. set it as thumbnail
            set_post_thumbnail($iPostID, $attachment->ID);
            $this->ai1ecf_add_debug_log("image " . $image[0] . " was set as featured image for post " . $iPostID, false);
            // only want one image
            break;
          }
        }
      }
    } else {
      $this->ai1ecf_add_debug_log("could not media_sideload_image() to post " . $iPostID, false);
    }
  }
  /**
   * Replace links in text with html links
   *
   * @param  string $text
   * @return string
   * 
   * Sources: http://stackoverflow.com/a/1959073, http://daringfireball.net/2010/07/improved_regex_for_matching_urls
   */
  private function ai1ecf_auto_link_text($text, $bOpenInNewWindow = false) {
    $pattern  = '#(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#';
    $strTarget = $bOpenInNewWindow ? 'target="_blank" ' : '';
    $callback = create_function('$matches', '
         $url       = array_shift($matches);
         $url_parts = parse_url($url);

         $text = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
         $text = preg_replace("/^www./", "", $text);

         $last = -(strlen(strrchr($text, "/"))) + 1;
         if ($last < 0) {
             $text = substr($text, 0, $last) . "&hellip;";
         }

         return sprintf(\'<a rel="nowfollow" '.$strTarget.'href="%s">%s</a>\', $url, $text);
    ');

    return preg_replace_callback($pattern, $callback, $text);
  }
  
  private function ai1ecf_get_events_with_no_featured_image() {
    $aArgs = array(
      'posts_per_page' => -1,
      'post_type' => 'ai1ec_event',
      'meta_query' => array(
        array(
          'key' => '_thumbnail_id',
          'value' => '?',
          'compare' => 'NOT EXISTS'
        )
      )
    );
    return get_posts( $aArgs );
  }
  
  public function ai1ecf_add_missing_featured_images() {
    $aPostsWithoutFI = $this->ai1ecf_get_events_with_no_featured_image();

    $this->ai1ecf_add_debug_log( "ai1ecf_add_missing_featured_images" );

    if (is_array($aPostsWithoutFI) && !empty($aPostsWithoutFI)) {
      foreach ($aPostsWithoutFI as $oPost) {
        $this->ai1ecf_parse_and_add_featured_image( $oPost );
      }
    }
  }
  
  public function ai1ecf_action_pre_save_event( $eventObject, $update ) {
    if (isset($GLOBALS['ai1ecf_event_save']) && true === $GLOBALS['ai1ecf_event_save'] && isset($GLOBALS['ai1ecf_event_fname'])) {
      $columns    = $eventObject->prepare_store_entity();
      $backward_compatibility = true; // default anyway //
      $format     = $eventObject->prepare_store_format( $columns, $backward_compatibility );
      $this->ai1ecf_add_debug_log('columns: ' . var_export($columns, true), false, $GLOBALS['ai1ecf_event_fname']);
      $this->ai1ecf_add_debug_log('format: ' . var_export($format, true), false, $GLOBALS['ai1ecf_event_fname']);
    }
  }

  // public function ai1ecf_action_event_saved( $post_id, $eventObject, $update ) {
  // }

  private function ai1ecf_add_debug_log( $strText, $bAddCountOnDuplicates = true, $strDebugFileName = "debug.kk" ) {
    $strDebugFile = __DIR__.'/'.$strDebugFileName;
    if ($bAddCountOnDuplicates) {
      $strDate = date('Y-m-d H:i');
      $strSec = date('s');
      $strLogText = $strDate . " " . $strText;
      $strDebugFileContents = file_get_contents($strDebugFile);
      if (strpos($strDebugFileContents, $strLogText) !== false) {
        $strDebugFileContents = str_replace( $strLogText, $strLogText . " " . $strSec, $strDebugFileContents );
        file_put_contents($strDebugFile, $strDebugFileContents);
      } else {
        $strLogText = $strDate . " " . $strText . " " . $strSec;
        file_put_contents($strDebugFile, $strLogText."\n", FILE_APPEND | LOCK_EX);
      }
    } else {
      $strDateExact = date('Y-m-d H:i:s');
      $strLogText = $strDateExact . " " . $strText;
      file_put_contents($strDebugFile, $strLogText."\n", FILE_APPEND | LOCK_EX);
    }
  }
  
}

$AI1EC_Fixes = new AI1EC_Fixes();
register_activation_hook(__FILE__, array('AI1EC_Fixes', 'ai1ecf_activate'));
register_deactivation_hook(__FILE__, array('AI1EC_Fixes', 'ai1ecf_deactivate'));