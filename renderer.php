<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines the renderers for the StudentQuiz module.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Base renderer for Studentquiz with helpers
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_renderer extends plugin_renderer_base {

    /**
     * TODO: document blocks missing everywhere here
     * @param $celldata
     * @param $rowattributes
     * @return array
     */
    public function render_table_data(array $celldata, array $rowattributes=array()) {
        $rows = array();
        foreach($celldata as $num => $row){
            $cells = array();
            foreach($row as $cell){
                if (!empty($rowattributes[$num])) {
                    $cells[] = $this->render_table_cell($cell, $rowattributes[$num]);
                } else {
                    $cells[] = $this->render_table_cell($cell);
                }
            }
            $rows[] = $this->render_table_row($cells);
        }
        return $rows;
    }

    public function render_table_cell(string $text, array $attributes=array()) {
        $cell = new html_table_cell();
        $cell->text = $text;
        if(!empty($attributes)) {
            $cell->attributes = $attributes;
        }
        return $cell;
    }

    public function render_table_row($cells) {
        $row = new html_table_row();
        $row->cells = $cells;
        return $row;
    }

    public function render_table($data, $size, $align, $head, $caption) {
        $table = new html_table();
        $table->caption = $caption;
        $table->head = $head;
        $table->align = $align;
        $table->size = $size;
        $table->data = $data;
        return html_writer::table($table);
    }

    /**
     * Return a svg representing a progress bar filling 100% of is containing element
     * @param stdClass $info
     * @return string
     */
    public function render_progress_bar($info) {

        // Check input.
        $validInput = true;
        if (!isset($info->total)) {
            $validInput = false;
        }

        if (!isset($info->attempted)) {
            $validInput = false;
        }

        if (!isset($info->lastattemptcorrect)) {
            $validInput = false;
        }

        // Stylings.
        $rgb_stroke = 'rgb(100,100,100)';
        $rgb_background = 'rgb(200,200,200)';
        $rgb_green = 'rgb(0,255,0)';
        $rgb_blue = 'rgb(0,0,255)';
        $rgb_white = 'rgb(255,255,255)';
        $bar_stroke = 'stroke-width:3;stroke:' . $rgb_stroke .';';
        $svg_dims = array('width' => '100%', 'height' => 20);
        $bar_dims = array('height' => '100%', 'rx' => 5, 'ry' => 5);
        $id_blue = 'blue';
        $id_green = 'green';
        $gradient_dims = array('cx' => '50%', 'cy' => '50%', 'r' => '50%', 'fx' => '50%', 'fy' => '50%');
        $stopColorWhite = html_writer::tag('stop', null,
            array('offset' => '0%', 'style' => 'stop-color:' . $rgb_white .';stop-opacity:1'));
        $stopColorGreen = html_writer::tag('stop', null,
            array('offset' => '100%','style' => 'stop-color:' . $rgb_green . ';stop-opacity:1'));
        $stopColorBlue = html_writer::tag('stop', null,
            array('offset' => '100%','style' => 'stop-color:' . $rgb_blue . ';stop-opacity:1'));
        $gradientBlue = html_writer::tag('radialGradient', $stopColorWhite . $stopColorBlue,
            array_merge($gradient_dims, array('id' => $id_blue)));
        $gradientGreen = html_writer::tag('radialGradient', $stopColorWhite . $stopColorGreen,
            array_merge($gradient_dims, array('id' => $id_green)));
        $gradients = array($gradientBlue, $gradientGreen);
        $defs = html_writer::tag('defs', implode($gradients));

        // Background bar.
        $barbackground = html_writer::tag('rect', null, array_merge($bar_dims,
            array('width' => '100%', 'style' => $bar_stroke . 'fill:' . $rgb_background)));

        // Return empty bar if no questions are in StudentQuiz.
        if( !$validInput || $info->total <= 0) {
            return html_writer::tag('svg', $barbackground, $svg_dims);
        }

        // Calculate Percentages to display.
        $percent_attempted = round(100 * ($info->attempted / $info->total));
        $percent_lastattemptcorrect = round(100 * ($info->lastattemptcorrect / $info->total));

        // Return stacked bars.
        $bars = array($barbackground);
        $bars[] = html_writer::tag('rect', null, array_merge($bar_dims,
            array('width' => $percent_attempted . '%', 'style' => $bar_stroke . 'fill:url(#' . $id_blue .')')));
        $bars[] = html_writer::tag('rect', null, array_merge($bar_dims,
            array('width' => $percent_lastattemptcorrect . '%', 'style' => $bar_stroke . 'fill:url(#' . $id_green .')')));
        return html_writer::tag('svg', $defs . implode($bars), $svg_dims);
    }

    /**
     * Prints the error message
     * @param string $errormessage string error message
     * @return string error as HTML
     */
    public function show_error($errormessage) {
        return html_writer::div($errormessage, 'error');
    }

}


