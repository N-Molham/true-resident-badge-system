<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 09-Mar-17
 * Time: 7:08 PM
 */
?>
<div id="trbs-badges-challenges"></div>

<script id="trbs-checklist-template" type="text/template">
	<div id="trbs-checklist-{{=it.step_id}}" class="trbs-checklist-container">
		<h2 class="widget-title">{{=it.title}}</h2>
		<ul class="trbs-checklist" data-badge="{{=it.badge_id}}" data-step="{{=it.step_id}}">
			{{ for ( var point_index in it.challenges_checklist ) { }}
			<li class="trbs-checklist-item">
				<label>
					<input type="checkbox" {{? it.challenges_checklist_marks[point_index] }} checked="checked" {{?}} value="{{=point_index}}" data-badge="{{=it.badge_id}}" data-step="{{=it.step_id}}" />
					<span><span class="trbs-text">{{=it.challenges_checklist[point_index]}}</span></span>
				</label>
			</li>
			{{ } }}
		</ul>

		<a href="<?php echo admin_url( 'admin-ajax.php' ); ?>?action=activity_suggestion_form&badge_id={{=it.badge_id}}" class="button button-secondary trbs-suggestion-button"><?php _e( 'Make a suggestion', TRBS_DOMAIN ); ?></a>
	</div>
</script>