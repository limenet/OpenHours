<?php
/*
Plugin Name: Open Hours
Plugin URI: http://linusmetzler.me
Description: Open Hours, Based on Biz Hours by http://francisaltomare.com/
Version: 1.3.1
Author: Linus Metzler
Plugin URI: https://github.com/limenet/open-hours
License: GPLv2 or later
 */
/*  Copyright 2012  Linus Metzler  (email : limenet.ch@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

add_shortcode('open_hours', 'echo_hours');
function openhours_load_textdomain()
{
    load_plugin_textdomain('open-hours', false, __DIR__ . '/languages');
}
add_action('plugins_loaded', 'openhours_load_textdomain');
function echo_hours()
{
    date_default_timezone_set(get_option('timezone_string'));
    $output = '';

    $days = [1 => __('Monday', 'open-hours'), 2 => __('Tuesday', 'open-hours'), 3 => __('Wednesday', 'open-hours'), 4 => __('Thursday', 'open-hours'), 5 => __('Friday', 'open-hours'), 6 => __('Saturday', 'open-hours'), 7 => __('Sunday', 'open-hours')];
    $override_status = get_option('open_hours_override_status');
    foreach ($days as $index => $day) {
        $data[$index] = get_option('open_hours_' . $index);
    }
    $today = date('N');
    if (!empty($data[$today])) {
        $parts = explode(' ' . get_option('open_hours_multiple_time_delimiter') . ' ', $data[$today]);
        foreach ($parts as $key => $part) {
            $times = explode(' ' . get_option('open_hours_time_delimiter') . ' ', $part);
            $open = explode(':', $times[0]);
            $close = explode(':', $times[1]);
            if ((int) $open[0] < (int) date('G')) {
                $openNow = true;
            }
            if ((int) $open[0] == (int) date('G')) {
                if ((int) $open[1] <= (int) date('i')) {
                    $openNow = true;
                }
            }
            if ((int) $close[0] < (int) date('G')) {
                $openNow = false;
            }
            if ((int) $close[0] == (int) date('G')) {
                if ((int) $close[1] <= (int) date('i')) {
                    $openNow = false;
                }
            }
            if ($openNow) {
                $label = '<span class="label label-success">' . __('open', 'open-hours') . '</span>';
                break;
            }
            unset($times);
            unset($open);
            unset($close);
            unset($openNow);
        }
    }
    if (!isset($label)) {
        if ($override_status == '1') {
            $override = true;
            $label = '<span class="label label-success">' . __('open', 'open-hours') . '</span>';
        } else {
            $label = '<span class="label label-important">' . __('closed', 'open-hours') . '</span>';
        }
    }

    $output .= '<div>';

    $vac_start = get_option('open_hours_vac_start');
    $vac_end = get_option('open_hours_vac_end');
    $vac_reason = get_option('open_hours_vac_reason');
    if (get_option('open_hours_show_status_in_shortcode') === 'yes') {
        if (!empty($vac_start) && !empty($vac_end)) {
            $output .= '<hr>';
            $output .= '<h6><span class="label label-warning">' . __('Holidays', 'open-hours') . '</span></h6>';
            $output .= '<p>' . sprintf(__('We\'re on holidays from %1$s until %2$s because %3$s.', 'open-hours'), $vac_start, $vac_end, $vac_reason) . '</p>';
            $output .= '<p>' . sprintf(__('After %1$s we\'re happy to welcome you during the following hours:', 'open-hours'), $vac_end) . '</p>';
        } else {
            $output .= '<p>' . sprintf(__('At the moment we are %s', 'open-hours'), $label) . '';
            $output .= $override ? __(', though normally we\'re only open during these hours:', 'open-hours') : '.';
            $ouput .= '</p>';
        }
    }

    foreach ($data as $index => $day) {
        if (strlen($day) > 0) {
            $output .= '<div class="row"><div class="span1">';
            $output .= $days[$index] . '';
            $output .= '</div>';
            $output .= '<div class="span3">';
            $output .= $day;
            $output .= '</div></div>';
        }
    }
    $output .= '<hr>';

    $range = get_option('open_hours_range');
    $int = strlen($range);
    if ($int > 0) {
        $output .= '<div class="row"><div class="span2">';
        $output .= $range;
        $output .= '</div>';
        $output .= '<div class="span2">';
        $output .= get_option('open_hours_range_hours');
        $output .= '</div></div>';
    }

    $output .= '</div>';

    return $output;
}

if (is_admin()) {

/* Call the html code */
    add_action('admin_menu', 'hello_world_admin_menu');

    function hello_world_admin_menu()
    {
        add_options_page('Open Hours', 'Open Hours', 'administrator', 'open-hours', 'open_hours_html_page');
    }
}
function open_hours_html_page()
{
    ?>

<div id="openhoursettings">
<h2><?php _e('Settings', 'open-hours')?></h2>
<h4><?php _e('Any field left blank will not show up', 'open-hours')?></h4>
<style type="text/css">
	#openhoursettings fieldset {
		border-left: 1px solid black;
		padding: 5px;
		margin: 5px 0;
	}
	#openhoursettings legend {
		font-weight: bold;
	}