class mod_studentquiz_summary_renderer extends mod_studentquiz_renderer {
    /**
     * Renders the summary object given to html
     * @param mod_studentquiz_summary_view $summary summary view obj.
     * @return summary html
     */
    public function render_summary($summary) {
        $output = '';
        $output .= html_writer::start_tag('form', array('method' => 'post', 'action' => '',
            'enctype' => 'multipart/form-data', 'id' => 'responseform'));
        $output .= html_writer::start_tag('div', array('align' => 'center'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'back', 'value' => get_string('review_button', 'studentquiz')));
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'finish', 'value' => get_string('finish_button', 'studentquiz')));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');
        return $output;
    }
}

class mod_studentquiz_overview_renderer extends mod_studentquiz_renderer {

    /**
     * Builds the studentquiz_bank_view
     * @param studentquiz_view $view studentquiz_view class with the necessary information
     * @return string formatted html
     */
    public function render_overview($view)
    {
        $contents = '';

        $contents .= $this->heading(format_string($view->get_studentquiz_name()));

        //$contents .= html_writer::tag('div', $this->render_progress_bar($view->get_progress_info()));

        $contents .= $this->render_select_qtype_form($view);

        $contents .= $this->render_questionbank($view);

        if ($view->has_printableerror()) {
            $contents .= $this->show_error($view->get_errormessage());
        }

        return html_writer::tag('div', $contents, array('class' => implode(' ',
                array('questionbankwindow', 'boxwidthwide', 'boxaligncenter'))));
    }

    /**
     * @param $view
     * @return mixed
     * TODO: REFACTOR!
     */
    public function render_questionbank($view) {
        $pagevars = $view->get_qb_pagevar();
        return $view->get_questionbank()->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
            $pagevars['cat'], false, $pagevars['showhidden'],
            $pagevars['qbshowtext']);
    }

    /**
     * @param $view
     * @return string
     */
    public function render_select_qtype_form($view) {
        $output = '';
        $output .= $view->get_questionbank()->create_new_question_form($view->get_category_id(), true);
        return html_writer::tag('div', $output);
    }
}

class mod_studentquiz_attempt_renderer extends mod_studentquiz_renderer {
    /**
     * Generate some HTML to display comment list
     * @param array $comments comments joined by user.firstname and user.lastname, ordered by createdby ASC
     * @param int $userid viewing user id
     * @param bool $anonymize users can't see other comment authors user names except ismoderator
     * @param bool $ismoderator can delete all comments, can see all usernames
     * @return string HTML fragment
     * TODO: move mod_studentquiz_comment_renderer in here!
     */
    public function comment_list($comments, $userid, $anonymize = true, $ismoderator = false) {
        return mod_studentquiz_comment_renderer($comments, $userid, $anonymize, $ismoderator);
    }

