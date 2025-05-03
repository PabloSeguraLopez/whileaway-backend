<?php
header("Access-Control-Allow-Origin: http://localhost:4200"); // Permite solicitudes solo desde tu frontend
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Cabeceras permitidas
header("Access-Control-Allow-Credentials: true"); // Si necesitas enviar cookies
