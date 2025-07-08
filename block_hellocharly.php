<?php
defined('MOODLE_INTERNAL') || die();

class block_hellocharly extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_hellocharly');
    }

    public function get_content() {
        global $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $context = context_system::instance();
        if (!has_capability('block/hellocharly:view', $context)) {
            return $this->content;
        }

        $templatecontext = [
            'userid' => $USER->id,
            'buttontext' => get_string('accessbutton', 'block_hellocharly'),
            'sesskey' => sesskey()
        ];

        // Juste un rendu basique. La progression sera affichée en JS/AJAX
        $html = $OUTPUT->render_from_template('block_hellocharly/main', $templatecontext);

        $this->content->text = $html;
        return $this->content;
    }

    public function applicable_formats() {
        return array(
            'site' => true,
            'site-index' => true,
            'course-view' => false,
            'course-view-social' => false,
            'mod' => false,
            'mod-quiz' => false,
            'my' => true,
            'user' => true
        );
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return false;
    }
}
