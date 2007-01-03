/**
 * This array is used to remember mark status of rows in browse mode
 */
var marked_row = new Array;

var timeoutglobalvar;

 
//modifier la propri�t� display d'un �lement
function setdisplay (objet, statut) {
	var e = objet;
	if(e.style.display != statut){
		e.style.display = statut;
		return true;
	}
}

//tester le type de navigateur
function isIe(){
	var ie = false;	
	var appVer = navigator.appVersion.toLowerCase();
	var iePos = appVer.indexOf('msie');
	if (iePos != -1) {
		var is_minor = parseFloat(appVer.substring(iePos+5,appVer.indexOf(';',iePos)));
		var is_major = parseInt(is_minor);
	}
	if (navigator.appName.substring(0,9) == "Microsoft") { 
		// Check if IE version is 6 or older
		if (is_major <= 6) {
			ie = true;
		}
	}
	return ie;
}

function cleandisplay(id){
	var e = document.getElementById(id);
	if(e){
		setdisplay(e,'block');
		if (isIe()){
			doHideSelect(e);
		}
	}
}

function cleanhide(id){
	var e = document.getElementById(id);
	if(e){
		if(isIe()){
			doShowSelect(e);
		}
		setdisplay(e,'none');
	}
}

//effacer tous les smenu du menu principal
//afficher les selects du document
function hidemenu(idMenu){
	var e = document.getElementById(idMenu);
	var e = e.getElementsByTagName('ul');
	for(var i = 0; i < e.length; i++) {
		if (e[i]) {
			if (isIe()){
				doShowSelect(e[i]);
			}
			setdisplay(e[i],'none');
		}
	}	
}

//masquer le smenu actif par timeout
function afterView(idMenu){
	setdisplay(idMenu,'none');
	if (isIe()) {
		doShowSelect(idMenu);
	}
}

//execute la fonction showSelect
function doShowSelect(objet){
	if (objet) {
		//correction du bugg sur IE
		if(isIe()){
			if(setdisplay(objet,'block')){
				var selx=0; var sely=0; var selp;
				if(objet.offsetParent){
					selp = objet;
					while(selp.offsetParent){
						selp = selp.offsetParent;
					    selx += selp.offsetLeft;
					    sely += selp.offsetTop;
					}
					selx += objet.offsetLeft;
					sely += objet.offsetTop;
					selw = objet.offsetWidth;
					selh = objet.offsetHeight;
					showSelect(selx,sely,selw,selh);
				}
			}
			if(setdisplay(objet,'none')){
				return true;
			}		
		}
	}
}

//affiche les select du document
function showSelect(x,y,w,h){
	var selx,sely,selw,selh;
	var sel = document.getElementsByTagName("SELECT");
	for(var i=0; i<sel.length; i++){
		selx=0; sely=0; var selp;
		if(sel[i].offsetParent){
			selp=sel[i];
			while(selp.offsetParent){
				selp=selp.offsetParent;
				selx+=selp.offsetLeft;
				sely+=selp.offsetTop;
			}
		}
		selx+=sel[i].offsetLeft;
		sely+=sel[i].offsetTop;
		selw=sel[i].offsetWidth;
		selh=sel[i].offsetHeight;
		if(selx+selw>x && selx<x+w && sely+selh>y && sely<y+h)
			sel[i].style.visibility="visible";
	}
	return true;
}

//execute la fonction hideMenu
function doHideSelect(object){
	var e = object;
	if(isIe()){
		var selx=0; var sely=0; var selp;
		if(e.offsetParent){
			selp = e;
			while(selp.offsetParent){
				selp = selp.offsetParent;
				selx += selp.offsetLeft;
				sely += selp.offsetTop;
			}
		}
		
		selx += e.offsetLeft;
		sely += e.offsetTop;
		selw = e.offsetWidth;
		selh = e.offsetHeight;
		hideSelect(selx,sely,selw,selh);
	}
	return true;
}

