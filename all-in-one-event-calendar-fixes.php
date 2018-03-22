<?php
/**
 * Plugin Name: All-in-One Event Calendar Fixes
 * Description: All-in-One Event Calendar Fixes
 * Author: charliecek
 * Author URI: http://charliecek.eu/
 * Version: 1.1.0
 */

define( "AI1ECF_VERSION", "1.1.0" );
define( "ATTACHMENT_COUNT_NUMBER_LIMIT", 10 );
define( "ATTACHMENT_COUNT_NUMBER_LIMIT_TIMEOUT", 2*60 );
define( "AI1ECF_OPTION_LOC_FIELDS", "venue,address,contact_name");
define( "AI1ECF_OPTION_LOC_PREFIX", "ai1ecf_location_" );
define( "AI1ECF_OPTION_NONLOC_FIELDS", "reminder,cats-tabs");
define( "AI1ECF_OPTION_NONLOC_PREFIX", "ai1ecf_nonlocation_option_" );
define( "AI1ECF_OPTION_PREFIX", "ai1ecf_" );
define( "AI1ECF_PATH_TO_TEMPLATES", __DIR__ . "/view/" );
define( "AI1ECF_LOCATION_OVERRIDE_POSTMETA_ID", "ai1ecf-location-override" );
define( "AI1ECF_SKIP_EVENT_UPDATE_FROM_FEED_POSTMETA_ID", "ai1ecf-location-skip-event-update-from-feed" );

/*
TODO: skipping post refresh
  in filter ai1ec_pre_init_event_from_feed remove the data that we do not want to update - TODO test if that works!
*/

class AI1EC_Fixes {

  private $aFieldToPlaceholders         = array();
  private $aPlaceholderValues           = array();
  private $bLocationReplacementEnabled  = false;
  private $strOptionsPageSlug           = 'ai1ecf_ai1ec_fixes';
  private $aEventUpdateSkipFields       = array();
  
  /**
    * Constructor
    */
  public function __construct() {
    add_filter( 'ai1ec_pre_init_event_from_feed', array( $this, 'ai1ecf_filter_pre_init_event_from_feed' ), 10, 3 );
    add_filter( 'ai1ec_contact_url', array( $this, 'ai1ecf_filter_contact_url' ) );
    add_filter( 'pre_delete_post', array( $this, 'ai1ecf_filter_pre_delete_post' ), 10, 3 );
    add_action( 'ai1ec_ics_event_saved', array( $this, 'ai1ecf_action_ics_event_saved' ), 10, 2 );
    add_action( 'init', array( $this, 'ai1ecf_translations_load') );
    add_action( 'ai1ec_pre_save_event', array( $this, 'ai1ecf_action_pre_save_event' ), 10, 2 );
    add_action( 'admin_enqueue_scripts', array( $this, 'ai1ecf_action_admin_enqueue_scripts' ) );
    add_action( 'add_meta_boxes_ai1ec_event', array( $this, 'ai1ecf_event_metaboxes' ) );
    add_action( 'save_post_ai1ec_event', array( $this, 'ai1ecf_event_save_post' ) );
    add_action( 'ai1ec_ics_before_import', array( $this, 'ai1ecf_action_ics_before_import' ) );
//     add_action( 'ai1ec_event_saved', array( $this, 'ai1ecf_action_event_saved' ), 10, 3 );

    add_action( 'admin_menu', array( $this, "ai1ecf_add_options_page" ) );
  
    add_action( 'ai1ecf_add_missing_featured_images', array( $this, 'ai1ecf_add_missing_featured_images' ) );
    add_action( 'ai1ecf_send_newsletter_reminder', array( $this, 'ai1ecf_send_newsletter_reminder' ) );
    add_action( 'ai1ecf_add_missing_categories_and_tags', array( $this, 'ai1ecf_add_missing_categories_and_tags' ) );
    
    
    $this->aFieldToPlaceholders = array(
      'venue'                         => array('header-venue', 'header-location_replacement', 'header-examples', 'header-info-venue',
                                                'venue', 'venue_id', 'venue_value', 'examples', 'row-class'),
      'address'                       => array('header-address', 'header-location_replacement', 'header-only_if_empty', 'header-examples', 'header-info-address',
                                                'address', 'address_id', 'address_value', 'address_only_if_empty_value', 'checked', 'examples', 'row-class'),
      'contact_name'                  => array('header-contact_name', 'header-location_replacement', 'header-only_if_empty', 'header-examples', 'header-info-contact_name',
                                                'contact_name', 'contact_name_id', 'contact_name_value', 'contact_name_only_if_empty_value', 'checked', 'examples', 'row-class'),
      'location-metabox'              => array('location-metabox-title', 'location-metabox-description', 'location-metabox-value',
                                                'skip-event-update-from-feed-title', 'skip-event-update-from-feed-desc', 'skip-event-update-from-feed-checkboxes'),
      'skip-event-update-checkboxes'  => array( 'skip-event-update-checkbox-key', 'skip-event-update-checkbox-name', 'skip-event-update-checkbox-checked' ),
      'reminder'                      => array( 'label-users', 'label-email-addresses', 'label-email-subject', 'label-email-body', 'label-time', 'label-day',
                                                'users', 'reminder_users', 'email-addresses', 'email-subject', 'email-body-wp-editor', 'reminder_email-body-wp-editor', 'time-minute', 'time-hour', 'day',
                                                'options-weekdays', 'options-hour', 'options-minute' ),
      // ''  => array(  ),
    );
    $this->aPlaceholderValues = array(
      'header-venue'                        => __( "Venue", "ai1ecf" ),
      'header-location_replacement'         => __( "Location replacement", "ai1ecf" ),
      'header-contact_name'                 => __( "Contact name", "ai1ecf" ),
      'header-only_if_empty'                => __( "Replace only if location is empty?", "ai1ecf" ),
      'header-address'                      => __( "Address", "ai1ecf" ),
      'header-examples'                     => __( "Events", "ai1ecf" ),
      'header-info-venue'                   => __( "These rules affect hover pop-ins (calendar, agenda widget), single event pages, excerpts and the SRD newsletter theme.", "ai1ecf" ),
      'header-info-address'                 => __( "These rules affect hover pop-ins (calendar, agenda widget) and the SRD newsletter theme. <strong><em>They do not affect</em></strong> single event pages and excerpts.", "ai1ecf" ),
      'header-info-contact_name'            => __( "These rules affect hover pop-ins (calendar, agenda widget), single event pages, excerpts and the SRD newsletter theme.", "ai1ecf" ),
      'header-reminder'                     => __( "Newsletter reminder", "ai1ecf" ),
      'header-cats-tabs'                    => __( "Automatic categories and tabs", "ai1ecf" ),
      'button-value'                        => __( "Save", "ai1ecf" ),
      'location-replacement-enabled-label'  => __( "Enable location replacement rules?", "ai1ecf" ),
      'ai1ecf-metabox-title'                => __( "All-in-One Event Calendar Fixes", "ai1ecf" ),
      'skip-event-update-from-feed-title'   => __( "Skip update from feed", "ai1ecf" ),
      'skip-event-update-from-feed-desc'    => __( "Ceck the information that should not be updated when the event is (hourly) refreshed from the event feed", "ai1ecf" ),
      'location-metabox-title'              => __( "Location override", "ai1ecf" ),
      'location-metabox-description'        => __( "Override the event's location with this value. It takes priority over any other location override rules. Leave empty for no override.", "ai1ecf" ),
      'skip-event-update-checkbox_time'     => __( "Time", "ai1ecf" ),
      'skip-event-update-checkbox_place'    => __( "Place", "ai1ecf" ),
      'skip-event-update-checkbox_contact'  => __( "Contact", "ai1ecf" ),
      'label-users'                         => __( "Send reminders to these users: ", "ai1ecf" ),
      'label-email-addresses'               => __( "Send reminders to these email addresses as well: ", "ai1ecf" ),
      'label-email-subject'                 => __( "Email reminder subject: ", "ai1ecf" ),
      'label-email-body'                    => __( "Email reminder body: ", "ai1ecf" ),
      'label-day'                           => __( "Reminder day", "ai1ecf" ),
      'label-day-1'                         => __( "Monday", "ai1ecf" ),
      'label-day-2'                         => __( "Tuesday", "ai1ecf" ),
      'label-day-3'                         => __( "Wednesday", "ai1ecf" ),
      'label-day-4'                         => __( "Thursday", "ai1ecf" ),
      'label-day-5'                         => __( "Friday", "ai1ecf" ),
      'label-day-6'                         => __( "Saturday", "ai1ecf" ),
      'label-day-7'                         => __( "Sunday", "ai1ecf" ),
      'label-time'                          => __( "Reminder time", "ai1ecf" ),
      // ''  => __( "", "ai1ecf" ),
    );

    $this->bLocationReplacementEnabled = get_option( 'ai1ecf-location-replacement-enabled', false );
    
    if ($this->bLocationReplacementEnabled) {
      add_filter( 'ai1ec_rendering_single_event_venues',  array( $this, 'ai1ecf_filter_rendering_single_event_venues'), 10, 2 );
      add_filter( 'ai1ec_theme_args_month.twig',          array( $this, 'ai1ecf_filter_theme_args_month' ), 10, 2 );
      add_filter( 'ai1ec_theme_args_agenda-widget.twig',  array( $this, 'ai1ecf_filter_theme_args_agenda_widget' ), 10, 2 );
    }
    add_filter( 'ai1ec_theme_args_event-excerpt.twig',  array( $this, 'ai1ecf_filter_theme_args_event_excerpt' ), 10, 2 );
    
    $this->aEventUpdateSkipFields = array(
      'time'    => array( 'start', 'end', 'timezone_name', 'allday', 'instant_event', 'recurrence_rules', 'exception_rules', 'recurrence_dates', 'exception_dates' ),
      'place'   => array( 'venue', 'country', 'address', 'city', 'province', 'postal_code' ),
      'contact' => array( 'contact_name', 'contact_phone', 'contact_email', 'contact_url', 'ical_organizer', 'ical_contact')
    );

    $bCronsAdded = $this->ai1ecf_get_option_field("crons_added", false);
    if (!$bCronsAdded) {
      $this->ai1ecf_maybe_add_crons();
    }
  }

