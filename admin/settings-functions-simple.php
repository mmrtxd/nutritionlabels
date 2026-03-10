<?php
/
/**
 * Copyright (c) 2026 - Markus Hammer - https://github.com/mmrtxd/
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

**
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
