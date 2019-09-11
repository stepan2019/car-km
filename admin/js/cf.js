/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

function selectCFType(val, f_type) {

	var selected_val;

	if(!val) selected_val = $("#type").val();
	else selected_val=val;

	if(selected_val!="depending") {

	$("#div_depending_no").hide();

	// div_error, div_required
	if( selected_val!="checkbox") {

		if( selected_val!="username")
			$("#div_error").show();
		else
			$("#div_error").hide();
 
		if(selected_val!="user_email" && selected_val!="username" && selected_val!="password" && selected_val!="terms")
			$("#div_required").show();
		else 
			$("#div_required").hide();
	}
	else {
		$("#div_error").hide();
		$("#div_required").hide();
	}

	// div_validation
	if((selected_val=="textbox" && selected_val!="price") || selected_val=="phone" || selected_val=="whatsapp")
		$("#div_validation").show();
	else 
		$("#div_validation").hide();

	// div_other_val
	if(selected_val=="menu" || selected_val=="depending") {
		$("#div_other_val").show();
		$("#div_all_val").show();
	}
	else {
		$("#div_other_val").hide();
		$("#div_all_val").hide();
	}
	
	// div_is_numeric
	if(selected_val=="textbox" || selected_val=="menu" || selected_val=="price") {
		$("#div_is_numeric").show();
		onEnableNumeric() 
	}
	else {
		$("#div_is_numeric").hide();
		$("#div_number_format").hide();
	}

	//div_min, div_max
	if(selected_val=="textbox" || selected_val=="phone" || selected_val=="whatsapp" || selected_val=="textarea" || selected_val=="htmlarea") {
		$("#div_min").show();
		$("#div_max").show();
		$("#min").removeAttr('disabled');
		$("#max").removeAttr('disabled');
	}
	else {
		$("#div_min").hide();
		$("#div_max").hide();
		$("#min").attr('disabled','disabled');
		$("#max").attr('disabled','disabled');
	}
	
	// div min_selections, max_selections
	if(selected_val=="multiselect" || selected_val=="checkbox_group") {
		$("#div_min_selections").show();
		$("#div_max_selections").show();
		$("#min_selections").removeAttr('disabled');
		$("#max_selections").removeAttr('disabled');
	}
	else {
		$("#div_min_selections").hide();
		$("#div_max_selections").hide();
		$("#min_selections").attr('disabled','disabled');
		$("#max_selections").attr('disabled','disabled');
	}

	//div_length
	if(selected_val=="textbox" || selected_val=="textarea" || selected_val=="htmlarea" || selected_val=="youtube" || selected_val=="url" || selected_val=="email" || selected_val=="date" || selected_val=="multiselect" || selected_val=="terms" || selected_val=="phone" || selected_val=="twitter" || selected_val=="price") {
		$("#div_length").show();
	}
	else 
		$("#div_length").hide();


	//div_default_textbox
	if(selected_val=="textbox" || selected_val=="menu" || selected_val=="radio" || selected_val=="radio_group" || selected_val=="url" || selected_val=="email" || selected_val=="date" || selected_val=="price")
		$("#div_default_textbox").show();
	else 
		$("#div_default_textbox").hide();

	//div_default_textarea
	if(selected_val=="textarea" || selected_val=="htmlarea") {
		$("#div_default_textarea").show();
	}
	else 
		$("#div_default_textarea").hide();

	//div_default_checkbox
	if(selected_val=="checkbox") {
		$("#div_default_checkbox").show();
	}
	else 
		$("#div_default_checkbox").hide();

	//div_prefix, div_postfix
	if(selected_val=="textbox" || selected_val=="menu" || selected_val=="radio" || selected_val=="url" || selected_val=="email" || selected_val=="date" || selected_val=="phone" || selected_val=="whatsapp") {
		$("#div_prefix").show();
		$("#div_postfix").show();
	}
	else {
		$("#div_prefix").hide();
		$("#div_postfix").hide();
	}

	//div_elements
	if(selected_val=="menu" || selected_val=="radio" || selected_val=="radio_group" || selected_val=="checkbox_group" || selected_val=="multiselect")
		$("#div_elements").show();
	else
		$("#div_elements").hide();

	//div_terms
	if(f_type=="uf") {
	if(selected_val=="terms")
		$("#div_terms").show();
	else
		$("#div_terms").hide();
	}

	//div_uploaded
	if(selected_val=="file" || selected_val=="image" || selected_val=="logo" || selected_val=="video" || selected_val=="audio")
		$("#div_uploaded").show();
	else
		$("#div_uploaded").hide();

	//div_extensions
	if(selected_val=="file")
		$("#div_extensions").show();
	else
		$("#div_extensions").hide();

	//div_resize
	if(selected_val=="image" || selected_val=="logo")
		$("#div_resize").show();
	else
		$("#div_resize").hide();

	//div_date_format
	if(selected_val=="date")
		$("#div_date_format").show();
	else
		$("#div_date_format").hide();

	//div_top_str
	if(selected_val=="menu")
		$("#div_top_str").show();
	else
		$("#div_top_str").hide();

	//div_location_fields
	if(selected_val=="google_maps")
		$("#div_location_fields").show();
	else
		$("#div_location_fields").hide();

	// div unique, unique_error
	if(selected_val=="textbox" || selected_val=="phone" || selected_val=="email" || selected_val=="url" || selected_val=="twitter") {
		$("#div_unique").show();
		onUnique();
	}
	else {
		$("#div_unique").hide();
	}

	if(selected_val=="phone" || selected_val=="whatsapp") {
		$("#div_phone_extra").show();
		onUnique();
	}
	else {
		$("#div_phone_extra").hide();
	}

	if(selected_val=="password" || selected_val=="user_email") {
		$("#div_repeat").show();
	}
	else {
		$("#div_repeat").hide();
	}
	
	
    } // end if not depending

	if(selected_val=="depending") {

		$("#div_depending_no").show();

		$("#div_error").hide();
		$("#div_required").hide();
		$("#div_elements").hide();
		$("#div_validations").hide();
		$("#div_min").hide();
		$("#div_max").hide();
		$("#div_length").hide();
		$("#div_default_textbox").hide();
		$("#div_default_textarea").hide();
		$("#div_default_checkbox").hide();
		$("#div_prefix").hide();
		$("#div_postfix").hide();
		$("#div_uploaded").hide();
		$("#div_extensions").hide();
		$("#div_resize").hide();
		$("#div_date_format").hide();
		$("#div_top_str").hide();
		$("#div_other_val").show();
		$("#div_all_val").show();

		$("#div_min_selections").hide();
		$("#div_max_selections").hide();

		if(f_type=="uf") $("#div_terms").hide();

		var no_fields = $("#depending_no").val();

		$("#div_dep1").show();
		$("#div_top_str1").show();
		$("#div_dep_error1").show();
		$("#div_dep_required1").show();

		$("#div_dep2").show();
		$("#div_top_str2").show();
		$("#div_dep_error2").show();
		$("#div_dep_required2").show();

		if(no_fields>=3) {

		$("#div_dep3").show();
		$("#div_top_str3").show();
		$("#div_dep_error3").show();
		$("#div_dep_required3").show();

		} else {

		$("#div_dep3").hide();
		$("#div_top_str3").hide();
		$("#div_dep_error3").hide();
		$("#div_dep_required3").hide();

		}

		if(no_fields>=4) {

		$("#div_dep4").show();
		$("#div_top_str4").show();
		$("#div_dep_error4").show();
		$("#div_dep_required4").show();

		} else {

		$("#div_dep4").hide();
		$("#div_top_str4").hide();
		$("#div_dep_error4").hide();
		$("#div_dep_required4").hide();

		}

		if(no_fields>=5) {

		$("#div_dep5").show();
		$("#div_top_str5").show();
		$("#div_dep_error5").show();
		$("#div_dep_required5").show();

		} else {

		$("#div_dep5").hide();
		$("#div_top_str5").hide();
		$("#div_dep_error5").hide();
		$("#div_dep_required5").hide();

		}

	} else {

		$("#div_dep1").hide();
		$("#div_top_str1").hide();
		$("#div_dep_error1").hide();
		$("#div_dep_required1").hide();

		$("#div_dep2").hide();
		$("#div_top_str2").hide();
		$("#div_dep_error2").hide();
		$("#div_dep_required2").hide();

		$("#div_dep3").hide();
		$("#div_top_str3").hide();
		$("#div_dep_error3").hide();
		$("#div_dep_required3").hide();

		$("#div_dep4").hide();
		$("#div_top_str4").hide();
		$("#div_dep_error4").hide();
		$("#div_dep_required4").hide();

		$("#div_dep5").hide();
		$("#div_top_str5").hide();
		$("#div_dep_error5").hide();
		$("#div_dep_required5").hide();
	}

	if(f_type=="cf") {

	//div_advanced_search
	if( selected_val!="image" && selected_val!="logo" && selected_val!="file" && selected_val!="google_maps" && selected_val!="youtube" && selected_val!="checkbox_group" &&  selected_val!="radio_group" &&  selected_val!="video" &&  selected_val!="audio") {

		$("#div_advanced_search").show();
		$("#div_quick_search").show();

		if($('#advanced_search').is(':checked') || $('#quick_search').is(':checked')) {

			$("#div_search_type").show();

			if(((selected_val=="textbox" || selected_val=="menu" || selected_val=="price") && $('#is_numeric').is(':checked')) || selected_val=="date") {
				$("#span_search_interval").show();

				if($('#search_type').val()==2 && ( selected_val=="textbox" || selected_val=="price"))
					$("#div_search_interval").show();
				else 
					$("#div_search_interval").hide();

			} else {
				$("#span_search_interval").hide();
				$("#div_search_interval").hide();
			}
		}
		else { 
			$("#div_search_type").hide();
			$("#div_search_interval").hide();
		}

	}
	else {
		$("#div_advanced_search").hide();
		$("#div_quick_search").hide();
		$("#div_search_interval").hide();
	}

	} // end if cf
	else {

		if( selected_val!="password" && selected_val!="terms") 
			$("#div_public").show();
		else 
			$("#div_public").hide();

	}

	if( selected_val!="user_email" && selected_val!="username" && selected_val!="password")
		$("#div_active").show();
	else 
		$("#div_active").hide();

	if( selected_val!="password" && selected_val!="terms")
		$("#div_editable").show();
	else 
		$("#div_editable").hide();

	// public user choice
	if( selected_val=="textbox" || selected_val=="textarea" || selected_val=="htmlarea" || selected_val=="menu" || selected_val=="url" || selected_val=="phone" || selected_val=="email" || selected_val=="date" || selected_val=="whatsapp" || selected_val=="user_email" || selected_val=="twitter")
		$("#uc").show();
	else 
		$("#uc").hide();
	
	if(selected_val=="section") { $(".f_options").hide(); $("#div_active").show(); }
	
}


