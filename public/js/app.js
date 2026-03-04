// public/js/app.js
console.log("✅ app.js cargado correctamente");

window.cambiarEstado = function (id, estado) {
  console.log("Botón clickeado - ID:", id, "Estado:", estado);

  const datos = new URLSearchParams();
  datos.append("id", id);
  datos.append("estado", estado);

  fetch("cambiar_estado.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: datos.toString(),
  })
    .then((response) => {
      console.log("Respuesta status:", response.status);
      if (!response.ok) {
        throw new Error("HTTP error! status: " + response.status);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Respuesta JSON:", data);
      if (data.success) {
        // En lugar de recargar, actualizamos el color del botón y el estado visual
        actualizarEstadoVisual(id, estado);
      } else {
        alert("Error: " + (data.error || "Error desconocido"));
      }
    })
    .catch((error) => {
      console.error("Error en fetch:", error);
      alert("Error de conexión: " + error.message);
    });
};

// Nueva función para actualizar la UI sin recargar
function actualizarEstadoVisual(id, nuevoEstado) {
  console.log("Actualizando UI para ID:", id, "nuevo estado:", nuevoEstado);

  // Buscar la tarjeta que contiene este resultado
  const botones = document.querySelectorAll(
    `button[onclick*="cambiarEstado(${id},"]`,
  );
  if (botones.length > 0) {
    const tarjeta = botones[0].closest(".result-card");
    if (tarjeta) {
      // Actualizar el texto del estado en la esquina
      const estadoSpan = tarjeta.querySelector(
        'div[style*="position: absolute"] span',
      );
      if (estadoSpan) {
        const textos = ["pendiente", "interesante", "descartado"];
        const colores = ["#6b7280", "#10b981", "#ef4444"];
        estadoSpan.textContent = textos[nuevoEstado];
        estadoSpan.style.background = colores[nuevoEstado] + "20";
        estadoSpan.style.color = colores[nuevoEstado];
      }

      // Actualizar colores de los botones
      const botonPendiente = tarjeta.querySelector('button[onclick*=", 0)"]');
      const botonInteresante = tarjeta.querySelector('button[onclick*=", 1)"]');
      const botonDescartado = tarjeta.querySelector('button[onclick*=", 2)"]');

      if (botonPendiente)
        botonPendiente.style.background =
          nuevoEstado === 0 ? "#4f46e5" : "#6b7280";
      if (botonInteresante)
        botonInteresante.style.background =
          nuevoEstado === 1 ? "#10b981" : "#6b7280";
      if (botonDescartado)
        botonDescartado.style.background =
          nuevoEstado === 2 ? "#ef4444" : "#6b7280";
    }
  }
}
