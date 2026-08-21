// // #include <WiFi.h>
// #include <HTTPClient.h>
// #include <Wire.h>
// #include <Adafruit_GFX.h>
// #include <Adafruit_SSD1306.h>

// // =====================================================
// // CONFIGURAÇÃO WI-FI
// // =====================================================

// // Coloque aqui o nome da sua rede Wi-Fi
// const char* WIFI_SSID = "CELINA";

// // Coloque aqui a senha da sua rede Wi-Fi
// const char* WIFI_PASSWORD = "25301810";

// // =====================================================
// // CONFIGURAÇÃO DA API
// // =====================================================
// //
// // EXEMPLO:
// // const char* API_BASE_URL =
// //   "http://192.168.1.100/AerisGuard/api/sensor";
// //
// // NÃO use localhost aqui.
// //
// // Deve ser o IP do computador/servidor onde
// // seu PHP está rodando.
// //

// const char* API_BASE_URL =
//   "http://192.168.0.5:8000/api";
// // =====================================================
// // IDENTIFICAÇÃO DO DISPOSITIVO
// // =====================================================
// //
// // Esses valores precisam corresponder ao dispositivo
// // que você cadastrar no Dashboard.
// //

// const char* ESP32_ID =
//   "ESP32-MQ135-001";

// const char* MANUFACTURER_CODE =
//   "AERIS-MQ135-001";

// const char* HOSTNAME =
//   "Aeris-MQ135";

// const char* FIRMWARE_VERSION =
//   "1.0.0";

// // =====================================================
// // API KEY
// // =====================================================
// //
// // Depois de cadastrar o dispositivo no Dashboard,
// // o sistema vai gerar uma API Key.
// //
// // Cole a chave aqui.
// //
// // ANTES DO CADASTRO:
// // deixe como está abaixo.
// //
// // DEPOIS DO CADASTRO:
// // substitua pelo valor recebido.
// //

// const char* API_KEY =
//   "COLOQUE_A_API_KEY_AQUI";

// // =====================================================
// // OLED
// // =====================================================

// #define SCREEN_WIDTH 128
// #define SCREEN_HEIGHT 64
// #define OLED_RESET -1

// #define OLED_SDA 21
// #define OLED_SCL 22

// Adafruit_SSD1306 display(
//   SCREEN_WIDTH,
//   SCREEN_HEIGHT,
//   &Wire,
//   OLED_RESET
// );

// // =====================================================
// // PINOS
// // =====================================================

// #define SENSOR 34

// #define LED_VERDE 25
// #define LED_AMARELO 26
// #define LED_VERMELHO 27

// #define BUZZER 18

// // =====================================================
// // CONFIGURAÇÃO DE LEITURA
// // =====================================================

// #define NUM_LEITURAS 20
// #define INTERVALO_LEITURA 50

// // =====================================================
// // ADC
// // =====================================================

// #define ADC_MIN 0
// #define ADC_MAX 4095

// // =====================================================
// // PPM ESTIMADO
// // =====================================================
// //
// // IMPORTANTE:
// //
// // Estes valores são apenas uma estimativa para o
// // protótipo.
// //
// // O MQ135 precisa de calibração adequada para que
// // a conversão seja representativa de uma concentração
// // específica de gás.
// //

// #define PPM_MIN 0
// #define PPM_MAX 2000

// // =====================================================
// // LIMITES DO PROJETO
// // =====================================================

// #define LIMITE_NORMAL 400
// #define LIMITE_ATENCAO 700
// #define LIMITE_ALERTA 1000
// #define LIMITE_PERIGO 1600

// // =====================================================
// // INTERVALOS DE COMUNICAÇÃO
// // =====================================================

// const unsigned long INTERVALO_ENVIO = 5000;
// const unsigned long INTERVALO_ANNOUNCE = 10000;
// const unsigned long INTERVALO_RECONEXAO = 10000;

// // =====================================================
// // VARIÁVEIS
// // =====================================================

// float mediaADC = 0;
// float ppm = 0;

// String ultimoStatus = "NORMAL";

// unsigned long ultimoEnvio = 0;
// unsigned long ultimoAnnounce = 0;
// unsigned long ultimaTentativaWiFi = 0;
// unsigned long ultimoBipe = 0;

// // =====================================================
// // CALCULAR MÉDIA DO ADC
// // =====================================================

// float calcularMediaADC()
// {
//   long soma = 0;

