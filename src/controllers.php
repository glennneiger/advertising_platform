<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Controller\AdvertController;
use Controller\CategoryController;
use Controller\AuthController;
use Controller\UserController;
use Controller\MessageController;
use Controller\HomepageController;

$app->mount('/', new HomepageController());
$app->mount('/advert', new AdvertController());
$app->mount('/advert-photo', new Controller\AdvertPhotoController());
$app->mount('/category', new CategoryController());
$app->mount('/auth', new AuthController());
$app->mount('/user', new UserController());
$app->mount('/message', new MessageController());

//Request::setTrustedProxies(array('127.0.0.1'));

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
