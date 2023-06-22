<?php 

/*
    Plugin Name: 123 Unique Plugin
    Description: A very useful plugin.
    Version: 1.0
    Author: Jaguat
    Author URI: https://portfolio-vitor.vercel.app/
    Text Domain: wcpdomain
    Domain Path: /languages

*/

//using PHP classes to avoid conflict of the name of our functions with the name of other plugin's functions
class WordCountAndTimePlugin {
    function __construct() {
        add_action('admin_menu', array($this, 'adminPage'));
        add_action('admin_init', array($this, 'settings'));
        add_filter('the_content', array($this, 'ifWrap'));
        add_action('init', array($this, 'languages'));
    }

    function languages() {
        load_plugin_textdomain('wcpdomain', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    //the logic of the plugin itself:
    function ifWrap($content) {
        if (is_main_query() AND is_single() AND (get_option('wcp_wordcount', '1') OR get_option('wcp_charactercount', '1') OR get_option('wcp_readtime', '1'))) {
            return $this->createHTML($content);
        }
        return $content;
    }

    function createHTML($content) {
        $html = '<h5>' . esc_html(get_option('wcp_headline', 'Post Statistics')) . '</h5><p style="font-size: 1rem;">';

        // get word count once because both wordcount and readtime will need it
        if (get_option('wcp_wordcount', '1') OR get_option('wcp_readtime', '1')) {
            $wordCount = str_word_count(strip_tags($content));
        }

        //translated version of 1st sentence:
        if (get_option('wcp_wordcount', '1')) {
            $html .= esc_html__('This post has', 'wcpdomain') . ' ' . $wordCount . ' ' . __('words', 'wcpdomain') . '.<br>';
        }

        if (get_option('wcp_charactercount', '1')) {
            $html .= 'This post has ' . strlen(strip_tags($content)) . ' characters.<br>';
        }

        if (get_option('wcp_readtime', '1')) {
            $html .= 'This post will take about ' . ceil($wordCount/225) . ' minute' . ((ceil($wordCount/225) > 1) ? 's' : '') . ' to read.<br>';
        }

        $html .= '</p>';

        if (get_option('wcp_location', '0') == '0') {
            return $html . $content;
        } else {
            return $content . $html;
        }
    }

    //registering the settings table in the database:
    function settings() {
        add_settings_section('wcp_first_section', null, null, 'word-count-settings-page');

        //field 'Display Location'
        add_settings_field('wcp_location', 'Display Location', array($this, 'locationHTML'), 'word-count-settings-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_location', array(
            'sanitize_callback' => array($this, 'sanitizeLocation'),
            'default' => '0' //default value of this field
        ));

        //field 'Headline'
        add_settings_field('wcp_headline', 'Headline Text', array($this, 'headlineHTML'), 'word-count-settings-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_headline', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Post Statistics'
        ));

        //field 'Word Count'
        add_settings_field('wcp_wordcount', 'Word Count', array($this, 'wordcountHTML'), 'word-count-settings-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_wordcount', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '1'
        ));

        //field 'Character Count'
        add_settings_field('wcp_charactercount', 'Character Count', array($this, 'charactercountHTML'), 'word-count-settings-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_charactercount', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '1'
        ));

        //field 'Read Time'
        add_settings_field('wcp_readtime', 'Read Time', array($this, 'readtimeHTML'), 'word-count-settings-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_readtime', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '1'
        ));
    }

    // a custom function to validate the values, so that a malicious user cannot use the Inspector to inject other value than 0 or 1
    function sanitizeLocation($input) {
        if ($input != '0' and $input != '1') {
            add_settings_error('wcp_location', 'wcp_location_error', 'Display location must me either Beginning or End.');
            return get_option('wcp_location');
        } else {
            return $input;
        }
    }

    function readtimeHTML() { ?>
        <input type="checkbox" name="wcp_readtime" value="1" <?php checked(get_option('wcp_readtime', '1')); ?>>
    <?php }

    function charactercountHTML() { ?>
        <input type="checkbox" name="wcp_charactercount" value="1" <?php checked(get_option('wcp_charactercount', '1')); ?>>
    <?php }

    function wordcountHTML() { ?>
        <input type="checkbox" name="wcp_wordcount" value="1" <?php checked(get_option('wcp_wordcount', '1')); ?> >
    <?php }

    function headlineHTML() { ?>
        <input type="text" name="wcp_headline" value="<?php echo esc_attr(get_option('wcp_headline')); ?>">
    <?php }
    
    //the name attribute must match the name that is defined in add_settings_field() function
    function locationHTML() { ?>
        <select name="wcp_location">
            <!-- selected() function will output the tag 'selected' if the current value in the database is set to 0 / 1 -->
            <option value="0" <?php selected(get_option('wcp_location', '0')); ?>>Beginning of post</option>
            <option value="1" <?php selected(get_option('wcp_location', '1')); ?>>End of post</option>
        </select>
    <?php }

    function adminPage() {
        //original version (no translation):
        // add_options_page('Word Count Settings', 'Word Count', 'manage_options', 'word-count-settings-page', array($this, 'ourHTML')); //'manage_options' will give access only to user with manage_options capabilities (admin); 'word-count-settings-page' is the slug; 

        //EN-SP translated:
        add_options_page('Word Count Settings', __('Word Count', 'wcpdomain'), 'manage_options', 'word-count-settings-page', array($this, 'ourHTML')); //'manage_options' will give access only to user with manage_options capabilities (admin); 'word-count-settings-page' is the slug; 
    }
    
    function ourHTML() { ?>
        <!-- css class "wrap" is configured by WP itself, so that the admin pages of all plugins look the same -->
        <div class="wrap"> 
            <h1>Word Count Settings</h1>
            <form action="options.php" method="POST">
                <?php 
                    settings_fields('wordcountplugin');
                    do_settings_sections('word-count-settings-page');
                    submit_button();
                ?>
            </form>
        </div>
    <?php }
}

$wordCountAndTimePlugin = new WordCountAndTimePlugin();


// add_filter('the_content', 'addToEndOfPost');

// function addToEndOfPost($content) {
//     if (is_single() && is_main_query()) {
//         return $content . '<p>My name is Jaguat</p>';
//     }

//     return $content;
// }
