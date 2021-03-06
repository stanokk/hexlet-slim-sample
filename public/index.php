<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use App\Validator;

session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);


//$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);


$router = $app->getRouteCollector()->getRouteParser();
$repo = new App\UserRepository();

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
    $filteredNames = array_filter($users, fn($item) => str_contains(strtolower($item['nickname']), strtolower($term)) ? $item : null);
    $messages = $this->get('flash')->getMessages();
    $params = ['filteredNames' => $filteredNames, 'term' => $term, 'flash' => $messages];
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
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('newUser');

$app->get('/users/{id}', function ($request, $response, $args) use ($users) {
    $filtered = array_filter($users, fn($item) => $item['id'] === $args['id'] ? $item : null);
    if ($filtered === []) {
        return $response->write('User with this ID does not exist')->withStatus(404);
    }
    $array = array_map(fn($item) => $item['nickname'], $filtered);
    $nickname = array_shift($array);
    $params = ['id' => $args['id'], 'nickname' => $nickname];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->get('/users/{id}/edit', function ($request, $response, array $args) use ($repo) {
    //$repo = new App\UserRepository();
    $id = $args['id'];
    $user = $repo->find($id);
    $params = [
        'user' => $user,
        'errors' => [],
        'data' =>$user
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');

$app->patch('/users/{id}', function ($request, $response, array $args) use ($repo, $router)  {
    //$repo = new App\UserRepository();
    $id = $args['id'];
    $user = $repo->find($id);
    $data = $request->getParsedBodyParam('user');

    $validator = new Validator();
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        // Ручное копирование данных из формы в нашу сущность
        $user['nickname'] = $data['nickname'];
        $this->get('flash')->addMessage('success', 'User has been updated');
        $repo->save($user);
        $url = $router->urlFor('editUser', ['id' => $user['id']]);
        return $response->withRedirect($url);
    }

    $params = [
        'user' => $user,
        'errors' => $errors,
        'data' => $data
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});



$app->post('/users', function ($request, $response) use ($router) {
    $repo = new App\UserRepository();
    $userData = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($userData);
    if (count($errors) === 0) {
        $repo->save($userData);
        $this->get('flash')->addMessage('success', 'User was added successfully');
        $url = $router->urlFor('users');
        return $response->withRedirect($url, 302);
    }
    $params = [
        'userData' => $userData,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->run();