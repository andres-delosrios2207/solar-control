<?php
require_once 'conexion.php';

// Si la petición llega por POST (desde el chat en JavaScript), procesamos el mensaje y devolvemos JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $entrada = json_decode(file_get_contents('php://input'), true);
    $mensaje = trim($entrada['mensaje'] ?? '');
    $textoMinuscula = mb_strtolower($mensaje, 'UTF-8');

    $respuesta = "";

    // 1. Comando de bienvenida o ayuda
    if ($textoMinuscula === 'hola' || $textoMinuscula === 'ayuda' || $textoMinuscula === 'menu') {
        $respuesta = "Comandos disponibles conectados a la base de datos:\n\n"
                   . "• resumen -> Muestra el conteo total de citas y proyectos viables.\n"
                   . "• citas -> Lista las últimas 5 citas registradas.\n"
                   . "• buscar [nombre o ID] -> Consulta el estado de una cita (Ej: buscar CITA-001).\n"
                   . "• agendar [Cliente], [AAAA-MM-DD], [Tipo] -> Guarda una cita nueva en MySQL (Ej: agendar Laura Diaz, 2026-11-28, Técnica).";
    }
    // 2. Consulta de estadísticas con funciones de agregación SQL (COUNT)
    elseif ($textoMinuscula === 'resumen' || $textoMinuscula === 'estadisticas') {
        $total = $conexion->query("SELECT COUNT(*) FROM citas")->fetchColumn();
        $viables = $conexion->query("SELECT COUNT(*) FROM citas WHERE viable = 'Sí'")->fetchColumn();
        $revision = $conexion->query("SELECT COUNT(*) FROM citas WHERE viable = 'En revisión'")->fetchColumn();

        $respuesta = "Estado actual en MySQL (solar_control_db):\n"
                   . "• Total de citas registradas: $total\n"
                   . "• Proyectos viables: $viables\n"
                   . "• En revisión: $revision";
    }
    // 3. Consulta de las últimas citas (SELECT + ORDER BY + LIMIT)
    elseif ($textoMinuscula === 'citas') {
        $stmt = $conexion->query("SELECT id_cita, cliente, fecha, tipo_cita, viable FROM citas ORDER BY fecha DESC LIMIT 5");
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($filas) === 0) {
            $respuesta = "No hay citas guardadas en la tabla todavía.";
        } else {
            $respuesta = "Últimas citas en la base de datos:\n";
            foreach ($filas as $f) {
                $respuesta .= "• [{$f['id_cita']}] {$f['cliente']} | Fecha: {$f['fecha']} | Tipo: {$f['tipo_cita']} | Viable: {$f['viable']}\n";
            }
        }
    }
    // 4. Búsqueda parametrizada por ID o nombre de cliente (SELECT + WHERE + LIKE)
    elseif (strpos($textoMinuscula, 'buscar ') === 0) {
        $termino = trim(substr($mensaje, 7));
        if ($termino === '') {
            $respuesta = "Escribe un nombre o ID después de 'buscar'. Ejemplo: buscar CITA-001";
        } else {
            $stmt = $conexion->prepare("SELECT * FROM citas WHERE id_cita LIKE :q OR cliente LIKE :q LIMIT 3");
            $stmt->execute([':q' => "%$termino%"]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($resultados) === 0) {
                $respuesta = "No encontré ninguna cita que coincida con '$termino'.";
            } else {
                $respuesta = "Resultados encontrados:\n";
                foreach ($resultados as $r) {
                    $respuesta .= "• ID: {$r['id_cita']} - Cliente: {$r['cliente']}\n"
                               . "  Fecha: {$r['fecha']} | Asesor: {$r['asesor_asignado']}\n"
                               . "  Viable: {$r['viable']} | Checklist: {$r['checklist']}\n";
                }
            }
        }
    }
    // 5. Inserción directa en MySQL desde el chat (INSERT INTO)
    elseif (strpos($textoMinuscula, 'agendar ') === 0) {
        $datosTexto = trim(substr($mensaje, 8));
        $partes = array_map('trim', explode(',', $datosTexto));

        if (count($partes) < 3) {
            $respuesta = "Formato incorrecto. Usa: agendar Nombre Cliente, AAAA-MM-DD, Tipo\nEjemplo: agendar Carlos Ruiz, 2026-11-30, Técnica";
        } else {
            $cliente = $partes[0];
            $fecha = $partes[1];
            $tipo = $partes[2];
            $idGenerado = 'BOT-' . strtoupper(substr(uniqid(), -4));

            try {
                $sql = "INSERT INTO citas (id_cita, cliente, fecha, hora, tipo_cita, asesor_asignado, viable, checklist)
                        VALUES (:id, :cliente, :fecha, '09:00:00', :tipo, 'Por asignar', 'En revisión', 'Agendado por Bot')";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':id' => $idGenerado,
                    ':cliente' => $cliente,
                    ':fecha' => $fecha,
                    ':tipo' => $tipo
                ]);
                $respuesta = "Cita guardada en MySQL con el código $idGenerado para $cliente el día $fecha. Ya puedes verla en el panel principal.";
            } catch (PDOException $e) {
                $respuesta = "Error al guardar en la base de datos. Revisa que la fecha tenga el formato AAAA-MM-DD (ej. 2026-11-28).";
            }
        }
    }
    // Respuesta por defecto si no reconoce el comando
    else {
        $respuesta = "No entendí ese comando. Escribe 'ayuda' para ver las opciones conectadas a MySQL.";
    }

    echo json_encode(['respuesta' => $respuesta]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bot Asistente - SolarControl</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #eef2f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .chat-box { width: 420px; background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.12); display: flex; flex-direction: column; overflow: hidden; height: 540px; }
        .chat-header { background: #1a365d; color: #fff; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .chat-header a { color: #fbd38d; font-size: 12px; text-decoration: none; }
        .chat-messages { flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background: #f8fafc; }
        .msg { max-width: 82%; padding: 10px 12px; border-radius: 8px; font-size: 13px; line-height: 1.4; white-space: pre-line; }
        .msg-bot { background: #e2e8f0; color: #1a202c; align-self: flex-start; }
        .msg-user { background: #1a365d; color: #fff; align-self: flex-end; }
        .quick-btns { padding: 8px 10px; background: #edf2f7; display: flex; gap: 6px; flex-wrap: wrap; }
        .quick-btns button { background: #fff; border: 1px solid #cbd5e0; border-radius: 14px; padding: 4px 10px; font-size: 12px; cursor: pointer; }
        .quick-btns button:hover { background: #e2e8f0; }
        .chat-input { display: flex; border-top: 1px solid #cbd5e0; }
        .chat-input input { flex: 1; border: none; padding: 12px; font-size: 14px; outline: none; }
        .chat-input button { background: #1a365d; color: #fff; border: none; padding: 0 18px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

<div class="chat-box">
    <div class="chat-header">
        <span>Bot SolarControl (MySQL)</span>
        <a href="dashboard.php">Ir al Panel</a>
    </div>

    <div class="chat-messages" id="chatMessages">
        <div class="msg msg-bot">Hola. Soy el bot de consultas de SolarControl conectado a MySQL. Escribe "ayuda" o usa los botones de abajo para consultar o registrar citas.</div>
    </div>

    <div class="quick-btns">
        <button type="button" onclick="enviarRapido('ayuda')">Ayuda</button>
        <button type="button" onclick="enviarRapido('resumen')">Resumen BD</button>
        <button type="button" onclick="enviarRapido('citas')">Ver Citas</button>
    </div>

    <form class="chat-input" onsubmit="enviarMensaje(event)">
        <input type="text" id="inputMensaje" placeholder="Escribe un comando..." required autocomplete="off">
        <button type="submit">Enviar</button>
    </form>
</div>

<script>
    const chatMessages = document.getElementById('chatMessages');
    const inputMensaje = document.getElementById('inputMensaje');

    function agregarBurbuja(texto, clase) {
        const div = document.createElement('div');
        div.className = 'msg ' + clase;
        div.textContent = texto;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function enviarRapido(comando) {
        inputMensaje.value = comando;
        enviarMensaje(new Event('submit'));
    }

    async function enviarMensaje(e) {
        e.preventDefault();
        const texto = inputMensaje.value.trim();
        if (!texto) return;

        agregarBurbuja(texto, 'msg-user');
        inputMensaje.value = '';

        try {
            const resp = await fetch('bot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mensaje: texto })
            });
            const data = await resp.json();
            agregarBurbuja(data.respuesta, 'msg-bot');
        } catch (err) {
            agregarBurbuja('Error de conexión con el servidor PHP.', 'msg-bot');
        }
    }
</script>

</body>
</html>