    /**
     * Generate some HTML (which may be blank) that appears in the outcome area,
     * after the question-type generated output.
     *
     * For example, the CBM models use this to display an explanation of the score
     * adjustment that was made based on the certainty selected.
     *
     * @param question_definition $question the current question.
     * @param question_display_options $options controls what should and should not be displayed.
     * @param array $comments comments joined by user.firstname and user.lastname, ordered by createdby ASC
     * @param int $userid viewing user id
     * @param bool $anonymize users can't see other comment authors user names except ismoderator
     * @param bool $ismoderator can delete all comments, can see all usernames
     * @return string HTML fragment
     * @return string HTML fragment.
     */
    public function feedback(question_definition $question,
                             question_display_options $options, $cmid,
                             $comments, $userid, $anonymize = true, $ismoderator = false) {
        global $CFG;
        return html_writer::div($this->render_vote($question->id)
                . $this->render_comment($cmid, $question->id, $comments, $userid, $anonymize, $ismoderator), 'studentquiz_behaviour')
            . html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'baseurlmoodle'
            , 'id' => 'baseurlmoodle', 'value' => $CFG->wwwroot))
            . html_writer::start_div('none')
            . html_writer::start_div('none');
    }

    /**
     * Generate some HTML to display rating options
     *
     * @param  int $questionid Question id
     * @param  boolean $selected shows the selected vote
     * @param  boolean $readonly describes if rating is readonly
     * @return string HTML fragment
     */
    protected function vote_choices($questionid, $selected, $readonly) {
        $attributes = array(
            'type' => 'radio',
            'name' => 'q' . $questionid,
        );

        if ($readonly) {
            $attributes['disabled'] = 'disabled';
        }

        $selected = (int)$selected;

        $rateable = '';
        if (!$readonly) {
            $rateable = 'rateable ';
        }

        $choices = '';
        $votes = [5, 4, 3, 2, 1];
        foreach ($votes as $vote) {
            $class = 'star-empty';
            if ($vote <= $selected) {
                $class = 'star';
            }
            $choices .= html_writer::span('', $rateable . $class, array('data-rate' => $vote, 'data-questionid' => $questionid));
        }
        return get_string('vote_title', 'mod_studentquiz')
            . $this->output->help_icon('vote_help', 'mod_studentquiz') . ': '
            . html_writer::div($choices, 'rating')
            . html_writer::div(get_string('vote_error', 'mod_studentquiz'), 'hide error');
    }

    /**
     * Generate some HTML to display comment form for add comment
     *
     * @param  int $questionid Question id
     * @return string HTML fragment
     */
    protected function comment_form($questionid, $cmid) {
        return html_writer::tag('p', get_string('add_comment', 'mod_studentquiz')
                . $this->output->help_icon('comment_help', 'mod_studentquiz') . ':')
            . html_writer::tag('p', html_writer::tag(
                'textarea', '',
                array('class' => 'add_comment_field', 'name' => 'q' . $questionid)))
            . html_writer::tag('p', html_writer::tag(
                'button',
                get_string('add_comment', 'mod_studentquiz'),
                array('type' => 'button', 'class' => 'add_comment'))
            );
    }

    /**
     * Generate some HTML to display rating
     *
     * @param  int $questionid Question id
     * @param array $comments comments joined by user.firstname and user.lastname, ordered by createdby ASC
     * @param int $userid viewing user id
     * @param bool $anonymize users can't see other comment authors user names except ismoderator
     * @param bool $ismoderator can delete all comments, can see all usernames
     * @return string HTML fragment
     * @return string HTML fragment
     */
    protected function render_vote($questionid) {
        global $DB, $USER;

        $value = -1; $readonly = false;
        $vote = $DB->get_record('studentquiz_vote', array('questionid' => $questionid, 'userid' => $USER->id));
        if ($vote !== false) {
            $value = $vote->vote;
            $readonly = true;
        }

        return html_writer::div($this->vote_choices($questionid, $value , $readonly), 'vote');
    }

    /**
     * Generate some HTML to display the complete comment fragment
     *
     * @param  int $questionid Question id
     * @return string HTML fragment
     */
    protected function render_comment($cmid, $questionid, $comments, $userid, $anonymize = true, $ismoderator = false) {
        return html_writer::div(
            $this->comment_form($questionid, $cmid)
            . html_writer::div($this->comment_list($comments, $userid, $anonymize, $ismoderator),
                'comment_list'), 'comments');
    }
}

class mod_studentquiz_report_renderer extends mod_studentquiz_renderer{

