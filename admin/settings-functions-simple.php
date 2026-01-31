<?php
/**
 * Simple options handler for settings page
 */

function settings_fields($group) {
    do_settings_sections($group);
    do_settings_fields($group);
}

function settings_errors() {
    settings_errors();
}

function submit_button($text = 'Save Settings', $type = 'primary', $name = 'submit') {
    submit_button($text, $type, $name);
}