from flask import Flask, render_template, request, redirect, url_for, session, flash
from datetime import date
from conexionBD import connectionBD  # Asegúrate que este archivo y función existan
import re

app = Flask(__name__)
app.secret_key = '97110c78ae51a45af397be6534caef90ebb9b1dcb3380af008f90b23a5d1616bf19bc29098105da20fe'


@app.route('/login', methods=['GET', 'POST'])
def loginUser():
    if 'conectado' in session:
        return redirect(url_for('dashboard')) 

    msg = ''
    if request.method == 'POST' and 'email' in request.form and 'password' in request.form:
        email = request.form['email']
        password = request.form['password']
        
        conexion_MySQLdb = connectionBD()
        cursor = conexion_MySQLdb.cursor(dictionary=True)
        
        # CAMBIO: La consulta ahora apunta a la tabla 'usuario'
        cursor.execute("SELECT * FROM usuario WHERE email = %s", (email,))
        account = cursor.fetchone()
        
        if account and account['password'] == password:
            # CAMBIO: Se guardan en sesión los datos de la tabla 'usuario'
            session['conectado'] = True
            session['id'] = account['id']
            session['nombre'] = account['nombre']
            session['apellido'] = account['apellido']
            session['email'] = account['email']
            session['pais'] = account['pais']
            session['tipo_user'] = account['tipo_user']
            
            cursor.close()
            conexion_MySQLdb.close()
            return redirect(url_for('dashboard'))
        else:
            msg = 'Datos incorrectos, por favor verifique.'
    
    # Muestra el formulario de login
    return render_template('public/modulo_login/index.html', msjAlert=msg, typeAlert=0)

# Ruta para el Dashboard (página principal después de iniciar sesión)
@app.route('/dashboard')
def dashboard():
    if 'conectado' in session:
        return render_template('public/dashboard/home.html')
    return redirect(url_for('loginUser'))


# --- Ruta para Registro ---
@app.route('/registro-usuario', methods=['POST'])
def registerUser():
    msg = ''
    # CAMBIO: Se obtienen los nuevos campos del formulario
    nombre = request.form['nombre']
    apellido = request.form['apellido']
    email = request.form['email']
    password = request.form['password']
    pais = request.form['pais'] # Asegúrate que tu form tenga un input con name="pais"

    conexion_MySQLdb = connectionBD()
    cursor = conexion_MySQLdb.cursor(dictionary=True)
    
    # CAMBIO: La consulta ahora apunta a la tabla 'usuario'
    cursor.execute("SELECT * FROM usuario WHERE email = %s", (email,))
    account = cursor.fetchone()

    if account:
        msg = '¡El correo electrónico ya existe!'
    elif not re.match(r'[^@]+@[^@]+\.[^@]+', email):
        msg = '¡Formato de Email incorrecto!'
    elif not all([nombre, apellido, email, password, pais]):
        msg = '¡El formulario no debe estar vacío!'
    else:
        # CAMBIO: El INSERT ahora usa la tabla 'usuario' y todas sus columnas
        cursor.execute(
            "INSERT INTO usuario (nombre, apellido, email, password, pais, tipo_user) VALUES (%s, %s, %s, %s, %s, %s)",
            (nombre, apellido, email, password, pais, 1) # tipo_user 1 por defecto
        )
        conexion_MySQLdb.commit()
        msg = '¡Cuenta creada correctamente!'
        
    cursor.close()
    conexion_MySQLdb.close()
    
    # Usar flash es mejor para mostrar mensajes después de una redirección
    flash(msg)
    return redirect(url_for('loginUser'))

# --- Ruta para Actualizar Perfil ---
@app.route('/actualizar-mi-perfil', methods=['POST'])
def actualizarMiPerfil():
    if 'conectado' in session and request.method == 'POST':
        # CAMBIO: Se obtienen todos los campos del formulario de perfil
        id_usuario = session['id']
        nombre = request.form['nombre']
        apellido = request.form['apellido']
        email = request.form['email']
        pais = request.form['pais']
        password = request.form.get('password') # .get() para campos opcionales

        conexion_MySQLdb = connectionBD()
        cursor = conexion_MySQLdb.cursor()

        # Si el campo de contraseña no está vacío, se actualiza
        if password:
            cursor.execute(
                "UPDATE usuario SET nombre=%s, apellido=%s, email=%s, pais=%s, password=%s WHERE id=%s",
                (nombre, apellido, email, pais, password, id_usuario)
            )
        else: # Si no, se actualiza todo excepto la contraseña
            cursor.execute(
                "UPDATE usuario SET nombre=%s, apellido=%s, email=%s, pais=%s WHERE id=%s",
                (nombre, apellido, email, pais, id_usuario)
            )
        
        conexion_MySQLdb.commit()
        cursor.close()
        conexion_MySQLdb.close()
        
        flash('¡Perfil actualizado correctamente!')
        # Actualizamos los datos de la sesión por si cambiaron (ej. el nombre)
        session['nombre'] = nombre
        session['apellido'] = apellido
        session['email'] = email
        session['pais'] = pais

    return redirect(url_for('dashboard')) # O a la página de perfil


if __name__ == "__main__":
    app.run(debug=True, port=8001)