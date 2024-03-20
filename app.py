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

	response = request.get(url)
	data = response.json()

	conn = connect_db()

	cur = conn.cursor()

	sql = """insert into `clima` (id, fecha, lat, 
                                  lon, temperatura, humedad, viento, descripcion, url)
         values (%s, %s, %s, %s, %s, %s, %s, %s, %s) 
    """

	cur.execute(sql,(str(uuid.uiid4()), fecha, lat, lon, data['current']['temp'],data['current']['humidity'], data['current']['wind_speed'], data['current']['weather'][0]['description'], url))
	
	conn.commit()
	conn.close()

	return jsonify({"success": True, "message": "Datos del clima guardados correctamente.", "fecha":  fecha, "temperatura" : data['current']['temp'], "humedad" : data['current']['humidity'], "viento" : data['current']['wind_speed'], "descripcion" : data['current']['weather'][0]['description'], "url" : url})

if __name__ == '__main__':
	app.run(debug=True)
