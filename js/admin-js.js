var strSelectedDivID         = false;
var objSelectedTab           = false;
var strEnabledCheckboxID     = "#ai1ecf-location-replacement-enabled";

jQuery( document ).ready(function() {
  objSelectedTab = jQuery(".nav-tab").first();
  strSelectedDivID = localStorage.getItem( "ai1ecf-option-div-id" );
  if ("undefined" !== typeof strSelectedDivID && strSelectedDivID !== null && jQuery("#"+strSelectedDivID).length > 0) {
    jQuery(".nav-tab.nonloc").toggleClass("hidden");
    var objSelectedDiv = jQuery("#"+strSelectedDivID);
    objSelectedTab = jQuery(".nav-tab[data-div-id="+strSelectedDivID+"]");
    if (!objSelectedTab.hasClass("loc")) {
      objSelectedDiv.removeClass("hidden");
      objSelectedTab.addClass("nav-tab-active");
    }
  } else if (objSelectedTab.length > 0) {
    strSelectedDivID = objSelectedTab.attr("data-div-id");
    var objSelectedDiv = jQuery( "#"+strSelectedDivID );
    jQuery(".nav-tab.nonloc").toggleClass("hidden");
    if (!objSelectedTab.hasClass("loc")) {
      objSelectedDiv.removeClass("hidden");
      objSelectedTab.addClass("nav-tab-active");
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
    // toggle first/selected one //
    if (objSelectedTab.length > 0 && objSelectedTab.hasClass("loc")) {
      objSelectedTab.toggleClass("nav-tab-active");
      if (strSelectedDivID) {
        jQuery("#"+strSelectedDivID).toggleClass("hidden");
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

  // Set local storage //
  localStorage.setItem( "ai1ecf-option-div-id", strDivID );
}
