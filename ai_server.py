import mysql.connector
import numpy as np
import tensorflow as tf
import requests
import time
from collections import deque
from datetime import datetime

# KONFIGURASI


DB_HOST = "localhost"
DB_USER = "root"
DB_PASS = ""
DB_NAME = "gelang_monitoring"

MODEL_PATH = "model_cnn.h5"

BOT_TOKEN = "8509486480:AAHdzT64DGlKiJ1zzoVo__cGhVxCwOIYLHU"
CHAT_ID   = "509064648"

WINDOW_SIZE = 20
DELAY_LOOP  = 0.20      # cepat
MIN_PROB    = 0.80
COOLDOWN    = 15        # detik anti spam telegram

labels = [
    "diam",
    "berjalan",
    "diam_jatuh",
    "jalan_jatuh"
]

# LOAD MODEL

print("Loading model...")
model = tf.keras.models.load_model(MODEL_PATH)
print("Model loaded.")

# MYSQL

def connect_db():
    return mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME
    )

db = connect_db()
cursor = db.cursor(dictionary=True)

# TELEGRAM

def send_telegram(msg):
    try:
        url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage"

        requests.post(url, data={
            "chat_id": CHAT_ID,
            "text": msg
        }, timeout=5)

    except:
        pass

# DATABASE INSERT

def simpan_jatuh(pasien_id, aktivitas):

    sql = """
    INSERT INTO data_jatuh
    (pasien_id,waktu_kejadian,aktivitas_sebelumnya)
    VALUES (%s,NOW(),%s)
    """

    cursor.execute(sql, (pasien_id, aktivitas))
    db.commit()

    return cursor.lastrowid


def simpan_notif(data_jatuh_id, pesan):

    sql = """
    INSERT INTO notifikasi
    (data_jatuh_id,pesan,status)
    VALUES (%s,%s,'terkirim')
    """

    cursor.execute(sql, (data_jatuh_id, pesan))
    db.commit()

# AMBIL DATA SENSOR

def ambil_sensor():

    global db, cursor

    try:
        if not db.is_connected():
            db = connect_db()
            cursor = db.cursor(dictionary=True)

        cursor.execute("SELECT * FROM sensor_realtime")
        return cursor.fetchall()

    except:
        db = connect_db()
        cursor = db.cursor(dictionary=True)
        return []

# PREDIKSI


def prediksi(window):

    x = np.array(window, dtype=np.float32)
    x = x.reshape(1, WINDOW_SIZE, 6)

    hasil = model.predict(x, verbose=0)[0]

    kelas = np.argmax(hasil)
    prob  = float(np.max(hasil))

    return labels[kelas], prob


# MEMORY


buffers = {}
last_predict = {}
last_alert_time = {}


# START


print("AI Server Running...")

while True:

    try:

        rows = ambil_sensor()

        for row in rows:

            pasien_id = int(row["pasien_id"])

            # skip dummy
            if pasien_id == 0:
                continue

            # skip NULL
            if row["ax"] is None:
                continue

            data = [
                float(row["ax"] or 0),
                float(row["ay"] or 0),
                float(row["az"] or 0),
                float(row["gx"] or 0),
                float(row["gy"] or 0),
                float(row["gz"] or 0)
            ]

            # skip semua nol
            if sum(np.abs(data)) == 0:
                continue

            # buat buffer pasien baru
            if pasien_id not in buffers:
                buffers[pasien_id] = deque(maxlen=WINDOW_SIZE)
                last_predict[pasien_id] = ""
                last_alert_time[pasien_id] = 0

            # SELALU MASUKKAN DATA
            buffers[pasien_id].append(data)

            print(
                f"Pasien {pasien_id} Buffer {len(buffers[pasien_id])}/{WINDOW_SIZE}"
            )

            # prediksi jika penuh
            if len(buffers[pasien_id]) == WINDOW_SIZE:

                kelas, prob = prediksi(buffers[pasien_id])

                print(
                    f"Pasien {pasien_id} => {kelas} ({prob:.2f})"
                )

                
                # JIKA JATUH
                if prob >= MIN_PROB and kelas in [
                    "diam_jatuh",
                    "jalan_jatuh"
                ]:

                    now = time.time()

                    # cooldown anti spam
                    if now - last_alert_time[pasien_id] > COOLDOWN:

                        aktivitas = "diam"

                        if kelas == "jalan_jatuh":
                            aktivitas = "berjalan"

                        data_jatuh_id = simpan_jatuh(
                            pasien_id,
                            aktivitas
                        )

                        pesan = f"""
⚠️ DETEKSI JATUH

Pasien ID : {pasien_id}
Aktivitas : {aktivitas}
Waktu     : {datetime.now().strftime('%H:%M:%S')}
Confidence: {prob:.2f}
"""

                        send_telegram(pesan)
                        simpan_notif(data_jatuh_id, pesan)

                        print("Telegram terkirim 🔥")

                        last_alert_time[pasien_id] = now

        time.sleep(DELAY_LOOP)

    except Exception as e:

        print("ERROR:", e)
        time.sleep(2)