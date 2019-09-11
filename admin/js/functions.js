/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

function onBadwordsSettings() {

	var checked = $('#badwords_check').is(':checked');
	if(checked==1) $("#div_badwords").show();
	else $("#div_badwords").hide();

}

function doSel(obj)
{
     for (i = 0; i < obj.length; i++)
        if (obj[i].selected == true)
           eval(obj[i].value);
}

function activateImage() {

	$("#div_image").show();
	$("#div_code").hide();
	$("#div_link").show();

}

function activateCode() {

	$("#div_image").hide();
	$("#div_code").show();
	$("#div_link").hide();

}

function CPactivateLink() {

	$("#div_link").show();
	$("#div_title").hide();
	$("#div_meta1").hide();
	$("#div_meta2").hide();

}

function CPactivateCustom() {

	$("#div_link").hide();
	$("#div_title").show();
	$("#div_meta1").show();
	$("#div_meta2").show();

}

function onChooseGroup(fname) {

	if(fname.choose_group[0].checked) document.getElementById("div_groups").style.display='none';
	else document.getElementById("div_groups").style.display='block';

}

function chooseGroup(fname,group_str) {

	if(group_str=="") {
		fname.choose_group[0].checked=true;
		document.getElementById("div_groups").style.display='none';
	} else {
		fname.choose_group[1].checked=true;
		$("#div_groups").show();
		var split_groups=group_str.split(",");
		var no = split_groups.length;
		var len = fname.groups.length;

		for(i=0; i<len; i++) {

			var val = fname.groups.options[i].value;
			if (split_groups.toString().indexOf(","+val+",")!==-1 || split_groups[0]==val || split_groups[no-1]==val) 
				fname.groups.options[i].selected=true;
		}
	}
}

function onPlanType(fname, val) {

	if(val=='ad') {
		fname.no_ads.disabled=true;
		fname.subscription_time.disabled=true;
	} else {
		fname.no_ads.disabled=false;
		fname.subscription_time.disabled=false;
	}
	return;

}

function onCPType(tp) {
	if(tp==1) { 
		$("#div_external").hide();
		$("#div_internal").show();
		$("#div_slug").show();
	}
	else { 
		$("#div_external").show();
		$("#div_internal").hide();
		$("#div_slug").hide();
	}
}

function onNavlink() {

	var nav = $("#navlink").val();
	if(nav==1) $("#div_submenu").show();
	else $("#div_submenu").hide();

}

function editTranslation(id) {
	
	var div_str = "div_"+id;

	if($("#"+div_str).is(":visible")) $("#"+div_str).hide();
	else $("#"+div_str).show();
/*
	var disp = document.getElementById(div_str).style.display;
	if(disp=="none") document.getElementById(div_str).style.display="block";
	else document.getElementById(div_str).style.display = "none";*/
}



function mapStatus(display, hide) {

	var disp = document.getElementById("google_maps_location").style.display;
	if(disp=="none") { 
		document.getElementById("google_maps_location").style.display="block";
		document.getElementById("display_map").innerHTML = '<a href="javascript:;" onClick="mapStatus(\''+display+'\', \''+hide+'\')">'+hide+'</a><br><br>';
		init_gmap();
	}
	else { 
		document.getElementById("google_maps_location").style.display = "none";
		document.getElementById("display_map").innerHTML = '<a href="javascript:;" onClick="mapStatus(\''+display+'\', \''+hide+'\')">'+display+'</a><br><br>';
		hideMap();
	}

}

function hideMap() {
	
	$("#map").html("");
	//document.getElementById("map").innerHTML = '';
}

function onPaypalCurrency(fname) {

	var selected_index = fname.paypal_currency.selectedIndex;
	if(selected_index==0) document.getElementById("div_currency").style.display="block";	
	else  document.getElementById("div_currency").style.display="none";	

}
function getMultiple(ob, f) { 
	var len=ob.length;
	var str = '';
	for (l = 0; l < len; l++) {
		if(l) str+=',';
		str+=ob[l].value;
	}
	if(str) {
		f.value = str;
		return str;
	}
	else return '';
}

