<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Crear una oferta
$app->post('/offers', function (Request $request, Response $response) use ($db) {
    try {
        $data = json_decode($request->getBody()->getContents(), true);
        if ($data === null || empty($data['employer']) || empty($data['tags']) || empty($data['address']) || empty($data['title']) || empty($data['category'])) {
            $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $stmt = $db->prepare("INSERT INTO OFFERS (employee, employer, tags, address, additional_info, price, timetable, title, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['employee'] ?? null, $data['employer'],
            $data['tags'], $data['address'], $data['additional_info'] ?? null,
            $data['price'] ?? null, $data['timetable'] ?? null,
            $data['title'], $data['category']
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

// Obtener todas las ofertas con filtros opcionales desde la URL
$app->get('/offers', function (Request $request, Response $response) use ($db) {
    try {
        $queryParams = $request->getQueryParams();
        $sql = "SELECT * FROM OFFERS WHERE 1=1";
        $params = [];

        if (!empty($queryParams['tags'])) {
            $sql .= " AND tags LIKE ?";
            $params[] = '%' . $queryParams['tags'] . '%';
        }
        if (!empty($queryParams['category'])) {
            $sql .= " AND category LIKE ?";
            $params[] = '%' . $queryParams['category'] . '%';
        }
        if (!empty($queryParams['price'])) {
            $sql .= " AND price >= ?";
            $params[] = (float)$queryParams['price'];
        }
        if (!empty($queryParams['title'])) {
            $sql .= " AND title LIKE ?";
            $params[] = $queryParams['title'];
        }
        if (!empty($queryParams['employer'])) {
            $sql .= " AND employer = ?";
            $params[] = $queryParams['employer'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $offers = $stmt->fetchAll();

        $response->getBody()->write(json_encode($offers));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Offer retrieval error']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Obtener lista de posibles categorías
$app->get('/categories', function (Request $request, Response $response) {
    $categories = ['Cleaning', 'Gardening', 'Tutoring', 'Pet care', 'Maintenance', 'Other'];
    $response->getBody()->write(json_encode($categories));
    return $response->withHeader('Content-Type', 'application/json');
});
//Obtener lista de posibles tags
$app->get('/tags', function (Request $request, Response $response) {
    $tags = ['Exotic animal', 'Difficult plantcare'];
    $response->getBody()->write(json_encode($tags));
    return $response->withHeader('Content-Type', 'application/json');
});
//Obtener lista de posibles sueldos
$app->get('/prices', function (Request $request, Response $response) {
    $prices = [1000, 2000, 3000, 4000, 5000];
    $response->getBody()->write(json_encode($prices));
    return $response->withHeader('Content-Type', 'application/json');
});
