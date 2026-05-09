<!DOCTYPE html>
<html>
<head>
    <title>Nurse Station Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial;
            background: #0a0f1a;
            color: #fff;
        }

        h1 {
            text-align: center;
            color: #00ffcc;
        }

        #container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .card {
            background: #111827;
            border-radius: 12px;
            padding: 15px;
            margin: 10px;
            width: 420px;
            box-shadow: 0 0 15px #00ffcc55;
        }

        canvas {
            background: #000;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .jatuh { color: red; font-weight: bold; }
        .normal { color: #00ffcc; font-weight: bold; }
        .online { color: #00ffcc; }
        .offline { color: red; }
    </style>
</head>

<body>

<h1>🩺 Monitoring Pasien</h1>

<div id="container"></div>

<script>
let charts = {};

function createChart(id, label) {
    let ctx = document.getElementById(id).getContext('2d');

    charts[id] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: label+' X', data: [], borderColor: 'red' },
                { label: label+' Y', data: [], borderColor: 'lime' },
                { label: label+' Z', data: [], borderColor: 'cyan' }
            ]
        },
        options: {
            animation: false,
            scales: {
                x: { display: false },
                y: { ticks: { color: '#fff' } }
            },
            plugins: {
                legend: { labels: { color: '#fff' } }
            }
        }
    });
}

function updateChart(id, x, y, z) {
    let chart = charts[id];
    let t = new Date().toLocaleTimeString();

    chart.data.labels.push(t);
    chart.data.datasets[0].data.push(x);
    chart.data.datasets[1].data.push(y);
    chart.data.datasets[2].data.push(z);

    if (chart.data.labels.length > 20) {
        chart.data.labels.shift();
        chart.data.datasets.forEach(ds => ds.data.shift());
    }

    chart.update();
}

function loadData() {
    fetch('data.php')
    .then(res => res.json())
    .then(data => {

        let container = document.getElementById("container");

        data.forEach(p => {

            let id = p.pasien_id;
            let gyroId = "gyro_"+id;
            let accId = "acc_"+id;

            if (!charts[gyroId]) {

                let div = document.createElement("div");
                div.className = "card";

                div.innerHTML = `
                    <h3>${p.nama}</h3>
                    <p>Kamar: ${p.no_kamar}</p>
                    <p id="status_${id}"></p>

                    <b>Gyroscope</b>
                    <canvas id="${gyroId}" height="120"></canvas>

                    <b>Accelerometer</b>
                    <canvas id="${accId}" height="120"></canvas>
                `;

                container.appendChild(div);

                createChart(gyroId, "Gyro");
                createChart(accId, "Acc");
            }

            // update grafik
            updateChart(gyroId, p.gx ?? 0, p.gy ?? 0, p.gz ?? 0);
            updateChart(accId, p.ax ?? 0, p.ay ?? 0, p.az ?? 0);

            // status jatuh
            let status = "<span class='normal'>NORMAL</span>";
            if (p.aktivitas && p.aktivitas.includes("jatuh")) {
                status = "<span class='jatuh'>⚠️ JATUH</span>";
            }

            // status koneksi
            let koneksi = "<span class='offline'>OFFLINE 🔴</span>";

            if (p.waktu_sensor) {
                let last = new Date(p.waktu_sensor.replace(' ', 'T')).getTime();
                let now = new Date().getTime();

                if ((now - last)/1000 < 10) {
                    koneksi = "<span class='online'>ONLINE 🟢</span>";
                }
            }

            document.getElementById("status_"+id).innerHTML =
                "Status: "+status+"<br>Koneksi: "+koneksi;
        });
    });
}

loadData();
setInterval(loadData, 2000);
</script>

</body>
</html>