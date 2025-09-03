import mysql.connector
from mysql.connector import Error

def connectionBD():
    try:
        mydb = mysql.connector.connect(
            host="localhost",
            port='3306',
            user="root",
            passwd="",
            database="planti"
        )
        return mydb
    except mysql.connector.Error as err:
        print(f"Error al conectar a la base de datos: {err}")
        return None

