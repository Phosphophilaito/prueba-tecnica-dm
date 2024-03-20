from flask import Flask, request, jsonify
import requests
import pymysql
from datetime import datetime
from flask_cors import CORS
import configparser
import uuid

config = configparser.ConfigParser()
config.read('config.ini')

app = Flask(__name__)
CORS(app)


# Función para conectarse a la base de datos
def connect_db():
	#Conectate a la DB!
	conn = pymysql.connect(
		host = config['default']['host'],
		user = config['default']['username'],
		password = config['default']['password'],
		db= config['default']['db_name'],
	)
	return conn


@app.route('/guardar-clima', methods=['POST'])
def guardar_clima():
	# Obtener lat y lon de los datos enviados en la petición
	lat = request.form.get('lat')
	lon = request.form.get('lon')
	fecha = datetime.now().strftime('%Y-%m-%d')  # Fecha actual para el ejemplo
	# Realizar la petición a la API de clima
	api_key = config['default']['api-key']
	url = f"http://api.openweathermap.org/data/2.5/weather?lat={lat}&lon={lon}&appid={api_key}&units=metric&lang=es"
	#Realizamos petición, guardamos datos, y devolvemos json

	response = requests.get(url)
	data = response.json()

	#print(data)

	conn = connect_db()

	cur = conn.cursor()

	insert = """insert into `clima` (id, fecha, lat, 
                                  lon, temperatura, humedad, viento, descripcion, url)
         values (%s, %s, %s, %s, %s, %s, %s, %s, %s) 
    """

	cur.execute("SELECT id FROM clima ORDER BY id DESC")
	output = cur.fetchone()
	
	newid = 1

	if isinstance(output[0], int):
		newid += output[0]


	cur.execute(insert,(newid, fecha, lat, lon, data['main']['temp'],data['main']['humidity'], data['wind']['speed'], data['weather'][0]['description'], url))
	
	conn.commit()
	conn.close()

	return jsonify({"success": True, "message": "Datos del clima guardados correctamente.", "fecha":  fecha, "temperatura" : data['main']['temp'], "humedad" : data['main']['humidity'], "viento" : data['wind']['speed'], "descripcion" : data['weather'][0]['description'], "url" : url})

if __name__ == '__main__':
	app.run(debug=True)
