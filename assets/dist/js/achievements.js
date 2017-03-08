/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c,d){b(function(){var a=b("#badgeos-achievements-container");a.length<1||(
// badges with challenges checklist
!function(){
// badges click
a.on("click",".badgeos-achievements-challenges-item",function(a){var c=b(a.currentTarget),d=c.data("steps-data");console.log(d)})}(),
// badges filter
function(){var a=b("#achievements_list_filter");if(a.length)for(option_value in trbs_badges.filter_labels)trbs_badges.filter_labels.hasOwnProperty(option_value)&&a.find('option[value="'+option_value+'"]').text(trbs_badges.filter_labels[option_value])}(),
// badges popover init
function(){b(".badgeos-achievements-list-item").livequery(function(a,c){b(c).webuiPopover()})}(),
// badges for current open listing
function(){var a=c.body.className;if(!(a.indexOf("single")<0||a.indexOf("single-job_listing")<0)){var d=a.match(/postid-\d+/i);if(null!==d){
// loaded listing ID in the current page
var e=parseInt(d[0].replace("postid-",""));isNaN(e)||b.ajaxSetup({data:{trbs_listing_id:e}})}}}())})}(window,jQuery,document);