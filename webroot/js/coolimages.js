var color = new Array();
color[0] = '#000000';
color[1] = '#FFFFFF';
function changeColor(val){
document.body.style.background = color[val];
if(val==1) document.body.style.color = color[0];
else document.body.style.color = color[1];
createCookie('bg',val,365);
}
function createCookie(name,value,days) {
if (days) {
var date = new Date();
date.setTime(date.getTime()+(days*86400000));
var expires = "; expires="+date.toGMTString();
}
else var expires = "";
document.cookie = name+"="+value+expires+"; path=/";
}
function readCookie(name) {
var nameEQ = name + "=";
var ca = document.cookie.split(';');
for(var i=0;i < ca.length;i++) {
var c = ca[i];
while (c.charAt(0)==' ') c = c.substring(1,c.length);
if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
}
return null;
}