//   for (int i = 0; i < NUM_LEITURAS; i++)
//   {
//     soma += analogRead(SENSOR);

//     delay(INTERVALO_LEITURA);
//   }

//   return (float)soma / NUM_LEITURAS;
// }

// // =====================================================
// // CONVERTER ADC -> PPM ESTIMADO
// // =====================================================

// float converterParaPPM(float adc)
// {
//   if (adc < ADC_MIN)
//   {
//     adc = ADC_MIN;
//   }

//   if (adc > ADC_MAX)
//   {
//     adc = ADC_MAX;
//   }

//   float ppmEstimado =
//     ((adc - ADC_MIN) *
//      (PPM_MAX - PPM_MIN) /
//      (ADC_MAX - ADC_MIN))
//     + PPM_MIN;

//   return ppmEstimado;
// }

// // =====================================================
// // DETERMINAR STATUS
// // =====================================================

// void atualizarStatus()
// {
//   if (ppm < LIMITE_NORMAL)
//   {
//     // -------------------------------
//     // NORMAL
//     // -------------------------------

//     digitalWrite(
//       LED_VERDE,
//       HIGH
//     );

//     digitalWrite(
//       LED_AMARELO,
//       LOW
//     );

//     digitalWrite(
//       LED_VERMELHO,
//       LOW
//     );

//     digitalWrite(
//       BUZZER,
//       LOW
//     );

//     ultimoStatus = "NORMAL";
//   }

//   else if (ppm < LIMITE_ATENCAO)
//   {
//     // -------------------------------
//     // ATENCAO
//     // -------------------------------

//     digitalWrite(
//       LED_VERDE,
//       LOW
//     );

//     digitalWrite(
//       LED_AMARELO,
//       HIGH
//     );

//     digitalWrite(
//       LED_VERMELHO,
//       LOW
//     );

//     digitalWrite(
//       BUZZER,
//       LOW
//     );

//     ultimoStatus = "ATENCAO";
//   }

//   else if (ppm < LIMITE_ALERTA)
//   {
//     // -------------------------------
//     // ALERTA
//     // -------------------------------

//     digitalWrite(
//       LED_VERDE,
//       LOW
//     );

//     digitalWrite(
//       LED_AMARELO,
//       HIGH
//     );

//     digitalWrite(
//       LED_VERMELHO,
//       LOW
//     );

//     ultimoStatus = "ALERTA";

//     if (
//       millis() - ultimoBipe >= 2000
//     )
//     {
//       digitalWrite(
//         BUZZER,
//         HIGH
//       );

//       delay(200);

//       digitalWrite(
//         BUZZER,
//         LOW
//       );

//       ultimoBipe = millis();
//     }
//   }

//   else
//   {
//     // -------------------------------
//     // PERIGO
//     // -------------------------------

//     digitalWrite(
//       LED_VERDE,
//       LOW
//     );

//     digitalWrite(
//       LED_AMARELO,
//       LOW
//     );

//     digitalWrite(
//       LED_VERMELHO,
//       HIGH
//     );

//     digitalWrite(
//       BUZZER,
//       HIGH
//     );

//     ultimoStatus = "PERIGO";
//   }
// }

// // =====================================================
// // ATUALIZAR OLED
// // =====================================================

// void atualizarDisplay()
// {
//   display.clearDisplay();

//   display.setTextColor(
//     SSD1306_WHITE
//   );

//   // -------------------------------
//   // STATUS
//   // -------------------------------

//   display.setTextSize(2);

//   display.setCursor(
//     0,
//     0
//   );

//   display.println(
//     ultimoStatus
//   );

//   // -------------------------------
//   // PPM
//   // -------------------------------

//   display.setTextSize(1);

//   display.setCursor(
//     0,
//     26
//   );

//   display.print(
//     "Gas: "
//   );

//   display.setTextSize(2);

//   display.print(
//     (int)ppm
//   );

//   display.println(
//     " ppm"
//   );

//   // -------------------------------
//   // ADC
//   // -------------------------------

//   display.setTextSize(1);

//   display.setCursor(
//     0,
//     47
//   );

//   display.print(
//     "ADC:"
//   );

//   display.print(
//     (int)mediaADC
//   );

//   // -------------------------------
//   // WIFI
//   // -------------------------------

//   display.setCursor(
//     74,
//     47
//   );

//   if (
//     WiFi.status() ==
//     WL_CONNECTED
//   )
//   {
//     display.print(
//       "WiFi"
//     );
//   }
//   else
//   {
//     display.print(
//       "OFF"
//     );
//   }

