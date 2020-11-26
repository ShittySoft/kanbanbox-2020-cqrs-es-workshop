<?php

declare(strict_types=1);

namespace Building\App;

use Building\Domain\Command;
use Interop\Container\ContainerInterface;
use Prooph\ServiceBus\CommandBus;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Assert\Assert;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Zend\Expressive\Application;
use Zend\Expressive\Router\FastRouteRouter;

call_user_func(function () : void {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    /** @var ContainerInterface $sm */
    $sm = require __DIR__ . '/../container.php';

    //////////////////////////
    // Routing/frontend/etc //
    //////////////////////////

    // Error handling so that our eyes don't bleed: don't do this in production!
    $whoopsHandler = new PrettyPageHandler();
    $whoops        = new Run();

    $whoops->writeToOutput(true);
    $whoops->allowQuit(true);
    $whoops->pushHandler($whoopsHandler);

    $app = new Application(new FastRouteRouter(), $sm);

    $app->raiseThrowables();
    $app->pipeRoutingMiddleware();

    $getBodyParameter = static function (Request $request, string $parameter) : string
    {
        $body = $request->getParsedBody();

        Assert::isArray($body);
        Assert::isMap($body);
        Assert::keyExists($body, $parameter);

        $value = $body[$parameter];

        Assert::string($value);

        return $value;
    };

    $app->get('/', function (Request $request, Response $response) : Response {
        ob_start();
        require __DIR__ . '/../template/index.php';
        $content = ob_get_clean();

        $response->getBody()->write($content);

        return $response;
    });

    $app->post('/register-new-building', function (Request $request, Response $response) use ($sm, $getBodyParameter) : Response {
        $commandBus = $sm->get(CommandBus::class);
        $commandBus->dispatch(Command\RegisterNewBuilding::fromName($getBodyParameter($request, 'name')));

        return $response
            ->withStatus(302, 'Found')
            ->withAddedHeader('Location', '/');
    });

    $app->get('/building/{buildingId}', function (Request $request, Response $response) : Response {
        $buildingId = Uuid::fromString($request->getAttribute('buildingId'));

        ob_start();
        require __DIR__ . '/../template/building.php';
        $content = ob_get_clean();

        $response->getBody()->write($content);

        return $response;
    });

    $app->post('/checkin/{buildingId}', function (Request $request, Response $response) use ($sm, $getBodyParameter) : Response {
        $sm->get(CommandBus::class)
            ->dispatch(Command\CheckIn::fromBuildingAndUsername(
                Uuid::fromString($request->getAttribute('buildingId')),
                $getBodyParameter($request, 'username')
            ));

        return $response
            ->withStatus(302, 'Found')
            ->withAddedHeader('Location', '/building/' . $request->getAttribute('buildingId'));
    });

    $app->post('/checkout/{buildingId}', function (Request $request, Response $response) use ($sm) : Response {
        throw new \BadFunctionCallException('To be implemented: I should dispatch a command and redirect back to the previous page');
    });

    $app->pipeDispatchMiddleware();

    $whoops->register();
    $app->run();
});
