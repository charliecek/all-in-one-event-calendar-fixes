var strFirstDivID       = false;
var objFirstTab           = false;
var strEnabledCheckboxID  = "#ai1ecf-location-replacement-enabled";

jQuery( document ).ready(function() {
  objFirstTab = jQuery(".nav-tab").first();
  if (objFirstTab.length > 0) {
    strFirstDivID = objFirstTab.attr("data-div-id");
    objFirstDiv = jQuery( "#"+strFirstDivID );
    jQuery(".nav-tab.nonloc").toggleClass("hidden");
    if (!objFirstDiv.hasClass("loc")) {
      objFirstDiv.removeClass("hidden");
      objFirstTab.addClass("nav-tab-active");
    }
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
    var strDivID = objTab.attr("data-div-id");
    switchToDiv(strDivID, objTab);
  });
});

function toggleEnabledVisibility() {
  
  var objPreviouslyActiveTab = jQuery(".nav-tab.loc.nav-tab-active");
  if (objPreviouslyActiveTab.length > 0) {
    // toggle this one //
    var strPreviouslyActiveTabDivID = objPreviouslyActiveTab.attr("data-div-id");
    jQuery("#" + strPreviouslyActiveTabDivID).toggleClass("hidden");
  } else {
    // toggle first one //
    if (objFirstTab.length > 0 && objFirstTab.hasClass("loc")) {
      objFirstTab.toggleClass("nav-tab-active");
      if (strFirstDivID) {
        jQuery("#"+strFirstDivID).toggleClass("hidden");
      }
    }
  }
  jQuery(".nav-tab.loc").toggleClass("hidden");
  //jQuery(".nav-tab-wrapper").toggleClass("hidden");
  
  //jQuery("#save-ai1ecf-options-bottom").toggleClass("hidden");
}

function switchToDiv(strDivID, objTab) {
  if (objTab.hasClass("nav-tab-active")) {
    return;
  }
  var strPreviouslyActiveTabDivID = jQuery(".nav-tab.nav-tab-active").attr("data-div-id");
  jQuery(".nav-tab.nav-tab-active").removeClass("nav-tab-active");
  jQuery("#" + strPreviouslyActiveTabDivID).addClass("hidden");
  objTab.addClass("nav-tab-active");
  jQuery("#" + strDivID).removeClass("hidden");
}