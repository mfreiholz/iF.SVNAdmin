/**
 * Javascript object for dynamic HTML data.
 */
var HtmlData = new function() {
	
	this.ajaxLoadingImage = function() {
		return '<img src="templates/icons/ajax-loader.gif" border="0">';
	};

}


/**
 * Trims the given text.
 * 
 * @param String text
 * 
 * @return String
 */
function str_trim(text)
{
	var t = text;
	t = t.replace(/^\s+|\s+$/g, '');
	t = t.replace(/(\r\n|\r|\n)/gm, '');
	return t;
}

/**
 * Inserts alternate row colors to the given table.
 * 
 * @param eTable The table Element
 * @return void
 */
function alternateRowColors( eTable ){
  var table = eTable;
  var rows = table.getElementsByTagName( "TR" );

  var i = 0;
  for( i = 0; i < rows.length; i++ ){
    // Skip THEAD and TFOOT element rows.
    var parentTagName = rows[i].parentNode.tagName;
    if( parentTagName == "TFOOT" || parentTagName == "THEAD" ){
      continue;
    }
    // Change className of all other rows.
    if( i % 2 == 0 ){
      rows[i].className = "trbase";
    }
    else{
      rows[i].className = "tralt";
    }
  }
}

/**
 * Searches for all "datatables" and alternates the row colors of them.
 * 
 * @return void
 */
function highlightDatatables(){
  var tables = document.getElementsByTagName("table");
  var ti = 0;
  for( ti = 0; ti < tables.length; ti++ ){
    if( tables[ti].className.indexOf("datatable") != -1 ){
      alternateRowColors( tables[ti] );
    }
  }
}

/**
 * Steps through all elements and extracts the text from all TextNodes.
 * 
 * @param elem
 * 
 * @return String
 */
function getVisibleTextFromElement(node)
{
	var text = new String();
	
	if (node == null) {
		return text;
	}
	
	if (node.hasChildNodes()) {
		var i = 0;
		for (i = 0; i < node.childNodes.length; ++i) {
			var child = node.childNodes[i];
			text += getVisibleTextFromElement(child);
		}
	}
	else if (node.data) {
		text+= " ";
		text+= node.data;
	}
	
	return text;
}

/**
 * Searches in the given datatable identified by 'tableid' for the
 * text 'text' and hides all other rows in the table-tbody.
 * 
 * @param tableid
 * @param colidx (not longer used)
 * @param text
 * @return void
 */
function filterDataTable( tableid, colidx, text )
{
  // Modifiy the search query string.
  var strSearchText = new String(text);
  if( strSearchText.length == 1 ){
    if( strSearchText == "*" ){
      strSearchText = "\\*";
    }
  }

  // The table element.
  var table = document.getElementById(tableid);
  if (!table){
	  return;
  }
  
  // Get the table body.
  var bodies = table.getElementsByTagName("TBODY");
  if (bodies.length < 1){
    return;
  }
  var tbody = bodies[bodies.length-1]; // Always use the last found body tag!
  
  // All row objects of the table.
  var rows = tbody.getElementsByTagName("TR");
  if (!rows){
	  return;
  }

  // Go through all rows and get text from each cell.
  // After fetching all visible text, search over it.
  var ri = 0;
  for (ri = 0; ri < rows.length; ri++){
    var cells = rows[ri].getElementsByTagName("TD");
    var rowSearchData = new String("");
    
    var ci = 0;
    for (ci = 0; ci < cells.length; ci++){ 
      
      // Go to the last element and grap the visible data of it.
      var last = cells[ci];
      rowSearchData += getVisibleTextFromElement(last);
    }
    
    rowSearchData = str_trim(rowSearchData);
    
    // Search for "strSearchText" in "rowSearchData".
    // Hide the row if the content doesn't exists.
    if (rowSearchData.match(strSearchText)){
      rows[ri].style.display = "";
    }
    else{
      rows[ri].style.display = "none";
    }
  }
}

/**
 * Opens a dialog which ask the user, whether he really wants to delete
 * selected sources.
 * 
 * @param question
 * @returns bool
 */
