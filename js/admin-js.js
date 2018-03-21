var strFirstTableID       = false;
var objFirstTab           = false;
var strEnabledCheckboxID  = "#ai1ecf-location-replacement-enabled";

jQuery( document ).ready(function() {
  objFirstTab = jQuery(".nav-tab").first();
  if (objFirstTab.length > 0) {
    strFirstTableID = objFirstTab.attr("data-table-id");
  }
  var bEnabled = jQuery(strEnabledCheckboxID).is(':checked');
  if (bEnabled) {
    toggleEnabledVisibility();
  }
  jQuery(strEnabledCheckboxID+':checkbox').change(function() {
    toggleEnabledVisibility();
  });
  
  jQuery(".nav-tab").click(function(e) {
    e.preventDefault();
    var objTab = jQuery(this);
    var strTableID = objTab.attr("data-table-id");
    switchToTable(strTableID, objTab);
  });
});

function toggleEnabledVisibility() {
  var objPreviouslyActiveTab = jQuery(".nav-tab.nav-tab-active");
  if (objPreviouslyActiveTab.length > 0) {
    // toggle this one //
    var strPreviouslyActiveTabTableID = objPreviouslyActiveTab.attr("data-table-id");
    jQuery("#" + strPreviouslyActiveTabTableID).toggleClass("hidden");
  } else {
    // toggle first one //
    if (objFirstTab.length > 0) {
      objFirstTab.toggleClass("nav-tab-active");
    }
    if (strFirstTableID) {
      jQuery("#"+strFirstTableID).toggleClass("hidden");
    }
  }
  jQuery(".nav-tab-wrapper").toggleClass("hidden");
  jQuery("#save-ai1ecf-options-bottom").toggleClass("hidden");
}

function switchToTable(strTableID, objTab) {
  if (objTab.hasClass("nav-tab-active")) {
    return;
  }
  var strPreviouslyActiveTabTableID = jQuery(".nav-tab.nav-tab-active").attr("data-table-id");
  jQuery(".nav-tab.nav-tab-active").removeClass("nav-tab-active");
  jQuery("#" + strPreviouslyActiveTabTableID).addClass("hidden");
  objTab.addClass("nav-tab-active");
  jQuery("#" + strTableID).removeClass("hidden");
}