function checkAdsSettings(error_search){

	getMultiple(document.settings.allowed_html_box_right, document.settings.allowed_html);
	getMultiple(document.settings.location_fields_box_right, document.settings.location_fields);
	getMultiple(document.settings.search_location_fields_box_right, document.settings.search_location_fields);
	str = getMultiple(document.settings.search_in_fields_box_right, document.settings.search_in_fields);
	getMultiple(document.settings.prof_groups_box_right, document.settings.prof_groups);

	if(!str) { 
		alert(error_search);
		return false;
	}
	return true;

}

function checkDistanceSearch(myForm) {
	
	if(myForm.enable_distance_search.checked) {
	
		$("#div_distance_search").show();
	}
	else {
		$("#div_distance_search").hide();	
	}
	
	if(myForm.enable_location_autosuggest.checked) {
	
		$("#div_location_autosuggest").show();
	}
	else {
		$("#div_location_autosuggest").hide();	
	}
	
		
}

function checkSettings(){

	str = getMultiple(document.settings.location_fields_box_right, document.settings.location_fields);
	return true;

}

function checkInvoiceSettings(){

	str = getMultiple(document.settings.user_fields_box_right, document.settings.user_fields);
	return true;

}

function checkAddTemplate(error1, error2) {

	if(!document.getElementById("name").value) { 
		alert(error1);
		return false;
	}
	str = getMultiple(document.ie.myselect_right, document.ie.fields);

	if(!str) { 
		alert(error2);
		return false;
	}
	return true;
}

function changeFields() {

	var l = document.ie.type.length;

	var import_ad_fields = document.getElementById("import_ad_fields").value;
	var import_user_fields = document.getElementById("import_user_fields").value;
	var export_ad_fields = document.getElementById("export_ad_fields").value;
	var export_user_fields = document.getElementById("export_user_fields").value;

	var purpose = document.getElementById("purpose").value;

	if(document.ie.type[0].checked && purpose=="import")
		str = import_ad_fields;
	else 
	if(document.ie.type[0].checked && purpose=="export") 
		str = export_ad_fields;

	else 
	if(document.ie.type[1].checked && purpose=="import") 
		str = import_user_fields;

	else 
	if(document.ie.type[1].checked && purpose=="export") 
		str = export_user_fields;


	removeAllOptions(document.ie.myselect_left);
	removeAllOptions(document.ie.myselect_right);

	var index = 0;

	var str_array = str.split(',');
	var len = str_array.length;
	for (var i=0; i<len; i++) {
		if(str_array[i]) document.ie.myselect_left.options[index++] = new Option( str_array[i], str_array[i] );
	}

	return;
}

function removeAllOptions(selectbox)
{

	var i;
	for(i=selectbox.options.length-1;i>=0;i--)
	{
		selectbox.remove(i);
	}

}

