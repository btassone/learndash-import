<?php
/*
Plugin Name: Learndash JSON Import
Plugin URI: http://my-awesomeness-emporium.com
Description: a plugin to create awesomeness and spread joy
Version: 1.2
Author: Mr. Awesome
Author URI: http://mrtotallyawesome.com
License: GPL2
*/
require_once("vendor/autoload.php");

global $wpdb;

\Kint::$maxLevels = 0;

define('COURSES_POST_TYPE', 'sfwd-courses');
define('QUIZ_POST_TYPE', 'sfwd-quiz');

define('QUIZ_MASTER_TABLE', $wpdb->prefix . 'wp_pro_quiz_master');
define('QUIZ_QUESTION_TABLE', $wpdb->prefix . 'wp_pro_quiz_question');
define('QUIZ_PREREQ_TABLE', $wpdb->prefix . 'wp_pro_quiz_prerequisite');

define('WP_POSTMETA_TABLE', $wpdb->prefix . 'postmeta');
define('WP_POSTS_TABLE', $wpdb->prefix . 'posts');

add_action('admin_menu', 'learndash_import_menu');

function learndash_import_menu() {
    add_menu_page('LearnDash Import', 'LearnDash Import', 'manage_options', 'learndash-import', 'learndash_import_menu_page');
}

