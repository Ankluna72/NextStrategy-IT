# routes.py

from flask import Flask, render_template, redirect, url_for, session, request, flash
from funciones import * # Importando tus Funciones
from conexionBD import * # Importando tu Conexión a BD
import re                     # Importando el módulo de expresiones regulares

# Declarando nombre de la aplicación e inicializando
app = Flask(__name__)
application = app

# Clave secreta para la sesión
app.secret_key = '97110c78ae51a45af397be6534caef90ebb9b1dcb3380af008f90b23a5d1616bf19bc29098105da20fe'


# --- Rutas Principales y de Sesión ---

# Redireccionando cuando la página no existe (Error 404)
@app.errorhandler(404)
def not_found(error):
    return redirect(url_for('inicio'))

# Ruta de inicio, redirige al dashboard si hay sesión activa
@app.route('/')
def inicio():
    if 'conectado' in session:
        # Pasa los datos de la sesión a la plantilla del dashboard
        return render_template('public/dashboard/home.html', dataLogin=dataLoginSesion())
    else:
        # Muestra la página de login si no hay sesión
        return render_template('public/modulo_login/index.html', dataPaises=listaPaises())

# Ruta para procesar el inicio de sesión (Login)
@app.route('/acceso-login', methods=['POST'])
def acceso_login():
    if request.method == 'POST' and 'email' in request.form and 'password' in request.form:
        email = request.form['email']
        password = request.form['password']

        # Conectamos a la BD usando tu función
        db = connectionBD()
        cursor = db.cursor(dictionary=True)

        # Consulta adaptada a tu tabla 'usuario'
        cursor.execute("SELECT * FROM usuario WHERE email = %s", (email,))
        user = cursor.fetchone()
        cursor.close()
        db.close()

        # Verificamos si el usuario existe y la contraseña coincide
        if user and user['password'] == password:
            # Creamos la sesión con los datos correctos de la tabla 'usuario'
            session['conectado'] = True
            session['id'] = user['id']
            session['tipo_user'] = user['tipo_user']
            session['nombre'] = user['nombre']
            session['apellido'] = user['apellido']
            session['email'] = user['email']
            session['pais'] = user['pais']
            
            return redirect(url_for('inicio'))
        else:
            # Usamos flash para enviar mensajes de error a la plantilla
            flash('Email o Contraseña incorrectos. Inténtalo de nuevo.', 'error')
            return redirect(url_for('inicio'))

    return redirect(url_for('inicio'))

# Ruta para procesar un nuevo registro de usuario
@app.route('/crear-registro', methods=['POST'])
def crear_registro():
    nombre = request.form['nombre']
    apellido = request.form['apellido']
    email = request.form['email']
    password = request.form['password']
    pais = request.form['pais']

    db = connectionBD()
    cursor = db.cursor(dictionary=True)
    
    # Verificamos si el email ya existe
    cursor.execute("SELECT * FROM usuario WHERE email = %s", (email,))
    if cursor.fetchone():
        flash('El correo electrónico ya está registrado.', 'error')
    elif not re.match(r'[^@]+@[^@]+\.[^@]+', email):
        flash('Formato de correo electrónico inválido.', 'warning')
    elif not all([nombre, apellido, email, password, pais]):
        flash('Todos los campos son obligatorios.', 'warning')
    else:
        # Insertamos el nuevo usuario en la tabla 'usuario'
        cursor.execute(
            "INSERT INTO usuario (nombre, apellido, email, password, pais, tipo_user) VALUES (%s, %s, %s, %s, %s, %s)",
            (nombre, apellido, email, password, pais, 1) # tipo_user=1 (usuario normal) por defecto
        )
        db.commit()
        flash('¡Registro exitoso! Ahora puedes iniciar sesión.', 'success')

    cursor.close()
    db.close()
    return redirect(url_for('inicio'))

# Ruta para la página principal del usuario logueado (Dashboard)
@app.route('/dashboard')
def dashboard():
    if 'conectado' in session:
        return render_template('public/dashboard/home.html', dataLogin=dataLoginSesion())
    else:
        return redirect(url_for('inicio'))
        
# Ruta para editar el perfil del usuario
@app.route('/edit-profile', methods=['GET', 'POST'])
def editProfile():
    if 'conectado' in session:
        # La lógica para actualizar (POST) podría ir aquí
        return render_template('public/dashboard/pages/Profile.html', dataUser=dataPerfilUsuario(), dataLogin=dataLoginSesion(), dataPaises=listaPaises())
    return redirect(url_for('inicio'))

@app.route('/logout')
def logout():
    session.clear() 
    flash("La sesión fue cerrada correctamente", "success")
    return redirect(url_for('inicio'))