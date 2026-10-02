<?php
/**
 * Plugin Name: CV OpenAI Polylang Translator
 * Description: Manually translate Finnish pages/posts into English and B2B Arabic drafts using OpenAI and Polylang.
 * Version: 2.0.0
 * Author: Antigravity AI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: i4ware-cv-openai-polylang-translator
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */


if (!defined('ABSPATH')) {
    exit;
}

// 1. Load main plugin controller class
require_once plugin_dir_path(__FILE__) . 'includes/class-cv-oai-pll-plugin.php';

// 2. Instantiate and run the plugin lifecycle hooks
function cv_oai_pll_bootstrap()
{
    $plugin = new CV_OAI_PLL_Plugin();
    $plugin->run();
}
cv_oai_pll_bootstrap();

// 3. Register activation hook for custom tables
register_activation_hook(__FILE__, 'cv_oai_pll_activate');
function cv_oai_pll_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-cv-oai-pll-db.php';
    CV_OAI_PLL_DB::create_tables();
}