</style>
<legend><?php _e('Override', 'open-hours')?></legend>
<?php
$override_status = get_option('open_hours_override_status');
    if ($override_status == '0'):
    ?>
<form method="post" action="options.php">
	<?php wp_nonce_field('update-options');
    ?>
	<input name="open_hours_override_status" type="hidden" id="open_hours_override_status" value="1">
	<input type="submit" value="<?php _e('open', 'open-hours')?>" />
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="open_hours_override_status" />
</form>
<?php else: ?>
<form method="post" action="options.php">
	<?php wp_nonce_field('update-options');
    ?>
	<input name="open_hours_override_status" type="hidden" id="open_hours_override_status" value="0">
	<input type="submit" value="<?php _e('closed', 'open-hours')?>" />
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="open_hours_override_status" />
</form>
<?php endif;
    ?>
<hr>
<form method="post" action="options.php">
<?php wp_nonce_field('update-options');
    ?>
<fieldset>
	<legend><?php _e('Preferences', 'open-hours')?></legend>
	<?php _e('Delimiter between multiple open hours', 'open-hours')?>:
	<input name="open_hours_multiple_time_delimiter" type="text" id="open_hours_multiple_time_delimiter"
	value="<?php echo get_option('open_hours_multiple_time_delimiter');
    ?>" />
	<br>
	<?php _e('Delimiter in-between open hours', 'open-hours')?>:
	<input name="open_hours_time_delimiter" type="text" id="open_hours_time_delimiter"
	value="<?php echo get_option('open_hours_time_delimiter');
    ?>" />
    <br/>
	<?php _e('Show open/closed in shortcode', 'open-hours')?>:
	<input name="open_hours_show_status_in_shortcode" type="checkbox" id="open_hours_show_status_in_shortcode"
	value="yes" <?php echo get_option('open_hours_show_status_in_shortcode') === 'yes' ? 'checked' : '';
    ?> />
</fieldset>
<fieldset>
	<legend><?php _e('Range', 'open-hours')?></legend>
	<?php _e('Days', 'open-hours')?>:
	<input name="open_hours_range" type="text" id="open_hours_range"
	value="<?php echo get_option('open_hours_range');
    ?>" />
	<br />
	<?php _e('Hours', 'open-hours')?>:
	<input name="open_hours_range_hours" type="text" id="open_hours_range_hours"
	value="<?php echo get_option('open_hours_range_hours');
    ?>" />
</fieldset>
<fieldset>
	<legend><?php _e('Days', 'open-hours')?></legend>
	<?php _e('Monday', 'open-hours')?>:
	<input name="open_hours_1" type="text" id="open_hours_1"
	value="<?php echo get_option('open_hours_1');
    ?>" />
	<br />
	<?php _e('Tuesday', 'open-hours')?>:
	<input name="open_hours_2" type="text" id="open_hours_2"
	value="<?php echo get_option('open_hours_2');
    ?>" />
	<br />
	<?php _e('Wednesday', 'open-hours')?>:
	<input name="open_hours_3" type="text" id="open_hours_3"
	value="<?php echo get_option('open_hours_3');
    ?>" />
	<br />
	<?php _e('Thursday', 'open-hours')?>:
	<input name="open_hours_4" type="text" id="open_hours_4"
	value="<?php echo get_option('open_hours_4');
    ?>" />

	<br />
	<?php _e('Friday', 'open-hours')?>:
	<input name="open_hours_5" type="text" id="open_hours_5"
	value="<?php echo get_option('open_hours_5');
    ?>" />

	<br />
	<?php _e('Saturday', 'open-hours')?>:
	<input name="open_hours_6" type="text" id="open_hours_6"
	value="<?php echo get_option('open_hours_6');
    ?>" />
	<br />

	<?php _e('Sunday', 'open-hours')?>:
	<input name="open_hours_7" type="text" id="open_hours_7"
	value="<?php echo get_option('open_hours_7');
    ?>" />
</fieldset>
<fieldset>
	<legend><?php _e('Holidays', 'open-hours')?></legend>
	<?php _e('Start', 'open-hours')?>:
	<input name="open_hours_vac_start" type="text" id="open_hours_vac_start"
	value="<?php echo get_option('open_hours_vac_start');
    ?>" />
	<br />
	<?php _e('End', 'open-hours')?>:
	<input name="open_hours_vac_end" type="text" id="open_hours_vac_end"
	value="<?php echo get_option('open_hours_vac_end');
    ?>" />
	<br />
	<?php _e('Reason', 'open-hours')?>:
	<input name="open_hours_vac_reason" type="text" id="open_hours_vac_reason"
	value="<?php echo get_option('open_hours_vac_reason');
    ?>" />
</fieldset>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="open_hours_7, open_hours_1, open_hours_2, open_hours_3, open_hours_4, open_hours_5, open_hours_6, open_hours_range, open_hours_range_hours, open_hours_vac_start, open_hours_vac_end, open_hours_multiple_time_delimiter, open_hours_time_delimiter, open_hours_vac_reason, open_hours_show_status_in_shortcode" />


<p>
<input type="submit" value="<?php _e('Save Changes')?>" />
</p>

</form>
</div>
<?php

}

?>
