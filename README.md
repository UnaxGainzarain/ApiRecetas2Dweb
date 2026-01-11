4VChef API
API REST desarrollada con Symfony 7 para la gestión de recetas culinarias, ingredientes, pasos de preparación y valores nutricionales.
El proyecto implementa un CRUD completo, borrado lógico, sistema de votaciones con validación de IP y catálogos de tipos.
Tecnologías Utilizadas:
•	PHP 8.2+
•	Symfony 7.3 (API pura)
•	Doctrine ORM
•	MySQL / MariaDB
•	Composer
Instalación y Configuración
Sigue estos pasos para levantar el proyecto en tu máquina local:
1.	Clonar el repositorio y entrar en la carpeta: git clone <URL_DE_TU_REPOSITORIO> cd 4vchef_api
2.	Instalar dependencias: composer install
3.	Configurar la Base de Datos: Edita el archivo .env y ajusta la línea DATABASE_URL con tus credenciales. Ejemplo para XAMPP: DATABASE_URL=“mysql://root:@127.0.0.1:3306/4vchef_db?serverVersion=10.4.32-MariaDB&charset=utf8mb4”
4.	Crear la Base de Datos y las Tablas: php bin/console doctrine:database:create php bin/console doctrine:schema:update –force
5.	Carga de Datos Iniciales (SQL): Para que la API funcione, debes ejecutar este SQL en tu gestor de base de datos (phpMyAdmin) para crear los tipos y nutrientes básicos:
 	INSERT INTO recipe_type (id, name, description) VALUES (1, ‘Postre’, ‘Dulces y final de comida’), (2, ‘Italiana’, ‘Pastas y pizzas’), (3, ‘Mediterránea’, ‘Saludable y fresca’), (4, ‘Asiática’, ‘Sabores de oriente’);
 	INSERT INTO nutrient_type (id, name, unit) VALUES (1, ‘Calorías’, ‘Kcal’), (2, ‘Grasas’, ‘gr’), (10, ‘Carbohidratos’, ‘gr’), (11, ‘Proteínas’, ‘gr’);
6.	Arrancar el Servidor: php -S localhost:8000 -t public
________________________________________
🔌 Documentación de la API (Endpoints)
La API responde en formato JSON.
RECETAS (/recipes)
1.	Listar Recetas Método: GET URL: /recipes Opcional: /recipes?type=1 (Filtrar por tipo)
2.	Crear Receta Método: POST URL: /recipes Body (JSON): Ver ejemplo más abajo.
3.	Borrar Receta (Borrado Lógico) Método: DELETE URL: /recipes/{id} Ejemplo: /recipes/1
VALORACIONES
4.	Votar Receta Método: POST URL: /recipes/{id}/rating/{score} Ejemplo: /recipes/1/rating/5 Nota: Valida que la puntuación sea 0-5 y que la IP no haya votado antes.
CATÁLOGOS
5.	Tipos de Receta Método: GET URL: /recipe-types
6.	Tipos de Nutriente Método: GET URL: /nutrient-types
________________________________________
📝 Ejemplo de JSON para Crear Receta
Usa este JSON en el Body para probar el endpoint POST /recipes:
{ “title”: “Tiramisú Casero”, “number-diner”: 4, “type-id”: 1, “ingredients”: [ { “name”: “Queso Mascarpone”, “quantity”: 500, “unit”: “gr” }, { “name”: “Huevos”, “quantity”: 4, “unit”: “unidad” } ], “steps”: [ { “order”: 1, “description”: “Separar las yemas.” }, { “order”: 2, “description”: “Mezclar con azúcar.” } ], “nutrients”: [ { “type-id”: 1, “quantity”: 450 } ] }
