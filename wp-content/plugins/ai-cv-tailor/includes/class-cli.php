<?php

class AI_CV_Tailor_CLI_Command {
    public function autopilot($args, $assoc_args) {
        $subcommand = $args[0] ?? null;

        switch ($subcommand) {
            case 'fetch':
                do_action('ai_cv_tailor_autopilot_fetch', $assoc_args);
                WP_CLI::success('Autopilot fetch completed.');
                break;

            case 'analyze':
                do_action('ai_cv_tailor_autopilot_analyze', $assoc_args);
                WP_CLI::success('Autopilot analyze completed.');
                break;

            case 'generate-applications':
                do_action('ai_cv_tailor_generate_applications', $assoc_args);
                WP_CLI::success('Application generation completed.');
                break;

            case 'digest':
                do_action('ai_cv_tailor_autopilot_digest', $assoc_args);
                WP_CLI::success('Digest completed.');
                break;

            case 'run-debug':
                $this->run_debug();
                break;

            case 'reset-test':
                $this->reset_test();
                break;

            case 'list-jobs':
                $this->list_jobs();
                break;

            case 'list-applications':
                $this->list_applications();
                break;

            default:
                WP_CLI::error('Unknown autopilot command. Use fetch, analyze, generate-applications, digest, run-debug, reset-test, list-jobs, list-applications.');
        }
    }

    public function followups($args, $assoc_args) {
        do_action('ai_cv_tailor_followups_due', $assoc_args);
        WP_CLI::success('Followups checked.');
    }

    public function links($args, $assoc_args) {
        do_action('ai_cv_tailor_links_expire', $assoc_args);
        WP_CLI::success('Expired links checked.');
    }

    public function stats($args, $assoc_args) {
        do_action('ai_cv_tailor_stats_cleanup', $assoc_args);
        WP_CLI::success('Stats cleanup completed.');
    }

    public function run_debug() {
        AI_CV_Tailor_Autopilot_Logger::info('--- RUN DEBUG STARTED ---');
        WP_CLI::log('--- RUN DEBUG STARTED ---');

        // 1. Lue ai_cv_tailor_autopilot_sources
        $sources = get_option( 'ai_cv_autopilot_sources_list', array() );
        
        // 2. Tulosta sources count
        $count = count( $sources );
        WP_CLI::log( "Sources count: {$count}" );
        AI_CV_Tailor_Autopilot_Logger::info( "Sources count: {$count}" );

        // 3. Aja fetch
        WP_CLI::log('Running fetch...');
        do_action('ai_cv_tailor_autopilot_fetch');

        // 4. Tulosta montako freelance_job löytyi
        // (Fetch function already logs the count, but let's query all to be sure)
        $jobs = get_posts(array(
            'post_type' => 'freelance_job',
            'post_status' => 'any',
            'posts_per_page' => -1,
        ));
        $job_count = count($jobs);
        WP_CLI::log("Total freelance_job posts: {$job_count}");
        AI_CV_Tailor_Autopilot_Logger::info("Total freelance_job posts: {$job_count}");

        // 5. Tulosta kaikki freelance_job ID:t
        // 6. Tulosta autopilot_processed meta
        foreach ($jobs as $job) {
            $processed = get_post_meta($job->ID, 'autopilot_processed', true);
            $processed_text = empty($processed) ? 'No' : $processed;
            WP_CLI::log("ID: {$job->ID} | Title: {$job->post_title} | Processed: {$processed_text}");
            AI_CV_Tailor_Autopilot_Logger::info("ID: {$job->ID} | Title: {$job->post_title} | Processed: {$processed_text}");
        }

        // 7. Aja analyze
        WP_CLI::log('Running analyze...');
        do_action('ai_cv_tailor_autopilot_analyze');

        // 8. Tulosta match score
        // We will just query again to print the newly generated match scores
        $analyzed_jobs = get_posts(array(
            'post_type' => 'freelance_job',
            'post_status' => 'any',
            'posts_per_page' => -1,
        ));
        foreach ($analyzed_jobs as $job) {
            $score = get_post_meta($job->ID, 'match_score', true);
            if ($score !== '') {
                WP_CLI::log("ID: {$job->ID} | Match Score: {$score}");
                AI_CV_Tailor_Autopilot_Logger::info("ID: {$job->ID} | Match Score: {$score}");
            }
        }

        // 9. Aja generate-applications (assuming min_score=75)
        WP_CLI::log('Running generate-applications...');
        do_action('ai_cv_tailor_generate_applications', array('min-score' => 75));

        // 10. Tulosta luodut ai_cv_application ID:t
        $apps = get_posts(array(
            'post_type' => 'ai_cv_application',
            'post_status' => 'any',
            'posts_per_page' => -1,
        ));
        WP_CLI::log("Total ai_cv_application posts: " . count($apps));
        AI_CV_Tailor_Autopilot_Logger::info("Total ai_cv_application posts: " . count($apps));
        foreach ($apps as $app) {
            WP_CLI::log("Application ID: {$app->ID} | Title: {$app->post_title}");
            AI_CV_Tailor_Autopilot_Logger::info("Application ID: {$app->ID} | Title: {$app->post_title}");
        }

        WP_CLI::success('Run debug completed.');
        AI_CV_Tailor_Autopilot_Logger::success('Run debug completed.');
    }

    public function reset_test() {
        $jobs = get_posts(array(
            'post_type' => 'freelance_job',
            'post_status' => 'any',
            'posts_per_page' => -1,
        ));

        $count = 0;
        foreach ($jobs as $job) {
            delete_post_meta($job->ID, 'autopilot_processed');
            delete_post_meta($job->ID, 'match_score');
            delete_post_meta($job->ID, 'generated_application_id');
            // reset status to New to allow analysis again
            update_post_meta($job->ID, 'status', 'New');
            $count++;
        }
        
        WP_CLI::success("Reset completed. {$count} freelance_job posts were reset.");
    }

    public function list_jobs() {
        $jobs = get_posts(array(
            'post_type' => 'freelance_job',
            'post_status' => 'any',
            'posts_per_page' => -1,
        ));
        
        $items = array();
        foreach ($jobs as $job) {
            $items[] = array(
                'ID' => $job->ID,
                'Title' => $job->post_title,
                'Status' => get_post_meta($job->ID, 'status', true),
                'Match Score' => get_post_meta($job->ID, 'match_score', true),
                'Processed' => get_post_meta($job->ID, 'autopilot_processed', true),
                'Source URL' => get_post_meta($job->ID, 'source_url', true),
            );
        }
        
        WP_CLI\Utils\format_items('table', $items, array('ID', 'Title', 'Status', 'Match Score', 'Processed', 'Source URL'));
    }

    public function list_applications() {
        $apps = get_posts(array(
            'post_type' => 'ai_cv_application',
            'post_status' => 'any',
            'posts_per_page' => -1,
        ));
        
        $items = array();
        foreach ($apps as $app) {
            $items[] = array(
                'ID' => $app->ID,
                'Title' => $app->post_title,
                'Job ID' => get_post_meta($app->ID, 'source_freelance_job_id', true),
                'Created' => $app->post_date,
            );
        }
        
        WP_CLI\Utils\format_items('table', $items, array('ID', 'Title', 'Job ID', 'Created'));
    }
}
