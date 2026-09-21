// #include <WiFi.h>
// #include <HTTPClient.h>
// #include <WebServer.h>
// #include <ESPmDNS.h>
// #include <Wire.h>
// #include <Adafruit_GFX.h>
// #include <Adafruit_SSD1306.h>
// #include "soc/soc.h"
// #include "soc/rtc_cntl_reg.h"

// // =====================================================
// // WI-FI
// //
// // NUNCA suba credenciais reais para um repositório público.
// // Troque os valores abaixo apenas na sua cópia local antes
// // de gravar o firmware (este arquivo pode ser versionado com
// // placeholders, como está aqui).
// // =====================================================

// const char* WIFI_SSID     = "motoedge60fusion_2923";
// const char* WIFI_PASSWORD = "javali244";

// // =====================================================
// // SERVIDOR (backend PHP + MySQL)
// //
// // Aponte para o IP local do computador que roda o XAMPP/PHP
// // na MESMA rede Wi-Fi do ESP32. Não é preciso internet nem
// // expor o servidor publicamente — tudo roda na rede local.
// // =====================================================

// const char* SERVER_HOST = "10.46.21.202";          // IP deste notebook na rede motoedge60fusion_2923
// const int   SERVER_PORT = 8000;                    // porta do Caddy (tools\Caddyfile / tools\iniciar-servidor.bat)
// const char* API_BASE    = "/api";                  // sem prefixo (servidor roda direto na raiz do projeto)

// // =====================================================
// // IDENTIFICAÇÃO DO DISPOSITIVO
// //
// // esp32_id e api_key vêm do cadastro feito no dashboard
// // (botão "Conectar dispositivo" ou tela de dispositivos).
// // A api_key só é exibida UMA VEZ no momento do cadastro.
// // =====================================================

// const char* ESP32_ID           = "ESP32-MQ6-001";
// const char* MANUFACTURER_CODE  = "AERIS-MQ6-001";
// const char* API_KEY            = "f40322901073082d7eb181660981fa17f8eed4353814b3d5";
// const char* HOSTNAME           = "Aeris-MQ6";
// const char* FIRMWARE_VERSION   = "2.0.0";

// // =====================================================
// // OLED (SSD1306 128x64 I2C)
// // =====================================================

// #define SCREEN_WIDTH 128
// #define SCREEN_HEIGHT 64
// #define OLED_RESET -1

// #define OLED_SDA 21
// #define OLED_SCL 22

// Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);
// bool oledOk = false;

// // =====================================================
// // PINOS
// // =====================================================

// #define SENSOR_PIN 34   // ADC1 (input only) — necessário para usar Wi-Fi ao mesmo tempo

// #define LED_VERDE    25
// #define LED_AMARELO  26
// #define LED_VERMELHO 27

// #define BUZZER 18

// // =====================================================
// // LEITURA DO MQ-6
// //
// // Ligação prevista (divisor resistivo para proteger o ADC
// // de 3.3V do ESP32, já que o MQ-6 é alimentado em 5V):
// //
// //   MQ-6 VCC -> 5V
// //   MQ-6 GND -> GND
// //   MQ-6 AO  -> resistor 10k -> GPIO34
// //   GPIO34   -> resistor 20k -> GND
// //   MQ-6 DO  -> não utilizado
// //
// // IMPORTANTE: o MQ-6 não está calibrado neste projeto (não há
// // curva Rs/R0 nem valor de R0 no ar limpo medido). Por isso:
// //   - rawAdc       = leitura real do sensor (0-4095), o dado confiável.
// //   - estimatedPpm = conversão linear simples do rawAdc, apenas
// //                    para dar uma ideia de tendência/nível na
// //                    demonstração. NÃO é uma concentração real
// //                    de gás em ppm. Para uso real, calibre o
// //                    sensor e substitua converterParaPpmEstimado().
// // =====================================================

// #define NUM_LEITURAS 10
// #define INTERVALO_LEITURA_MS 15

// #define ADC_MIN 0
// #define ADC_MAX 4095

// // Divisor resistivo: node = AO * R2/(R1+R2) = AO * 20/(10+20)
// #define DIVISOR_R1 10000.0
// #define DIVISOR_R2 20000.0
// #define ADC_VREF 3.3

// // Faixa usada apenas para a ESTIMATIVA (não calibrada)
// #define PPM_ESTIMADO_MIN 0
// #define PPM_ESTIMADO_MAX 2000

