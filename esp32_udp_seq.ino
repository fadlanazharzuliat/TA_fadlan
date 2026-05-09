// =======================================================
// ESP32 + MPU6050 DATASET RECORDER FINAL IEEE VERSION
// 200 Hz Sinkron Accelerometer + Gyroscope
// UDP + Sequence Number + Timestamp
// =======================================================

#include <WiFi.h>
#include <WiFiUdp.h>
#include <Wire.h>
#include <MPU6050.h>

const char* ssid     = "A26";
const char* password = "12345678";

// IP Laptop / PC penerima Python
const char* udpAddress = "10.241.83.13";
const int udpPort = 4210;

WiFiUDP udp;
MPU6050 mpu;

// ===============================
// SAMPLE RATE
// ===============================
const uint32_t SAMPLE_US = 5000;   // 5000 us = 200 Hz
uint32_t lastMicros = 0;

// sequence packet
uint32_t seq = 0;

// ===============================

void setup() {

  Serial.begin(115200);
  delay(1000);

  Wire.begin(21, 22);
  Wire.setClock(400000);   // I2C Fast Mode

  mpu.initialize();

  if (!mpu.testConnection()) {
    Serial.println("MPU6050 gagal terhubung!");
    while (1);
  }

  // =====================================
  // KONFIGURASI SENSOR PROFESIONAL
  // =====================================

  // Accelerometer ±2g (paling sensitif)
  mpu.setFullScaleAccelRange(MPU6050_ACCEL_FS_2);

  // Gyroscope ±250 deg/s (paling sensitif)
  mpu.setFullScaleGyroRange(MPU6050_GYRO_FS_250);

  // Sample Rate = 1000/(1+4)=200Hz
  mpu.setRate(4);

  // Digital Low Pass Filter
  mpu.setDLPFMode(MPU6050_DLPF_BW_42);

  // =====================================

  WiFi.begin(ssid, password);

  Serial.print("Menghubungkan WiFi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.println("WiFi Connected");
  Serial.print("IP ESP32: ");
  Serial.println(WiFi.localIP());

  udp.begin(udpPort);

  lastMicros = micros();
}

// =======================================================

void loop() {

  uint32_t now = micros();

  if (now - lastMicros >= SAMPLE_US) {

    lastMicros += SAMPLE_US;   // anti drift timer
    seq++;

    int16_t ax, ay, az;
    int16_t gx, gy, gz;

    // ambil 6 channel sinkron
    mpu.getMotion6(&ax, &ay, &az, &gx, &gy, &gz);

    // konversi ke satuan asli
    float fax = ax / 16384.0;
    float fay = ay / 16384.0;
    float faz = az / 16384.0;

    float fgx = gx / 131.0;
    float fgy = gy / 131.0;
    float fgz = gz / 131.0;

    // timestamp ms
    uint32_t tms = millis();

    // ===================================================
    // FORMAT:
    // seq,timestamp,ax,ay,az,gx,gy,gz
    // ===================================================

    char packet[128];

    snprintf(packet, sizeof(packet),
             "%lu,%lu,%.4f,%.4f,%.4f,%.4f,%.4f,%.4f",
             seq,
             tms,
             fax, fay, faz,
             fgx, fgy, fgz);

    udp.beginPacket(udpAddress, udpPort);
    udp.write((uint8_t*)packet, strlen(packet));
    udp.endPacket();

    // debug tiap 1 detik
    static uint32_t lastPrint = 0;

    if (millis() - lastPrint > 1000) {
      lastPrint = millis();

      Serial.print("SEQ: ");
      Serial.print(seq);

      Serial.print(" | Hz: ");
      Serial.print(seq / (millis() / 1000.0));

      Serial.print(" | AX:");
      Serial.print(fax);

      Serial.print(" AY:");
      Serial.print(fay);

      Serial.print(" AZ:");
      Serial.println(faz);
    }
  }
}