//   display.display();
// }

// // =====================================================
// // MOSTRAR TELA DE INICIALIZAÇÃO
// // =====================================================

// void mostrarTelaInicial()
// {
//   display.clearDisplay();

//   display.setTextColor(
//     SSD1306_WHITE
//   );

//   display.setTextSize(2);

//   display.setCursor(
//     0,
//     0
//   );

//   display.println(
//     "AERIS"
//   );

//   display.setTextSize(1);

//   display.setCursor(
//     0,
//     27
//   );

//   display.println(
//     "Detector de gas"
//   );

//   display.setCursor(
//     0,
//     42
//   );

//   display.println(
//     "Sensor: MQ135"
//   );

//   display.display();
// }

// // =====================================================
// // MOSTRAR TELA DE WIFI
// // =====================================================

// void mostrarTelaWiFi(
//   bool conectado
// )
// {
//   display.clearDisplay();

//   display.setTextColor(
//     SSD1306_WHITE
//   );

//   display.setTextSize(1);

//   display.setCursor(
//     0,
//     0
//   );

//   display.println(
//     "AERIS GUARD"
//   );

//   display.setCursor(
//     0,
//     15
//   );

//   display.println(
//     "Sensor: MQ135"
//   );

//   if (conectado)
//   {
//     display.setCursor(
//       0,
//       30
//     );

//     display.println(
//       "WiFi conectado!"
//     );

//     display.setCursor(
//       0,
//       45
//     );

//     display.print(
//       "IP: "
//     );

//     display.println(
//       WiFi.localIP()
//     );
//   }
//   else
//   {
//     display.setCursor(
//       0,
//       30
//     );

//     display.println(
//       "WiFi offline"
//     );

//     display.setCursor(
//       0,
//       45
//     );

//     display.println(
//       "Sensor funcionando"
//     );
//   }

//   display.display();
// }

// // =====================================================
// // CONECTAR AO WIFI
// // =====================================================

// void conectarWiFi()
// {
//   Serial.println();
//   Serial.println(
//     "================================"
//   );

//   Serial.println(
//     "        CONEXAO WI-FI"
//   );

//   Serial.println(
//     "================================"
//   );

//   Serial.print(
//     "SSID: "
//   );

//   Serial.println(
//     WIFI_SSID
//   );

//   mostrarTelaWiFi(false);

//   WiFi.mode(
//     WIFI_STA
//   );

//   WiFi.setAutoReconnect(
//     true
//   );

//   WiFi.persistent(
//     false
//   );

//   WiFi.begin(
//     WIFI_SSID,
//     WIFI_PASSWORD
//   );

//   Serial.println(
//     "Conectando..."
//   );

//   unsigned long inicio =
//     millis();

//   while (
//     WiFi.status() != WL_CONNECTED &&
//     millis() - inicio < 15000
//   )
//   {
//     delay(300);

//     Serial.print(
//       "."
//     );
//   }

//   Serial.println();

//   if (
//     WiFi.status() ==
//     WL_CONNECTED
//   )
//   {
//     Serial.println(
//       "Wi-Fi conectado!"
//     );

//     Serial.print(
//       "IP: "
//     );

//     Serial.println(
//       WiFi.localIP()
//     );

//     Serial.print(
//       "Gateway: "
//     );

//     Serial.println(
//       WiFi.gatewayIP()
//     );

//     Serial.print(
//       "RSSI: "
//     );

//     Serial.print(
//       WiFi.RSSI()
//     );

//     Serial.println(
//       " dBm"
//     );

//     mostrarTelaWiFi(true);

//     delay(1500);
//   }
//   else
//   {
//     Serial.print(
//       "Falha no Wi-Fi. Status: "
//     );

//     Serial.println(
//       WiFi.status()
//     );

//     mostrarTelaWiFi(false);

//     delay(1500);
//   }
// }

// // =====================================================
// // ENVIAR ANNOUNCE
// // =====================================================

// void anunciarESP32()
// {
//   if (
//     WiFi.status() !=
//     WL_CONNECTED
//   )
//   {
//     return;
//   }

//   HTTPClient http;

//   String url =
//     String(API_BASE_URL) +
//     "/app.php?action=announce";

//   Serial.println();
//   Serial.println(
//     "Enviando ANNOUNCE..."
//   );

//   Serial.println(
//     url
//   );