function changeTemplates(str, all_fields, data, type) {

	var index = 0;
	if(type=='csv') {
		document.ie.csv_template.options.length = 0;
		document.ie.csv_template.options[index++] = new Option( all_fields, '' );
		if(data == 'ad') { 
			document.getElementById("csv_ad_additional").style.display = "block";
			document.getElementById("csv_user_additional").style.display = "none";
		} else {
			document.getElementById("csv_ad_additional").style.display = "none";
			document.getElementById("csv_user_additional").style.display = "block";
		}
	}
	else { // xml
		document.ie.xml_template.options.length = 0;
		document.ie.xml_template.options[index++] = new Option( all_fields, '' );
		if(data == 'ad') { 
			document.getElementById("xml_ad_additional").style.display = "block";
			document.getElementById("xml_user_additional").style.display = "none";
		} else {
			document.getElementById("xml_ad_additional").style.display = "none";
			document.getElementById("xml_user_additional").style.display = "block";
		}
	}

	var len;

	if(str.length==0) len=0;
	else {
		var str_array = str.split(',');
		len = str_array.length;
	}

	for (var i=0; i<len; i++) {
		split_t = str_array[i].split(":");
		if(type=='csv')
			document.ie.csv_template.options[index++] = new Option( split_t[1], split_t[0] );
		else document.ie.xml_template.options[index++] = new Option( split_t[1], split_t[0] );
	}
	return;

}
/*
function onEditPriority(id) {

	var div_name = "div_name"+id;
	var div_price = "div_price"+id;

	if(document.getElementById(div_name).style.display=='none') { 
		document.getElementById(div_name).style.display='block'; 
		document.getElementById(div_price).style.display='block'; 
	} else {
		document.getElementById(div_name).style.display='none'; 
		document.getElementById(div_price).style.display='none'; 
	}

}
*/
/*
function onEditProcessorTitle(id, recurring) {

	var div_title = "div_title"+id;
	var div_recurring = "div_recurring"+id;
	var span_title = "span_title"+id;
	var span_recurring = "span_recurring"+id;

	if(document.getElementById(div_title).style.display=='block' || document.getElementById(div_recurring).style.display=='block') { 

		document.getElementById(div_title).style.display='none'; 
		document.getElementById(span_title).style.display='block'; 

		if(recurring>=0) {
		document.getElementById(div_recurring).style.display='none'; 
		document.getElementById(span_recurring).style.display='block'; 
		}

	} else {

		document.getElementById(div_title).style.display='block'; 
		document.getElementById(span_title).style.display='none'; 

		if(recurring>=0) {
		document.getElementById(div_recurring).style.display='block'; 
		document.getElementById(span_recurring).style.display='none'; 
		}

	}

}

*/
function changeCouponType(myForm, curr) {

	if(myForm.type[0].checked) { // fixed
		document.getElementById("postfix").innerHTML=curr;
	} else {
		document.getElementById("postfix").innerHTML="%";
	} // percent

}

function onChooseCateg(fname) {

	if(fname.choose_categ[0].checked) document.getElementById("div_categories").style.display='none';
	else document.getElementById("div_categories").style.display='block';

}

function chooseCateg(fname,cat_str) {

	if(cat_str=="") {
		fname.choose_categ[0].checked=true;
		document.getElementById("div_categories").style.display='none';
	} else {
		fname.choose_categ[1].checked=true;
		document.getElementById("div_categories").style.display='block';
		var split_cats=cat_str.split(",");
		var no = split_cats.length;
		var len = fname.categories.length;
		for(i=0; i<len; i++) {
			var val = fname.categories.options[i].value;
			if (split_cats.toString().indexOf(","+val+",")!==-1 || split_cats[0]==val || split_cats[no-1]==val) fname.categories.options[i].selected=true;
		}
	}
}

function onChooseSection(fname) {

	if(fname.choose_section[0].checked) document.getElementById("div_sections").style.display='none';
	else document.getElementById("div_sections").style.display='block';

}

function chooseSection(fname, section_str) {

	if(section_str=="") {
		fname.choose_section[0].checked=true;
		document.getElementById("div_choose_sections").style.display='none';
		document.getElementById("div_sections").style.display='none';
	} else {

		fname.choose_section[1].checked=true;
		document.getElementById("div_choose_sections").style.display='block';
		document.getElementById("div_sections").style.display='block';
		var split_sections=section_str.split(",");
		var no = split_sections.length;
		var len = fname.sections.length;
		for(i=0; i<len; i++) {
			var val = fname.sections.options[i].value;
			if (split_sections.toString().indexOf(","+val+",")!==-1 || split_sections[0]==val || split_sections[no-1]==val) fname.sections.options[i].selected=true;
		}
	}
}






