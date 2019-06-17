<?php

namespace Deployer;

// require 'recipe/laravel.php';
require 'recipe/common.php';

set('repository', 'git@github.com:abhayagiri/abhayagiri-website.git');
set('git_tty', true); // [Optional] Allocate tty for git on first deployment
set('shared_files', [
    '.env',
    'public/head.html',
    'database/PostImportDatabase.php',
]);
set('shared_dirs', [
    'storage/app/public',
    'storage/backups',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'storage/tmp',
    'public/ai-cache',
    'public/exports',
    'public/media',
]);
set('writable_dirs', []);

host('staging.abhayagiri.org')
    ->stage('staging')
    ->user('abhayagiri_staging')
    ->identityFile('~/.ssh/id_rsa')
    ->set('bin/php', '/usr/local/php73/bin/php')
    ->set('deploy_path', '/home/abhayagiri_staging/staging.abhayagiri.org')
    ->set('local_build_path', 'storage/builds/staging');

host('www.abhayagiri.org')
    ->stage('production')
    ->user('abhayagiri')
    ->identityFile('~/.ssh/id_rsa')
    ->set('bin/php', '/usr/local/php73/bin/php')
    ->set('deploy_path', '/home/abhayagiri/www.abhayagiri.org')
    ->set('local_build_path', 'storage/builds/production');

// /**
//  * Main task
//  */
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy');
after('deploy', 'success');

task('deploy:upload-new-assets', function() {
    # Copy .env from remote (if possible).
    $host = Task\Context::get()->getHost();
    runLocally('scp ' . $host . ':{{deploy_path}}/shared/.env ' .
               '{{local_build_path}}/.env || true');
    runLocally('cd {{local_build_path}} && npm install');
    runLocally('cd {{local_build_path}} && npm run build');
    upload('{{local_build_path}}/public/new/', '{{release_path}}/public/new');
})->desc('Upload New Assets');
before('deploy:symlink', 'deploy:upload-new-assets');

task('deploy:stamp', function() {
    run('cd {{release_path}} && {{bin/php}} artisan app:stamp');
})->desc('Stamp deployment');
after('deploy:upload-new-assets', 'deploy:stamp');

task('deploy:restart-php-processes', function() {
    run('killall php73.cgi || true');
})->desc('Restart PHP processes');
after('deploy:symlink', 'deploy:restart-php-processes');

task('deploy:migrate', function() {
    write(run('cd {{deploy_path}}/current && {{bin/php}} artisan migrate --force'));
})->desc('Run database migrations');
after('deploy:symlink', 'deploy:migrate');

task('deploy:import-database', function() {
    $stage = input()->getArgument('stage');
    if ($stage == 'production') {
        throw new Exception('Not to be run on production');
    }
    run('cd {{deploy_path}}/current && {{bin/php}} artisan app:import-database');
})->desc('Import database')
  ->onStage('staging');

task('deploy-and-import', [
    'deploy',
    'deploy:import-database',
])->desc('Deploy and import database');
