<?php
namespace Deployer;

use Deployer\Exception\Exception;
use Deployer\Type\Csv;
use function Deployer\Support\str_contains;

/**
 * preparing remotehost for deployment, creating directories
 */
task('deploy:prepare', function () {
    //Check if shell is POSIX-compliant
    $result = run('echo $0');
    
    if (!str_contains($result, 'bash') && !str_contains($result, 'sh')) {
        throw new \RuntimeException(
            'Shell on your server is not POSIX-compliant. Please change to sh, bash or similar.'
            );
    }
    
    //create deploy path folder
    run('if [ ! -d {{deploy_path}} ]; then mkdir -p {{deploy_path}}; fi');
    
    //Check for existing /current directory (not symlink)
    $result = test('[ ! -L {{deploy_path}}/current ] && [ -d {{deploy_path}}/current ]');
    if ($result) {
        throw new Exception('There already is a directory (not symlink) named "current" in ' . get('deploy_path') . '. Remove this directory so it can be replaced with a symlink for atomic deployments.');
    }
    
    //Create releases dir.
    run("cd {{deploy_path}} && if [ ! -d releases ]; then mkdir releases; fi");
    
    //Create shared dir.
    run("cd {{deploy_path}} && if [ ! -d shared ]; then mkdir shared; fi");
    
    //Create .dep dir and releases log file.
    run("cd {{deploy_path}} && if [ ! -d .dep ]; then mkdir .dep; fi");
    run("cd {{deploy_path}} && if [ ! -f .dep/releases ]; then touch .dep/releases; fi");
    
    //test if the release already exists on server, if not, create folder
    $result = test('[ -d {{deploy_path}}/{{release_path}} ] ');
    if ($result) {
        throw new Exception('The selected release "' . get('release_name') . '" does already exist on the server!');
    } else {
        run('cd {{basic_deploy_path_cygwin}}/{{relative_deploy_path}} && mkdir {{release_path}}');
    }
});

/**
 * fix permissions for directories
 */
task('deploy:fix_permissions', function() {
    run('chmod +x {{deploy_path}}/{{release_path}}');
    run('chmod +x {{deploy_path}}/{{release_path}}/vendor/bin');
});

/**
 * copy directories given in 'copy_dirs' array
 */
task('deploy:copy_directories', function () {
    $copyDirArray = get('copy_dirs');
    $hasPreviousRelease = false;
    set('target_dir_cygwin', get('basic_deploy_path_cygwin') . '/' . get('relative_deploy_path') . '/' . get('release_path') );
    set('target_dir', get('deploy_path'). '/'. get('release_path'));
    //check if symlink 'curent' exists, if so, set path to it as previous_release
    if (test("[ -L {{deploy_path}}/current ]")) {        
        set('cygwin_path_previous_release', get('basic_deploy_path_cygwin') . '/' . get('relative_deploy_path') . '/current');
        $hasPreviousRelease = true;
    }        
    foreach ($copyDirArray as $dirToCopy) {
        set('current_copy_dir' , $dirToCopy );
        run('cd {{target_dir}} && mkdir -p {{current_copy_dir}}');
        if ($hasPreviousRelease === true) {
            if(test( '[ -d {{cygwin_path_previous_release}}/{{current_copy_dir}} ]')) {
                run('cp -rf {{cygwin_path_previous_release}}/{{current_copy_dir}}/. {{target_dir}}/{{current_copy_dir}}/');
            }
        }
    }  
    
    // make sure config dir exists and copy files form base-config folder that dont already exist
    if (!test('[ -d {{basic_deploy_path_cygwin}}/{{relative_deploy_path}}/{{release_path}}/{{config_dir}} ]')) {        
        run('cd {{basic_deploy_path_cygwin}}/{{relative_deploy_path}}/{{release_path}}/ && chmod +w ./ && mkdir -p {{config_dir}}');
    }
    
    // TODO #deploy_config add support for deployment configs and default_app_config instead 
    // of the old base-config folder
    /*run('cp -rn {{target_dir}}/base-config/*.* {{target_dir}}/{{config_dir}}/');
    run('rm -rf {{target_dir}}/base-config');
    writeln('Generated initial configuration files.');*/
    writeln('Host config files not updated: please review ' . get('target_dir') . '/' . get('config_dir') . ' manually!');
});

