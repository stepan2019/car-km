/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

/*
	* ads titles to search fields
*/

$(document).ready(function()
{
    $(".defaultText").focus(function(srcc)
    {
        if ($(this).val() == $(this)[0].title)
        {
            $(this).removeClass("defaultTextActive");
            $(this).val("");
        }
    });

    $(".defaultText").blur(function()
    {
        if ($(this).val() == "")
        {
            $(this).addClass("defaultTextActive");
            $(this).val($(this)[0].title);
        }
    });

    $(".defaultText").blur();

   $("#search").submit(function(){clearInputs()});
   $("#qsearch").submit(function(){clearInputs()});
   $("#browse").submit(function(){clearInputs()});
   $("#refine").submit(function(){clearInputs()});


});


function clearInputs()
    {
	var allInputs = $(":input");

	var no=allInputs.length;
	for(var i=0; i<no; i++) {
		if (allInputs[i].title == allInputs[i].value) allInputs[i].value="";
	}
    }

function myConfirm(msg)
{
    if(!confirm(msg)) return false;
    return true;
}

function doSel(obj)
{
     for (i = 0; i < obj.length; i++)
        if (obj[i].selected == true)
           eval(obj[i].value);
}

function changeLoc(obj, base, field, param) {

     for (i = 0; i < obj.length; i++)
        if (obj[i].selected == true)
		location.href=base+"?"+param+"&"+field+"="+obj[i].value;

}

function CountTitleLeft(field, max) {

	if (field.value.length > max)
		field.value = field.value.substring(0, max);

}

function splitMapsCoord(str) {
	if(!str) return 0;
	var split_str=str.split(",");
	var no = split_str.length;
	if(no!=3) return 0;
	return split_str;
}


function PreloadImages(str){
	
	a = str.split(",");
	var d=document; if(d.images){
	    if(!d.MM_p) d.MM_p=new Array();
	    var i,j=d.MM_p.length;
	
	    for(i=0; i<a.length; i++) {
		if (a[i].indexOf("#")!=0){ 
		    d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];
		}
	    }
	}
}

function SwapImagesThick(str){

document.getElementById('bigImg').innerHTML=str;
document.getElementById('galleryVideo').style.display="none";
document.getElementById('bigImg').style.display="block";
//tb_init('a.thickbox, area.thickbox, input.thickbox');

}

function SwapToVideo(){

document.getElementById('bigImg').style.display="none";
document.getElementById('galleryVideo').style.display="block";

}

function CountTextLeft(field, count) {

	if(!$("#"+count).val()) return;
	var text=$("#"+field).val() + " ";
	var no_words=$("#no_words").val();

	if(no_words>0) {
		var number = 0;
		var matches = $("#"+field).val().match(/\b/g);
		if (matches) {
			number = matches.length / 2;
		}
		words_left = no_words - number;
		$("#"+count).val(words_left);
		
		if(number==no_words) cutPos= i;
		
		if(words_left<0) {
			text = text.substring(0,cutPos);
			$("#"+field).val(text);
			words_left=0;
		}
		count.value=words_left;
	}

}

//uncomment while commenting the other "CountTextLeft" function for latin + chinese characters

/*
function CountTextLeft(myForm, field, count) {

	if (typeof myForm.count == 'undefined') return;

	var text=field.value + " ";
	var no_words=myForm.no_words.value;
	if(no_words>0)
	{

		var counter,i,cutPos;
		counter=0;
		for(i=0;i<text.length;i++){
			while(text.charAt(i)==' '|| text.charAt(i)=='\n'|| text.charAt(i)==','|| text.charAt(i)=='.') i++;
			if((text.charAt(i+1)==' '||text.charAt(i+1)=='\n' || text.charAt(i+1)==','|| text.charAt(i+1)=='.' || text.charCodeAt(i+1)>255 || i==text.length-1) && text.charCodeAt(i)<=255) counter++;
			if(text.charCodeAt(i)>255){//skip if was space before!!!!
				if(i>0 && text.charAt(i-1)!=' '||text.charAt(i-1)!='\n' || text.charAt(i-1)!=','|| text.charAt(i-1)!='.')
				counter++;
			}
			if(counter==no_words) cutPos= i;
		}

		words_left=no_words-counter;
		
		if(words_left<0) {
			text = text.substring(0,cutPos);
			field.value = text;
			words_left=0;
		}
		count.value=words_left;
	}
}
*/
function strchlen(str){
	var counter,i;
	counter=0;
	for(i=0;i<str.length;i++){
		if(str.charCodeAt(i)>255){
			counter++;
		}
	}
	return counter;
}

