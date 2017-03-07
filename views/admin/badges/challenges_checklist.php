<?php
/**
 * Manage badge challenges checklist
 *
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 07-Mar-17
 * Time: 3:57 PM
 */
use True_Resident\Badge_System\Helpers;

// load assets path
$enqueue_path = Helpers::enqueue_path() . '%s?ver=' . Helpers::assets_version();

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- Bootstrap core CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
	      integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

	<!-- Admin CSS -->
	<link rel="stylesheet" href="<?php printf( $enqueue_path, 'css/admin.css' ); ?>" crossorigin="anonymous">

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->    <!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>    <![endif]-->
</head>
<body>

<div class="container">
	<form id="checklist-form" action="" method="post">
		<div class="panel panel-primary panel-checklist">
			<!-- Default panel contents -->
			<div class="panel-heading"><?php _e( 'Challenges Checklist', TRBS_DOMAIN ); ?></div>
			<div class="panel-body">
				<!-- List -->
				<ul class="checklist-repeatable list-unstyled" data-empty-list-message="item" data-add-button-class="btn btn-default"
				    data-confirm-remove="yes" data-add-button-label="<?php esc_attr_e( 'Add New', TRBS_DOMAIN ); ?>"
				    data-confirm-remove-message="<?php esc_attr_e( 'Are you sure?', TRBS_DOMAIN ); ?>"
				    data-values="<?php echo esc_attr( json_encode( $step_data['challenges_checklist'] ) ); ?>">
					<li data-template="yes" class="list-item">
						<div class="row">
							<p class="col-md-10 col-sm-9">
								<input type="text" name="checklist_points[{index}]" placeholder="<?php esc_attr_e( 'Challenge Label', TRBS_DOMAIN ); ?>" class="form-control" value="{value}" />
							</p>
							<p class="col-md-2 col-sm-3">
								<a href="#" class="btn btn-default btn-danger btn-block" data-remove="yes"><?php esc_attr_e( 'Remove', TRBS_DOMAIN ); ?></a>
							</p>
						</div>
					</li>
				</ul>
			</div><!-- .panel-body -->
			<div class="panel-footer">
				<input type="submit" name="submit" value="<?php esc_attr_e( 'Save Changes', TRBS_DOMAIN ); ?>" class="btn btn-primary" />

				<input type="hidden" name="trbs_action" value="save_checklist" />
				<?php wp_nonce_field( 'trbs_save_challenges_checklist' ); ?>
				<input type="hidden" name="checklist_step" value="<?php echo esc_attr( $step_id ); ?>" />
				<input type="hidden" name="checklist_badge" value="<?php echo esc_attr( $badge_id ); ?>" />
			</div><!-- .panel-footer -->
		</div>
	</form>
</div><!-- .container -->

<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<?php wp_scripts()->print_scripts( 'jquery' ); ?>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
        integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script src="<?php printf( $enqueue_path, 'js/doT.js' ); ?>"></script>
<script src="<?php printf( $enqueue_path, 'js/jquery.repeatable.item.js' ); ?>"></script>
<script src="<?php printf( $enqueue_path, 'js/admin.js' ); ?>"></script>
</html>
