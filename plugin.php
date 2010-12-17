<?php
/**
 Plugin Name: Automatic Comment Scheduler
 Plugin URI: http://www.mijnpress.nl
 Description: A plugin that automatically schedules pending comments for approval, depending on a min/max threshold and the last comment's publish date and time.
 Version: 1.0.1
 Author: Ramon Fincken
 Author URI: http://www.mijnpress.nl
 License: GPL2
 Based on: Automatic Post Scheduler by Tudor Sandu : http://tudorsandu.ro/
 */

if (!defined('ABSPATH')) die("Aren't you supposed to come here via WP-Admin?");

if(!class_exists('mijnpress_plugin_framework'))
{
	include('mijnpress_plugin_framework.php');
}

/**
 * @see File description
 * @author Ramon Fincken
 *
 */
class automatic_comment_scheduler extends mijnpress_plugin_framework
{

	function __construct()
	{
		$this->plugin_title = 'Automatic comment scheduler';
		$this->plugin_class = 'automatic_comment_scheduler';
		$this->plugin_filename = 'automatic-comment-scheduler/plugin.php';
		$this->plugin_config_url = 'options-discussion.php#plugin_automatic_comment_settings';

		define('PLUGIN_ACS_COMMENT_UNAPPROVED_STATUS',0);
		define('PLUGIN_ACS_COMMENT_APPROVED_STATUS',1);

		define('PLUGIN_ACS_COMMENT_SET_APPROVE_STATUS','approve');
			
		$comment_id = $this->has_unapproved();
		if($comment_id)
		{
			$approved_time = $this->calculate_next_time();
			$this->check_for_approval($comment_id, $approved_time);
		}
	}

	/**
	 * Gets interval settings
	 */
	public function get_interval()
	{
		$default = array( 'min' => 900, 'max' => 1800, 'notify' => 1 );
		$interval = get_option( 'plugin_automatic_comment_settings_interval', $default );
		if ( !is_array( $interval ) )
		{
			$interval = $default;
		}
		if( ! isset( $interval['min'] ) || ! is_numeric( $interval['min'] ) )
		{
			$interval['min'] = $default['min'];
		}
		if( ! isset( $interval['max'] ) || ! is_numeric( $interval['max'] ) )
		{
			$interval['max'] = $interval['min'];
		}
		if( $interval['min'] > $interval['max'] )
		{
			$interval['max'] = $interval['min'];
		}

		return $interval;
	}

	/**
	 * Checks and gets the last comment_ID from the comment that was approved most recent
	 */
	private function has_unapproved()
	{
		global $wpdb;
		$sql = 'SELECT comment_ID FROM '.$wpdb->comments.' WHERE comment_approved = \''.PLUGIN_ACS_COMMENT_UNAPPROVED_STATUS.'\' ORDER BY comment_ID ASC LIMIT 1';
		$rows = $wpdb->get_results($sql);
		foreach ($rows AS $row) {
			return $row->comment_ID;
		}
		return false;
	}

	private function calculate_next_time()
	{
		return strtotime( $this->get_time_last_approved() ) + $this->get_random_interval();
	}

	/**
	 * Retrieves the comment_date from the last approved comment
	 */
	private function get_time_last_approved()
	{
		global $wpdb;
		$sql = 'SELECT MAX(comment_date) AS max_comment_date FROM '.$wpdb->comments.' WHERE comment_approved = \''.PLUGIN_ACS_COMMENT_APPROVED_STATUS.'\' LIMIT 1';

		$rows = $wpdb->get_results($sql);
		foreach ($rows AS $row) {
			return $row->max_comment_date;
		}

		// Should not come here by check of has_unapproved()
		die( __('No approved comments found, approve a comment first!') );
	}

	/**
	 * Generates a random interval based on a min and max interval rand generator
	 * @return	int
	 */
	private function get_random_interval()
	{
		$interval = $this->get_interval();
		return rand( $interval['min'], $interval['max'] );
	}

