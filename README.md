### Crear Login con PYTHON - FLASK y MYSQL

Login con Python Flask y MySQL.

### 1: Crear entorno virtual
	python -m venv env


### 2: Activar el entorno virtual
	. env/Scripts/activate

### 3: Instalar Flask
	pip install flask

### 4: Instalar Python MySQL Connector
	pip install mysql-connector-python

### Crear/Actualizar el archivo requirements.txt
	pip freeze > requirements.txt

## IMPORTANTE: Para ejecutar el proyecto, instalar dependencias desde requirements.txt
	pip install -r requirements.txt

#### Probar la conexión a BD
	python testBD.py

#### Correr el proyecto
	python app/app.py

Visitar la url: http://127.0.0.1:8001
