=== Automatic Comment Scheduler ===
Contributors: Ramon Fincken
Donate link: http://donate.ramonfincken.com
Tags: automatic, auto, schedule, approve, comment, comments, pending, notify, notification
Requires at least: 2.0.2
Tested up to: 4.2.1
Stable tag: 1.6.1

A plugin that automatically schedules pending comments for approval, depending on a min/max threshold and the last comment's publish date and time.

== Description ==

A plugin that automatically schedules pending comments for approval, depending on a min/max threshold and the last comment's publish date and time.
<br>When a comment is posted or when the admin is logged in, the plugin computes the most recent interval when a comment can be auto approved and picks a timestamp in that interval when to approve the pending comment.
<br>Optional: Notify the site admin by email when a comment is approved.
<br>Warning All pending comments will be scheduled, make sure you have an antispam plugin in place. <a href="http://wordpress.org/extend/plugins/antispam-for-all-fields/">I wrote one over here</a>.
<br>Based on: <a href="http://wordpress.org/extend/plugins/automatic-post-scheduler/">Automatic Post Scheduler</a> by <a href="http://profiles.wordpress.org/users/tetele/">Tetele</a>
<br>
<br>Coding by: <a href="http://www.mijnpress.nl">MijnPress.nl</a> <a href="http://twitter.com/#!/ramonfincken">Twitter profile</a> <a href="http://wordpress.org/extend/plugins/profile/ramon-fincken">More plugins</a>

 
== Installation ==

1. Upload `automatic-comment-scheduler` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to *Settings* > *Discussion* and choose min/max interval between posts and configure email (yes/no) notification

== Frequently Asked Questions ==

= How do I select the minimun and maximum interval boundaries? =
Go to *Settings* > *Discussion* in your WP admin.

== Changelog ==
= 1.6.1 =
Changed: IP whois url

= 1.6 =
Bugfix: Failed to return status upon default call.
Added: More calls to make sure that comments are scheduled

= 1.5 =
Bugfix: Framework did not work on multisite, is_admin() problem.<br>If anyone could help me with that ? :)

= 1.0 =
First release


== Screenshots ==

1. Settings
<a href="http://s.wordpress.org/extend/plugins/automatic-comment-scheduler/screenshot-1.png">Fullscreen Screenshot 1</a><br>

2. Notification email
<a href="http://s.wordpress.org/extend/plugins/automatic-comment-scheduler/screenshot-2.png">Fullscreen Screenshot 2</a><br>