function strenlen(str){
	var counter,i;
	counter=0;
	for(i=0;i<str.length;i++){
	while(str.charAt(i)==' '||str.charCodeAt(i)>255||str.charAt(i)=='\n') i++;
	if(str.charAt(i+1)==' '||str.charAt(i+1)=='\n'||str.charCodeAt(i+1)>255||i==str.length-1) counter++;
	}
	return counter;
}

function clickBanner(id, live_site) {

	url_str=live_site+"/include/get_info.php?type=banner&id="+id;
	$.ajax({
		url: url_str,
		success: function(data) {
		}
	});
}

function onDelete(id,str,obj_type)
{

	if (myConfirm(str)==false) return;
	var url_str="include/actions.php?action=delete&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

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

function add_to_fav(id, confirm_str, done_str, site_url) {

	if(confirm_str!="" && myConfirm(confirm_str)==false) return;

	var url_str = site_url+"/include/actions.php?action=favourite&object=listing&id="+id;

	$.get( url_str, function( data ) {

		alert(done_str);
		location.reload(true);

	});

}

function checkOther(elem, val) {

	var selected_index = elem.selectedIndex;
	var selected_val = elem.options[selected_index].value;

	if(selected_val=="-1") 
		document.getElementById("span_"+val+"_other_val").style.display="block";
	else 
		document.getElementById("span_"+val+"_other_val").style.display="none";

}

function selDepending(field_no, caption1, caption2, dep_id, categ_id, other_val, other_str, all_val, all_str, live_site, prev_dep_id) {

// field_no - number of fields in the depending field
// caption1, caption2 - the database field name for the current selected field and the next field to be filled with values depending on current value
// dep_id - the id of the depending field from depending_fields table
// other_val - 1 if the field supports other values
// other_str - current language translation for "Other"
// all_val - 1 if the field supports all values
// all_str - current language translation for "All"
// live_site - current site url
// prev_dep_id - the value of previous selected depending value

var sel_value = $("#"+caption1).val();

// get the id for selected value, depending on previous depending id
var prev_id = 0;
if(prev_dep_id) prev_id =  $("#"+prev_dep_id).val();

if(other_val){
	if(sel_value=="-1") {

		$("#span_"+caption1+"_other_val").show();
		$("#span_"+caption2+"_other_val").show();
		$("#"+caption2).removeAttr('disabled');

		var option = $('<option></option>').attr("value", "-1").text(other_str).attr('selected', 'selected');
		$("#"+caption2).empty().append(option);

	} else {

		$("#span_"+caption1+"_other_val").hide();
		$("#span_"+caption2+"_other_val").hide();

	}
}

if(all_val){
	if(sel_value=="all") {

		$("#"+caption2).removeAttr('disabled');

		var option = $('<option></option>').attr("value", "all").text(all_str).attr('selected', 'selected');
		$("#"+caption2).empty().append(option);

	}
}

sel_value = encodeURIComponent(sel_value);

$("#"+caption2).removeAttr('disabled');

url_str=live_site+"/include/get_info.php?type=depending&field="+dep_id+"&dep="+sel_value+"&field_no="+field_no;
if(categ_id) url_str+="&cat="+categ_id;
if(prev_id) url_str+="&prev_id="+prev_id;

$.get( url_str, function( data ) {

		var dep_str=data;//response=top_str:crt_value_id:next_caption1:next_caption2:next_top_str1:next_top_str2:id[0],name[0]:id[1],name[1] .....
//alert(dep_str);
 		var split_dep=dep_str.split(":");
		var no = split_dep.length;

		var option = $('<option></option>').attr("value", "").text(split_dep[0]);
		$("#"+caption2).empty().append(option);

		if(other_val) {
	
			option = $('<option></option>').attr("value", "-1").text(other_str);
			if(sel_value==-1) 
				option.attr('selected', 'selected');
			$("#"+caption2).append(option);

		}

		if(all_val) {
	
			option = $('<option></option>').attr("value", "all").text(all_str);
			if(sel_value=="all") 
				option.attr('selected', 'selected');
			$("#"+caption2).append(option);

		}

		for (var j=6;j<no;j++) {

			split_d=split_dep[j].split(',');
			var dep_id=split_d[0];
			var dep_name=split_d[1];

			option = $('<option></option>').attr("value", dep_name).text(dep_name);
			$("#"+caption2).append(option);

		}

		var crt_value_id = split_dep[1];
		$("#dep_id_"+caption1).val(crt_value_id);
		var next_caption1 = split_dep[2];
		var next_caption2 = split_dep[3];

		var next_top_str1 = split_dep[4];
		var next_top_str2 = split_dep[5];

		if( next_caption1 && $("#"+next_caption1)!=null ) { 

			option = $('<option></option>').attr("value", "").text(next_top_str1);
			$("#"+next_caption1).append(option);
			$("#"+next_caption1).attr('disabled', 'disabled');

			if(other_val){
				// if other value selected
				if(sel_value=="-1") {

					$("#span_"+next_caption1+"_other_val").show();
					$("#"+next_caption1).removeAttr('disabled');

					option = $('<option></option>').attr("value", "-1").text(other_str).attr('selected', 'selected');
					$("#"+next_caption1).empty().append(option);

				} else {

					$("#span_"+next_caption1+"_other_val").hide();

				}
			}//end if other_val

			if(all_val){
				// if all value selected
				if(sel_value=="all") {

					$("#"+next_caption1).removeAttr('disabled');

					option = $('<option></option>').attr("value", "all").text(all_str).attr('selected', 'selected');
					$("#"+next_caption1).empty().append(option);

				}
			}//end if all_val
			
		}
		if(next_caption2 && $("#"+next_caption2)!=null) { 

			option = $('<option></option>').attr("value", "").text(next_top_str2);
			$("#"+next_caption2).append(option);
			$("#"+next_caption2).attr('disabled', 'disabled');

			if(other_val){
				// if other value selected
				if(sel_value=="-1") {

					$("#span_"+next_caption2+"_other_val").show();
					$("#"+next_caption2).removeAttr('disabled');

					option = $('<option></option>').attr("value", "-1").text(other_str).attr('selected', 'selected');
					$("#"+next_caption2).empty().append(option);

				} else {

					$("#span_"+next_caption2+"_other_val").hide();

				}
			}//end if other_val

			if(all_val){
				// if All value selected
				if(sel_value=="all") {

					$("#"+next_caption2).removeAttr('disabled');

					option = $('<option></option>').attr("value", "all").text(all_str).attr('selected', 'selected');
					$("#"+next_caption2).empty().append(option);

				} 
			}//end if all_val
			
		} // end next_caption2

}); // end get


}

function oldDepending(field_no, caption1, caption2, dep_id, sec_val, other_val, other_str, all_val, all_str, live_site) {

var id = $("#"+caption1).val();

if(other_val){
	if(id=="-1") {

		$("#span_"+caption1+"_other_val").show();
		$("#span_"+caption2+"_other_val").show();
		$("#"+caption2).removeAttr('disabled'); //$('#id).attr('disabled', 'disabled');

		var option = $('<option></option>').attr("value", "-1").text(other_str);
		$("#"+caption2).empty().append(option);
		return;

	} else {

		$("#span_"+caption1+"_other_val").hide();
		$("#span_"+caption2+"_other_val").hide();

	}
}

if(all_val){
	if(id=="all") {

		$("#"+caption2).removeAttr('disabled'); //$('#id).attr('disabled', 'disabled');

		var option = $('<option></option>').attr("value", "all").text(all_str);
		$("#"+caption2).empty().append(option);
		return;

	}
}

id = encodeURIComponent(id);

$("#"+caption2).removeAttr('disabled');

url_str=live_site+"/include/get_info.php?type=depending&field="+dep_id+"&field_no="+field_no+"&dep="+id;

//syncronous ajax
 jQuery.ajax({
         url:    url_str,
         success: function(data) {

		var split_dep=data.split(":");
		var no = split_dep.length;

		var option = $('<option></option>').attr("value", "").text(split_dep[0]);
		$("#"+caption2).empty().append(option);

		if(other_val) {

			option = $('<option></option>').attr("value", "-1").text(other_str);
			$("#"+caption2).append(option);

		}

		if(all_val) {

			option = $('<option></option>').attr("value", "all").text(all_str);
			$("#"+caption2).append(option);

		}

		var found = 0;
		for (var j=6;j<no;j++) {
			split_d=split_dep[j].split(',');
			var dep_id=split_d[0];
			var dep_name=split_d[1];

			if(sec_val==dep_name) {

				option = $('<option></option>').attr("value", dep_name).text(dep_name).attr('selected', 'selected');
				found = 1;
			}
			else 
				option = $('<option></option>').attr("value", dep_name).text(dep_name);
			$("#"+caption2).append(option);

		}

		if(!found && sec_val && other_val) { 

			document.getElementById(caption2).selectedIndex=1;
			$("#span_"+caption2+"_other_val").show();
			$("#span_"+caption2+"_other_val").val(sec_val);

		}

	},
	async:   false
    });     // end ajax


}

function reloadDep(str, live_site) {

var cat = $("#qs_category").val();
url_str=live_site+"/include/get_info.php?type=reload_depending&str="+str+"&cat="+cat;

$.get( url_str, function( data ) {

	if(!data) return;

 	var split_dep=data.split("^");
	var no_fields = split_dep.length;

	for (var idx=0;idx<no_fields;idx++) {

		var vals=split_dep[idx].split(",");
		var no = vals.length;
		caption = "qs_"+vals[0];
		top_str = vals[1];
		caption2 = "qs_"+vals[2];
		top_str2 = vals[3];

		$("#"+caption).removeAttr('disabled'); 
		var option = $('<option></option>').attr("value", "").text(top_str);
		$("#"+caption).empty().append(option);

		option = $('<option></option>').attr("value", "").text(top_str2);
		$("#"+caption2).empty().append(option);
		$("#"+caption2).attr('disabled', 'disabled'); 

		if (no<=4) { $("#"+caption).attr('disabled', 'disabled');  continue; }

		for (var j=4;j<no;j++) {
			option = $('<option></option>').attr("value", vals[j]).text(vals[j]);
			$("#"+caption).append(option);
		}

	} //end for (var idx=1;idx<no;idx++) 

}); // end get

}

function calculateCredits(total, no_decimals) {

	var cr = total/$("#credit_value").val();
	var needed_credits = cr.toFixed(no_decimals);

	if(needed_credits>parseFloat($("#current_credits").val())) {

		var crt_processor = $('input:radio[name=processor]:checked').val();

		if($("#current_credits").val()>0) $("#not_enough_credits").show();
		$("#no_credits").hide();
 		$("input[name=processor][value=credits]").attr('disabled',true);

		// select the next payment processor if credits was selected
		if(crt_processor=="credits")
			$('input[name=processor]:nth-child(1)').attr('checked', true);

	} else {

		$("#not_enough_credits").hide();
		$("#no_credits").show();
		$("#needed_credits").html(needed_credits);
		if(needed_credits>1) { 
			$("#scredits").show();
			$("#scredit").hide();
		} else {
			$("#scredits").hide();
			$("#scredit").show();
		}
		$("input[name=processor][value=credits]").attr('disabled',false);

	}

}

function adjustTotal(myForm, price, field, no_decimals) {

	var total = $("#full_total").val();

	if(field.checked==true)
		// add to total
		total = parseFloat(total)+parseFloat(price);
	else
		// substract from total
		total = parseFloat(total)-parseFloat(price);

	$("#full_total").val(total.toFixed(no_decimals));
	$("#total").html(total.toFixed(no_decimals));

	var discount_enabled = 0;
	if ($("#div_discount")) discount_enabled = 1;

	var credits_enabled = 0;
	if ($("#div_credits")) credits_enabled = 1;

	if(total>0) { 
		$("#div_total").show();
		if(discount_enabled) $("#div_discount").show();
		if(credits_enabled) $("#div_credits").show();
	}
	else { 
		$("#div_total").hide();
		if(discount_enabled) $("#div_discount").hide();
		if(credits_enabled) $("#div_credits").hide();
	}

	getDiscount (myForm,"ads", 0, no_decimals);

	if(only_processor)
		var processor = $('input[name=processor]').val();
	else 
		var processor = $('input:radio[name=processor]:checked').val();

	var total = $("#full_total").val();
	calculateTax(processor, total);
	calculateCredits(total, no_decimals);

	reinit();

}

function calculateTax(p, t) {
//alert("calculate tax "+p+"  "+t+" only_pr = "+only_processor);
	$.post("include/get_info.php?type=processor", { processor: p, total: t }, function(data){ 

		var split_data=data.split("|");
		tax = split_data[0];
		full_total = split_data[1];
		tax_str = split_data[2];

		if(tax) {

			$("#tax_info").show();
			$("#total_with_tax_info").show();
			$("#tax").html(tax);
			$("#tax_str").html(tax_str);
			$("#total_with_tax").html(full_total);

		}

		else {

			$("#tax_info").hide();
			$("#total_with_tax_info").hide();

		}

	});	

}

function changePriority(myForm, no_decimals) {

	var total = myForm.full_total.value;
	var prev = myForm.pri.value;
	var val;
	if($('#enable_priority').prop('checked')) {
		$("#priority").prop('disabled', false);
		val = $('#priority option:selected').attr("title");
	}
	else {
		$("#priority").prop('disabled', 'disabled');
		val=0;
	}
	if(!prev) prev=0;
	if(!val) val=0;
	total = parseFloat(total)+parseFloat(val);
	total = parseFloat(total)-parseFloat(prev);

	myForm.pri.value = val;
	myForm.full_total.value = total.toFixed(no_decimals);
	document.getElementById("total").innerHTML = total.toFixed(no_decimals);

	var discount_enabled = 0;
	if ($("#div_discount")) discount_enabled = 1;

	var credits_enabled = 0;
	if ($("#div_credits")) credits_enabled = 1;

	if(total>0) { 
		$("#div_total").show();
		if(discount_enabled) $("#div_discount").show();
		if(credits_enabled) $("#div_credits").show();
	}
	else { 
		$("#div_total").hide();
		if(discount_enabled) $("#div_discount").hide();
		if(credits_enabled) $("#div_credits").hide();
	}

	getDiscount (myForm,"ads", 0, no_decimals);

	if(only_processor)
		var processor = $('input[name=processor]').val();
	else 
		var processor = $('input:radio[name=processor]:checked').val();

	var total = $("#full_total").val();
	calculateTax(processor, total);
	calculateCredits(total, no_decimals);

	reinit();

}

function changeFeaturedPlan(myForm, no_decimals) {

	var total = myForm.full_total.value;
	var prev = myForm.featured_val.value;
	var val;
	if($('#enable_featured').prop('checked')) {
		$("#featured").prop('disabled', false);
		val = $('#featured option:selected').attr("title");
	}
	else {
		$("#featured").prop('disabled', 'disabled');
		val=0;
	}
	if(!prev) prev=0;
	if(!val) val=0;
	total = parseFloat(total)+parseFloat(val);
	total = parseFloat(total)-parseFloat(prev);

	myForm.featured_val.value = val;
	myForm.full_total.value = total.toFixed(no_decimals);
	document.getElementById("total").innerHTML = total.toFixed(no_decimals);

	var discount_enabled = 0;
	if ($("#div_discount")) discount_enabled = 1;

	var credits_enabled = 0;
	if ($("#div_credits")) credits_enabled = 1;

	if(total>0) { 
		$("#div_total").show();
		if(discount_enabled) $("#div_discount").show();
		if(credits_enabled) $("#div_credits").show();
	}
	else { 
		$("#div_total").hide();
		if(discount_enabled) $("#div_discount").hide();
		if(credits_enabled) $("#div_credits").hide();
	}

	getDiscount (myForm,"ads", 0, no_decimals);

	if(only_processor)
		var processor = $('input[name=processor]').val();
	else 
		var processor = $('input:radio[name=processor]:checked').val();

	var total = $("#full_total").val();
	calculateTax(processor, total);
	calculateCredits(total, no_decimals);

	reinit();

}

function roundit(Num, Places) {
   if (Places > 0) {
      if ((Num.toString().length - Num.toString().lastIndexOf('.')) > (Places + 1)) {
         var Rounder = Math.pow(10, Places);
         return Math.round(Num * Rounder) / Rounder;
      }
      else return Num;
   }
   else return Math.round(Num);
}

function getDiscount (myForm, type, hide_processors, no_decimals) {
// remove myForm, hide_processors??!!!
if (!$("#discount_code"))  return;

var total = $("#full_total").val();

if(!total) return;

var code = $("#discount_code").val();

var credits_enabled = 0;
if ($("#div_credits")) credits_enabled = 1;

$.get( "include/get_info.php?type=discount&id="+code+"&dtype="+type+"&amount="+total, function( data ) {

	var new_total=parseFloat(data);
	if(new_total==-1) new_total = parseFloat(total);
	$("#total").html(new_total.toFixed(no_decimals));

	//if(hide_processors) {
		if(new_total==0) { 
	
			$("#processors").hide();
			$("#div_total").hide();
			if(credits_enabled) $("#div_credits").hide();

		}
		else { 

			$("#processors").show();
			$("#div_total").show();
			if(credits_enabled) $("#div_credits").show();

		}
	//}

	if(only_processor)
		var processor = $('input[name=processor]').val();
	else 
		var processor = $('input:radio[name=processor]:checked').val();

	calculateTax(processor, new_total);
	calculateCredits(new_total, no_decimals);

});

}


function radioSelected() {

	var search = document.searchFolderContents.search
	var radioLength = (typeof search.length == "number") ? (search.length) : 1;

}


function checkAll(doc) {
  var c = new Array();
  c = doc.getElementsByTagName('input');
  for (var i = 0; i < c.length; i++)
  {
    if (c[i].type == 'checkbox')
    {
      c[i].checked = true;
    }
  }
}

function uncheckAll(doc)
{
  var c = new Array();
  c = doc.getElementsByTagName('input');
  for (var i = 0; i < c.length; i++)
  {
    if (c[i].type == 'checkbox')
    {
      c[i].checked = false;
    }
  }

}

function checkDeleteData(field_vis, field) {
	if(document.getElementById(field_vis).value=='') document.getElementById(field).value = '';
	return 1;
}

function onDeleteAlert(id) {

	url_str="include/actions.php?object=alert&action=delete&id="+id;
	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onDeleteSavedSearch(id) {

	url_str="include/actions.php?object=search&action=delete&id="+id;
	$.get( url_str, function( data ) {
		location.reload(true);
	});

}


function onChangeLocation(id, table, live_site) {

	$("#location_tabnav").find(".active").removeClass("active");
	$("#location_tab"+id).addClass("active");
	$(".locdiv").hide();
	$("#loc1_"+id).show();

}

function onChangeMake(id, table, live_site) {

	$("#make_tabnav").find(".active").removeClass("active");
	$("#make_tab"+id).addClass("active");
	$(".makediv").hide();
	$("#make1_"+id).show();

}


function onQuickSearch(myForm,live_site) {

	var selected_val = $("#qs_category").val();
	var split_str=selected_val.split(",");
	id=split_str[0];

	url_str=live_site+"/include/get_info.php?type=quick-search&id="+id;

	$.get( url_str, function( data ) {

		if(data==0) return;

		var split_str=data.split(",");
		var no = split_str.length;
		for(f=0; f< no; f++) {

			div_spec=split_str[f];
			var split_spec=div_spec.split("=");
			var field_name=split_spec[0];
		
			if($("#"+field_name).val()!=null) {

				if(split_spec[1]==1) $("#"+field_name).fadeIn();
				else $("#"+field_name).fadeOut();

			} // end if field not null
		} // end for
	});

}

function IsNumeric(inputVal) {
	if (isNaN(parseFloat(inputVal))) {
		return false;
	}
	return true;
}

function dump(arr,level) {
var dumped_text = "";
if(!level) level = 0;

//The padding given at the beginning of the line.
var level_padding = "";
for(var j=0;j<level+1;j++) level_padding += "    ";

if(typeof(arr) == 'object') { //Array/Hashes/Objects
 for(var item in arr) {
  var value = arr[item];
 
  if(typeof(value) == 'object') { //If it is an array,
   dumped_text += level_padding + "'" + item + "' ...\n";
   dumped_text += dump(value,level+1);
  } else {
   dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
  }
 }
} else { //Stings/Chars/Numbers etc.
 dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
}
return dumped_text;
} 


function onReportMsg(id,str)
{

	if (myConfirm(str)==false) return;
	var url_str="include/actions.php?action=report&object=msg&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function changeLocation(live_site, str) {

	var split_res=str.split("|");
	var field = split_res[0];	
	var val = split_res[1];	
	$.post(live_site+"/include/set_location.php", { field: field, location: val, direct: 1 }, function(data){ 
		location.reload(true);
	} );

}

function changeDoubleLocation(live_site, sef_links, str) {

	var split_res=str.split(",");
	var arr1 = split_res[0];
	var arr2 = split_res[1];
	
	split_res=arr1.split("|");
	var field1 = split_res[0];	
	var val1 = split_res[1];	
	
	split_res=arr2.split("|");
	var field2 = split_res[0];	
	var val2 = split_res[1];	
	//alert(field1+"-"+val1+"-"+field2+"-"+val2);
	
	jQuery.post(live_site+"/include/set_location.php", { field1: field1, location1: val1, field2: field2, location2: val2, double_type: 1 }, function(data){ 
		var extension;
		if(sef_links) extension="html"; else extension="php";
		location.href=live_site+"/listings."+extension;
	} );
	

}

function deleteFile(caption, id, otype) {

	var url_str="include/actions.php?action=delete_file&object="+otype+"&id="+id+"&field_name="+caption;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function load_gmaps() {

/*	if(window.gmaps_already_loaded) return;

	window.gmaps_already_loaded = 1;*/
//return;
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = 'https://maps.googleapis.com/maps/api/js?callback=gmap_callback';
	if(typeof(places)!="undefined" && places) script.src+="&libraries=places";

	if(typeof(gmak)!="undefined" && gmak!=null ) script.src+='&key='+gmak;
	if(typeof(gmalng)!="undefined" && gmalng!=null ) script.src+='&language='+gmalng;
	if(typeof(gmareg)!="undefined" && gmareg!=null ) script.src+='&region='+gmareg;
	
	document.body.appendChild(script);

}

function gmap_callback () {
	
	if(places) enable_autocomplete();
	if(typeof init_gmap === "function")
		init_gmap();

}

jQuery.expr[':'].icontains = function(a, i, m) {
  return jQuery(a).text().toUpperCase()
      .indexOf(m[3].toUpperCase()) >= 0;
};

function reinit() {
	// for embedded payments
	$("#payment").empty();
	$(".form_footer").show();
}

function onEnable(id,str,obj_type)
{

	if(str) {
		if (myConfirm(str)==false) return;
	}
	var url_str="include/actions.php?action=enable&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function onDisable(id,str,obj_type)
{

	if(str) {
		if (myConfirm(str)==false) return;
	}
	var url_str="include/actions.php?action=favourite&object="+obj_type+"&id="+id;

	$.get( url_str, function( data ) {
		location.reload(true);
	});

}

function makeFavorite(listing_id, live_site) {
	
	if(live_site) var url_str = live_site+"/";
	url_str+="include/actions.php?object=favourite&action=add&id="+listing_id;

	$.ajax({
        url: url_str,
        success: function (result) {
            
        },
        async: false
    });
	
}

function remFavorite(listing_id, live_site) {
	
	if(live_site) var url_str = live_site+"/";
	url_str+="include/actions.php?object=favourite&action=delete&id="+listing_id;

	$.ajax({
        url: url_str,
        success: function (result) {
            
        },
        async: false
    });
	
}

function loadExtScript(url, callback, attributes){

    var script = document.createElement("script")
    script.type = "text/javascript";

    if (script.readyState){  //IE
        script.onreadystatechange = function(){
            if (script.readyState == "loaded" ||
                    script.readyState == "complete"){
                script.onreadystatechange = null;
                callback();
            }
        };
    } else {  //Others
        script.onload = function(){
            callback();
        };
    }

    script.src = url;
	
	for (var key in attributes) {
		script.setAttribute(key, attributes[key]);
	}
	
    document.getElementsByTagName("head")[0].appendChild(script);
}
