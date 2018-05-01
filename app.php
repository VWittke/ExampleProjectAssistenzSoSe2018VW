<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

# [START all]
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
require('CloudStorage.php');
use BeispielApp\FileSystem\CloudStorage;

// create the Silex application
$app = new Application();
$app->register(new TwigServiceProvider());
$app['twig.path'] = [ __DIR__ ];

// get entries from database ad show them on the website
$app->get('/', function () use ($app) {
    /** @var PDO $db */
    $db = $app['database'];
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    // Show existing chat entries.
    $results = $db->query('SELECT * from pictures');

    return $twig->render('cloudsql.html.twig', [
        'results' => $results,
    ]);
});

// insert entries into database
$app->post('/', function (Request $request) use ($app) {
    /** @var PDO $db */
    $db = $app['database'];
	$cb = $app['storage'];
    $files = $request->files;
    $image = $files->get('datei');
	if ($image) {
        $datei = $cb->storeFile(
            $image->getRealPath(),
            $image->getMimeType()
        );
    } else {
		return $request->request->get('bildunterschrift');
		
	}
    $bildunterschrift = $request->request->get('bildunterschrift');

    if ($datei && $bildunterschrift) {
        $stmt = $db->prepare('INSERT INTO pictures (pictureURL, bildU) VALUES (:datei, :bildunterschrift)');
        $stmt->execute([
            ':datei' => $datei,
            ':bildunterschrift' => $bildunterschrift,
        ]);
    }

    return $app->redirect('/');
});

// function to return the PDO instance
$app['database'] = function () use ($app) {
    // Connect to CloudSQL from App Engine.
    $dsn = getenv('MYSQL_DSN');
    $user = getenv('MYSQL_USER');
    $password = getenv('MYSQL_PASSWORD');
    if (!isset($dsn, $user) || false === $password) {
        throw new Exception('Set MYSQL_DSN, MYSQL_USER, and MYSQL_PASSWORD environment variables');
    }

    $db = new PDO($dsn, $user, $password);

    return $db;
};
# [END all]

$app['storage'] = function () use ($app) {
    /** @var array $config */
    $projectId = getenv('GPROJECT_ID');
    $bucketName = $projectId . '.appspot.com';
    return new CloudStorage($projectId, $bucketName);
};

// create table if it not exists (with your URL + create_tables)
$app->get('/create_tables', function () use ($app) {
    /** @var PDO $db */
    $db = $app['database'];
    # [START create_tables]
    // create the tables
    $stmt = $db->prepare('CREATE TABLE IF NOT EXISTS pictures ('
        . 'idpictures INT NOT NULL AUTO_INCREMENT, '
        . 'pictureURL VARCHAR(255), '
        . 'bildU VARCHAR(255), '
        . 'PRIMARY KEY(idpictures))');
    $result = $stmt->execute();
    # [END create_tables]

    if (false === $result) {
        return sprintf("Error: %s\n", $stmt->errorInfo()[2]);
    } else {
        return 'Tables created';
    }
});

$app->get('/delete/{id}', function ($id) use ($app) {
	$db = $app['database'];
	
	    if ($id) {
        $stmt = $db->prepare('DELETE FROM pictures WHERE idpictures = :id;');
        $stmt->execute([
            ':id' => $id,
        ]);
		}
    return $app->redirect('/');
});

return $app;