function onEnableSearch(t) {

	var checked = 0;

	if($('#advanced_search').is(':checked') || $('#quick_search').is(':checked')) checked = 1;

	var selected_val;

	if(t)	selected_val=t;
	else selected_val = $("#type").val();

	if(checked==1 && ( selected_val!="file" && selected_val!="image" && selected_val!="logo" && selected_val!="checkbox" && selected_val!="checkbox_group" && selected_val!="depending") ) { 
	
		$("#div_search_type").show();

		if(((selected_val=="textbox" || selected_val=="menu" || selected_val=="price") && $('#is_numeric').is(':checked')) || selected_val=="date") {

			$("#span_search_interval").show();


			if( $( "input:radio[name=search_type]:checked" ).val()==2 && ( selected_val=="textbox" || selected_val=="price" )) {
				$("#div_search_interval").show();
			}
			else 
				$("#div_search_interval").hide();

		} else {
			$("#span_search_interval").hide();
			$("#div_search_interval").hide();
		}
	}
	else
		$("#div_search_interval").hide(); 

}

function onEnableNotLoggedIn(fname) {

	var checked = $('#not_logged_in').is(':checked');
	if(checked) { 
 		$("#div_choose_groups").hide();
		$("#div_groups").hide();
	}
	else {
		$("#div_choose_groups").show();
	}
}

function onEnableNumeric() {

	var checked = $('#is_numeric').is(':checked');
	if(checked) { 
 		$("#div_number_format").show();
	}
	else {
		$("#div_number_format").hide();
	}
}

function onUnique() {
	
	var checked = $('#unique').is(':checked');

	if(checked) { 
 		$("#div_unique_error").show();
	}
	else {
		$("#div_unique_error").hide();
	}
}


function onEnableInternationalFormat() {

	var checked = $('#international_format').is(':checked');

	if(checked) { 
 		$("#div_only_countries").show();
	}
	else {
		$("#div_only_countries").hide();
	}
}