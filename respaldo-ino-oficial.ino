#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <DHT.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_TSL2561_U.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <time.h>

// Pantallas LCD
LiquidCrystal_I2C lcd1(0x27, 20, 4);
LiquidCrystal_I2C lcd2(0x23, 20, 4);

// Wi-Fi y servidor
const char* ssid = "Red_Quiroz";
const char* password = "0104092002D$q";
const char* server = "http://192.168.1.27/allvision3/datos.php"; // Tu PHP

// Pines sensores
#define DHTPIN 18
#define DHTTYPE DHT22
#define HUMEDAD_SUELO_PIN 34
#define PLUVIOMETRO_PIN 5
#define SENSOR_LLUVIA_FC37 35
#define VANE_PIN 33
#define ANEMOMETRO_PIN 14
#define MM_POR_PULSO 0.279

DHT dht(DHTPIN, DHTTYPE);
Adafruit_TSL2561_Unified tsl = Adafruit_TSL2561_Unified(TSL2561_ADDR_FLOAT, 12345);

volatile int conteoInterrupciones = 0;
volatile int contPulsosLluvia = 0;
float velocidadViento = 0.0;

unsigned long tiempoUltimaMedicion = 0;
unsigned long tiempoUltimaActualizacion = 0;

const unsigned long intervaloMedicion = 10000;
const unsigned long tiempoVentanaViento = 2000;

String getWindDirection(int valor) {
  if (valor < 300) return "Norte";
  else if (valor < 800) return "Noreste";
  else if (valor < 1300) return "Este";
  else if (valor < 1800) return "Sureste";
  else if (valor < 2300) return "Sur";
  else if (valor < 2800) return "Suroeste";
  else if (valor < 3300) return "Oeste";
  else return "Noroeste";
}

void contarInterrupcionViento() {
  conteoInterrupciones++;
}

void contarPulsoLluvia() {
  contPulsosLluvia++;
}

void setup() {
  Serial.begin(115200);
  lcd1.init(); lcd1.backlight();
  lcd2.init(); lcd2.backlight();

  dht.begin();
  Wire.begin();
  tsl.begin();
  tsl.enableAutoRange(true);
  tsl.setIntegrationTime(TSL2561_INTEGRATIONTIME_13MS);

  pinMode(PLUVIOMETRO_PIN, INPUT_PULLUP);
  pinMode(SENSOR_LLUVIA_FC37, INPUT);
  pinMode(VANE_PIN, INPUT);
  pinMode(ANEMOMETRO_PIN, INPUT_PULLUP);

  attachInterrupt(digitalPinToInterrupt(ANEMOMETRO_PIN), contarInterrupcionViento, RISING);
  attachInterrupt(digitalPinToInterrupt(PLUVIOMETRO_PIN), contarPulsoLluvia, FALLING);

  Serial.print("Conectando a WiFi...");
  WiFi.begin(ssid, password);

  int intentos = 0;
  while (WiFi.status() != WL_CONNECTED && intentos < 20) {
    delay(500);
    Serial.print(".");
    intentos++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ Conectado al WiFi");
    Serial.print("📡 IP local del ESP32: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n❌ No se pudo conectar al WiFi");
  }

  configTime(-5 * 3600, 0, "pool.ntp.org", "time.nist.gov");

  struct tm timeinfo;
  if (!getLocalTime(&timeinfo)) {
    Serial.println("⚠ No se pudo obtener la hora desde NTP");
  } else {
    Serial.println("🕒 Hora local obtenida por NTP:");
    Serial.println(&timeinfo, "%Y-%m-%d %H:%M:%S");
  }
}

