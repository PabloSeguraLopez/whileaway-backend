<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Determinar is_employer e is_employee en base al rol recibido
        $is_employer = ($data['role'] === 'employer') ? 1 : 0;
        $is_employee = ($data['role'] === 'employee') ? 1 : 0;

        $stmt = $db->prepare("INSERT INTO USERS (name, email, hashed_password, is_employer, is_employee, photo_path, address, phone_number, cv) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $data['name'], 
            $data['email'], 
            $data['password'],
            $is_employer,
            $is_employee, 
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
        $stmt = $db->prepare("SELECT hashed_password, id FROM USERS WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        $isValid = $user && ($data['password'] === $user['hashed_password']);
        if ($isValid) {
            $response->getBody()->write(json_encode(['authenticated' => 1, 'id' => $user['id']]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['authenticated' => -1]));
            return $response->withHeader('Content-Type', 'application/json');
        }
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