function learndash_import_menu_page() {
    $run = $_GET['run'];
    $delete = $_GET['delete'];
    $json_file = file_get_contents(plugin_dir_path(__FILE__) . "json-import.json");
    $data = json_decode($json_file);

    ?>
    <style>
        .course { margin-bottom: 60px; }
        .course:last-child { margin-bottom: 0; }
        .title-wrap, .sub-title-wrap { padding: 10px; box-sizing: border-box; }
        .title-wrap { background: #1e8cbe; color: #fff; }
        .title-wrap.delete-data { margin-bottom: 20px; }
        .sub-title-wrap { border: 1px solid #1e8cbe; border-top: none; }

        .heading-title, .sub-heading-title { text-transform: uppercase; margin: 0; padding: 0; }
        .title-wrap .heading-title, .title-wrap .sub-heading-title { margin: 0; padding: 0; }
        .heading-title { color: #fff; }
        .sub-heading-title { color: #222; }

        .heading-wrap { margin-bottom: 20px; }

        .quizzes-container { padding: 10px; box-sizing: border-box; border: 1px solid #1e8cbe; }

        .vert-center-me { display: flex; align-items: center; }

        .quiz-info-item label { width: 125px; text-transform: uppercase; font-weight: 700; }

        .quiz-container { margin-bottom: 30px; }
        .quiz-heading { background: orange; padding: 10px; color: #fff;  }
        .quiz-container:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .questions-container { border: 1px solid orange; padding: 20px 10px 10px 10px; border-top: none; box-sizing: border-box; }

        .question-container { margin-bottom: 20px; }
        .question-container:last-child { margin-bottom: 0; }

        .question-info-item label { font-weight: 700; text-transform: uppercase; width: 125px; }
        .question-heading { background: #1e8bce; padding: 10px; color: #fff; }
        .question-possible-answers { padding: 10px; border: 1px solid #1e8cbe; border-top: none; }

        .possible-answer-container { background: orangered; color: #fff; margin-bottom: 10px; padding: 10px; box-sizing: border-box; }
        .possible-answer-container.correct { background: limegreen; }
        .possible-answer-container:last-child { margin-bottom: 0; }

        .rows-removed-container { background: #ddd; padding: 10px; box-sizing: border-box; margin-bottom: 20px; }

        .deleted-tables-status { padding: 10px; box-sizing: border-box; color: #fff; font-weight: 700; }
        .deleted-tables-status.success { background: limegreen; }
        .deleted-tables-status.failure { background: orangered; }

        .main-action-wrap { background: #1e8cbe; padding: 20px; box-sizing: border-box; display: block; width: calc(100% - 160px); position: fixed; bottom: 0; left: 160px; z-index: 9999; }

        .main-action-wrap button { padding: 6px; background: orange; border: none; color: #fff; margin-right: 10px; cursor: pointer; transition: all 0.3s ease; }
        .main-action-wrap button:last-child { margin-right: 0; }
        .main-action-wrap button:hover { background: darkorange; }

        .json-structure { background: #ddd; padding: 10px; box-sizing: border-box; }

        #go-back-to-main { float: right; }

        @media(max-width: 960px) {
            .main-action-wrap {
                width: calc(100% - 36px);
                left: 36px;
            }
        }

        @media(max-width: 782px) {
            .main-action-wrap {
                width: 100%;
                left: 0;
            }
        }
    </style>
    <div class="wrap" style="margin-top: 20px; margin-bottom: 30px;">
        <div class="main-action-wrap">
            <button id="run-import">Run Import</button>
            <button id="delete-all-data">Delete All LearnDash Data</button>
            <button id="go-back-to-main">Home</button>
        </div>
    <?php

    if($run) {

        foreach($data as $course):
            $course_id = $course->course_id;
            $course_title = $course->course_title;

            $wp_course_id = learndash_import_create_course($course_id, $course_title);
            ?>
            <div class="course">
                <div class="heading-wrap">
                    <div class="title-wrap">
                        <h1 class="heading-title"><?php echo $course_title; ?></h1>
                    </div>
                    <div class="sub-title-wrap">
                        <h6 class="sub-heading-title"><?php echo $course_id; ?></h6>
                    </div>
                </div>
                <div class="quizzes-container">
            <?php
            $quiz_prereq_info = array();

            foreach($course->quizzes as $quiz):
                $quiz_id = $quiz->quiz_id;
                $quiz_title = $quiz->quiz_title;
                $prereq_quiz_id = $quiz->prereq_quiz_id ?: 'NULL';

                $prereq_info = learndash_import_create_quiz($quiz_id, $quiz_title, $wp_course_id, $prereq_quiz_id);
                $quiz_prereq_info[] = $prereq_info;
                ?>
                <div class="quiz-container">
                    <div class="quiz-heading">
                        <div class="quiz-info-item vert-center-me"><label>PreReq Quiz Id: </label><?php echo $prereq_quiz_id; ?></div>
                        <div class="quiz-info-item vert-center-me"><label>Quiz Id: </label><?php echo $quiz_id; ?></div>
                        <div class="quiz-info-item vert-center-me"><label>Quiz Title: </label><?php echo $quiz_title; ?></div>
                    </div>
                    <div class="questions-container">
                    <?php

                    foreach($quiz->questions as $question):
                        $question_id = $question->question_id;
                        $question_text = $question->question_text;
                        $possible_answers = $question->possible_answers;

                        learndash_import_create_question($prereq_info["quiz_id_master"], $question_text, $possible_answers);
                        ?>
                        <div class="question-container">
                            <div class="question-heading">
                                <div class="question-info-item vert-center-me"><label>Question Id: </label><?php echo $question_id; ?></div>
                                <div class="question-info-item vert-center-me"><label>Question Text: </label><?php echo $question_text; ?></div>
                            </div>
                            <div class="question-possible-answers">
                                <?php
                                foreach($possible_answers as $possible_answer):
                                    $answer_id = $possible_answer->answer_id;
                                    $answer_text = $possible_answer->answer_text;
                                    $correct = $possible_answer->correct;
                                    ?>
                                    <div class="possible-answer-container <?php echo $correct ? 'correct' : ''; ?>">
                                        <div class="possible-answer-item vert-center-me"><label>Answer Id: </label><?php echo $answer_id; ?></div>
                                        <div class="possible-answer-item vert-center-me"><label>Answer Text: </label><?php echo $answer_text; ?></div>
                                        <div class="possible-answer-item vert-center-me"><label>Correct: </label><?php echo $correct ? 'true' : 'false'; ?></div>
                                    </div>
                                    <?php
                                endforeach;
                                ?>
                            </div>
                        </div>
                        <?php
                    endforeach;

                    ?>
                    </div>
                </div>
                <?php
            endforeach;

            // Update the recently imported prerequisites
            learndash_import_set_prerequisites($quiz_prereq_info);
            ?>
            </div>
            </div>
            <?php
        endforeach;
    }

    if($delete) {
        ?>
        <div class="title-wrap delete-data">
            <h1 class="heading-title">Remove All Data</h1>
        </div>
        <?php
        $passed = learndash_import_delete_all_data();
        $passed_text = $passed ? "Deleted everything successfully" : "Failed deleting a table somewhere.";
        $passed_class = $passed ? "success" : "failure";
        ?>
        <div class="deleted-tables-status <?php echo $passed_class; ?>">
            <?php echo $passed_text; ?>
        </div>
        <?php
    }

    if(!$run && !$delete) {
        ?>
        <div class="title-wrap description">
            <h1 class="heading-title">LearnDash Import</h1>
        </div>
        <div style="margin-bottom: 30px;">
            <p>
                LearnDash Import is a JSON importer. Currently there are 2 supported actions Run Import which will run
                the JSON import and Delete All LearnDash data. Best used when its a brand new site and you messed up
                the import somehow and need to start again
            </p>
            <p>
                Listed below is the current JSON template. More options will be added in the future
            </p>
        </div>
        <div>
            <h3>Example JSON Import Structure</h3>
            <pre class="json-structure">[
    {
        "course_id": 1,
        "course_title": "Some Course Title",
        "quizes": [
            {
                "id": 1,
                "prereq_quiz_id": null,
                "quiz_title": "Some Quiz Title",
                "quiz_desc": "Some Quiz Description",
                "questions": [
                    {
                        "id": 1,
                        "question_text": "Some Title",
                        "possible_answers": [
                            { "id": 1, "answer": "Possible Answer 1", "correct": false }
                            { "id": 2, "answer": "Possible Answer 2", "correct": false }
                            { "id": 3, "answer": "Possible Answer 3", "correct": true }
                            { "id": 4, "answer": "Possible Answer 4", "correct": false }
                        ]
                    },
                    {
                        "id": 2,
                        "question_text": "Some Title 2",
                        "possible_answers": [
                            { "id": 5, "answer": "Possible Answer 1", "correct": false }
                            { "id": 6, "answer": "Possible Answer 2", "correct": false }
                            { "id": 7, "answer": "Possible Answer 3", "correct": false }
                            { "id": 8, "answer": "Possible Answer 4", "correct": true }
                        ]
                    },
                    {
                        "id": 3,
                        "question_text": "Some Title 3",
                        "possible_answers": [
                            { "id": 9, "answer": "Possible Answer 1", "correct": false }
                            { "id": 10, "answer": "Possible Answer 2", "correct": true }
                            { "id": 11, "answer": "Possible Answer 3", "correct": false }
                            { "id": 12, "answer": "Possible Answer 4", "correct": false }
                        ]
                    }
                ]
            }
        ]
    },
    {
        "course_id": 2,
        "course_title": "Some Course Title 2",
        "quizes": [
            {
                "id": 2,
                "prereq_quiz_id": null,
                "quiz_title": "Some Quiz Title",
                "quiz_desc": "Some Quiz Description",
                "questions": [
                    {
                        "id": 4,
                        "question_text": "Some Title",
                        "possible_answers": [
                            { "id": 13, "answer": "Possible Answer 1", "correct": false }
                            { "id": 14, "answer": "Possible Answer 2", "correct": false }
                            { "id": 15, "answer": "Possible Answer 3", "correct": true }
                            { "id": 16, "answer": "Possible Answer 4", "correct": false }
                        ]
                    },
                    {
                        "id": 5,
                        "question_text": "Some Title 2",
                        "possible_answers": [
                            { "id": 17, "answer": "Possible Answer 1", "correct": false }
                            { "id": 18, "answer": "Possible Answer 2", "correct": false }
                            { "id": 19, "answer": "Possible Answer 3", "correct": false }
                            { "id": 20, "answer": "Possible Answer 4", "correct": true }
                        ]
                    },
                    {
                        "id": 6,
                        "question_text": "Some Title 3",
                        "possible_answers": [
                            { "id": 21, "answer": "Possible Answer 1", "correct": false }
                            { "id": 22, "answer": "Possible Answer 2", "correct": true }
                            { "id": 23, "answer": "Possible Answer 3", "correct": false }
                            { "id": 24, "answer": "Possible Answer 4", "correct": false }
                        ]
                    }
                ]
            },
            {
                "id": 3,
                "prereq_quiz_id": 2,
                "quiz_title": "Some Quiz Title 2",
                "quiz_desc": "Some Quiz Description 2",
                "questions": [
                    {
                        "id": 7,
                        "question_text": "Some Title",
                        "possible_answers": [
                            { "id": 25, "answer": "Possible Answer 1", "correct": false }
                            { "id": 26, "answer": "Possible Answer 2", "correct": false }
                            { "id": 27, "answer": "Possible Answer 3", "correct": true }
                            { "id": 28, "answer": "Possible Answer 4", "correct": false }
                        ]
                    },
                    {
                        "id": 8,
                        "question_text": "Some Title 2",
                        "possible_answers": [
                            { "id": 29, "answer": "Possible Answer 1", "correct": false }
                            { "id": 30, "answer": "Possible Answer 2", "correct": false }
                            { "id": 31, "answer": "Possible Answer 3", "correct": false }
                            { "id": 32, "answer": "Possible Answer 4", "correct": true }
                        ]
                    },
                    {
                        "id": 9,
                        "question_text": "Some Title 3",
                        "possible_answers": [
                            { "id": 33, "answer": "Possible Answer 1", "correct": false }
                            { "id": 34, "answer": "Possible Answer 2", "correct": true }
                            { "id": 35, "answer": "Possible Answer 3", "correct": false }
                            { "id": 36, "answer": "Possible Answer 4", "correct": false }
                        ]
                    }
                ]
            }
        ]
    }
]</pre>
        </div>
        <?php
    }
    ?>
    </div>
    <script>
        var runImportBtn = document.getElementById("run-import");
        var runDeleteAllDataBtn = document.getElementById("delete-all-data");
        var runBackBtn = document.getElementById("go-back-to-main");

        runImportBtn.addEventListener('click', runImport);
        runDeleteAllDataBtn.addEventListener('click', runDeleteAllData);
        runBackBtn.addEventListener('click', runBack);

        function runImport(){
            window.location.href = "/wp-admin/admin.php?page=learndash-import&run=true";
        }

        function runDeleteAllData() {
            window.location.href = "/wp-admin/admin.php?page=learndash-import&delete=true";
        }

        function runBack() {
            window.location.href = "/wp-admin/admin.php?page=learndash-import";
        }
    </script>
    <?php
}

function learndash_import_create_question($quiz_master_id, $question_text, $possible_answers){
    global $wpdb;

    $sort_next = ($wpdb->get_results($wpdb->prepare("SELECT count(quiz_id) AS sort_next FROM wp_wp_pro_quiz_question WHERE quiz_id = %d", array($quiz_master_id))))[0]->sort_next;
    $answer_types = array();

    foreach($possible_answers as $possible_answer) {
        $answer_id = $possible_answer->answer_id;
        $answer_text = $possible_answer->answer_text;
        $correct = $possible_answer->correct;

        $answer_type = new WpProQuiz_Model_AnswerTypes();
        $answer_type->setAnswer($answer_text);
        $answer_type->setCorrect($correct);

        $answer_types[] = $answer_type;
    }

    $answer_types = serialize($answer_types);

    $wpdb->insert(QUIZ_QUESTION_TABLE, array(
        "quiz_id"                               => $quiz_master_id,
        "online"                                => 1,
        "sort"                                  => intval($sort_next) + 1,
        "points"                                => 1,
        "title"                                 => "Question #" . (intval($sort_next) + 1),
        "question"                              => $question_text,
        "correct_same_text"                     => 0,
        "tip_enabled"                           => 0,
        "answer_type"                           => "multiple",
        "show_points_in_box"                    => 0,
        "answer_points_activated"               => 0,
        "answer_data"                           => $answer_types,
        "category_id"                           => 0,
        "answer_points_diff_modus_activated"    => 0,
        "disable_correct"                       => 0,
        "matrix_sort_answer_criteria_width"     => 0
    ));
}

function learndash_import_create_course(string $course_id, string $course_title): int {

    // Create the course
    $wp_course_id = wp_insert_post(array(
        "post_title"    => $course_title,
        "post_type"     => COURSES_POST_TYPE,
        "post_status"   => "publish"
    ));

    // Add the associated import id from the json for later reference
    add_post_meta($wp_course_id, "imported_course_id", $course_id, true);

    return $wp_course_id;
}

function learndash_import_delete_all_data() {
    global $wpdb;

    $passed = true;
    $tables = array(WP_POSTS_TABLE, QUIZ_MASTER_TABLE, QUIZ_QUESTION_TABLE, QUIZ_PREREQ_TABLE);
    $wp_tables = array(WP_POSTS_TABLE);

    echo "<pre class='rows-removed-container'>";

    foreach($tables as $table) {

        if(in_array($table, $wp_tables)) {
            $query = array(
                "posts_per_page"        => -1,
                "post_type"             => array('sfwd-quiz', 'sfwd-courses')
            );
            $posts = get_posts($query);

            foreach($posts as $post) {
                wp_delete_post($post->ID);
            }

            $passed = count($posts);
        } else {
            $passed = $wpdb->query($wpdb->prepare("DELETE FROM $table", array()));
        }

        echo "Table: $table | Rows Deleted: $passed<br />";

        if(!is_numeric($passed) && !$passed) {
            echo "</pre>";
            return false;
        }
    }

    echo "</pre>";

    return true;
}

function learndash_import_set_prerequisites($quiz_prereq_info) {
    global $wpdb;

    foreach($quiz_prereq_info as $prereq_info) {
        if($prereq_info["prereq_quiz_uid"] != 'NULL') {
            $wpdb->query($wpdb->prepare("UPDATE " . QUIZ_MASTER_TABLE . " SET prerequisite=%d WHERE id=%d", array(1, intval($prereq_info["quiz_id_master"]))));
            $prereq_master_id = learndash_import_find_prereq_quiz_master_id($prereq_info["prereq_quiz_uid"]);

            $wpdb->insert(QUIZ_PREREQ_TABLE, array(
               "prerequisite_quiz_id"       => intval($prereq_info["quiz_id_master"]),
                "quiz_id"                   => $prereq_master_id
            ));
        }
    }
}

function learndash_import_find_prereq_quiz_master_id($prereq_quiz_uid){
    global $wpdb;

    $master_id = $wpdb->get_results(
        $wpdb->prepare("SELECT meta_value FROM wp_postmeta WHERE post_id IN (
                            SELECT post_id FROM wp_postmeta WHERE meta_value = %s
                         ) AND meta_key = 'quiz_pro_id'", array($prereq_quiz_uid))
    );
    $master_id = $master_id[0]->meta_value;

    return $master_id;
}

function learndash_import_create_quiz(string $quiz_id, string $quiz_title, int $course_id, string $prereq_quiz_id) {
    global $wpdb;

    $quiz_master_inserted_ids = array();

    // Create the quiz
    $wp_quiz_id = wp_insert_post(array(
       "post_title"     => $quiz_title,
        "post_type"     => QUIZ_POST_TYPE,
        "post_status"   => "publish"
    ));

    // Add the associated import id from the json for later reference
    add_post_meta($wp_quiz_id, "imported_quiz_id", $quiz_id, true);
    $quiz_insert_arr = array(
        "name"      => $quiz_title,
        "text"      => ""
    );

    $wpdb->insert(QUIZ_MASTER_TABLE, $quiz_insert_arr);
    $quiz_id_master = $wpdb->insert_id;

    add_post_meta($wp_quiz_id, "course_id", $course_id, true);
    add_post_meta($wp_quiz_id, "lesson_id", 0, true);
    add_post_meta($wp_quiz_id, "quiz_pro_id", $quiz_id_master, true);
    add_post_meta($wp_quiz_id, "quiz_pro_id_$quiz_id_master", $quiz_id_master, true);
    add_post_meta($wp_quiz_id, "_sfwd-quiz", array(
        "sfwd-quiz_course"              => $course_id,
        "sfwd-quiz_quiz_pro"            => $quiz_id_master,
        "sfwd-quiz_repeats"             => 0,
        "sfwd-quiz_threshold"           => 0.7,
        "sfwd-quiz_passingpercentage"   => 70,
        "sfwd-quiz_lesson"              => 0,
        "sfwd-quiz_certificate"         => 0
    ), true);

    return array(
        "wp_quiz_id"        => $wp_quiz_id,
        "quiz_id_master"    => $quiz_id_master,
        "quiz_uid"          => $quiz_id,
        "prereq_quiz_uid"   => $prereq_quiz_id
    );
}