	/**
	 * Compares time of comment with current time and approves comment if needed
	 * @param int $comment_id
	 * @param int $approved_time
	 */
	private function check_for_approval($comment_id, $approved_time)
	{
		global $wpdb;
		// Approve?
		if ( $approved_time < current_time( 'timestamp' ) )
		{
			$this->approve_comment($comment_id);
			
			$interval = $this->get_interval();
			if($interval['notify'])
			{
				// Some lines of code from pluggable.php :: function wp_notify_moderator($comment_id)
				$comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_ID=%d LIMIT 1", $comment_id));
				$post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d LIMIT 1", $comment->comment_post_ID));
	
				$notify_message = 'A comment has been auto approved by the plugin "Automatic comment scheduler"'. "\r\n";
	
				$notify_message  .= sprintf( __('This comment was posted on the post #%1$s "%2$s"'), $post->ID, $post->post_title ) . "\r\n";
				$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
				$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
				$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
				$notify_message .= sprintf( __('Whois  : http://ws.arin.net/cgi-bin/whois.pl?queryinput=%s'), $comment->comment_author_IP ) . "\r\n";
				$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
					
				$notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=cdc&c=$comment_id") ) . "\r\n";
				$notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=cdc&dt=spam&c=$comment_id") ) . "\r\n\r\n";;
				
				$notify_message .= sprintf( __('Edit it: %s'), admin_url("comment.php?action=editcomment&c=$comment_id") ) . "\r\n";
				
				$admin_email = get_option('admin_email');
				@wp_mail($admin_email, '[Auto approve comment]', $notify_message);
			}
		}
	}

	/**
	 * Actually approves a comment
	 * @return	void
	 */
	private function approve_comment($id)
	{
		global $wpdb;

		// Perform a status change, this will activate any hooks
		wp_set_comment_status($id, PLUGIN_ACS_COMMENT_SET_APPROVE_STATUS);

		// Now update the comment's date, otherwise the comments will be directly after eachother
		$sql = 'UPDATE '.$wpdb->comments.' SET comment_date = \''.current_time( 'mysql' ).'\' WHERE comment_ID = \''.$id.'\' LIMIT 1';
		$wpdb->get_results($sql);
	}


	public function sanitize($data)
	{
		$factors = array(
			'min' => 60,
			'hour' => 60*60,
			'day' => 60*60*24,
		);

		$data['min'] *= $factors[$data['min_unit']];
		$data['max'] *= $factors[$data['max_unit']];

		$data['notify'] = intval($data['notify']);
		
		unset( $data['min_unit'], $data['max_unit'] );

		return $data;
	}

	public function plugin_automatic_comment_aps_select_unit( $id = '', $value = null, $name = '' ) {
		$options = array(
				'min' => __( 'Minutes' ),
				'hour' => __( 'Hours' ),
				'day' => __( 'Days' )
		);
		if( empty($name) )
		$name = $id;
		?>
	<select id="<?php echo $id; ?>" name="<?php echo $name; ?>">
	<?php foreach( $options as $key => $v ) : ?>
		<option value="<?php echo esc_attr( $key ); ?>"
		<?php echo ($value === $key) ? 'selected="selected"' : ''; ?>><?php echo esc_attr( $v ); ?></option>
		<?php endforeach; ?>
	</select>
		<?php
	}	
	
		
	function admin_discussion_settings_interval() {
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
		

	/**
	 * Additional links on the plugin page
	 */
	function addPluginContent($links, $file) {
		$plugin = new automatic_comment_scheduler();
		$links = parent::addPluginContent($plugin->plugin_filename,$links,$file,$plugin->plugin_config_url);
		return $links;
	}
}

/**
 * Inits schedule after a comment has been submit of as background in admin_header
 * @param unknown_type $status
 */
function plugin_automatic_comment_scheduler($status = array())
{
	$automatic_comment_scheduler = new automatic_comment_scheduler();
}

// To trigger check
add_filter('pre_comment_approved', 'plugin_automatic_comment_scheduler', 0);
add_action( 'admin_menu', 'plugin_automatic_comment_scheduler' );

if(mijnpress_plugin_framework::is_admin())
{
	add_filter('plugin_row_meta',array('automatic_comment_scheduler', 'addPluginContent'), 10, 2);
	require_once( 'settings.php' );
}
?>