//masque les select du document
function hideSelect(x,y,w,h){
	var selx,sely,selw,selh,i;
	var sel=document.getElementsByTagName("SELECT");
	for(i=0;i<sel.length;i++){
		selx=0; sely=0; var selp;
		if(sel[i].offsetParent){
			selp=sel[i];
			while(selp.offsetParent){
				selp=selp.offsetParent;
				selx+=selp.offsetLeft;
				sely+=selp.offsetTop;
			}
		}
		selx+=sel[i].offsetLeft;
		sely+=sel[i].offsetTop;
		selw=sel[i].offsetWidth;
		selh=sel[i].offsetHeight;
		if(selx+selw>x && selx<x+w && sely+selh>y && sely<y+h)
			sel[i].style.visibility="hidden";
	}
	return true;
}

function menuAff(id,idMenu){
	var m = document.getElementById(idMenu);
	var item = m.getElementsByTagName('li');
	for(var i=0; i<item.length; i++){
		if(item[i].id == id)
			var ssmenu = item[i];
	}	
	var m = m.getElementsByTagName('ul');
	
	if(isIe()){
		//masquage des �lements select du document
		if(m){
			for (var i=1; i<10 ;i++) { //probl�me dans le listage et le nomage des menus xhtml
				//listage des �l�ments li nomm�s du type smenu + i
				var e = document.getElementById('menu'+i);
				if(e){
					var smenu = e.getElementsByTagName('ul');
					doShowSelect(smenu[0]);
				}
			}
		}		
	}
	
	if (ssmenu) {
		var smenu = ssmenu.getElementsByTagName('ul');
		if (smenu) {
			//masquer tous les smenu ouverts
			for(var i = 0; i < m.length; i++){
				setdisplay(	m[i],'none');
			}
			setdisplay(smenu[0],'block');
			clearTimeout(timeoutglobalvar);
			timeoutglobalvar = setTimeout(function(){afterView(smenu[0])},1000);
			if (isIe()) {
				doHideSelect(smenu[0]);
			}
		}
	}
	
}


function jumpTo(URL_List){ var URL = URL_List.options[URL_List.selectedIndex].value;  window.location.href = URL; }


browserName=navigator.appName;
browserVer=parseInt(navigator.appVersion);
if ((browserName=="Netscape" && browserVer>=3) || (browserName=="Microsoft Internet Explorer" && browserVer>=4)) version="n3";
else version="n2"; 

function historyback() { history.back(); }

function historyforward() { history.forward(); }


function fillidfield(Type,Id){
	window.opener.document.forms["helpdeskform"].elements["computer"].value = Id;
	window.opener.document.forms["helpdeskform"].elements["device_type"].value = Type;
	window.close();
}

/**
 * marks all rows and selects its first checkbox inside the given element
 * the given element is usaly a table or a div containing the table or tables
 * From phpMyAdmin
 *
 * @param    container    DOM element
 */
function markAllRows( container_id ) {
	var rows = document.getElementById(container_id).getElementsByTagName('tr');
	var unique_id;
	var checkbox;

	for ( var i = 0; i < rows.length; i++ ) {

		checkboxes = rows[i].getElementsByTagName( 'input' );

		for ( var j = 0; j < checkboxes.length; j++ ) {
			checkbox=checkboxes[j];
			if ( checkbox && checkbox.type == 'checkbox' ) {
				unique_id = checkbox.name + checkbox.value;
				if ( checkbox.disabled == false ) {
					checkbox.checked = true;
					if ( typeof(marked_row[unique_id]) == 'undefined' || !marked_row[unique_id] ) {
						rows[i].className += ' marked';
						marked_row[unique_id] = true;
					}
				}
			}
		}
	}

	return true;
}


/**
 * marks all rows and selects its first checkbox inside the given element
 * the given element is usaly a table or a div containing the table or tables
 * From phpMyAdmin 
 *
 * @param    container    DOM element
 */
function unMarkAllRows( container_id ) {
	var rows = document.getElementById(container_id).getElementsByTagName('tr');
	var unique_id;
	var checkbox;

	for ( var i = 0; i < rows.length; i++ ) {
		checkboxes = rows[i].getElementsByTagName( 'input' );

		for ( var j = 0; j < checkboxes.length; j++ ) {
			checkbox=checkboxes[j];
			if ( checkbox && checkbox.type == 'checkbox' ) {
				unique_id = checkbox.name + checkbox.value;
				checkbox.checked = false;
				rows[i].className = rows[i].className.replace(' marked', '');
				marked_row[unique_id] = false;
			}
		}
	}
	return true;
}

function confirmAction(text,where){
	if (confirm(text)) {
		window.location = where;
	}
}



