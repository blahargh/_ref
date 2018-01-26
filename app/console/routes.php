<?php

$app->route('get', '{app}', function ($args) {
    $app = $args['app'];

    $this->render('home.html', [
        'app' => $app,
    ]);
});


$app->route('get', '{app}/load', function ($args) {
    $app = $args['app'];
    $dir = getenv('APPLOGS');
    $file = "$dir/console_{$app}.txt";
    $output = file_exists($file) ? file_get_contents($file) : '[FILE NOT FOUND]';
    $this->write($output);
});


$app->route('get', '{app}/clear', function ($args) {
    $app = $args['app'];
    $dir = getenv('APPLOGS');
    $file = "$dir/console_{$app}.txt";
    file_put_contents($file, '');
});