void loop() {
  unsigned long tiempoActual = millis();

  if (tiempoActual - tiempoUltimaMedicion >= tiempoVentanaViento) {
    velocidadViento = (float)conteoInterrupciones / (tiempoVentanaViento / 1000.0) * 2.4;
    conteoInterrupciones = 0;
    tiempoUltimaMedicion = tiempoActual;
  }

  if (tiempoActual - tiempoUltimaActualizacion >= intervaloMedicion) {
    tiempoUltimaActualizacion = tiempoActual;

    int valorLluvia = analogRead(SENSOR_LLUVIA_FC37);
    String estadoLluvia = (valorLluvia > 3000) ? "Nula" : (valorLluvia > 2000) ? "Media" : "Fuerte";
    int valorHumedad = analogRead(HUMEDAD_SUELO_PIN);
    int porcentajeHumedad = map(valorHumedad, 4095, 0, 0, 100);
    float temperatura = dht.readTemperature();
    float humedadAmbiente = dht.readHumidity();
    sensors_event_t event;
    tsl.getEvent(&event);
    float lux = event.light;
    int valorViento = analogRead(VANE_PIN);
    String direccionViento = getWindDirection(valorViento);
    float lluviaMM = contPulsosLluvia * MM_POR_PULSO;

    // LCD 1
    lcd1.clear();
    lcd1.setCursor(0, 0); lcd1.print("H. Suelo: "); lcd1.print(porcentajeHumedad); lcd1.print(" %");
    lcd1.setCursor(0, 1); lcd1.print("Luz: "); lcd1.print(lux); lcd1.print(" lx");
    lcd1.setCursor(0, 2); lcd1.print("Lluvia: "); lcd1.print(estadoLluvia);
    lcd1.setCursor(0, 3); lcd1.print("Precipit.: "); lcd1.print(lluviaMM, 1); lcd1.print(" mm");

    // LCD 2
    lcd2.clear();
    lcd2.setCursor(0, 0); lcd2.print("Temp: "); lcd2.print(temperatura); lcd2.print(" C");
    lcd2.setCursor(0, 1); lcd2.print("Humedad: "); lcd2.print(humedadAmbiente); lcd2.print(" %");
    lcd2.setCursor(0, 2); lcd2.print("Dir: "); lcd2.print(direccionViento);
    lcd2.setCursor(0, 3); lcd2.print("Viento: "); lcd2.print(velocidadViento, 1); lcd2.print(" km/h");

    // Serial
    struct tm timeinfo;
    getLocalTime(&timeinfo);
    char hora[30];
    strftime(hora, sizeof(hora), "%Y-%m-%d %H:%M:%S", &timeinfo);
    Serial.println("============== " + String(hora) + " ==============");
    Serial.print("Temp: "); Serial.println(temperatura);
    Serial.print("Hum: "); Serial.println(humedadAmbiente);
    Serial.print("Suelo: "); Serial.println(porcentajeHumedad);
    Serial.print("Luz: "); Serial.println(lux);
    Serial.print("Viento: "); Serial.print(velocidadViento); Serial.print(" ("); Serial.print(direccionViento); Serial.println(")");
    Serial.print("Lluvia: "); Serial.print(estadoLluvia); Serial.print(" | "); Serial.print(lluviaMM); Serial.println(" mm");
    Serial.print("🧮 Pulsos lluvia: "); Serial.println(contPulsosLluvia);

    if (WiFi.status() == WL_CONNECTED) {
      HTTPClient http;
      http.begin(server);
      http.addHeader("Content-Type", "application/json");

      String lluviaTexto = (estadoLluvia == "Fuerte" || estadoLluvia == "Media") ? "Sí" : "No";

      String jsonPayload = "{";
      jsonPayload += "\"temperatura\":" + String(temperatura, 1) + ",";
      jsonPayload += "\"humedad\":" + String(humedadAmbiente, 1) + ",";
      jsonPayload += "\"suelo\":" + String(porcentajeHumedad) + ",";
      jsonPayload += "\"luz\":" + String(lux, 1) + ",";
      jsonPayload += "\"viento_direccion\":\"" + direccionViento + "\",";
      jsonPayload += "\"viento_velocidad\":" + String(velocidadViento, 1) + ",";
      jsonPayload += "\"lluvia\":\"" + lluviaTexto + "\",";
      jsonPayload += "\"lluvia_mm\":" + String(lluviaMM, 1) + ",";
      jsonPayload += "\"estado_lluvia\":\"" + estadoLluvia + "\"";
      jsonPayload += "}";

      int httpCode = http.POST(jsonPayload);
      if (httpCode > 0) {
        Serial.println("🛰 Enviado con éxito: " + http.getString());
      } else {
        Serial.print("❌ Error HTTP: ");
        Serial.println(httpCode);
      }

      http.end();
    }

    contPulsosLluvia = 0;
  }
}