function onUserBlock(id, path, block, unblock) {

	var url_str="include/actions.php?action=block&object=user&id="+id;

	$.get( url_str, function( data ) {
		$("#div_block"+id).html('<a href="javascript:;" onclick="onUserUnblock(\''+id+'\',\''+path+'\',\''+block+'\',\''+unblock+'\');" style="text-decoration: none;"> <img src="'+path+'images/unblock.gif" class="tooltip" title="'+unblock+'" alt=""> </a>');
	});

}

function onUserUnblock(id, path , block, unblock) {

	var url_str="include/actions.php?action=unblock&object=user&id="+id;

	$.get( url_str, function( data ) {
		$("#div_block"+id).html('<a href="javascript:;" onclick="onUserBlock(\''+id+'\',\''+path+'\',\''+block+'\',\''+unblock+'\');" style="text-decoration: none;"><img src="'+path+'images/block.gif" class="tooltip" title="'+block+'" alt=""></a>');
	});

}

function onListingBlock(id, path, block, unblock) {

	var url_str="include/actions.php?action=block&object=listing&id="+id;

	$.get( url_str, function( data ) {
		$("#div_block"+id).html('<a href="javascript:;" onclick="onListingUnblock(\''+id+'\',\''+path+'\',\''+block+'\',\''+unblock+'\');" style="text-decoration: none;"> <img src="'+path+'images/unblock.gif" class="tooltip" title="'+unblock+'" alt=""> </a>');
	});

}

function onListingUnblock(id, path , block, unblock) {

	var url_str="include/actions.php?action=unblock&object=listing&id="+id;

	$.get( url_str, function( data ) {
		$("#div_block"+id).html('<a href="javascript:;" onclick="onListingBlock(\''+id+'\',\''+path+'\',\''+block+'\',\''+unblock+'\');" style="text-decoration: none;"><img src="'+path+'images/block.gif" class="tooltip" title="'+block+'" alt=""></a>');
	});

}


function onMsgBlock(id, path, block, unblock) {

	var url_str="include/actions.php?action=block&object=msg&id="+id;

	$.get( url_str, function( data ) {
		$("#div_block"+id).html('<a href="javascript:;" onclick="onMsgUnblock(\''+id+'\',\''+path+'\',\''+block+'\',\''+unblock+'\');" style="text-decoration: none;"> <img src="'+path+'images/unblock.gif" class="tooltip" title="'+unblock+'" alt=""> </a>');
	});

}

function onMsgUnblock(id, path , block, unblock) {

	var url_str="include/actions.php?action=unblock&object=msg&id="+id;

	$.get( url_str, function( data ) {
		$("#div_block"+id).html('<a href="javascript:;" onclick="onMsgBlock(\''+id+'\',\''+path+'\',\''+block+'\',\''+unblock+'\');" style="text-decoration: none;"><img src="'+path+'images/block.gif" class="tooltip" title="'+block+'" alt=""></a>');
	});

}


function onUserDelete(id, path, confirm_str) {

	if(confirm_str != undefined && confirm_str != '' && myConfirm(confirm_str)==false) return false;
	
	var url_str="include/actions.php?action=delete&object=user&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onUserAccept(id) {

	var url_str="include/actions.php?action=enable&object=user&id="+id;
	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onGroupDelete(id, path) {

	var url_str="include/actions.php?action=delete&object=group&id="+id;
	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onGroupEnable(id, path, enable, disable) {

	var url_str="include/actions.php?action=enable&object=group&id="+id;

	$.get( url_str, function( data ) {
		$("#div_active"+id).html('<a href="javascript:;" onclick="onGroupDisable(\''+id+'\',\''+path+'\',\''+enable+'\',\''+disable+'\');" style="text-decoration: none;"><img src="'+path+'images/disable.png" class="tooltip" title="'+disable+'" alt=""> </a>');
	});

}

function onGroupDisable(id, path, enable, disable) {

	var url_str="include/actions.php?action=disable&object=group&id="+id;

	$.get( url_str, function( data ) {
		$("#div_active"+id).html('<a href="javascript:;" onclick="onGroupEnable(\''+id+'\',\''+path+'\',\''+enable+'\',\''+disable+'\');" style="text-decoration: none;"> <img src="'+path+'images/enable.png" class="tooltip" name="'+enable+'" alt=""> </a>');
	});

}

function onEnable(id, obj_type, confirm_str) {

	if(confirm_str != undefined && confirm_str != '' && myConfirm(confirm_str)==false) return false;

	var url_str="include/actions.php?action=enable&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onDisable(id, obj_type, confirm_str) {

	if(confirm_str != undefined && confirm_str != '' && myConfirm(confirm_str)==false) return false;

	var url_str="include/actions.php?action=disable&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onUninstall(id, confirm_str) {

	if(confirm_str != undefined && confirm_str != '' && myConfirm(confirm_str)==false) return false;

	var url_str="include/actions.php?object=module&action=uninstall&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onInstall(id) {

	var url_str="include/actions.php?object=module&action=install&id="+id;

	$.get( url_str, function( data ) {
		window.location="modules/"+id+"/index.php";
	});

}

function onEnablePayment(id, obj_type) {

	var url_str="include/actions.php?action=enable&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		if(data)	alert(data);
		location.reload(true);
	});

}

