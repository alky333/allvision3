console.log("✔️ script.js cargado correctamente");

let charts = {};

function direccionAVientoGrados(direccion) {
  const mapa = {
    'Norte': 0, 'Noreste': 45, 'Este': 90, 'Sureste': 135,
    'Sur': 180, 'Suroeste': 225, 'Oeste': 270, 'Noroeste': 315
  };
  return mapa[direccion] !== undefined ? mapa[direccion] : 0;
}

function crearGrafico(id, label, unidad) {
  const ctx = document.getElementById(id).getContext('2d');
  return new Chart(ctx, {
    type: 'line',
    data: {
      labels: [],
      datasets: [{
        label: label,
        data: [],
        tension: 0.3,
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: { beginAtZero: true, title: { display: true, text: unidad } },
        x: { title: { display: true, text: 'Hora' } }
      }
    }
  });
}

function actualizarGrafico(grafico, valor) {
  const hora = new Date().toLocaleTimeString().slice(0, 5);
  if (grafico.data.labels.length > 10) {
    grafico.data.labels.shift();
    grafico.data.datasets[0].data.shift();
  }
  grafico.data.labels.push(hora);
  grafico.data.datasets[0].data.push(valor);
  grafico.update();
}

async function obtenerDatos() {
  try {
    const res = await fetch("datos.php?nocache=" + Date.now());
    const data = await res.json();

    // 👉 Forzado para pruebas: mostrar ícono
    // data.estado_lluvia = "Aguacero";

    // Mostrar valores en tarjetas
    document.getElementById('res-temp').textContent = data.temperatura + " °C";
    document.getElementById('res-hum').textContent = data.humedad + " %";
    document.getElementById('res-soil').textContent = data.suelo + " %";
    document.getElementById('res-light').textContent = data.luz + " lux";
    document.getElementById('res-wind').textContent = data.viento_velocidad + " km/h";
    document.getElementById('res-wind-dir').textContent = "Dirección: " + data.viento_direccion;
    document.getElementById('res-rain').textContent = data.lluvia_mm + " mm";
    document.getElementById('res-rain-total').textContent = "Total hoy: " + data.lluvia_mm_total + " mm";

    // Rotar flecha
    const grados = direccionAVientoGrados(data.viento_direccion);
    document.getElementById("wind-arrow").setAttribute("transform", `rotate(${grados},50,50)`);

    // Intensidad de lluvia con ícono
    const estado = data.estado_lluvia || "--";
    const icono = estado === "Ligera" ? "☁️" :
                  estado === "Moderada" ? "🌧️" :
                  estado === "Aguacero" ? "⛈️" : "❔";
    const statusElem = document.getElementById('res-rain-status');
    statusElem.textContent = `${icono} ${estado}`;
    statusElem.className = '';
    if (estado === 'Ligera') statusElem.classList.add('lluvia-ligera');
    else if (estado === 'Moderada') statusElem.classList.add('lluvia-moderada');
    else if (estado === 'Aguacero') statusElem.classList.add('lluvia-aguacero');
    else statusElem.classList.add('text-light');

    // Actualizar gráficos
    actualizarGrafico(charts.temp, data.temperatura);
    actualizarGrafico(charts.hum, data.humedad);
    actualizarGrafico(charts.soil, data.suelo);
    actualizarGrafico(charts.light, data.luz);
    actualizarGrafico(charts.wind, data.viento_velocidad);
    actualizarGrafico(charts.rain, data.lluvia_mm);

  } catch (error) {
    console.error("❌ Error al obtener datos:", error);
  }
}

function iniciarGraficos() {
  charts.temp = crearGrafico("tempChart", "Temperatura", "°C");
  charts.hum = crearGrafico("humChart", "Humedad", "%");
  charts.soil = crearGrafico("soilChart", "H. Suelo", "%");
  charts.light = crearGrafico("lightChart", "Luz", "lux");
  charts.wind = crearGrafico("windChart", "Velocidad Viento", "km/h");
  charts.rain = crearGrafico("rainChart", "Lluvia", "mm");
}

window.onload = () => {
  iniciarGraficos();
  obtenerDatos();
  setInterval(obtenerDatos, 5000);
};
