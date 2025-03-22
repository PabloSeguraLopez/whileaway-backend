# Options of the API

## Crear un usuario
Método: POST

URL: http://localhost:8000/users

Ejemplo de cuerpo del mensaje:
```
{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "123456",
  "is_employer": 1,
  "is_employee": 0,
  "photo_path": "path/to/photo.jpg",
  "address": "Calle 123",
  "phone_number": "123456789",
  "cv": "path/to/cv.pdf"
}

```

## Identificar un usuario
Método: POST

URL: http://localhost:8000/users/authenticate

Ejemplo de cuerpo del mensaje:
```
{
  "email": "juan@example.com",
  "password": "123456"
}

```

Devuelve una respuesta:
```
{
  "authenticated": true
}

```

## Obtener un usuario por ID
Método: GET

URL: http://localhost:8000/users/{id}

Respuesta esperada:
```
{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "123456",
  "is_employer": 1,
  "is_employee": 0,
  "photo_path": "path/to/photo.jpg",
  "address": "Calle 123",
  "phone_number": "123456789",
  "cv": "path/to/cv.pdf"
}

```

## Crear una oferta
Método: POST

URL: http://localhost:8000/offers

Ejemplo de cuerpo del mensaje:
```
{
  "employee": null,
  "employer": 1,
  "tags": "programación, php",
  "address": "Calle 456",
  "additional_info": "Trabajo remoto",
  "price": 500,
  "timetable": "Lunes a viernes, 9 AM - 5 PM"
}

```
La respuesta devuelve el id

## Obtener una oferta por ID
Método: GET

URL: http://localhost:8000/offers/{id}

Respuesta:
```
{
  "id": 1,
  "employee": null,
  "employer": 1,
  "tags": "programación, php",
  "address": "Calle 456",
  "additional_info": "Trabajo remoto",
  "price": 500,
  "timetable": "Lunes a viernes, 9 AM - 5 PM"
}

```

## Editar una oferta
Método: PUT

URL: http://localhost:8000/offers/{id}

En el cuerpo deben ir los campos que se deseen modificar

## Eliminar una oferta
Método: DELETE

URL: http://localhost:8000/offers/{id}

## Obtener todas las ofertas (con filtros opcionales)
Método: GET

URL: http://localhost:8000/offers

Ejemplo sin filtros: http://localhost:8000/offers

*Nota: de momento solo se puede filtrar por employer, employee, tags y address*

Ejemplo filtrando por employer: http://localhost:8000/offers?employer=1

Ejemplo filtrando por tags: http://localhost:8000/offers?tags=php


## NOTA IMPORTANTE: LAS OFERTAS SE HAN MODIFICADO PARA QUE TENGAN UN TÍTULO Y UNA CATEGORÍA


