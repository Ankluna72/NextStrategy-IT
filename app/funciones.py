# funciones.py

from flask import session
from conexionBD import * 
def dataLoginSesion():

    if 'conectado' in session:
        inforLogin = {
            "idLogin": session['id'],
            "tipoLogin": session.get('tipo_user'), 
            "nombre": session.get('nombre'),
            "apellido": session.get('apellido'),
            "emailLogin": session.get('email'),
            "pais": session.get('pais')
        }
        return inforLogin
    return None


def listaPaises():
    conexion_MySQLdb = connectionBD()
    mycursor = conexion_MySQLdb.cursor(dictionary=True)
    querySQL = ("SELECT * FROM countries ORDER BY name_country ASC") 
    mycursor.execute(querySQL)
    paises = mycursor.fetchall()
    mycursor.close()
    conexion_MySQLdb.close()
    return paises

def dataPerfilUsuario():
    conexion_MySQLdb = connectionBD()
    mycursor = conexion_MySQLdb.cursor(dictionary=True)
    idUser = session['id']
    
    querySQL = "SELECT * FROM usuario WHERE id = %s"
    mycursor.execute(querySQL, (idUser,))
    
    datosUsuario = mycursor.fetchone() 
    mycursor.close()
    conexion_MySQLdb.close()
    return datosUsuario