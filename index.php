<?php
require 'vendor/autoload.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Crear un usuario
$app->post('/users', function (Request $request, Response $response) use ($db) {
    try {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);  // Forzar decodificación JSON

        // Comprobamos si $data sigue siendo null
        if ($data === null) {
            $response->getBody()->write(json_encode(['error' => 'JSON inválido o falta Content-Type: application/json']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validar campos requeridos
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }


        $stmt = $db->prepare("INSERT INTO USERS (name, email, hashed_password, is_employer, is_employee, photo_path, address, phone_number, cv) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $data['name'], 
            $data['email'], 
            $data['password'],
            $data['is_employer'] ?? 0, 
            $data['is_employee'] ?? 0, 
            $data['photo_path'] ?? null, 
            $data['address'] ?? null, 
            $data['phone_number'] ?? null, 
            $data['cv'] ?? null
        ]);

        $response->getBody()->write(json_encode(['id' => $db->lastInsertId()]));
        return $response->withHeader('Content-Type', 'application/json');
    
    } catch (PDOException $e) {
        // Capturar cualquier error de la base de datos y devolver un mensaje genérico
        $response->getBody()->write(json_encode(['error' => 'User creation error']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


// Identificar usuario
$app->post('/users/authenticate', function (Request $request, Response $response) use ($db) {
    try {
        $data = json_decode($request->getBody()->getContents(), true);
        if ($data === null || empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $stmt = $db->prepare("SELECT hashed_password FROM USERS WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        $isValid = $user && ($data['password'] === $user['hashed_password']);
        $response->getBody()->write(json_encode(['authenticated' => $isValid]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Authentication error']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Obtener un usuario
$app->get('/users/{id}', function (Request $request, Response $response, array $args) use ($db) {
    try {
        $stmt = $db->prepare("SELECT id, name, email, is_employer, is_employee, photo_path, address, phone_number, cv FROM USERS WHERE id = ?");
        $stmt->execute([$args['id']]);
        $user = $stmt->fetch();
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode($user));    
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'User retrieval error']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Crear una oferta
$app->post('/offers', function (Request $request, Response $response) use ($db) {
    try {
        $data = json_decode($request->getBody()->getContents(), true);
        if ($data === null || empty($data['employer']) || empty($data['tags']) || empty($data['address'])) {
            $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $stmt = $db->prepare("INSERT INTO OFFERS (employee, employer, tags, address, additional_info, price, timetable) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['employee'] ?? null, $data['employer'],
            $data['tags'], $data['address'], $data['additional_info'] ?? null,
            $data['price'] ?? null, $data['timetable'] ?? null
        ]);
        $response->getBody()->write(json_encode(['id' => $db->lastInsertId()]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Offer creation error']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Obtener una oferta
$app->get('/offers/{id}', function (Request $request, Response $response, array $args) use ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM OFFERS WHERE id = ?");
        $stmt->execute([$args['id']]);
        $offer = $stmt->fetch();
        if (!$offer) {
            $response->getBody()->write(json_encode(['error' => 'Offer not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode($offer));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Offer retrieval error']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Actualizar una oferta
$app->put('/offers/{id}', function (Request $request, Response $response, array $args) use ($db) {
    try {
        $id = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        if ($data === null) {
            $response->getBody()->write(json_encode(['error' => 'JSON inválido o falta Content-Type: application/json']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Verificar si la oferta existe
        $stmt = $db->prepare("SELECT id FROM OFFERS WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $response->getBody()->write(json_encode(['error' => 'Offer not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Construir la consulta dinámicamente
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }

        if (empty($fields)) {
            $response->getBody()->write(json_encode(['error' => 'No fields to update']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $values[] = $id;
        $sql = "UPDATE OFFERS SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Offer update error']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


// Eliminar una oferta
$app->delete('/offers/{id}', function (Request $request, Response $response, array $args) use ($db) {
    try {
        $stmt = $db->prepare("DELETE FROM OFFERS WHERE id = ?");
        $stmt->execute([$args['id']]);
        $response->getBody()->write(json_encode(['message' => 'Offer deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Offer deletion error']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();
