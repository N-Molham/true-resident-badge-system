/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c,d){b(function(){var a=b("#badgeos-achievements-container");if(!(a.length<1)){var d=b("#achievements_list_filter");if(d.length)for(option_value in trbs_badges.filter_labels)trbs_badges.filter_labels.hasOwnProperty(option_value)&&d.find('option[value="'+option_value+'"]').text(trbs_badges.filter_labels[option_value]);
// popover init
b(".badgeos-achievements-list-item").livequery(function(a,c){b(c).webuiPopover()});var e=c.body.className;if(!(e.indexOf("single")<0||e.indexOf("single-job_listing")<0)){var f=e.match(/postid-\d+/i);if(null!==f){
// loaded listing ID in the current page
var g=parseInt(f[0].replace("postid-",""));isNaN(g)||b.ajaxSetup({data:{trbs_listing_id:g}})}}}})}(window,jQuery,document);