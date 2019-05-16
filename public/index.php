<?php
require ("../vendor/autoload.php"); // lier à l'autoloader
header("Access-Control-Allow-Origin: *");

//utilisation d'un Middleware opérations à exécuter automatiquement lors du retour vers le client
class DemoMiddleware {
    public function __invoke(\Slim\Http\Request $request,\Slim\Http\Response $response,$next){
        $debut = microtime();
        $response->write("<p style='margin-bottom: 5px;border-bottom-style: solid;border-bottom-width: thin;font-weight: bold'>Middleware</p>");
        $response = $next($request,$response);
        $fin = microtime();
        $diff = $fin - $debut;
        $response->write("<p style='margin-top: 5px;border-top-style: solid;border-top-width: thin;font-weight: bold'>execution time: " . $diff . "</p>");
        return $response;
    }
}
// instancier la classe App
$app = new \Slim\App([
    // demande afficher erreurs au niveau du framework slim3
    'settings'=> [
        'displayErrorDetails' => true
    ]
]);

// *********************************************************************************************
// gérer le container
$container = $app->getContainer();
// dans ce tableau on crée des fonctions anonymes pour générer des objets dans le framework


// définir l'objet pdo
$container['pdo'] = function(){
// utiliser une connexion à la base de données
// tester et vérifier le namespace
// à changer cf classe Parameters.php attention namespace \app\entitiesTools\ correspond au chemin ...
    $dsn = "mysql:host=".\app\entitiesTools\Parameters::SERVER.";dbname=".\app\entitiesTools\Parameters::DATABASE.";port=".\app\entitiesTools\Parameters::PORT;
    $user = \app\entitiesTools\Parameters::USER;
    $password = \app\entitiesTools\Parameters::PASSWORD;
    \app\dao\MyPDO::parametres($dsn,$user,$password);
    \app\dao\MyPDO::param_charset(true);
    \app\dao\MyPDO::debug_off();
    $pdo = \app\dao\MyPDO::getInstancePDO();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

// définir l'objet wiews
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('../app/views', [
        'cache' => false //'path/to/cache'
    ]);
    // définir l'objet view en mentionnat correctement le chemin - path du dossier views
    // ici en test ne pas mettre de cache ...

    // Instantiate and add Slim specific extension
    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};
// *********************************************************************************************


// utiliser le MiddleWare
// $app->add(new DemoMiddleware());
// tester la méthode get sur l'objet $app
// avec fonction callBack
// route sans paramètre get
/*
 * définir un virtualHost pointant vers le dossier public
 * ou lancer le serveur en ligne de commande en mentionnant le dossier public
 * php -S localhost:8080 -t public/ -ddisplay_errors=1 -dzned_extension=xdebug.so
 * attention modifier le path de windows (variables d'environnement) pour rajouter un path pour localiser le fichier php.exe
 * pour utiliser en ligne de commande ...
 */
// slim gère les différentes routes au lieu d'utiliser un fichier .htaccess à la root du dossier ...
// revoir la gestion des .htaccess


// route1 (tester affichage)
$app->get("/",function (\Slim\Http\Request $request, \Slim\Http\Response $response){
    // return $response->getBody()->write("hello world");
    return $response->write("hello world <br/>"); // version courte
});
// ok
// revoir la récupération d'une variable get et la passer au controller externe
$app->get("/test",function (\Slim\Http\Request $request, \Slim\Http\Response $response){
    // return $response->getBody()->write("hello world");
    return $response->write("tester URL différente que / <br/>"); // version courte
});
// ok
/*
 * attention AllOverride à All + .htaccess ...
 */


$app->get("/liste",function (\Slim\Http\Request $request, \Slim\Http\Response $response){
    // récupérer l'administrateur
    // requête select
    $sql = "SELECT * FROM admin;";
    $result = $this->get('pdo')->query($sql); // on appelle l'objet container et ensuite on récupère un pbjet pdo
    $resultSet = $result->fetchAll();

    $message = "";
    foreach ($resultSet as $value){
        $message .= "lastname: " . $value['lastname'] . "<br>";
        $message .= "firstname: " . $value['firstname'] . "<br>";
        $message .= "email: " . $value['email'] . "<br>";
        $message .= "<hr>";
    }
    return $response->write($message);
});
//ok

// tester récupération variables get à partir de la route
$app->get("/salut/{lastname}/{firstname}",function (\Slim\Http\Request $request, \Slim\Http\Response $response){
    $nom = $request->getAttribute('lastname');
    $prenom = $request->getAttribute('firstname');
    $message = "bonjour " . $nom . " " . $prenom  . "<br>";
    return $response->write($message);
});
// ok
$app->get("/salut2/{lastname}/{firstname}",function (\Slim\Http\Request $request, \Slim\Http\Response $response){
    $array = $request->getAttributes(); // on récupère un tableau ...
    $message = "bonjour " . $array['lastname'] . " " . $array['firstname']  . "<br>";
    return $response->write($message);
});
// ok


// appel objet controller TestPDO + méthode tester (le controller se trouve dans le dossier voir MVC)
$app->get("/liste3",\app\controllers\TestPDO::class . ":tester");
// route vers controller

// route avec Controller et View ...
$app->get("/liste4",\app\controllers\TestPDOTwig::class . ":tester");
// attention bien définir $view
$app->get("/liste5/{id}",\app\controllers\TestPDOTwig2::class . ":tester");


// pour tester la récupération d'une variable type post et retour d'une structure json en premier lieu
// puis XML à traiter dans la page html
$app->get("/test_ajax/{id}",\app\controllers\TestAjax::class . ":tester");
// requête en mode post


//https://www.cloudways.com/blog/twig-templates-in-slim/
//https://www.grafikart.fr/tutoriels/slim-framework-831
$app->run();
?>