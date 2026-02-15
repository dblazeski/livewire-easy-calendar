<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

Route::get('/', fn () => redirect('/docs'));

Route::get('/docs', fn () => view('docs.index'));

Route::get('/docs/{page}', function (string $page) {
    if (! preg_match('/\A[a-z0-9\/-]+\z/', $page)) {
        abort(404);
    }

    $view = 'docs.'.str_replace('/', '.', $page);

    abort_unless(View::exists($view), 404);

    return view($view);
})->where('page', '.*');
