<?php

$app->route('get', '{app}', function ($args) {
    $app = $args['app'];
    $root = realpath(dirname($this->server['DOCUMENT_ROOT']) . '/' . $app . '/models/');
	if (empty($root)) {
        $this->write('<i>No models directory found.</i>');
		return;
	}

    $cwd = $this->getParam('p');

    if ($this->getParam('v')) {
		$docer = new \IMP\Docer();

        $this->render('docer.html', [
            'app' => $app,
            'back' => $cwd,
            'doc' => $docer->renderDoc($root . '/' . $cwd . '/' . $_GET['v'], ['hideProtected'=>true, 'hidePrivate'=>true]),
        ]);
	} else {
		$fileWalker = new \IMP\FileWalker($root . '/' . $cwd);
		$fileWalker->setReturnDirectories(true);
		$fileWalker->setRecursiveWalk(false);

        $dirs = array();
        while ($fileWalker->walk()) {
            if (!$fileWalker->isDir) { continue; }
            $dirs[$fileWalker->filename] = $fileWalker->relativeFile;
        }

        $files = array();
        while ($fileWalker->walk()) {
            if ($fileWalker->isDir) { continue; }
            $files[$fileWalker->filename] = $fileWalker->relativeFile;
        }

        $back = dirname($cwd);
        if ($back === '/' || $back === '\\') { $back = ''; }

        $this->render('list.html', [
            'app' => $app,
            'cwd' => $cwd,
            'back' => $back,
            'dirs' => $dirs,
            'files' => $files,
        ]);
	}
});