// // =====================================================
// // LIMITES LOCAIS (LED/buzzer)
// //
// // Usados apenas para o indicador visual/sonoro do próprio
// // ESP32, funcionando mesmo sem Wi-Fi. Os limites "oficiais"
// // usados pelo dashboard ficam salvos no banco (user_settings,
// // tela Configurações) e são aplicados pelo backend em
// // api/receive.php ao classificar cada leitura recebida.
// // =====================================================

// #define LIMITE_NORMAL  400
// #define LIMITE_ATENCAO 550
// #define LIMITE_PERIGO  700

// // =====================================================
// // INTERVALOS (não bloqueantes)
// // =====================================================

// #define INTERVALO_ENVIO_MS     500UL    // envia leitura para /receive.php (perto do limite util do sensor)
// #define INTERVALO_ANUNCIO_MS   10000UL  // avisa presença para /app.php?action=announce
// #define INTERVALO_RECONEXAO_MS 5000UL   // intervalo entre tentativas de reconexão Wi-Fi
// #define TIMEOUT_WIFI_MS        15000UL  // tempo máximo esperando conectar

// // =====================================================
// // SERVIDOR HTTP LOCAL (diagnóstico na rede local)
// // =====================================================

// WebServer server(80);

// // =====================================================
// // ESTADO
// // =====================================================

// int    rawAdc = 0;
// float  nodeVoltage = 0;
// float  sensorVoltage = 0;
// float  estimatedPpm = 0;
// String statusAtual = "NORMAL";

// unsigned long ultimoBipe = 0;
// unsigned long ultimoEnvio = 0;
// unsigned long ultimoAnuncio = 0;
// unsigned long ultimaTentativaReconexao = 0;
// bool servidorRespondendo = false;

// // =====================================================
// // CORS (endpoints locais de diagnóstico)
// // =====================================================

// void adicionarCORS() {
//   server.sendHeader("Access-Control-Allow-Origin", "*");
//   server.sendHeader("Access-Control-Allow-Methods", "GET, OPTIONS");
//   server.sendHeader("Access-Control-Allow-Headers", "Content-Type");
// }

// // =====================================================
// // LEITURA DO SENSOR
// // =====================================================

// int lerMediaADC() {
//   long soma = 0;
//   for (int i = 0; i < NUM_LEITURAS; i++) {
//     soma += analogRead(SENSOR_PIN);
//     delay(INTERVALO_LEITURA_MS);
//   }
//   return (int)(soma / NUM_LEITURAS);
// }

// // Estimativa simples e NÃO calibrada — ver aviso acima.
// float converterParaPpmEstimado(int adc) {
//   adc = constrain(adc, ADC_MIN, ADC_MAX);
//   return ((float)(adc - ADC_MIN) * (PPM_ESTIMADO_MAX - PPM_ESTIMADO_MIN) /
//           (ADC_MAX - ADC_MIN)) + PPM_ESTIMADO_MIN;
// }

// void atualizarLeitura() {
//   rawAdc = lerMediaADC();

//   nodeVoltage = (rawAdc / (float)ADC_MAX) * ADC_VREF;
//   sensorVoltage = nodeVoltage * ((DIVISOR_R1 + DIVISOR_R2) / DIVISOR_R2);

//   estimatedPpm = converterParaPpmEstimado(rawAdc);
// }

// // =====================================================
// // STATUS / LEDS / BUZZER (indicador local, sempre ativo)
// // =====================================================

// void atualizarStatusLocal() {
//   if (estimatedPpm < LIMITE_NORMAL) {
//     digitalWrite(LED_VERDE, HIGH);
//     digitalWrite(LED_AMARELO, LOW);
//     digitalWrite(LED_VERMELHO, LOW);
//     digitalWrite(BUZZER, LOW);
//     statusAtual = "NORMAL";

//   } else if (estimatedPpm < LIMITE_ATENCAO) {
//     digitalWrite(LED_VERDE, LOW);
//     digitalWrite(LED_AMARELO, HIGH);
//     digitalWrite(LED_VERMELHO, LOW);
//     digitalWrite(BUZZER, LOW);
//     statusAtual = "ATENCAO";