/**
 * create 'current' and 'exface' symlink to new release
 */
task('deploy:create_symlinks', function() {
    run("cd {{basic_deploy_path_cygwin}}/{{relative_deploy_path}}  && {{bin/symlink}} {{release_path}} current");
    run("cd {{basic_deploy_path_cygwin}} && {{bin/symlink}} {{relative_deploy_path}}/current exface");
});

/**
 * create links to shared directories
 */
task('deploy:create_shared_links', function() {
    foreach (get('shared_dirs') as $dir) {
        $shared_dir = get('deploy_path') .'/shared';
        run('cd {{deploy_path}}; export set CYGWIN=winsymlinks:nativestrict; ln -nfs --relative ' . $shared_dir . '/' . $dir . ' ' . get('release_path') . '/' . $dir);
    };
});

/**
 * delete old releases
 */
task('deploy:cleanup_old_releases', function() {
    cd('{{deploy_path}}');
    
    // If there is no releases return empty list.
    if (!test('[ -d releases ] && [ "$(ls -A releases)" ]')) {
        $releases = [];
    }
    
    // Will list only dirs in releases.
    $list = explode("\n", run('cd releases && ls -t -1 -d */'));
    
    // Prepare list.
    $list = array_map(function ($release) {
        return basename(rtrim(trim($release), '/'));
    }, $list);
        
    $releases = []; // Releases list.
    
    // Collect releases based on .dep/releases info.
    // Other will be ignored.
    
    if (test('[ -f .dep/releases ]')) {
        $keepReleases = get('keep_releases');
        if ($keepReleases === -1) {
            $csv = run('cat .dep/releases');
        } else {
            // Instead of `tail -n` call here can be `cat` call,
            // but on hosts with a lot of deploys (more 1k) it
            // will output a really big list of previous releases.
            // It spoils appearance of output log, to make it pretty,
            // we limit it to `n*2 + 5` lines from end of file (15 lines).
            // Always read as many lines as there are release directories.
            $csv = run("tail -n " . max(count($releases), ($keepReleases * 2 + 5)) . " .dep/releases");
        }
        
        $metainfo = Csv::parse($csv);
        
        for ($i = count($metainfo) - 1; $i >= 0; --$i) {
            if (is_array($metainfo[$i]) && count($metainfo[$i]) >= 2) {
                list(, $release) = $metainfo[$i];
                $index = array_search($release, $list, true);
                if ($index !== false) {
                    $releases[] = $release;
                    unset($list[$index]);
                }
            }
        }
    }
    
    // Metainfo.
    $date = run('date +"%Y%m%d%H%M%S"');
    
    // Save metainfo about release
    run("echo '{$date},{{release_name}}' >> .dep/releases");
    
    $releaseName = get('release_name');
    
    // Add to releases list
    array_unshift($releases, $releaseName);
    
    $keep = get('keep_releases');
    
    if ($keep === -1) {
        // Keep unlimited releases.
        return;
    }
    
    while ($keep > 0) {
        array_shift($releases);
        --$keep;
    }
    
    foreach ($releases as $release) {
        run("rm -rf {{deploy_path}}/releases/{$release}");
    }
});

/**
 * show new release path
 */
task('deploy:show_release_names', function () {
    writeln('Deployed to new release: {{deploy_path}}/{{release_path}}');
    /*if(has('previous_release')) {
        writeln('Previous release: {{previous_release}}');
    }*/
});

/**
 * show success message
 */
task('deploy:success', function () {
    writeln('<info>Successfully deployed!</info>');
})
->local()
->shallow()
->setPrivate();