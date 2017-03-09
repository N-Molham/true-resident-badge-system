/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c,d){b(function(){var a=b("#badgeos-achievements-container");if(!(a.length<1)){
// vars
var d="",e=c.body.className,f=e.indexOf("single-job_listing")>-1&&e.indexOf("single")>-1;
// badges with challenges checklist
!function(){if(!1!==f){
// compile checklist template
var c=doT.template(b("#trbs-checklist-template").html()),e=b("#trbs-badges-challenges"),g=[];
// badges click
a.on("click",".badgeos-achievements-challenges-item",function(a){var f=b(a.currentTarget),h=f.data("steps-data");
// reset
g=[];for(var i in h)if(h.hasOwnProperty(i)){
// current step
var j=h[i];
// pass on the ID
j.id=i,"challenges_checklist_listing_id"in j&&j.challenges_checklist_listing_id.toString()===d.toString()&&"challenges_checklist"in j&&!b.isEmptyObject(j.challenges_checklist)&&
// render badge's step checklist
g.push(c(j))}
// output checklist(s)
e.html(g.join(""))})}}(),
// badges filter
function(){var a=b("#achievements_list_filter");if(a.length)for(option_value in trbs_badges.filter_labels)trbs_badges.filter_labels.hasOwnProperty(option_value)&&a.find('option[value="'+option_value+'"]').text(trbs_badges.filter_labels[option_value])}(),
// badges popover init
function(){b(".badgeos-achievements-list-item").livequery(function(a,c){b(c).webuiPopover()})}(),
// badges for current open listing
function(){if(!1!==f){var a=e.match(/postid-\d+/i);null!==a&&(
// loaded listing ID in the current page
d=parseInt(a[0].replace("postid-","")),isNaN(d)||b.ajaxSetup({data:{trbs_listing_id:d}}))}}()}})}(window,jQuery,document);