function deletionPrompt(question) {
  var agree = confirm(question);
  if (agree) {
    return true;
  }
  return false;
}

/**
 * Selects all checkboxes in a table.
 * 
 * Change by Sean Chan:
 *  Only selects visible rows.
 *  The checkboxes must be in a table cell, because the function checks,
 *  whether the parent row (TR) tag is visible.
 * 
 * @param srcObj The "select-all" checkbox.
 * @param targetCheckBoxName The name of the other checkboxes.
 */
function selectAll(srcObj, targetCheckBoxName)
{
  var checked_status = srcObj.checked;
  $("input[name='"+targetCheckBoxName+"'][type='checkbox']").each(function(){
    this.checked = false;
    //check if this row is display or not(is in the filter or not)
    //I assume each <input> are in the row format like:
    //"<tr><td><input></input></td></tr>"
    visible = this.parentNode.parentNode.style.display;
    if(visible != "none"){
      this.checked = checked_status;
    }
  });
}

/**
 * Flashes (blink) a specific element.
 * 
 * @param Object elem
 */
function flashElement(elem)
{
	var speed = 100;
	var repeat = 2;
	
	var i = 0;
	for (i = 0; i < repeat; ++i) {
		elem.fadeOut(speed).fadeIn(speed);
	}
}

/**
 * Executes an test of the given settings-section.
 * 
 * @param testSection The section to test.
 * @param requestVars The variables which are required for an successful test.
 * @param resultContainer The id of the container, which will contain the result.
 */
function testSettings(testSection, params, resultContainer)
{	
	// Append static control parameters.
	params.dotest = 1;
	params.dotestsec = testSection;
	
	var C = $(resultContainer);
	
	$.ajax({
		type: "post",
		url: "settings.php",
		data: params,
		cache: false,
		dataType: "json",
		
		beforeSend: function(jqXHR, settings) {
			// Hide old result element.
			C.removeClass();
			C.html(HtmlData.ajaxLoadingImage());
			C.show();
		},
		
		error: function(jqXHR, textStatus, errorThrown) {
			var s =
				"AJAX Error:<br>" +
				"jqXHR=" + jqXHR + "<br>" +
				"textStatus=" + textStatus + ";<br>" +
				"errorThrown=" + errorThrown + ";<br>";
			C.addClass("errormsg");
			C.html(s).show();
		},
		
		success: function(data, textStatus, jqXHR) {
			// Get response text.
			var msg = data.message;
			
			if (typeof data.php_error !== "undefined") {
				msg += "<br><b>An PHP error occured!</b><br>";
				msg += data.php_error.message;
			}
			
			// Set style class of container.
			if (data.type == "error") {
				C.addClass("errormsg");
			}
			else {
				C.addClass("okmsg");
			}
			
			// Display response to user.
			C.html(data.message);
		},
		
		complete: function(jqXHR, textStatus) {
			// Show result.
			C.show();
		}
	});
}

/**
 * This function should be called with any change of the provider types.
 * It handles the logic of possible combinations and updates the user interface.
 * On this way the user can not make a wrong configuration.
 * 
 * @return void
 */