    /**
     * Builds the quiz report table for the admin
     * @param mod_studentquiz_report $report studentquiz_report class with necessary information
     * @param array $usersdata
     * @return string rank report table
     */
    public function view_quizreport_table($report, $usersdata) {
        $output = $this->heading(get_string('reportquiz_admin_title', 'studentquiz'), 2, 'reportquiz_total_heading');
        $table = new html_table();
        $table->attributes['class'] = 'generaltable boxaligncenter';
        $table->head = array(get_string('reportrank_table_column_fullname', 'studentquiz')
        , get_string('reportquiz_total_questions_answered', 'studentquiz')
        , get_string('reportquiz_total_questions_right', 'studentquiz')
        , get_string('reportquiz_total_obtained_marks', 'studentquiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $table->data = array();
        $rows = array();
        foreach ($usersdata as $user) {
            $cellfullname = new html_table_cell();
            $cellfullname->text = $user->name;

            $cellnumattempts = new html_table_cell();
            $cellnumattempts->text = $user->numattempts;

            $cellobtainedmarks = new html_table_cell();
            $cellobtainedmarks->text = $user->attemptedgrade . ' / ' . $user->maxgrade;

            $cellquestionsanswered = new html_table_cell();
            $cellquestionsanswered->text = $user->questionsanswered;

            $cellquestionsright = new html_table_cell();
            $cellquestionsright->text = $user->questionsright;

            $row = new html_table_row();

            if ($report->is_loggedin_user($user->id)) {
                $style = array('class' => 'mod-studentquiz-summary-highlight');
                $cellfullname->attributes = $style;
                $cellobtainedmarks->attributes = $style;
                $cellquestionsanswered->attributes = $style;
                $cellquestionsright->attributes = $style;
                $row->attributes = $style;
            }
            $row->cells = array($cellfullname, $cellquestionsanswered
            , $cellquestionsright, $cellobtainedmarks);
            $rows[] = $row;
        }
        $table->data = $rows;
        $output .= html_writer::table($table);
        return $output;
    }


    /**
     * Get quiz admin statistic view
     * $userid of viewing user
     * @param mod_studentquiz_report $report
     * @return string pre rendered /mod/stundentquiz view_quizreport_table
     */
    public function get_quiz_statistic_view(mod_studentquiz_report $report) {
        $output = '';
        $output .= $this->heading(get_string('reportquiz_stats_title', 'studentquiz'), 2, 'reportquiz_stats_heading');
        $output .= $this->view_quizreport_stats($report->get_overalltotal(), $report->get_admintotal(), $report->get_outputstats(), $report->get_usergrades(), true);
        if($report->is_admin()) {
            $output .= $this->view_quizreport_table($report, $report->get_usersdata());
        }
        return $output;
    }

    /**
     * Builds the quiz report total section
     * @param stdClass $total
     * @param stdClass $usergrades
     * @param bool $isadmin
     * @return string quiz report data
     */
    public function view_quizreport_stats($total, $owntotal, $stats, $usergrades, $isadmin = false) {
    // No stats for admin yet.
    $output = '';
    if ($stats != null) {
        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_stats_nr_of_questions', 'studentquiz') . ': ', 'reportquiz_total_label')
            .html_writer::span($stats->totalnrofquestions)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_stats_nr_of_own_questions', 'studentquiz')
                . ': ', 'reportquiz_total_label')
            .html_writer::span($stats->totalusersquestions)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_stats_nr_of_approved_questions', 'studentquiz')
                . ': ', 'reportquiz_total_label')
            .html_writer::span($stats->numapproved)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_stats_avg_rating', 'studentquiz')
                . ': ', 'reportquiz_total_label')
            .html_writer::span($stats->avgvotes)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_stats_right_answered_questions', 'studentquiz')
                . ': ', 'reportquiz_total_label')
            .html_writer::span($stats->totalrightanswers)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_stats_own_grade_of_max', 'studentquiz') . ': ', 'reportquiz_total_label')
            .html_writer::span($usergrades->usermark . ' / ' . $usergrades->stuquizmaxmark)
        );
    }

    if ($owntotal) {
        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_stats_questions_answered', 'studentquiz') . ': ', 'reportquiz_total_label')
            . html_writer::span($owntotal->questionsanswered)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_stats_questions_right', 'studentquiz') . ': ', 'reportquiz_total_label')
            . html_writer::span($owntotal->questionsright)
        );
        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_stats_learning_quotient', 'studentquiz') . ': ', 'reportquiz_total_label')
            . html_writer::span((($owntotal->questionsright) / ($stats->totalusersquestions)))
        );
    }

    // TODO: This makes no sense or enforces the operation result
    if ($total != null && false) {
        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_attempt', 'studentquiz') . ': ', 'reportquiz_total_label')
            . html_writer::span($total->numattempts)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_questions_answered', 'studentquiz') . ': ', 'reportquiz_total_label')
            . html_writer::span($total->questionsanswered)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_questions_right', 'studentquiz') . ': ', 'reportquiz_total_label')
            . html_writer::span($total->questionsright)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_questions_wrong', 'studentquiz') . ': ', 'reportquiz_total_label')
            . html_writer::span(($total->questionsanswered - $total->questionsright))
        );
        // Ex Label with: reportquiz_total_label and $total->obtainedmarks,.

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_users', 'studentquiz') . ': ', 'reportquiz_total_label')
            . html_writer::span($total->usercount)
        );
    }

    return $output;
    }


    /**
     * Get quiz tables
     * @return string rendered /mod/quiz/view tables
     */
    public function get_quiz_tables($report) {
        $total = $this->get_user_quiz_summary($report->get_user_id(), null);
        $outputstats = $this->get_user_quiz_stats($report->get_user_id());
        $usergrades = $this->get_user_quiz_grade($report->get_user_id());
        $output = $this->view_quizreport_stats(null, $total, $outputstats, $usergrades);
        return $output;
    }


}


