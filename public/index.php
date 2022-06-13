<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);


$router = $app->getRouteCollector()->getRouteParser();

//$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];
$users = json_decode(file_get_contents('people.json'), true);


$app->get('/', function ($request, $response) use ($router) {
    $router->urlFor('users');
    $router->urlFor('user', ['id' => '62a468ef40f7c']);
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});

$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $filteredNames = array_filter($users, fn($item) => str_contains(strtolower($item['user']['nickname']), $term) ? $item : null);
    $params = ['filteredNames' => $filteredNames, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

/*$app->post('/users', function ($request, $response) {
    return $response->withStatus(302);
});*/

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('course');

$app->get('/users/new', function ($request, $response) {
    $id = uniqid();
    $params = [
        'user' => ['id' => $id, 'nickname' => '', 'email' => '']
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('newUser');

$app->get('/users/{id}', function ($request, $response, $args) use ($users) {
    $filtered = array_filter($users, fn($item) => $item['user']['id'] === $args['id'] ? $item : null);
    if ($filtered === []) {
        return $response->write('404 Page not found')->withStatus(404);
    }
    $nickname = array_shift(array_map(fn($item) => $item['user']['nickname'], $filtered));
    $params = ['id' => $args['id'], 'nickname' => $nickname];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBody();
    $file = 'people.json';
    $current = file_get_contents($file);
    empty($current) ? $updated = [] : $updated = json_decode($current, true);
    $updated[] = $user;
    file_put_contents($file, json_encode($updated));
    return $response->withRedirect('users', 302);
});

$app->run();