<?php

$app->route('get', '{app}', function ($args) {
    $app = $args['app'];
    $this->render('home.html', [
        'app' => $app,
    ]);
});