  public static function ai1ecf_activate() {
    $this->ai1ecf_maybe_add_crons();
  }

  private function ai1ecf_maybe_add_crons() {
    if ( !wp_next_scheduled( 'ai1ecf_add_missing_featured_images' ) ) {
      wp_schedule_event( time(), 'hourly', 'ai1ecf_add_missing_featured_images');
    }
    if ( !wp_next_scheduled( 'ai1ecf_send_newsletter_reminder' ) ) {
      wp_schedule_event( time(), 'hourly', 'ai1ecf_send_newsletter_reminder');
    }
    if ( !wp_next_scheduled( 'ai1ecf_add_missing_categories_and_tags' ) ) {
      wp_schedule_event( time(), 'twicedaily', 'ai1ecf_add_missing_categories_and_tags');
    }
    $this->ai1ecf_save_option_field("crons_added", true);
  }
  
  public static function ai1ecf_deactivate() {
    $this->ai1ecf_remove_crons();
  }

  private function ai1ecf_remove_crons() {
    wp_clear_scheduled_hook('ai1ecf_add_missing_featured_images');
    wp_clear_scheduled_hook('ai1ecf_send_newsletter_reminder');
    wp_clear_scheduled_hook('ai1ecf_add_missing_categories_and_tags');
  }

  public function ai1ecf_translations_load() {
    load_plugin_textdomain('ai1ecf', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
  }
  
  public function ai1ecf_event_metaboxes() {
    global $wp_meta_boxes;
    add_meta_box(
      'ai1ecf_event_location_override_metabox',
      $this->aPlaceholderValues['ai1ecf-metabox-title'],
      array( $this, 'ai1ecf_event_location_override_metabox_html' ),
      'ai1ec_event',
      'side',
      'high'
    );
  }
  
  public function ai1ecf_event_location_override_metabox_html() {
    global $post;
    $strTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "edit-event-location-override-metabox.html");
    $aPlaceholderValues = $this->aPlaceholderValues;
    $aPlaceholderValues['location-metabox-value'] = get_post_meta($post->ID, AI1ECF_LOCATION_OVERRIDE_POSTMETA_ID, true);
    
    $aCheckboxMeta = get_post_meta($post->ID, AI1ECF_SKIP_EVENT_UPDATE_FROM_FEED_POSTMETA_ID, true);
    if (!is_array($aCheckboxMeta) && empty($aCheckboxMeta)) {
      $aCheckboxMeta = array();
    } elseif (!is_array($aCheckboxMeta) && !empty($aCheckboxMeta)) {
      $this->ai1ecf_add_debug_log('WARN: $aCheckboxMeta should be an array: "'.var_export($aCheckboxMeta, true).'"');
    }
    $strCheckboxes = '';
    $strCheckboxTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "skip-event-update-from-feed-checkbox.html");
    foreach( $this->aEventUpdateSkipFields as $strKey => $aFields ) {
      $strCheckboxTemplateLoc = $strCheckboxTemplate;
      $aFieldPlaceholderValues = array(
        'skip-event-update-checkbox-key' => $strKey,
        'skip-event-update-checkbox-name' => $aPlaceholderValues['skip-event-update-checkbox_'.$strKey],
        'skip-event-update-checkbox-checked' => in_array($strKey, $aCheckboxMeta) ? 'checked="checked"' : ''
      );
      foreach ($this->aFieldToPlaceholders['skip-event-update-checkboxes'] as $strPlaceholder) {
        $strPlaceholderValue = $aFieldPlaceholderValues[$strPlaceholder];
        $strCheckboxTemplateLoc = str_replace('%%'.$strPlaceholder.'%%', $strPlaceholderValue, $strCheckboxTemplateLoc);
      }
      $strCheckboxes .= $strCheckboxTemplateLoc;
    }
    $aPlaceholderValues['skip-event-update-from-feed-checkboxes'] = $strCheckboxes;
    foreach ($this->aFieldToPlaceholders['location-metabox'] as $strPlaceholder) {
      $strPlaceholderValue = $aPlaceholderValues[$strPlaceholder];
      $strTemplate = str_replace('%%'.$strPlaceholder.'%%', $strPlaceholderValue, $strTemplate);
    }
    echo $strTemplate;
  }
  
  public function ai1ecf_event_save_post() {
    global $post;
    if (isset($_POST['ai1ecf_event_location_override_metabox_input'])) {
      update_post_meta( $post->ID, AI1ECF_LOCATION_OVERRIDE_POSTMETA_ID, $_POST['ai1ecf_event_location_override_metabox_input'] );
    }
    $strCheckboxKey = 'ai1ecf_event_skip_event_update_from_feed_checkboxes';
    if (isset($_POST[$strCheckboxKey]) && !empty($_POST[$strCheckboxKey])) {
      $aSkip = $_POST[$strCheckboxKey];
    } else {
      $aSkip = array();
    }
    update_post_meta( $post->ID, AI1ECF_SKIP_EVENT_UPDATE_FROM_FEED_POSTMETA_ID, $aSkip );
  }
  
  public function ai1ecf_add_options_page() {
    add_options_page(
      "All-in-One Event Calendar Fixes",
      "All-in-One Event Calendar Fixes",
      "manage_options",
      $this->strOptionsPageSlug,
      array( $this, "ai1ecf_options_page" )
    );
  }
  
  public function ai1ecf_action_admin_enqueue_scripts($strHook) {
    if ($strHook != "settings_page_" . $this->strOptionsPageSlug) {
      return;
    }
    
    wp_enqueue_style( 'ai1ecf-fa', "https://opensource.keycdn.com/fontawesome/4.7.0/font-awesome.min.css", array(), "4.7.0" );
    wp_enqueue_style( 'ai1ecf-admin-style', plugins_url('css/admin-style.css', __FILE__), array(), AI1ECF_VERSION );
    wp_enqueue_script( 'ai1ecf-admin-js', plugins_url('js/admin-js.js', __FILE__), array(), AI1ECF_VERSION );
  }
  
  private function ai1ecf_get_field_value_id($strValue) {
    $strValue = preg_replace('/\<br(\s*)?\/?\>/i', "", $strValue); // remove <br /? > tags //
    return preg_replace('/[^a-z0-9]/', "_", strtolower(trim($strValue)));
  }
  
  private function ai1ecf_get_wp_editor($strContent, $strEditorID, $aSettings = array()) {
    ob_start();
    wp_editor($strContent, $strEditorID, $aSettings);
    return ob_get_clean();
  }
  
  public function ai1ecf_options_page() {
    echo "<h1>" . __("All-in-One Event Calendar Fixes", "ai1ecf" ) . "</h1>";

    if (isset($_POST['save-ai1ecf-options'])) {
      $this->ai1ecf_save_option_page_options($_POST);
    }
    
    $strTablesTemplate = '';
    $strTabs = '';
    $strTableTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-table.html");
    $strTabTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-tab.html");
    $strDivTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-div.html");
    $aFieldToPlaceholders = $this->aFieldToPlaceholders;
    $aPlaceholderValues = $this->aPlaceholderValues;
    
    // Reminder, category + tag tabs //
    $aFieldsNonLoc = explode(',', AI1ECF_OPTION_NONLOC_FIELDS);
    foreach ($aFieldsNonLoc as $strField) {
      $strRowTemplate = '';
      $strHeaderTemplate = '';
      $strDivTemplateLoc = $strDivTemplate;
      $strBodyTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-div-body_".$strField.".html");
      $aOptionValues = $this->ai1ecf_get_option_field($strField);
      // echo "<pre>".var_export($aOptionValues, true)."</pre>";
      foreach ($aFieldToPlaceholders[$strField] as $strPlaceholder) {
        $strCustomPlaceholder = $strField."_".$strPlaceholder;
        if (!isset($aPlaceholderValues[$strPlaceholder])) {
          $mixOptionValue = (isset($aOptionValues[$strPlaceholder])) ? $aOptionValues[$strPlaceholder] : "";
          if ($strPlaceholder == "email-body-wp-editor") {
            $mixOptionValue = (isset($aOptionValues[$strCustomPlaceholder])) ? $aOptionValues[$strCustomPlaceholder] : "";
            $aSettings = array(
              'media_buttons' => false,
            );
            $aPlaceholderValues[$strPlaceholder] = $this->ai1ecf_get_wp_editor( $mixOptionValue, $strCustomPlaceholder, $aSettings );
          } elseif ($strPlaceholder == "options-weekdays") {
            $strCustomPlaceholder = 'day';
            $mixOptionValue = (isset($aOptionValues[$strCustomPlaceholder])) ? $aOptionValues[$strCustomPlaceholder] : "";
            $strDayOptionTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-select-option.html");
            $aPlaceholderValues[$strPlaceholder] = '';
            
            for ($i = 1; $i <= 7; $i++) {
              if ($mixOptionValue == $i) {
                $strSelected = 'selected="selected"';
              } else {
                $strSelected = '';
              }
              $aPlaceholderValues[$strPlaceholder] .= str_replace(
                array( '%%value%%', '%%selected%%', '%%label%%' ),
                array( $i, $strSelected, $aPlaceholderValues['label-day-'.$i] ),
                $strDayOptionTemplate
              );
            }
          } elseif ($strPlaceholder == "options-hour") {
            $strCustomPlaceholder = 'time-hour';
            $mixOptionValue = (isset($aOptionValues[$strCustomPlaceholder])) ? $aOptionValues[$strCustomPlaceholder] : "";
            $strHourOptionTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-select-option.html");
            $aPlaceholderValues[$strPlaceholder] = '';
            
            for ($i = 0; $i <= 23; $i++) {
              if ($mixOptionValue == $i) {
                $strSelected = 'selected="selected"';
              } else {
                $strSelected = '';
              }
              $aPlaceholderValues[$strPlaceholder] .= str_replace(
                array( '%%value%%', '%%selected%%', '%%label%%' ),
                array( $i, $strSelected, $i ),
                $strHourOptionTemplate
              );
            }
          } elseif ($strPlaceholder == "options-minute") {
            $strCustomPlaceholder = 'time-minute';
            $mixOptionValue = (isset($aOptionValues[$strCustomPlaceholder])) ? $aOptionValues[$strCustomPlaceholder] : "";
            $strMinuteOptionTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-select-option.html");
            $aPlaceholderValues[$strPlaceholder] = '';
            
            for ($i = 0; $i <= 59; $i++) {
              if ($mixOptionValue == $i) {
                $strSelected = 'selected="selected"';
              } else {
                $strSelected = '';
              }
              $aPlaceholderValues[$strPlaceholder] .= str_replace(
                array( '%%value%%', '%%selected%%', '%%label%%' ),
                array( $i, $strSelected, $i ),
                $strMinuteOptionTemplate
              );
            }
          } elseif ($strPlaceholder == "users") {
            $mixOptionValue = (isset($aOptionValues[$strCustomPlaceholder])) ? $aOptionValues[$strCustomPlaceholder] : "";
            if (empty($mixOptionValue)) {
              $mixOptionValue = array();
            }
            $aArgs = array(
              'blog_id' => get_current_blog_id(),
            );
            $aUsers = get_users( $aArgs );
            $strUserTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-div-body_".$strField."_user.html");
            foreach ($aUsers as $objUser) {
              if (in_array($objUser->ID, $mixOptionValue)) {
                $strChecked = 'checked="checked"';
              } else {
                $strChecked = '';
              }
              $aPlaceholderValues[$strPlaceholder] .= str_replace(
                array( '%%prefix%%', '%%user_id%%', '%%email%%', '%%name%%', '%%checked%%' ),
                array( $strField.'_', $objUser->ID, esc_html( $objUser->user_email ), esc_html( $objUser->display_name ), $strChecked ),
                $strUserTemplate
              );
            }
          } else {
            // Use option value or empty string //
            $aPlaceholderValues[$strPlaceholder] = $mixOptionValue;
          }
        }
      }
      
      // Replace placeholders in (local) DIV template //
      $strDivTemplateLoc = str_replace(
        array( '%%header%%', '%%rows%%', '%%div-id%%', '%%body%%' ), 
        array( $strHeaderTemplate, $strRowTemplate, $strField, $strBodyTemplate ),
        $strDivTemplateLoc
      );

      if (!empty($aFieldToPlaceholders[$strField])) {
        foreach ($aFieldToPlaceholders[$strField] as $strPlaceholder) {
          $strPlaceholderValue = stripslashes( $aPlaceholderValues[$strPlaceholder] );
          $strDivTemplateLoc = str_replace('%%'.$strPlaceholder.'%%', $strPlaceholderValue, $strDivTemplateLoc);
        }
      }

      // Add replaced DIV template to tab content template //
      $strTablesTemplate .= $strDivTemplateLoc;
      // Add replacce TAB template //
      $strTabs .= str_replace(
        array( '%%div-id%%', '%%tab-title%%', '%%type%%' ),
        array( $strField, $aPlaceholderValues['header-'.$strField], 'nonloc' ),
        $strTabTemplate
      );
    }
    
    // Location override tabs //
    $aOptionValues = array();
    $aFields = explode(',', AI1ECF_OPTION_LOC_FIELDS);
    foreach ($aFields as $strField) {
      $strRowTemplate = '';
      $strHeaderTemplate = '';
      
      $strTableTemplateLoc = $strTableTemplate;
      $aRowTemplate[$strField] = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-row-". $strField .".html");
      $aHeaderTemplate[$strField] = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-header-". $strField .".html");
      $aOptionValues[$strField] = $this->ai1ecf_get_location_field($strField);
      $aOptionValuesInfo[$strField] = $this->ai1ecf_get_location_field_info($strField);
      $aRules[$strField] = $this->ai1ecf_get_location_rules_by_field($strField);
      $aRules[$strField . "_only_if_empty_value"] = $this->ai1ecf_get_location_rule_checkboxes_by_field($strField);
      
      $iCnt = 0;
      foreach ($aOptionValues[$strField] as $strID => $strValue) {
        if ($iCnt % 2 === 0) {
          $aPlaceholderValues['row-class'] = 'class="alternate"';
        } else {
          $aPlaceholderValues['row-class'] = '';
        }
        $strExampleEvents = implode(', ', $aOptionValuesInfo[$strField][$strID]);
        
        $aPlaceholderValues[$strField] = $strValue;
        $aPlaceholderValues[$strField . "_id"] = $strID;
        if (isset($aRules[$strField][$strID])) {
          $aPlaceholderValues[$strField . "_value"] = $aRules[$strField][$strID];
        } else {
          $aPlaceholderValues[$strField . "_value"] = "";
        }
        if (isset($aRules[$strField . "_only_if_empty_value"][$strID])) {
          $aPlaceholderValues[$strField . "_only_if_empty_value"] = $aRules[$strField . "_only_if_empty_value"][$strID];
        } else {
          $aPlaceholderValues[$strField . "_only_if_empty_value"] = true; // default //
        }
        if ($aPlaceholderValues[$strField . "_only_if_empty_value"]) {
          $aPlaceholderValues['checked'] = 'checked="checked"';
        } else {
          $aPlaceholderValues['checked'] = '';
        }
        $aPlaceholderValues['examples'] = $strExampleEvents;
        
        $strRowTemplateLoc = $aRowTemplate[$strField];
        foreach ($aFieldToPlaceholders[$strField] as $strPlaceholder) {
          $strPlaceholderValue = stripslashes( $aPlaceholderValues[$strPlaceholder] );
          $strRowTemplateLoc = str_replace('%%'.$strPlaceholder.'%%', $strPlaceholderValue, $strRowTemplateLoc);
        }
        $strRowTemplate .= $strRowTemplateLoc;
        $iCnt++;
      }

      $strHeaderTemplateLoc = $aHeaderTemplate[$strField];
      foreach ($aFieldToPlaceholders[$strField] as $strPlaceholder) {
        $strPlaceholderValue = stripslashes( $aPlaceholderValues[$strPlaceholder] );
        $strHeaderTemplateLoc = str_replace('%%'.$strPlaceholder.'%%', $strPlaceholderValue, $strHeaderTemplateLoc);
      }
      $strHeaderTemplate .= $strHeaderTemplateLoc;

      $strTableTemplateLoc = str_replace(
        array( '%%header%%', '%%rows%%', '%%div-id%%' ), 
        array( $strHeaderTemplate, $strRowTemplate, $strField ),
        $strTableTemplateLoc
      );

      $strTablesTemplate .= $strTableTemplateLoc;
      $strTabs .= str_replace(
        array( '%%div-id%%', '%%tab-title%%', '%%type%%' ),
        array( $strField, $aPlaceholderValues['header-'.$strField], 'loc' ),
        $strTabTemplate
      );
    }
    
    $bEnabled = $this->bLocationReplacementEnabled;
    if ($bEnabled) {
      $strEnabledChecked = 'checked="checked"';
    } else {
      $strEnabledChecked = '';
    }
    
    $strFormTemplate = file_get_contents(AI1ECF_PATH_TO_TEMPLATES . "options-form.html");
    $strFormTemplate = str_replace(
      array('%%tables%%', '%%button-value%%', '%%checked%%', '%%location-replacement-enabled-label%%', '%%tabs%%'),
      array($strTablesTemplate, $aPlaceholderValues['button-value'], $strEnabledChecked, $aPlaceholderValues['location-replacement-enabled-label'], $strTabs),
      $strFormTemplate
    );
    echo $strFormTemplate;

    // file_put_contents(__DIR__.'/'."debug-kk", var_export($aRules, true));

  }
  
  private function ai1ecf_save_option_page_options($aPost) {

    $aFieldsLoc = explode(',', AI1ECF_OPTION_LOC_FIELDS);
    $aFieldsNonLoc = explode(',', AI1ECF_OPTION_NONLOC_FIELDS);
    
    if (isset($aPost['ai1ecf-location-replacement-enabled']) && $aPost['ai1ecf-location-replacement-enabled'] === "1") {
      $bEnabled = true;
    } else {
      $bEnabled = false;
    }
    update_option( 'ai1ecf-location-replacement-enabled', $bEnabled, true );
    $this->bLocationReplacementEnabled = $bEnabled;
    
    $aFieldToPlaceholders = $this->aFieldToPlaceholders;
    $aPlaceholderValues = $this->aPlaceholderValues;
    foreach ($aFieldsNonLoc as $strField) {
      $aFieldOptionValues = $this->ai1ecf_get_option_field($strField);
      foreach ($aFieldToPlaceholders[$strField] as $strPlaceholder) {
        if (!isset($aPlaceholderValues[$strPlaceholder])) {
          if (isset($aPost[$strPlaceholder])) {
            $aFieldOptionValues[$strPlaceholder] = $aPost[$strPlaceholder];
          }
        }
      }
      $this->ai1ecf_save_option_field( $strField, $aFieldOptionValues );
      // echo "<pre>".var_export($aFieldOptionValues, true)."</pre>";
    }
    
    foreach ($aFieldsLoc as $strField) {
      $aOptionValues[$strField] = $this->ai1ecf_get_location_field($strField);
      $aRules[$strField] = array();
      if ($strField !== 'venue') {
        $aRules[$strField . "_only_if_empty_value"] = array();
      }
      
      foreach ($aOptionValues[$strField] as $strID => $strValue) {
        if (isset($aPost[$strField.'_'.$strID]) && !empty($aPost[$strField.'_'.$strID])) {
          $aRules[$strField][$strID] = stripslashes( $aPost[$strField.'_'.$strID] );
          if ($strField !== 'venue') {
            if (isset($aPost[$strField.'_'.$strID.'_only_if_empty']) && $aPost[$strField.'_'.$strID.'_only_if_empty'] === "1") {
              $aRules[$strField . "_only_if_empty_value"][$strID] = true;
            } else {
              $aRules[$strField . "_only_if_empty_value"][$strID] = false;
            }
          }
        }
      }
      $this->ai1ecf_save_location_rules_by_field( $strField, $aRules[$strField] );
      if ($strField !== 'venue') {
        $this->ai1ecf_save_location_rule_checkboxes_by_field( $strField, $aRules[$strField . "_only_if_empty_value"] );
      }
    }
  }
  
  private function ai1ecf_save_location_field($strField, $strValue, $strTitle, $strUrl) {
    if (!isset($strField) || empty($strField)) {
      return;
    }
    $strOptionName = AI1ECF_OPTION_LOC_PREFIX . $strField;
    $aLocationFieldValues = get_option($strOptionName, false);
    if (false === $aLocationFieldValues) {
      $aLocationFieldValues = array();
    }
    $strValue = trim($strValue);
    $strID = $this->ai1ecf_get_field_value_id($strValue);
    if (!in_array($strValue, $aLocationFieldValues)) {
      $aLocationFieldValues[$strID] = $strValue;
      update_option($strOptionName, $aLocationFieldValues, true);
    } else if (!isset($aLocationFieldValues[$strID])) {
      $iID = array_search($strValue, $aLocationFieldValues);
      unset($aLocationFieldValues[$iID]);
      $aLocationFieldValues[$strID] = $strValue;
      update_option($strOptionName, $aLocationFieldValues, true);
    }
    
    $strOptionName = AI1ECF_OPTION_LOC_PREFIX . $strField . "_info";
    $aLocationFieldValuesInfo = get_option($strOptionName, false);
    if (false === $aLocationFieldValuesInfo) {
      $aLocationFieldValuesInfo = array();
    }
    $strExampleEvents = "<a href='{$strUrl}' target='_blank'>{$strTitle}</a>";
    if (!isset($aLocationFieldValuesInfo[$strID])) {
      $aLocationFieldValuesInfo[$strID] = array();
      $aLocationFieldValuesInfo[$strID][] = $strExampleEvents;
      update_option($strOptionName, $aLocationFieldValuesInfo, true);
    } elseif (!in_array($strExampleEvents, $aLocationFieldValuesInfo[$strID])) {
      $aLocationFieldValuesInfo[$strID][] = $strExampleEvents;
      update_option($strOptionName, $aLocationFieldValuesInfo, true);
    }
  }
  
  private function ai1ecf_get_option_field($strField, $default = array()) {
    $strOptionName = AI1ECF_OPTION_PREFIX . $strField;
    return get_option($strOptionName, $default);
  }

  private function ai1ecf_save_option_field($strField, $mixOptionValue) {
    $strOptionName = AI1ECF_OPTION_PREFIX . $strField;
    update_option($strOptionName, $mixOptionValue, true);
  }

  private function ai1ecf_get_location_field($strField) {
    $strOptionName = AI1ECF_OPTION_LOC_PREFIX . $strField;
    $aOptionValue = get_option($strOptionName, array());
    asort($aOptionValue);
    return $aOptionValue;
  }

  private function ai1ecf_get_location_field_info($strField) {
    $strOptionName = AI1ECF_OPTION_LOC_PREFIX . $strField . "_info";
    $aOptionValue = get_option($strOptionName, array());
    return $aOptionValue;
  }

  private function ai1ecf_save_location_rules_by_field($strField, $aRules) {
    if (!isset($aRules) || empty($aRules)) {
      return;
    }
    $strOptionName = AI1ECF_OPTION_LOC_PREFIX . "rules_" . $strField;
    update_option($strOptionName, $aRules, true);
  }
  
  private function ai1ecf_get_location_rules_by_field($strField) {
    $strOptionName = AI1ECF_OPTION_LOC_PREFIX . "rules_" . $strField;
    $aOptionValue = get_option($strOptionName, array());
    return $aOptionValue;
  }

  private function ai1ecf_save_location_rule_checkboxes_by_field($strField, $aRules) {
    if (!isset($aRules) || empty($aRules)) {
      return;
    }
    $strOptionName = AI1ECF_OPTION_LOC_PREFIX . "rule_checkboxes_" . $strField;
    update_option($strOptionName, $aRules, true);
  }
  
  private function ai1ecf_get_location_rule_checkboxes_by_field($strField) {
    $strOptionName = AI1ECF_OPTION_LOC_PREFIX . "rule_checkboxes_" . $strField;
    $aOptionValue = get_option($strOptionName, array());
    return $aOptionValue;
  }
  
  public function ai1ecf_filter_rendering_single_event_venues($strVenue, $oEvent) {
    if (!$this->bLocationReplacementEnabled) {
      return $strValue;
    }

    $strVenue = $this->ai1ecf_fix_location($strVenue, $oEvent->get("post_id"), $oEvent->get("address"), $oEvent->get("contact_name"), false);
    return $strVenue;
  }

  public function ai1ecf_fix_location($strVenue, $iPostID = 0, $strAddress = '', $strContactName = '', $bUseAddressIfVenueEmpty = true ) {
    if (!$this->bLocationReplacementEnabled) {
      return $strVenue;
    }

    if (isset($iPostID) && $iPostID > 0) {
      $strLocationOverride = get_post_meta($iPostID, AI1ECF_LOCATION_OVERRIDE_POSTMETA_ID, true);
      if (!empty($strLocationOverride)) {
        return $strLocationOverride;
      }
    }
    
    $aFields = explode(',', AI1ECF_OPTION_LOC_FIELDS);
    foreach ($aFields as $strField) {
      $aRules[$strField] = $this->ai1ecf_get_location_rules_by_field($strField);
      if ($strField !== 'venue') {
        $aRules[$strField . "_only_if_empty_value"] = $this->ai1ecf_get_location_rule_checkboxes_by_field($strField);
      }
      
      switch ($strField) {
        case 'venue':
          $strID = $this->ai1ecf_get_field_value_id($strVenue);
          if (isset($aRules[$strField][$strID])) {
            return $aRules[$strField][$strID];
          }
          break;
        case 'address':
          if (!empty($strAddress)) {
            $strID = $this->ai1ecf_get_field_value_id($strAddress);
            if (
                  isset($aRules[$strField][$strID])
                  && (
                        !isset($aRules[$strField . "_only_if_empty_value"][$strID])
                        || $aRules[$strField . "_only_if_empty_value"][$strID] !== true
                        || (empty($strVenue) && $aRules[$strField . "_only_if_empty_value"][$strID] === true)
                  )
            ) {
                return $aRules[$strField][$strID];
            }
          }
          break;
        case 'contact_name':
          if (!empty($strContactName)) {
            $strID = $this->ai1ecf_get_field_value_id($strContactName);
            if (
                  isset($aRules[$strField][$strID])
                  && (
                        !isset($aRules[$strField . "_only_if_empty_value"][$strID])
                        || $aRules[$strField . "_only_if_empty_value"][$strID] !== true
                        || (empty($strVenue) && $aRules[$strField . "_only_if_empty_value"][$strID] === true)
                  )
            ) {
                return $aRules[$strField][$strID];
            }
          }
          break;
        default:
      }
    }
    
    if (true === $bUseAddressIfVenueEmpty && empty($strVenue) && !empty($strAddress)) {
      $strVenue = $strAddress;
    }
    
    return $strVenue;
  }

  public function ai1ecf_filter_theme_args_month( $aArgs, $bIsAdmin ) {
    if (!$this->bLocationReplacementEnabled) {
      return $aArgs;
    }
    
    foreach ( $aArgs['cell_array'] as &$week ) {
      foreach ( $week as &$day ) {
        foreach ( $day['events'] as &$event ) {
          $oEvent = $this->ai1ecf_get_event_by_post_id($event['post_id']);
          $event['venue'] = $this->ai1ecf_fix_location($event['venue'], $event['post_id'], $oEvent->get("address"), $oEvent->get("contact_name"));
        }
      }
    }
    return $aArgs;
  }
  
  public function ai1ecf_filter_theme_args_agenda_widget( $args, $bIsAdmin ) {
    if (!$this->bLocationReplacementEnabled) {
      return $args;
    }

    foreach ( $args['dates'] as $date => &$date_info ) {
      foreach ( $date_info['events'] as &$category ) {
        foreach ( $category as &$event ) {
          $oEvent = $this->ai1ecf_get_event_by_post_id($event['post_id']);
          $event['venue'] = $this->ai1ecf_fix_location($event['venue'], $event['post_id'], $oEvent->get("address"), $oEvent->get("contact_name"));
        }
      }
    }
    return $args;
  }
  
  public function ai1ecf_filter_theme_args_event_excerpt( $args, $bIsAdmin ) {
    if (isset($GLOBALS['disable_ai1ec_excerpt_filter']) && true === $GLOBALS['disable_ai1ec_excerpt_filter']) {
      $args['disable_excerpt'] = true;
      return $args;
    }
    if (!$this->bLocationReplacementEnabled) {
      return $args;
    }

    $oEvent = &$args['event'];
    $args['location'] = $this->ai1ecf_fix_location($args['location'], $oEvent->get('post_id'), $oEvent->get("address"), $oEvent->get("contact_name"), false);
//     $oEvent->set(
//       'venue',
//       $this->ai1ecf_fix_location($oEvent->get('venue'), $oEvent->get('post_id'), $oEvent->get("address"), $oEvent->get("contact_name"))
//     );
    return $args;
  }
  
  public function ai1ecf_get_event_by_post_id( $iPostID ) {
    global $ai1ec_registry;
    $oEvent = new Ai1ec_Event( $ai1ec_registry );
    $oEvent->initialize_from_id( $iPostID );
    return $oEvent;
  }
  
  public function ai1ecf_filter_pre_init_event_from_feed( $aData, $oEvent, $oFeed ) {
    // Find post ID; if successful replace fields defined by postmeta in $aData by the value currently in DB //
    global $ai1ec_registry;
    $event = $ai1ec_registry->get( 'model.event', $aData );
    $recurrence = $event->get( 'recurrence_rules' );
    $search = $ai1ec_registry->get( 'model.search' );
    // first let's check by UID
    $iPostID = $search
      ->get_matching_event_by_uid_and_url(
        $event->get( 'ical_uid' ),
        $event->get( 'ical_feed_url' )
      );
    // if no result, perform the legacy check.
    if ( null === $iPostID ) {
      $iPostID = $search
        ->get_matching_event_id(
          $event->get( 'ical_uid' ),
          $event->get( 'ical_feed_url' ),
          $event->get( 'start' ),
          ! empty( $recurrence )
        );
    }
    $aReplacedFieldGroups = array();
    if (empty($iPostID) || $iPostID === 0 || $iPostID === null) {
      // Post ID not found => post is being created => no custom postmeta for such post, so we continue //
      // $this->ai1ecf_add_debug_log('creating post in ai1ecf_filter_pre_init_event_from_feed (i.e. post ID not found for ical_source_url "'.$aData['ical_source_url'].'")');
    } else {
      // Post ID found //
      // $this->ai1ecf_add_debug_log('updating '.$iPostID.' in ai1ecf_filter_pre_init_event_from_feed');
      $aSkipUpdateFieldCheckboxMeta = get_post_meta($iPostID, AI1ECF_SKIP_EVENT_UPDATE_FROM_FEED_POSTMETA_ID, true);
      if (isset($aSkipUpdateFieldCheckboxMeta) && !empty($aSkipUpdateFieldCheckboxMeta) && is_array($aSkipUpdateFieldCheckboxMeta)) {
        // postmeta found, apply rule //
        // get old event data //
        $oEventOld = ai1ecf_get_event_by_post_id( $iPostID );
        $this->ai1ecf_add_debug_log('non-empty postmeta '.AI1ECF_SKIP_EVENT_UPDATE_FROM_FEED_POSTMETA_ID.' found for '.$iPostID.' in ai1ecf_filter_pre_init_event_from_feed: '.var_export($aSkipUpdateFieldCheckboxMeta, true));
        foreach ($aSkipUpdateFieldCheckboxMeta as $strKey) { // the field key is saved as value in this array! //
          $aSkipFields = $this->aEventUpdateSkipFields[$strKey];
          foreach ($aSkipFields as $strDelKey) {
            if (isset($aData[$strDelKey])) {
              $mixNewValue = $aData[$strDelKey];
            } else {
              $mixNewValue = NULL;
            }
            $aData[$strDelKey] = $oEventOld->get( $strDelKey );
            $mixOldValue = $aData[$strDelKey];
            if ($strDelKey === 'start' || $strDelKey === 'end') {
              $this->ai1ecf_add_debug_log('replaced new value ("...") for key "'.var_export($strDelKey,true).'" with old value ("...") in $aData for post ID '.$iPostID);
            } else {
              $this->ai1ecf_add_debug_log('replaced new value ("'.var_export($mixNewValue,true).'") for key "'.var_export($strDelKey,true).'" with old value ("'.var_export($mixOldValue,true).'") in $aData for post ID '.$iPostID);
            }
          }
          $aReplacedFieldGroups[$strKey] = true;
        }
      }
    }
    
    // Only apply contact fixing if contact fields have not been replaced by DB version (then they should be clean already) //
    if (!isset($aReplacedFieldGroups['contact']) || $aReplacedFieldGroups['contact'] !== true) {
//       $this->ai1ecf_add_debug_log('fixing contact for post ID '.$iPostID);
      $aFields = array("contact_name", "contact_email");
      foreach ($aFields as $strField) {
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
    } else {
      $this->ai1ecf_add_debug_log('skipping contact fixing for post ID '.$iPostID);
    }
    
    // Fix post info //
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
    
    // save location data to DB for the fixer screens //
    $aFieldsToSave = explode(',', AI1ECF_OPTION_LOC_FIELDS);
    foreach ($aFieldsToSave as $strFieldToSave) {
      if (isset($aData[$strFieldToSave]) && !empty($aData[$strFieldToSave])) {
        $this->ai1ecf_save_location_field($strFieldToSave, $aData[$strFieldToSave], $aData['post']['post_title'], $aData['ical_source_url']);
      }
    }
    
    $this->ai1ecf_add_debug_log( "ai1ecf_filter_pre_init_event_from_feed" );
    
    return $aData;
  }

  public function ai1ecf_filter_contact_url( $strString ) {
    return __( 'Organizer website', "ai1ecf" );
  }
  
  public function ai1ecf_filter_pre_delete_post( $bDelete, $oPost, $bForceDelete ) {
    if ( isset($GLOBALS['ai1ecf_import_running']) && true === $GLOBALS['ai1ecf_import_running'] && $oPost->post_type === 'ai1ec_event' ) {
      wp_trash_post( $oPost->ID );
      return false;
    }
    return true;
  }
  
  public function ai1ecf_action_ics_before_import( $aArgs ) {
    $GLOBALS['ai1ecf_import_running'] = true;
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
   * @param  bool $bOpenInNewWindow
   * @return string
   * 
   * Sources: http://stackoverflow.com/a/1959073, http://daringfireball.net/2010/07/improved_regex_for_matching_urls
   */
  private function ai1ecf_auto_link_text($text, $bOpenInNewWindow = false) {
    $strTarget = $bOpenInNewWindow ? 'target="_blank" ' : '';
    $subpattern = '([a-z0-9.\-]+[.])+(com|sk|hu)([^/])|'; // Also match .com, .sk, .hu //
    $pattern  = '#(?i)(?<=^|[^a-z@])'.$subpattern.'((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#';
    $callback = create_function('$matches', '
         $url = array_shift($matches);
         if (preg_match(\'~(?i)([a-z0-9.\-]+[.])+(com|sk|hu)([^/])$~\', $url)) {
          $prefix = "";
          $suffix = substr($url, strlen($url) - 1, strlen($url));
          $url = substr($url, strlen($prefix), strlen($url) - strlen($suffix));
         } else {
          $prefix = "";
          $suffix = "";
         }
         $sub7 = strtolower(substr($url, 0, 7));
         $sub8 = strtolower(substr($url, 0, 8));
         if ( $sub7 !== "http://" && $sub8 !== "https://") {
           $url = "http://".$url;
         }
         //$url_parts = parse_url($url);

         $text = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
         $text = preg_replace("~^(https?://)?www.~", "", $text);

         $last = -(strlen(strrchr($text, "/"))) + 1;
         if ($last < 0) {
             $text = substr($text, 0, $last) . "&hellip;";
         }

         return sprintf(\'%s<a rel="nowfollow" '.$strTarget.'href="%s">%s</a>%s\', $prefix, strtolower($url), $text, $suffix);
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
  
  public function ai1ecf_send_newsletter_reminder() {
    $aOptions = $this->ai1ecf_get_option_field("reminder");
    foreach (array('time-hour', 'time-minute', 'day') as $key) {
      if (!isset($aOptions[$key])) {
        $this->ai1ecf_add_debug_log(var_export($aOptions, true), false, 'debug-send-notifications-err-1.kk');
        return;
      }
    }

    $strLastSentDate = $this->ai1ecf_get_option_field("reminder_last_sent_date", "");
    $strToday = date("Ymd");
    if (!empty($strLastSentDate) && $strLastSentDate == $strToday) {
      // Today's reminder has already been sent //
      // $this->ai1ecf_add_debug_log(var_export($strLastSentDate, true), false, 'debug-send-notifications-err-3.kk');
      return;
    }
    if (intval($aOptions["day"]) !== date("N")) {
      // Not the right day //
      if (!empty($strLastSentDate)) {
        $iLastSentDate = intval($strLastSentDate);
        $iToday = intval($strToday);
        if ($iToday - $iLastSentDate > 7) {
          // continue with sending - the last reminder was sent more than a week ago! //
        } else {
          // OK: the last reminder was sent less than a week ago //
          return;
        }
      } else {
        // No LastSentDate: let's wait for the next time of sending //
        // $this->ai1ecf_add_debug_log(var_export(date("N"), true), false, 'debug-send-notifications-err-2.kk');
        return;
      }
    } else {
      // Today's reminder has not yet been sent AND it's the right day, so we check the time //
      $iTimeNow = date("G")*100+date("i");
      $iTimeScheduled = $aOptions['time-hour']*100+$aOptions['time-minute'];
      if ($iTimeNow < $iTimeScheduled) {
        // The time has not yet arrived //
        // $this->ai1ecf_add_debug_log(var_export(array($iTimeNow,$iTimeScheduled), true), false, 'debug-send-notifications-err-4.kk');
        return;
      }
    }

    // send email
    $aArgs = array(
      'blog_id' => get_current_blog_id(),
      'include' => $aOptions['reminder_users'],
    );
    $aUsers = get_users( $aArgs );
    if (isset($aOptions['email-addresses']) && !empty($aOptions['email-addresses'])) {
      $aEmails = explode( ',', $aOptions['email-addresses']);
    } else {
      $aEmails = array();
    }
    foreach ($aUsers as $objUser) {
      $aEmails[] = $objUser->user_email;
    }
    if (empty($aEmails)) {
      $this->ai1ecf_add_debug_log(var_export($aOptions, true), false, 'debug-send-notifications-err-5.kk');
      return;
    }

    $aHeaders = array('Content-Type: text/html; charset=UTF-8');
    $bRes = wp_mail( $aEmails, $aOptions['email-subject'], $aOptions['reminder_email-body-wp-editor'], $aHeaders);
    if ($bRes) {
      $this->ai1ecf_save_option_field( "reminder_last_sent_date", $strToday );
    } else {
      $this->ai1ecf_add_debug_log(var_export($bRes, true), false, 'debug-send-notifications-res.kk');
      $this->ai1ecf_add_debug_log(var_export($aEmails, true), false, 'debug-send-notifications-emails.kk');
      $this->ai1ecf_add_debug_log(var_export($aOptions, true), false, 'debug-send-notifications-options.kk');
    }
  }

  public function ai1ecf_add_missing_categories_and_tags() {
    // TODO
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
        file_put_contents($strDebugFile, $strLogText.PHP_EOL, FILE_APPEND | LOCK_EX);
      }
    } else {
      $strDateExact = date('Y-m-d H:i:s');
      $strLogText = $strDateExact . " " . $strText;
      file_put_contents($strDebugFile, $strLogText.PHP_EOL, FILE_APPEND | LOCK_EX);
    }
  }
  
}

$AI1EC_Fixes = new AI1EC_Fixes();
register_activation_hook(__FILE__, array('AI1EC_Fixes', 'ai1ecf_activate'));
register_deactivation_hook(__FILE__, array('AI1EC_Fixes', 'ai1ecf_deactivate'));

function ai1ecf_fix_location($strVenue, $iPostID = 0, $strAddress = '', $strContactName = '', $bUseAddressIfVenueEmpty = true) {
  global $AI1EC_Fixes;
  return $AI1EC_Fixes->ai1ecf_fix_location($strVenue, $iPostID, $strAddress, $strContactName, $bUseAddressIfVenueEmpty);
}
function ai1ecf_get_event_by_post_id( $iPostID ) {
  global $AI1EC_Fixes;
  return $AI1EC_Fixes->ai1ecf_get_event_by_post_id( $iPostID );
}