//   http.begin(
//     url
//   );

//   http.addHeader(
//     "Content-Type",
//     "application/json"
//   );

//   String json = "{";

//   json +=
//     "\"esp32_id\":\"";

//   json +=
//     ESP32_ID;

//   json +=
//     "\",";

//   json +=
//     "\"manufacturer_code\":\"";

//   json +=
//     MANUFACTURER_CODE;

//   json +=
//     "\",";

//   json +=
//     "\"hostname\":\"";

//   json +=
//     HOSTNAME;

//   json +=
//     "\",";

//   json +=
//     "\"ip\":\"";

//   json +=
//     WiFi.localIP().toString();

//   json +=
//     "\",";

//   json +=
//     "\"rssi\":";

//   json +=
//     String(
//       WiFi.RSSI()
//     );

//   json +=
//     ",";

//   json +=
//     "\"firmware_version\":\"";

//   json +=
//     FIRMWARE_VERSION;

//   json +=
//     "\"";

//   json +=
//     "}";

//   Serial.println(
//     "JSON:"
//   );

//   Serial.println(
//     json
//   );

//   int httpCode =
//     http.POST(
//       json
//     );

//   Serial.print(
//     "HTTP ANNOUNCE: "
//   );

//   Serial.println(
//     httpCode
//   );

//   if (
//     httpCode > 0
//   )
//   {
//     String resposta =
//       http.getString();

//     Serial.println(
//       "Resposta:"
//     );

//     Serial.println(
//       resposta
//     );
//   }
//   else
//   {
//     Serial.println(
//       "Falha ao comunicar com a API."
//     );
//   }

//   http.end();
// }

// // =====================================================
// // ENVIAR LEITURA
// // =====================================================

// void enviarLeitura()
// {
//   if (
//     WiFi.status() !=
//     WL_CONNECTED
//   )
//   {
//     return;
//   }

//   // Ainda não cadastrado
//   if (
//     strlen(API_KEY) == 0 ||
//     String(API_KEY) ==
//       "COLOQUE_A_API_KEY_AQUI"
//   )
//   {
//     Serial.println(
//       "API Key ainda nao configurada."
//     );

//     return;
//   }

//   HTTPClient http;

//   String url =
//     String(API_BASE_URL) +
//     "/receive.php";

//   Serial.println();
//   Serial.println(
//     "Enviando leitura..."
//   );

//   http.begin(
//     url
//   );

//   http.addHeader(
//     "Content-Type",
//     "application/json"
//   );

//   http.addHeader(
//     "X-API-Key",
//     API_KEY
//   );

//   String json = "{";

//   json +=
//     "\"device_id\":\"";

//   json +=
//     ESP32_ID;

//   json +=
//     "\",";

//   json +=
//     "\"ppm\":";

//   json +=
//     String(
//       ppm,
//       2
//     );

//   json +=
//     ",";

//   json +=
//     "\"rssi\":";

//   json +=
//     String(
//       WiFi.RSSI()
//     );

//   json +=
//     "}";

//   Serial.println(
//     "JSON:"
//   );

//   Serial.println(
//     json
//   );

//   int httpCode =
//     http.POST(
//       json
//     );

//   Serial.print(
//     "HTTP LEITURA: "
//   );

//   Serial.println(
//     httpCode
//   );

//   if (
//     httpCode > 0
//   )
//   {
//     String resposta =
//       http.getString();

//     Serial.println(
//       "Resposta API:"
//     );

//     Serial.println(
//       resposta
//     );
//   }
//   else
//   {
//     Serial.println(
//       "Erro ao enviar leitura."
//     );
//   }

//   http.end();
// }

// // =====================================================
// // SETUP
// // =====================================================

// void setup()
// {
// Serial.begin(19200);

//   delay(
//     500
//   );

//   Serial.println();
//   Serial.println(
//     "================================"
//   );

//   Serial.println(
//     "       AERIS GUARD MQ135"
//   );

//   Serial.println(
//     "================================"
//   );

//   // ===================================================
//   // LEDs
//   // ===================================================

//   pinMode(
//     LED_VERDE,
//     OUTPUT
//   );

//   pinMode(
//     LED_AMARELO,
//     OUTPUT
//   );

//   pinMode(
//     LED_VERMELHO,
//     OUTPUT
//   );

//   // ===================================================
//   // BUZZER
//   // ===================================================

//   pinMode(
//     BUZZER,
//     OUTPUT
//   );

