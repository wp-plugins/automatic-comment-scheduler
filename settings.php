<?php
/**
 Automatic Comment Scheduler
 @author: Ramon Fincken
 License: GPL2
 Based on: Automatic Post Scheduler by Tudor Sandu : http://tudorsandu.ro/* 
 * @see plugin.php :: Automatic Comment Scheduler
 */
if (!defined('ABSPATH')) die("Aren't you supposed to come here via WP-Admin?");

function plugin_automatic_comment_settings_interval() {
	$int = automatic_comment_scheduler::get_interval();
	$min = intval( $int['min'] / 60 );
	$max = intval( $int['max'] / 60 );
	$notify = $int['notify'];

	$min_unit = $max_unit = 'min';

	if( intval( $min / 60 ) ) {
		$min_unit = 'hour';
		$min = intval( $min / 60 );
		if( intval( $min / 24 ) ) {
			$min_unit = 'day';
			$min = intval( $min / 24 );
		}
	}

	if( intval( $max / 60 ) ) {
		$max_unit = 'hour';
		$max = intval( $max / 60 );
		if( intval( $max / 24 ) ) {
			$max_unit = 'day';
			$max = intval( $max / 24 );
		}
	}

	?>
	<a name="plugin_automatic_comment_settings"></a>
<input
	type="text" id="plugin_automatic_comment_settings_interval_min"
	class="small-text"
	name="plugin_automatic_comment_settings_interval[min]"
	value="<?php echo $min; ?>" />
	<?php automatic_comment_scheduler::plugin_automatic_comment_aps_select_unit( 'plugin_automatic_comment_settings_interval_min_unit', $min_unit, 'plugin_automatic_comment_settings_interval[min_unit]' ); ?>
<label for="plugin_automatic_comment_settings_interval_min"><?php _e( 'minimum', 'plugin_automatic_comment_scheduler' ); ?></label>

<br />
<input
	type="text" id="plugin_automatic_comment_settings_interval_max"
	class="small-text"
	name="plugin_automatic_comment_settings_interval[max]"
	value="<?php echo $max; ?>" />
	<?php automatic_comment_scheduler::plugin_automatic_comment_aps_select_unit( 'plugin_automatic_comment_settings_interval_max_unit', $max_unit, 'plugin_automatic_comment_settings_interval[max_unit]' ); ?>
<label for="plugin_automatic_comment_settings_interval_max"><?php _e( 'maximum', 'plugin_automatic_comment_scheduler' ); ?></label>
<br />
<span class="description"><?php _e( 'These values define the random interval limits for the <strong>Automatic Comment Scheduler</strong> plugin.', 'plugin_automatic_comment_scheduler' ); ?></span>

<br />
<input
	type="radio" id="plugin_automatic_comment_settings_notify_no"
	class="small-text"
	name="plugin_automatic_comment_settings_interval[notify]"
	value="0" <?php if(!$notify) {echo ' checked="checked"';} ?> />
	<label for="plugin_automatic_comment_settings_notify_no"><?php _e( 'No'); ?></label>
<input
	type="radio" id="plugin_automatic_comment_settings_notify_yes"
	class="small-text"
	name="plugin_automatic_comment_settings_interval[notify]"
	value="1" <?php if($notify) {echo ' checked="checked"';} ?> />
	
<label for="plugin_automatic_comment_settings_notify_yes"><?php _e( 'Yes'); ?></label>
<br />
<span class="description"><?php _e( 'Sent email with details to site admin if a comment is approved?', 'plugin_automatic_comment_scheduler' ); ?></span>
	<?php
}


function plugin_automatic_comment_scheduler_admin_init() {
	add_settings_field( 'plugin_automatic_comment_settings_interval', __( 'Interval and notification for automatic approval', 'plugin_automatic_comment_scheduler' ), 'plugin_automatic_comment_settings_interval', 'discussion' );
	register_setting( 'discussion', 'plugin_automatic_comment_settings_interval', array('automatic_comment_scheduler','sanitize'));
}
add_action( 'admin_init', 'plugin_automatic_comment_scheduler_admin_init' );
?>