class mod_studentquiz_ranking_renderer extends mod_studentquiz_renderer {

    /**
     * @param $report
     */
    public function view_ranking($report) {
       return $this->heading(get_string('reportrank_title', 'studentquiz'))
                . $this->view_quantifier_information($report)
                . $this->view_rankreport_table($report);
    }

    /**
     * displays quantifier information
     */
    public function view_quantifier_information($report) {
        $align = array('left', 'left');
        $size = array('', '', '');
        $head = array(get_string('reportrank_table_column_quantifier_name', 'studentquiz')
        , get_string('reportrank_table_column_factor', 'studentquiz')
        , get_string('reportrank_table_column_description', 'studentquiz'));
        $caption = get_string('reportrank_table_quantifier_caption', 'studentquiz');
        $celldata = array(
            array(get_string('settings_questionquantifier', 'studentquiz'),
                round($report->get_quantifier_question(), 2),
                'description' => get_string('settings_questionquantifier_help', 'studentquiz')),
            array(get_string('settings_approvedquantifier', 'studentquiz'),
                round($report->get_quantifier_approved(), 2),
                'description' => get_string('settings_approvedquantifier_help', 'studentquiz')),
            array('text' => get_string('settings_votequantifier', 'studentquiz'),
                round($report->get_quantifier_vote(), 2),
                'value' => get_string('settings_votequantifier_help', 'studentquiz')),
            array('text' => get_string('settings_correctanswerquantifier', 'studentquiz'),
                round($report->get_quantifier_correctanswer(), 2),
                'value' => get_string('settings_correctanswerquantifier_help', 'studentquiz')),
            array('text' => get_string('settings_incorrectanswerquantifier', 'studentquiz'),
                round($report->get_quantifier_incorrectanswer(), 2),
                'value' => get_string('settings_incorrectanswerquantifier_help', 'studentquiz'))
        );
        $data = $this->render_table_data($celldata);
        return $this->render_table($data, $size, $align, $head, $caption);
    }

    /**
     * builds the rank report table
     * @param mod_studentquiz_report $report studentquiz_report class with necessary information
     * @return string rank report table
     * @throws coding_exception
     * TODO: TODO: REFACTOR! Paginate ranking table or limit its length.
     */
    public function view_rankreport_table($report) {
        $align = array('left', 'left');
        $size = array('', '', '');
        $head = array(get_string('reportrank_table_column_rank', 'studentquiz')
        , get_string('reportrank_table_column_fullname', 'studentquiz')
        , get_string('reportrank_table_column_total_points', 'studentquiz')
        , get_string( 'reportrank_table_column_countquestions', 'studentquiz')
        , get_string( 'reportrank_table_column_approvedquestions', 'studentquiz')
        , get_string( 'reportrank_table_column_summeanvotes', 'studentquiz')
        , get_string( 'reportrank_table_column_correctanswers', 'studentquiz')
        , get_string( 'reportrank_table_column_incorrectanswers', 'studentquiz')
        );
        $caption = get_string('reportrank_table_title', 'studentquiz');
        $celldata = array();
        $rowstyle = array();

        $rank = 1;
        // TODO Refactor
        foreach ($report->get_user_ranking() as $ur) {
            $username = $ur->firstname . ' ' . $ur->lastname;
            if ($report->is_anonym() && !$report->is_loggedin_user($ur->userid)) {
                $username = get_string('creator_anonym_firstname', 'studentquiz') . ' ' . get_string('creator_anonym_lastname', 'studentquiz');
            }
            $celldata[] = array(
                $rank,
                $username,
                round($ur->points, 2),
                round($ur->countquestions * $report->get_quantifier_question(), 2),
                round($ur->numapproved * $report->get_quantifier_approved(), 2),
                round($ur->summeanvotes * $report->get_quantifier_vote(), 2),
                round($ur->correctanswers * $report->get_quantifier_correctanswer(), 2),
                round($ur->incorrectanswers * $report->get_quantifier_incorrectanswer(), 2)
            );
            $rowstyle[] = $report->is_loggedin_user($ur->userid)? array('class' => 'mod-studentquiz-summary-highlight'): array();
            $rank++;
        }

        $data = $this->render_table_data($celldata, $rowstyle);
        return $this->render_table($data, $size, $align, $head, $caption);
    }
}