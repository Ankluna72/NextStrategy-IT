import mysql.connector

def conectar_bd(host, usuario, clave, base_datos):
    try:
        # Conexión a la base de datos
        conexion = mysql.connector.connect(
            host=host,
            user=usuario,
            passwd=clave,
            database=base_datos
        )
        print("Conexión exitosa a la base de datos MySQL")
        return conexion
    except mysql.connector.Error as error:
        print("Error al conectar a la base de datos MySQL:", error)
        return None

# Ejemplo de uso
if __name__ == "__main__":
    host = "localhost"
    usuario = "root"
    clave = "123456"  
    base_datos = "PETI"

    conexion = conectar_bd(host, usuario, clave, base_datos)

    if conexion:
        conexion.close()
