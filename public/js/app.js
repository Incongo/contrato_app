// js/app.js
function cambiarEstado(id, estado) {
  console.log("Cambiando estado:", id, estado);

  fetch("cambiar_estado.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "id=" + id + "&estado=" + estado,
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Respuesta:", data);
      if (data.success) {
        // Actualizar la UI sin recargar
        actualizarEstadoUI(id, estado);
        actualizarContadores();
      } else {
        alert("Error al cambiar estado");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error de conexión");
    });
}

function actualizarEstadoUI(id, nuevoEstado) {
  // Buscar la tarjeta que contiene este resultado
  const botones = document.querySelectorAll(
    `button[onclick*="cambiarEstado(${id},"]`,
  );
  if (botones.length === 0) return;

  const tarjeta = botones[0].closest(".bg-white.rounded-xl");
  if (!tarjeta) return;

  // 1. Actualizar el badge de estado (esquina superior derecha)
  const badge = tarjeta.querySelector("div.absolute.top-3.right-3 span");
  if (badge) {
    const textos = ["pendiente", "interesante", "descartado"];
    const colores = ["#6b7280", "#10b981", "#ef4444"];
    badge.textContent = textos[nuevoEstado];
    badge.style.backgroundColor = colores[nuevoEstado] + "15";
    badge.style.color = colores[nuevoEstado];
  }

  // 2. Actualizar colores de los botones
  const botonPendiente = tarjeta.querySelector('button[onclick*=", 0)"]');
  const botonInteresante = tarjeta.querySelector('button[onclick*=", 1)"]');
  const botonDescartado = tarjeta.querySelector('button[onclick*=", 2)"]');

  if (botonPendiente) {
    botonPendiente.style.background = nuevoEstado === 0 ? "#4f46e5" : "#f3f4f6";
    botonPendiente.style.color = nuevoEstado === 0 ? "white" : "#374151";
  }
  if (botonInteresante) {
    botonInteresante.style.background =
      nuevoEstado === 1 ? "#10b981" : "#f3f4f6";
    botonInteresante.style.color = nuevoEstado === 1 ? "white" : "#374151";
  }
  if (botonDescartado) {
    botonDescartado.style.background =
      nuevoEstado === 2 ? "#ef4444" : "#f3f4f6";
    botonDescartado.style.color = nuevoEstado === 2 ? "white" : "#374151";
  }
}

function actualizarContadores() {
  fetch("estadisticas_estados.php")
    .then((response) => response.json())
    .then((data) => {
      // Actualizar los tres contadores
      const contadores = document.querySelectorAll(
        ".grid-cols-1.md\\:grid-cols-3 .text-3xl",
      );
      if (contadores.length >= 3) {
        contadores[0].textContent = data.pendientes;
        contadores[1].textContent = data.interesantes;
        contadores[2].textContent = data.descartados;
      }
    });
}