function onPaymentPending(id) {

	var url_str="include/actions.php?action=pending&object=payment&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onPaymentNotPending(id) {

	var url_str="include/actions.php?action=not_pending&object=payment&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}
/*
function onSold(id, obj_type) {

	var url_str="include/actions.php?action=sold&object="+obj_type+"&id="+id;
	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onUnsold(id, obj_type) {

	var url_str="include/actions.php?action=unsold&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onRented(id, obj_type) {

	var url_str="include/actions.php?action=rented&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onUnrented(id, obj_type) {

	var url_str="include/actions.php?action=unrented&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}
*/
function onAccept(id) {

	var url_str="include/actions.php?action=accept&object=listing&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onMoveUp(id,obj_type) {

	var url_str="include/actions.php?action=move_up&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onMoveDown(id,obj_type) {

	var url_str="include/actions.php?action=move_down&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onCategMove( order_no, move_to) {

//	if(way=="up") action = "move_up"; else action = "move_down";

	$.get("include/actions.php", { action: "move", object: "categ", order_no: order_no, move_to: move_to },
  	function(data){
		location.reload(true);
  	});
}

function onFieldMove( order_no, way, objtype, fieldset) {

	if(way=="up") action = "move_up"; else action = "move_down";
	$.get("include/actions.php", { action : action, object : objtype, order_no : order_no, fieldset : fieldset },
  	function(data){

		if(!data) return;
		if(data<0) {
			location.reload(true);
			return;
		}
		if(objtype=="cf")
			var ar = new Array("checkbox", "description","id", "name", "fieldset", "type", "caption", "action");
		else var ar = new Array("checkbox", "description","id", "name", "type", "caption", "action");
		no = ar.length;

		for(i=0; i<no; i++) {
			//console.log(ar[i]+data);	
			var crt = document.getElementById(ar[i]+order_no).innerHTML
			var sw_to = document.getElementById(ar[i]+data).innerHTML
			
			document.getElementById(ar[i]+order_no).innerHTML = sw_to;
			document.getElementById(ar[i]+data).innerHTML = crt;

		}
		// change row style to keep read only fields visible
		var old_class = document.getElementById("row"+order_no).getAttribute("class");
		var sw_to_class = document.getElementById("row"+data).getAttribute("class");

		if(old_class!="nicetablerow_pending" && sw_to_class!="nicetablerow_pending") return;

		var old_over_class = document.getElementById("row"+order_no).getAttribute("onmouseover");
		var old_out_class = document.getElementById("row"+order_no).getAttribute("onmouseout");

		var sw_to_over_class = document.getElementById("row"+data).getAttribute("onmouseover");
		var sw_to_out_class = document.getElementById("row"+data).getAttribute("onmouseout");

		document.getElementById("row"+order_no).setAttribute("class", sw_to_class);
		document.getElementById("row"+order_no).setAttribute("onmouseover", sw_to_over_class);
		document.getElementById("row"+order_no).setAttribute("onmouseout", sw_to_out_class);

		document.getElementById("row"+data).setAttribute("class", old_class);
		document.getElementById("row"+data).setAttribute("onmouseover", old_over_class);
		document.getElementById("row"+data).setAttribute("onmouseout", old_out_class);

  	});
}


function onClearHits(id,str,obj_type)
{
	if (myConfirm(str)==false) return;
	var url_str="include/actions.php?action=clear_hits&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onDeleteAll(str,obj_type)
{
	if (myConfirm(str)==false) return;
	var url_str="include/get_info.php?type="+obj_type;


	$.get( url_str, function( data ) {
		if(data) {
//alert(data);
			var split_arr=data.split(",");
			var no=split_arr.length;
			var i;
			for(i=0; i<no; i++) {
				var chk="chk"+split_arr[i];
				if(document.getElementById(chk).checked) {
					var url_str="include/actions.php?action=delete&object="+obj_type+"&id="+split_arr[i];
					$.ajax({
						url: url_str,
						async: false
					});
				}
			}

		}
		location.reload(true);
	});

}

function onRenew(id, str) {

	if (myConfirm(str)==false) return;
	url_str="include/actions.php?object=listing&action=renew&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function selDep(n, max, id, caption1, caption2) {

	var sel1 = "sel_"+caption1;
	var list1 = caption2+"_list";
	if(n<max) var sel2 = "sel_"+caption2; else sel2='';

	var selDep = $("#sel_"+caption1).val();
	var option;

	var url_str="include/get_info.php?type=dep&id="+selDep+"&dep_id="+id+"&table="+n;

	$.ajax({
        url: url_str,
        success: function (data) {
		if(data=='0') return;

		var split_arr=data.split(":");
		var no=split_arr.length;
		var i;
					
		$("#"+list1).empty();
		if(sel2) $("#"+sel2).empty();
//alert(list1);		
//alert(sel2);
		for(i=0; i<no; i++) {
			split_dep=split_arr[i].split(',');
			var dep_id=split_dep[0];
			var dep_name=split_dep[1];
			$("#"+list1).append($('<option>', {value:dep_id, text:dep_name}));
			if(sel2) $("#"+sel2).append($('<option>', {value:dep_id, text:dep_name}));

		}
        },
        async: false
    });

}

function chooseUserGroup(myForm) {

	var selected_val = $("#group").val();
	url_str="include/get_info.php?type=group&id="+selected_val;

	$.get( url_str, function( data ) {
		if(data=='0') return;

		var split_div=data.split(",");
		var no = split_div.length;

		for(f=0; f< no; f++) {
			div_spec=split_div[f];
			var split_spec=div_spec.split("=");
			//var div_name=split_spec[0];
			if(split_spec[1]==1) $("#"+split_spec[0]).show();
			else $("#"+split_spec[0]).hide();
		}
	});

}

function onDeleteLanguage(str, id) {

	if (myConfirm(str)==false) return;

	var url_str="include/actions.php?action=delete&object=language&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onLanguageEnable(id, path, enable, disable) {

	var url_str="include/actions.php?action=enable&object=language&id="+id;

	$.get( url_str, function( data ) {
		$("#div_active"+id).html('<a href="javascript:;" onclick="onLanguageDisable(\''+id+'\',\''+path+'\',\''+enable+'\',\''+disable+'\');" style="text-decoration: none;"> <img src="'+path+'images/disable.png" class="tooltip" name="'+disable+'" alt=""> </a>');
	});

}

function onLanguageDisable(id, path, enable, disable) {

	var url_str="include/actions.php?action=disable&object=language&id="+id;

	$.get( url_str, function( data ) {
		$("#div_active"+id).html('<a href="javascript:;" onclick="onLanguageEnable(\''+id+'\',\''+path+'\',\''+enable+'\',\''+disable+'\');" style="text-decoration: none;"> <img src="'+path+'images/enable.png" class="tooltip" title="'+enable+'" alt=""> </a>');
	});

}

function onLanguageMoveUp(id) {

	var url_str="include/actions.php?action=move_up&object=language&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onLanguageMoveDown(id) {

	var url_str="include/actions.php?action=move_down&object=language&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onLanguageDefault(id) {

	var url_str="include/actions.php?action=default&object=language&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function savePriority(id) {

	var name_value = $("#name"+id).val();
	var price_value =  $("#price"+id).val();

	var url_str="include/actions.php?action=edit&object=pri&id="+id+"&name="+name_value+"&price="+price_value;

	$.get( url_str, function( data ) {

		if(data==1) {

			$("#span_name"+id).html(name_value);
			$("#span_price"+id).html(price_value);

			$("#div_name"+id).hide();
			$("#div_price"+id).hide();
		
		} else alert(response);

	});

}

function chooseBannerPosition(myForm) {

	var pos = myForm.position.value;

	var url_str="include/get_info.php?type=positions&pos="+pos;

	$.get( url_str, function( data ) {

		if(data==1) {
			$("#div_choose_sections").hide();
			$("#div_sections").hide();
		} else { 
			$("#div_choose_sections").show();
			$("#div_sections").show();
		}
	});

}

function onChooseRuleType(fname) {

	var val = $("#type").val();

	if(val=="allowed") {
		$("#div_values").show();
		$("#div_allow").show();
		$("#div_required").hide();
		$("#div_required_gr").hide();
		$("#span_info").html($("#info_allowed").html());
	}
	else if(val=="required") {
		$("#div_values").show();
		$("#div_allow").hide();
		$("#div_required").show();
		$("#div_required_gr").hide();
		$("#span_info").html($("#info_required").html());
	}
	else if(val=="required_gr") {
		$("#div_values").show();
		$("#div_allow").hide();
		$("#div_required").hide();
		$("#div_required_gr").show();
		$("#span_info").html($("#info_required_gr").html());
	}

}

function markPaid(id, val) {

 	var url_str="include/actions.php?object=aff_payment&action=mark_paid&id="+id+"&paid="+val;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onAddWhitelist(ip, confirm_str) {

	if(confirm_str != undefined && confirm_str != '' && myConfirm(confirm_str)==false) return false;

	var url_str="include/actions.php?object=blockedip&action=whitelist&ip="+ip;

	$.get( url_str, function( data ) {
		location.reload(true);
	});
	
}

function onAddWhitelistEmail(email, confirm_str) {

	if(confirm_str != undefined && confirm_str != '' && myConfirm(confirm_str)==false) return false;

	var url_str="include/actions.php?object=blockedemail&action=whitelist&email="+email;

	$.get( url_str, function( data ) {
		location.reload(true);
	});
	
}

function onAddWhitelistPhone(phone, confirm_str) {

	if(confirm_str != undefined && confirm_str != '' && myConfirm(confirm_str)==false) return false;

	var url_str="include/actions.php?object=blockedphone&action=whitelist&phone="+phone;

	$.get( url_str, function( data ) {
		location.reload(true);
	});
	
}

function onEditSMSGatewayTitle(gateway) {

	$("#div_title"+gateway).show();
	
}

function saveSMSGatewayTitle(gateway) {

	var title_value = $("#title"+gateway).val();
	var url_str="include/actions.php?action=edit&object=smsg&gateway="+gateway+"&title="+title_value;

	$.get( url_str, function( data ) {

		$("#span_title"+gateway).html(title_value);
		$("#div_title"+gateway).hide();

	});
	
}

function onSetDefault(gateway) {

	var url_str="include/actions.php?action=default&object=smsg&gateway="+gateway;

	$.get( url_str, function( data ) {

		if(data==1) {

			location.reload(true);
		
		} else alert(data);
		
		
	});
	
}

