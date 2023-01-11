function loadSub(rootid) {
	var subcatstring = document.upload.subcategoryid;
	subcatstring.options.length = 0;
	subcatstring.options[0] = new Option('--Select a subcategory--',0);
	document.getElementById("subcategoryid").selected = 0;
	if (subcat[rootid]) {
		for (var cat in subcat[rootid]) {
			var opt = new Option( subcat[rootid][cat].ctitle, subcat[rootid][cat].id);
			subcatstring.options.add(opt);
		}
		document.getElementById("subcategoryid").disabled = false;
	} else {
		document.getElementById("subcategoryid").disabled = true;
	}
	return true;
}


function vForms () {
	var required ={categoryid:"Select a category", title:"Enter a title", uploadedfile:"Select a file to upload"};
	var uploadform=document.upload;
	var selector;
	var er = "";
	for (x in required){
		selector=upload.elements[x];
		if (selector.options && (selector.options[selector.selectedIndex].value.length == 0)) {
			er += "\t" + required[x] + "\n";
		} else if (selector.type =="text" && selector.value.length == 0 ){
			er += "\t" + required[x] + "\n";
		} else if (selector.type =="file" && selector.value.length == 0 ){
			er += "\t" + required[x] + "\n";
		} 
	}
	if ( er != "") {
		alert("Please complete the following fields to continue:\n" + er);
		return false;
	} else return true;
}

function uploadFile(o) {
	var cntr = "controllers/upload.php?callback=?";
	$.post( cntr, o, function(reply){
		alert("file upload completed");
	});
}

$(function(){
	var $next = $('<div>').addClass("next");
	var $prev = $('<div>').addClass("prev");
	var steps = $('.step').length - 1;
	$('#intro').slideDown();
	$('.step').prepend(function(i){
		return $("<div>").addClass('num').html('<h1>'+(i+1)+'</h1>');
	}).each(function(i){
		if (i < steps) {
			$(this).append($next.clone());
		}
		if (i > 0 ) {
			$(this).append($prev.clone());
		}
	}).hide('fast',function() {$('.step').first().show();});
	$('.step>.prev').click(function(){
		$(this).parent().fadeOut('fast',function(){
			$(this).prev().fadeIn('fast');
		});
	});
	$('.step>.next').click(function(){
		$(this).parent().fadeOut('fast',function(){
			$(this).next().fadeIn('fast');
		});
	});
	$('.step>.next:first').click(function(){$('#intro').slideUp()});
	$('#orig_broadcast_date').datepicker({dateFormat:'yy-mm-dd'});
});

function showEmail() {

}

function loadSubs(rootid) {
	$('#subcategoryid').html($('<option>').attr({selected:true, value:0}).html('--Select a subcategory--'));
	if (subcat[rootid]) {
		//var $subcatstring = new String;
		$('#subcategoryid').html($('<option>').attr({selected:true, value:0}).html('--Select a subcategory--'));
		for (var cat in subcat[rootid]) {
			$('#subcategoryid').append($('<option>').attr('value',subcat[rootid][cat].id).html(subcat[rootid][cat].ctitle));
		}
		$("#subcategoryid").attr('disabled',false);
		return true;
	} else {
		$("#subcategoryid").attr('disabled',true);
		return false;
	}
}
	
function verify(f) {
	$.post('/controllers/authenticate.php',{
		username:f.username.value,
		password:f.password.value
	}, function(data){
		if(data.code == "error") {alert(data.message);return false;} else f.reset();f.submit();
	},'json');
}