//   } else if (estimatedPpm < LIMITE_PERIGO) {
//     digitalWrite(LED_VERDE, LOW);
//     digitalWrite(LED_AMARELO, HIGH);
//     digitalWrite(LED_VERMELHO, LOW);
//     statusAtual = "ALERTA";

//     if (millis() - ultimoBipe >= 2000) {
//       digitalWrite(BUZZER, HIGH);
//       delay(150);
//       digitalWrite(BUZZER, LOW);
//       ultimoBipe = millis();
//     }

//   } else {
//     digitalWrite(LED_VERDE, LOW);
//     digitalWrite(LED_AMARELO, LOW);
//     digitalWrite(LED_VERMELHO, HIGH);
//     digitalWrite(BUZZER, HIGH);
//     statusAtual = "PERIGO";
//   }
// }

// // =====================================================
// // OLED
// // =====================================================

// void mostrarTelaInicial() {
//   if (!oledOk) return;
//   display.clearDisplay();
//   display.setTextColor(SSD1306_WHITE);
//   display.setTextSize(2);
//   display.setCursor(0, 0);
//   display.println("AERIS");
//   display.setTextSize(1);
//   display.setCursor(0, 24);
//   display.println("Guard - Deteccao de gas");
//   display.setCursor(0, 40);
//   display.println("Sensor: MQ-6");
//   display.setCursor(0, 52);
//   display.println("Iniciando...");
//   display.display();
// }

// /*
//  * Display objetivo: só o que importa pra quem olha de longe —
//  * o PPM (estimativa, por isso o "*"), o estado por extenso, e um
//  * indicador de conexão compacto no canto. O valor bruto do ADC e o
//  * detalhe separado de Wi-Fi/servidor continuam disponíveis pelo
//  * dashboard (api/data.php), não precisam ocupar a telinha do sensor.
//  */
// void atualizarDisplay() {
//   if (!oledOk) return;

//   display.clearDisplay();
//   display.setTextColor(SSD1306_WHITE);

//   display.setTextSize(1);
//   display.setCursor(0, 0);
//   display.print("SADAG");

//   display.setCursor(94, 0);
//   if (WiFi.status() != WL_CONNECTED) {
//     display.print("OFF");
//   } else if (servidorRespondendo) {
//     display.print("ON");
//   } else {
//     display.print("WIFI");
//   }

//   display.drawLine(0, 10, 127, 10, SSD1306_WHITE);

//   display.setTextSize(3);
//   display.setCursor(4, 20);
//   display.print((int)estimatedPpm);

//   display.setTextSize(1);
//   display.setCursor(92, 36);
//   display.print("ppm*");

//   display.setTextSize(2);
//   display.setCursor(4, 46);
//   display.print(statusAtual);

//   display.display();
// }

// // =====================================================
// // WI-FI (conexão inicial + reconexão não bloqueante)
// // =====================================================

// void conectarWiFi() {
//   Serial.println();
//   Serial.println("==== CONEXAO WI-FI ====");

//   WiFi.mode(WIFI_STA);
//   WiFi.setAutoReconnect(true);
//   WiFi.persistent(false);
//   WiFi.setHostname(HOSTNAME);
//   WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

//   Serial.print("Conectando");
//   unsigned long inicio = millis();

//   while (WiFi.status() != WL_CONNECTED && millis() - inicio < TIMEOUT_WIFI_MS) {
//     delay(300);
//     Serial.print(".");
//   }
//   Serial.println();

//   if (WiFi.status() == WL_CONNECTED) {
//     Serial.println("Wi-Fi conectado!");
//     Serial.print("IP: ");
//     Serial.println(WiFi.localIP());
//   } else {
//     Serial.println("Falha ao conectar ao Wi-Fi (seguindo offline).");
//   }
// }

// // Chamada a cada loop; nunca trava o restante do sistema.
// void garantirWiFi() {
//   if (WiFi.status() == WL_CONNECTED) return;

//   if (millis() - ultimaTentativaReconexao < INTERVALO_RECONEXAO_MS) return;
//   ultimaTentativaReconexao = millis();

//   Serial.println("Wi-Fi desconectado, tentando reconectar...");
//   WiFi.disconnect();
//   WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
// }

// // =====================================================
// // COMUNICAÇÃO COM O BACKEND
// // =====================================================

// String urlFor(const char* caminho) {
//   String url = "http://";
//   url += SERVER_HOST;
//   url += ":";
//   url += String(SERVER_PORT);
//   url += API_BASE;
//   url += caminho;
//   return url;
// }