//   digitalWrite(
//     LED_VERDE,
//     LOW
//   );

//   digitalWrite(
//     LED_AMARELO,
//     LOW
//   );

//   digitalWrite(
//     LED_VERMELHO,
//     LOW
//   );

//   digitalWrite(
//     BUZZER,
//     LOW
//   );

//   // ===================================================
//   // ADC
//   // ===================================================

//   analogReadResolution(
//     12
//   );

//   // ===================================================
//   // I2C
//   // ===================================================

//   Wire.begin(
//     OLED_SDA,
//     OLED_SCL
//   );

//   // ===================================================
//   // OLED
//   // ===================================================

//   Serial.println(
//     "Inicializando OLED..."
//   );

//   if (
//     !display.begin(
//       SSD1306_SWITCHCAPVCC,
//       0x3C
//     )
//   )
//   {
//     Serial.println(
//       "ERRO: OLED nao encontrado!"
//     );

//     while (true)
//     {
//       digitalWrite(
//         LED_VERMELHO,
//         HIGH
//       );

//       delay(
//         200
//       );

//       digitalWrite(
//         LED_VERMELHO,
//         LOW
//       );

//       delay(
//         200
//       );
//     }
//   }

//   Serial.println(
//     "OLED OK!"
//   );

//   mostrarTelaInicial();

//   delay(
//     1500
//   );

//   // ===================================================
//   // WIFI
//   // ===================================================

//   conectarWiFi();

//   // ===================================================
//   // PRIMEIRA LEITURA
//   // ===================================================

//   mediaADC =
//     calcularMediaADC();

//   ppm =
//     converterParaPPM(
//       mediaADC
//     );

//   atualizarStatus();

//   atualizarDisplay();

//   // ===================================================
//   // PRIMEIRO ANNOUNCE
//   // ===================================================

//   if (
//     WiFi.status() ==
//     WL_CONNECTED
//   )
//   {
//     anunciarESP32();
//   }
// }

// // =====================================================
// // LOOP
// // =====================================================

// void loop()
// {
//   // ===================================================
//   // WIFI
//   // ===================================================

//   if (
//     WiFi.status() !=
//     WL_CONNECTED
//   )
//   {
//     if (
//       millis() -
//       ultimaTentativaWiFi >=
//       INTERVALO_RECONEXAO
//     )
//     {
//       ultimaTentativaWiFi =
//         millis();

//       Serial.println(
//         "Wi-Fi desconectado. Tentando novamente..."
//       );

//       conectarWiFi();
//     }
//   }

//   // ===================================================
//   // LEITURA DO SENSOR
//   // ===================================================

//   mediaADC =
//     calcularMediaADC();

//   ppm =
//     converterParaPPM(
//       mediaADC
//     );

//   // ===================================================
//   // STATUS
//   // ===================================================

//   atualizarStatus();

//   // ===================================================
//   // OLED
//   // ===================================================

//   atualizarDisplay();

//   // ===================================================
//   // SERIAL
//   // ===================================================

//   Serial.print(
//     "ADC medio: "
//   );

//   Serial.print(
//     mediaADC
//   );

//   Serial.print(
//     " | PPM estimado: "
//   );

//   Serial.print(
//     ppm
//   );

//   Serial.print(
//     " | Status: "
//   );

//   Serial.print(
//     ultimoStatus
//   );

//   Serial.print(
//     " | WiFi: "
//   );

//   if (
//     WiFi.status() ==
//     WL_CONNECTED
//   )
//   {
//     Serial.print(
//       WiFi.RSSI()
//     );

//     Serial.println(
//       " dBm"
//     );
//   }
//   else
//   {
//     Serial.println(
//       "OFFLINE"
//     );
//   }

//   // ===================================================
//   // ANNOUNCE
//   // ===================================================

//   if (
//     WiFi.status() ==
//       WL_CONNECTED &&
//     millis() -
//       ultimoAnnounce >=
//       INTERVALO_ANNOUNCE
//   )
//   {
//     ultimoAnnounce =
//       millis();

//     anunciarESP32();
//   }

//   // ===================================================
//   // ENVIO DE LEITURA
//   // ===================================================

//   if (
//     WiFi.status() ==
//       WL_CONNECTED &&
//     millis() -
//       ultimoEnvio >=
//       INTERVALO_ENVIO
//   )
//   {
//     ultimoEnvio =
//       millis();

//     enviarLeitura();
//   }

//   delay(
//     100
//   );
// }