function updateSettingsSelection()
{
  // UserView
  var speed = "slow";
  if ($("#UserViewProviderType").val() == "off")
  {
    $("#tbl_userfile").hide(speed);
    $("#tbl_userdigestfile").hide(speed);
    $("#tbl_ldapconnection").hide(speed);
    $("#tbl_ldapuser").hide(speed);
    $("#UserEditProviderType").val("off");
    $("#UserEditProviderType").attr("disabled", "disabled");
  }
  else if($("#UserViewProviderType").val() == "passwd")
  {
    $("#tbl_ldapconnection").hide(speed);
    $("#tbl_ldapuser").hide(speed);
    $("#tbl_userdigestfile").hide(speed);
    $("#tbl_userfile").show(speed);
    $("#UserEditProviderType").removeAttr("disabled");
	if($("#UserEditProviderType").val() != "off")
	  $("#UserEditProviderType").val("passwd");
  }
  else if($("#UserViewProviderType").val() == "digest")
  {
    $("#tbl_ldapconnection").hide(speed);
    $("#tbl_ldapuser").hide(speed);
    $("#tbl_userfile").hide(speed);
    $("#tbl_userdigestfile").show(speed);
    $("#UserEditProviderType").removeAttr("disabled");
	if($("#UserEditProviderType").val() != "off")
	  $("#UserEditProviderType").val("digest");
  }
  else if($("#UserViewProviderType").val() == "ldap")
  {
    $("#tbl_userfile").hide(speed);
    $("#tbl_userdigestfile").hide(speed);
    $("#tbl_ldapconnection").show(speed);
    $("#tbl_ldapuser").show(speed);
    $("#UserEditProviderType").val("off");
    $("#UserEditProviderType").attr("disabled", "disabled");
  }

  // Group view
  if ($("#GroupViewProviderType").val() == "off")
  {
    if ($("#UserViewProviderType").val() != "ldap"){
      $("#tbl_ldapconnection").hide(speed);
    }
    $("#tbl_ldapgroup").hide(speed);
    $("#GroupEditProviderType").val("off");
    $("#GroupEditProviderType").attr("disabled", "disabled");
  }
  else if ($("#GroupViewProviderType").val() == "svnauthfile")
  {
    if ($("#UserViewProviderType").val() != "ldap"){
      $("#tbl_ldapconnection").hide(speed);
    }
    $("#tbl_ldapgroup").hide(speed);
    $("#GroupEditProviderType").removeAttr("disabled");
  }
  else if ($("#GroupViewProviderType").val() == "ldap")
  {
    if ($("#UserViewProviderType").val() == "ldap")
    {
      $("#tbl_ldapconnection").show(speed);
      $("#tbl_ldapgroup").show(speed);
      $("#GroupEditProviderType").val("off");
      $("#GroupEditProviderType").attr("disabled", "disabled");
    }
    else
    {
      $("#GroupViewProviderType").val("off");
      $("#tbl_ldapgroup").hide(speed);
      $("#GroupEditProviderType").val("off");
      $("#GroupEditProviderType").attr("disabled", "disabled");
      alert("The users must be fetched from LDAP, if you want to use the groups from your LDAP server.");
    }
  }

  // Repository view
  if ($("#RepositoryViewProviderType").val() == "off")
  {
    $("#tbl_subversion").hide(speed);
    $("#RepositoryEditProviderType").val("off");
    $("#RepositoryEditProviderType").attr("disabled", "disabled");
  }
  else if ($("#RepositoryViewProviderType").val() == "svnclient")
  {
    $("#tbl_subversion").show(speed);
    $("#RepositoryEditProviderType").removeAttr("disabled");
  }
}

/**
 * Drop down menu functions
 */
var dd_timeout=500;
var dd_closetimer=0;
var dd_menuitem=0;

function dd_open()
{
  dd_canceltimer();
  dd_close();
  dd_menuitem=$(this).find('ul').css("display", "inline");
}

function dd_close()
{
  if (dd_menuitem)
  {
    dd_menuitem.hide();
  }
}

function dd_timer()
{
  dd_closetimer=window.setTimeout(dd_close, dd_timeout);
}

function dd_canceltimer()
{
  if (dd_closetimer)
  {
    window.clearTimeout(dd_closetimer);
    closetimer=null;
  }
}

$(document).ready(function(){
  $('#topnav > ul > li').bind('mouseover', dd_open);
  $('#topnav > ul > li').bind('mouseout', dd_timer);
});

/**
 * Locale selection.
 */
function ChangeLocale(localeCode)
{
  if (window.location.href)
  {
    var url=window.location.href;
    if (url.indexOf('?') != -1)
    {
      window.location.href=url+'&locale='+localeCode;
    }
    else
    {
      window.location.href=url+'?locale='+localeCode;
    }
  }
}

$(function(){
  $('#locale-selector').change(function() {
    var localeCode=$(this).val();
    ChangeLocale(localeCode);
  });
});

/**
 * Exception and message list.
 */
$(function() {
  
	$('.top-message').each(function() {
		flashElement($(this));
	});
  
});