// // Envia a leitura atual para api/receive.php
// void enviarLeitura() {
//   if (WiFi.status() != WL_CONNECTED) return;

//   HTTPClient http;
//   http.begin(urlFor("/receive.php"));
//   http.addHeader("Content-Type", "application/json");
//   http.addHeader("X-API-Key", API_KEY);
//   http.setTimeout(4000);

//   String payload = "{";
//   payload += "\"device_id\":\"" + String(ESP32_ID) + "\",";
//   payload += "\"api_key\":\"" + String(API_KEY) + "\",";
//   payload += "\"ppm\":" + String(estimatedPpm, 2) + ",";
//   payload += "\"raw_adc\":" + String(rawAdc) + ",";
//   payload += "\"rssi\":" + String(WiFi.RSSI());
//   payload += "}";

//   int codigo = http.POST(payload);
//   servidorRespondendo = (codigo > 0 && codigo < 500);

//   if (codigo > 0) {
//     Serial.print("POST /receive.php -> ");
//     Serial.println(codigo);
//   } else {
//     Serial.print("Falha ao enviar leitura: ");
//     Serial.println(http.errorToString(codigo));
//   }

//   http.end();
// }

// // Anuncia presença para api/app.php?action=announce (usado pelo
// // botão "Procurar dispositivo na rede" do dashboard).
// void anunciarPresenca() {
//   if (WiFi.status() != WL_CONNECTED) return;

//   HTTPClient http;
//   http.begin(urlFor("/app.php?action=announce"));
//   http.addHeader("Content-Type", "application/json");
//   http.setTimeout(4000);

//   String payload = "{";
//   payload += "\"esp32_id\":\"" + String(ESP32_ID) + "\",";
//   payload += "\"manufacturer_code\":\"" + String(MANUFACTURER_CODE) + "\",";
//   payload += "\"hostname\":\"" + String(HOSTNAME) + "\",";
//   payload += "\"ip\":\"" + WiFi.localIP().toString() + "\",";
//   payload += "\"rssi\":" + String(WiFi.RSSI()) + ",";
//   payload += "\"firmware_version\":\"" + String(FIRMWARE_VERSION) + "\"";
//   payload += "}";

//   int codigo = http.POST(payload);
//   if (codigo > 0) {
//     Serial.print("POST /app.php?action=announce -> ");
//     Serial.println(codigo);
//   }

//   http.end();
// }

// // =====================================================
// // ENDPOINTS LOCAIS (diagnóstico direto na rede, sem
// // depender do backend — úteis para testes na feira)
// // =====================================================

// void handleHealth() {
//   adicionarCORS();
//   server.send(200, "application/json", "{\"success\":true,\"status\":\"online\"}");
// }

// void handleInfo() {
//   adicionarCORS();
//   String json = "{";
//   json += "\"success\":true,";
//   json += "\"esp32_id\":\"" + String(ESP32_ID) + "\",";
//   json += "\"manufacturer_code\":\"" + String(MANUFACTURER_CODE) + "\",";
//   json += "\"hostname\":\"" + String(HOSTNAME) + "\",";
//   json += "\"ip\":\"" + WiFi.localIP().toString() + "\",";
//   json += "\"rssi\":" + String(WiFi.RSSI()) + ",";
//   json += "\"firmware_version\":\"" + String(FIRMWARE_VERSION) + "\",";
//   json += "\"sensor\":\"MQ-6\",";
//   json += "\"status\":\"" + statusAtual + "\"";
//   json += "}";
//   server.send(200, "application/json", json);
// }

// void handleData() {
//   adicionarCORS();
//   String json = "{";
//   json += "\"success\":true,";
//   json += "\"esp32_id\":\"" + String(ESP32_ID) + "\",";
//   json += "\"raw_adc\":" + String(rawAdc) + ",";
//   json += "\"sensor_voltage\":" + String(sensorVoltage, 3) + ",";
//   json += "\"estimated_ppm\":" + String(estimatedPpm, 2) + ",";
//   json += "\"calibrated\":false,";
//   json += "\"status\":\"" + statusAtual + "\",";
//   json += "\"rssi\":" + String(WiFi.RSSI()) + ",";
//   json += "\"ip\":\"" + WiFi.localIP().toString() + "\"";
//   json += "}";
//   server.send(200, "application/json", json);
// }

