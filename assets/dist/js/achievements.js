/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c,d){b(function(){/**
		 * Check if there is logged in user or not
		 *
		 * @return {boolean}
		 */
function d(){return Boolean(listify_child_overlays&&"is_user_logged_in"in listify_child_overlays?listify_child_overlays.is_user_logged_in:trbs_badges.is_logged_in)}var e=b("#badgeos-achievements-container");if(!(e.length<1)){
// vars
var f=b(c.body),g="",h=c.body.className,i=h.indexOf("single-job_listing")>-1&&h.indexOf("single")>-1;
// badges with challenges checklist
!function(){if(!1!==i){
// compile checklist template
var c=doT.template(b("#trbs-checklist-template").html()),h=b("#trbs-badges-challenges"),j=[];
// when ajax login is successful
f.on("tr-login-register-ajax-success",function(){
// force reload the page
a.location.reload(!0)}),
// challenges checklist item checked/unchecked
h.on("change",".trbs-checklist-item input[type=checkbox]",function(a){var c=b(a.currentTarget);b.post(listifySettings.ajaxurl,b.extend({},b(this).data(),{point:a.currentTarget.value,checked:a.currentTarget.checked,nonce:trbs_badges.checklist_nonce,action:"challenges_checklist_update"}),function(a){!1===a.success&&c.prop("checked",!c.prop("checked"))},"json")}),
// badges click
e.on("click",".badgeos-achievements-challenges-item",function(a){var e=b(a.currentTarget),f=e.data("steps-data"),i=e.data("id");
// reset
j=[];for(var k in f)if(f.hasOwnProperty(k)){
// current step
var l=b.extend({},f[k],{step_id:k,badge_id:i});"challenges_checklist_listing_id"in l&&l.challenges_checklist_listing_id.toString()===g.toString()&&"challenges_checklist"in l&&!b.isEmptyObject(l.challenges_checklist)&&
// render badge's step checklist
j.push(c(l))}
// user needs to login first
// output checklist(s)
return j.length<1||(!1===d()?(b("#secondary-nav-menu").find(".overlay-login").trigger("tr-click"),!0):void h.html(j.join("")))})}}(),
// badges filter
function(){var a=b("#achievements_list_filter");if(a.length)for(option_value in trbs_badges.filter_labels)trbs_badges.filter_labels.hasOwnProperty(option_value)&&a.find('option[value="'+option_value+'"]').text(trbs_badges.filter_labels[option_value])}(),
// badges popover init
function(){b(".badgeos-achievements-list-item").livequery(function(a,c){b(c).webuiPopover()})}(),
// badges for current open listing
function(){if(!1!==i){var a=h.match(/postid-\d+/i);null!==a&&(
// loaded listing ID in the current page
g=parseInt(a[0].replace("postid-","")),isNaN(g)||b.ajaxSetup({data:{trbs_listing_id:g}}))}}()}})}(window,jQuery,document);