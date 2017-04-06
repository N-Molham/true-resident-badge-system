/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c,d){b(function(){/**
		 * Check if there is logged in user or not
		 *
		 * @return {boolean}
		 */
function d(){return Boolean(listify_child_overlays&&"is_user_logged_in"in listify_child_overlays?listify_child_overlays.is_user_logged_in:trbs_badges.is_logged_in)}/**
		 * Get Query string in url
		 *
		 * @param {String} name
		 * @param {String} url
		 * @return {String}
		 */
function e(a,b){b||(b=window.location.href),a=a.replace(/[\[\]]/g,"\\$&");var c=new RegExp("[?&]"+a+"(=([^&#]*)|&|#|$)"),d=c.exec(b);return d?d[2]?decodeURIComponent(d[2].replace(/\+/g," ")):"":null}var f=b("#badgeos-achievements-container");if(!(f.length<1)){
// vars
var g=b(c.body),h="",i=c.body.className,j=i.indexOf("single-job_listing")>-1&&i.indexOf("single")>-1;
// badges with challenges checklist
!function(){if(!1!==j){
// compile checklist template
var c=doT.template(b("#trbs-checklist-template").html()),e=b("#trbs-badges-challenges"),i=[],k=0;
// when ajax login is successful
g.on("tr-login-register-ajax-success",function(){
// force reload the page
a.location.reload(!0)}),
// challenges checklist item checked/unchecked
e.on("change tr-change",".trbs-checklist-item input[type=checkbox]",function(a){var c=b(a.currentTarget),e=c.data();
// user needs to login first
// user needs to login first
// toggle back
// new request
return!1===d()?(b("#secondary-nav-menu").find(".overlay-login").trigger("tr-click"),c.prop("checked",!c.prop("checked")),!0):(k++,void b.post(listifySettings.ajaxurl,b.extend({},e,{point:a.currentTarget.value,checked:a.currentTarget.checked,nonce:trbs_badges.checklist_nonce,action:"challenges_checklist_update"}),function(a,c){return function(d){if(!1===d.success)
// toggle back
a.prop("checked",!a.prop("checked"));else{
// linked badge
var e=b("#badgeos-achievements-list-item-"+c).attr("data-completed",d.data);
// validate earning only if it's the last ajax request
if(k>1)return;
// query all checklist inputs
var f=a.closest(".trbs-checklist").find(".trbs-checklist-item input[type=checkbox]"),
// filter only checked ones
g=f.filter(":checked");
// not all points are checked
if(f.length!==g.length)return;
// toggle earn status
e.removeClass("user-has-not-earned").addClass("user-has-earned"),
// reset checked points
f.prop("checked",!1)}}}(c,e.badge),"json").always(function(){
// request done
k--,k<0&&(k=0)}))}),
// badges click
f.on("click tr-click",".badgeos-achievements-challenges-item",function(a){var d=b(a.currentTarget),f=d.data("steps-data"),g=d.data("id");
// reset
i=[];for(var j in f)if(f.hasOwnProperty(j)){
// current step
var k=b.extend({},f[j],{step_id:j,badge_id:g});"challenges_checklist_listing_id"in k&&k.challenges_checklist_listing_id.toString()===h.toString()&&"challenges_checklist"in k&&!b.isEmptyObject(k.challenges_checklist)&&
// render badge's step checklist
i.push(c(k))}
// output checklist(s)
return i.length<1||void e.html(i.join(""))})}}(),
// badges filter
function(){var a=b("#achievements_list_filter");if(a.length)for(option_value in trbs_badges.filter_labels)trbs_badges.filter_labels.hasOwnProperty(option_value)&&a.find('option[value="'+option_value+'"]').text(trbs_badges.filter_labels[option_value])}(),
// badges popover init
function(){b(".badgeos-achievements-list-item").livequery(function(a,c){b(c).webuiPopover({onShow:function(a){
// related badge
var c=b("#badgeos-achievements-container").find('.badgeos-achievements-list-item[data-target="'+a.attr("id")+'"]'),d=Math.abs(c.attr("data-completed"));
// 100% max
d=d>100?100:d,
// bar width
a.find(".badgeos-percentage-bar").css("width",d+"%"),
// bar text
a.find(".badgeos-percentage-number").html(d+"&percnt;")}})})}(),
// badges for current open listing
function(){if(!1!==j){var a=i.match(/postid-\d+/i);null!==a&&(
// loaded listing ID in the current page
h=parseInt(a[0].replace("postid-","")),isNaN(h)||(b.ajaxSetup({data:{trbs_listing_id:h}}),b(c).ajaxSuccess(function(a,b,c){"get-achievements"===e("action",c.url)&&"badges"===e("type",c.url)&&
// trigger first badge click
setTimeout(function(){f.find(".badgeos-achievements-challenges-item:first").trigger("tr-click")},100)})))}}()}})}(window,jQuery,document);