// void handleStatus() {
//   adicionarCORS();
//   String json = "{";
//   json += "\"online\":" + String(WiFi.status() == WL_CONNECTED ? "true" : "false") + ",";
//   json += "\"estimated_ppm\":" + String(estimatedPpm, 2) + ",";
//   json += "\"status\":\"" + statusAtual + "\",";
//   json += "\"rssi\":" + String(WiFi.RSSI());
//   json += "}";
//   server.send(200, "application/json", json);
// }

// void handleOptions() {
//   adicionarCORS();
//   server.send(204);
// }

// void iniciarServidorLocal() {
//   server.on("/health", HTTP_GET, handleHealth);
//   server.on("/info", HTTP_GET, handleInfo);
//   server.on("/data", HTTP_GET, handleData);
//   server.on("/status", HTTP_GET, handleStatus);

//   server.on("/health", HTTP_OPTIONS, handleOptions);
//   server.on("/info", HTTP_OPTIONS, handleOptions);
//   server.on("/data", HTTP_OPTIONS, handleOptions);
//   server.on("/status", HTTP_OPTIONS, handleOptions);

//   server.begin();

//   Serial.println("Servidor HTTP local iniciado (diagnostico na rede).");
//   Serial.print("http://");
//   Serial.println(WiFi.localIP());

//   if (MDNS.begin("aeris-mq6")) {
//     MDNS.addService("http", "tcp", 80);
//     Serial.println("mDNS: http://aeris-mq6.local");
//   }
// }

// // =====================================================
// // SETUP
// // =====================================================

// void setup() {
//   // Mitigacao para queda de tensao ao ligar o radio Wi-Fi (nao
//   // substitui uma fonte/cabo USB adequados — so evita reset em
//   // picos pequenos causados pelo consumo do Wi-Fi).
//   WRITE_PERI_REG(RTC_CNTL_BROWN_OUT_REG, 0);

//   Serial.begin(115200);
//   delay(300);

//   pinMode(LED_VERDE, OUTPUT);
//   pinMode(LED_AMARELO, OUTPUT);
//   pinMode(LED_VERMELHO, OUTPUT);
//   pinMode(BUZZER, OUTPUT);
//   digitalWrite(LED_VERDE, LOW);
//   digitalWrite(LED_AMARELO, LOW);
//   digitalWrite(LED_VERMELHO, LOW);
//   digitalWrite(BUZZER, LOW);

//   analogReadResolution(12);

//   Wire.begin(OLED_SDA, OLED_SCL);
//   oledOk = display.begin(SSD1306_SWITCHCAPVCC, 0x3C);

//   if (!oledOk) {
//     // Sem travar o sistema: o sensor e os LEDs continuam
//     // funcionando normalmente mesmo sem o display.
//     Serial.println("AVISO: OLED nao encontrado, seguindo sem display.");
//   } else {
//     mostrarTelaInicial();
//     delay(1200);
//   }

//   conectarWiFi();

//   if (WiFi.status() == WL_CONNECTED) {
//     iniciarServidorLocal();
//   }

//   atualizarLeitura();
//   atualizarStatusLocal();
//   atualizarDisplay();
// }

// // =====================================================
// // LOOP
// // =====================================================

// void loop() {
//   garantirWiFi();

//   if (WiFi.status() == WL_CONNECTED) {
//     server.handleClient();
//   }

//   atualizarLeitura();
//   atualizarStatusLocal();
//   atualizarDisplay();

//   unsigned long agora = millis();

//   if (agora - ultimoEnvio >= INTERVALO_ENVIO_MS) {
//     ultimoEnvio = agora;
//     enviarLeitura();
//   }

//   if (agora - ultimoAnuncio >= INTERVALO_ANUNCIO_MS) {
//     ultimoAnuncio = agora;
//     anunciarPresenca();
//   }

//   Serial.print("ADC: ");
//   Serial.print(rawAdc);
//   Serial.print(" | Estim.: ");
//   Serial.print(estimatedPpm);
//   Serial.print(" ppm* | Status: ");
//   Serial.print(statusAtual);
//   Serial.print(" | WiFi: ");
//   Serial.println(WiFi.status() == WL_CONNECTED ? String(WiFi.RSSI()) + " dBm" : "OFFLINE");

//   delay(50);
// }
