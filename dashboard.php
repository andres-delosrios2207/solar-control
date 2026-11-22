<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

function subirArchivo($inputName) {
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
        $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
        $nombreOriginal = $_FILES[$inputName]['name'];
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        if (in_array($ext, $extensionesPermitidas)) {
            $nombreNuevo = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $nombreOriginal);
            $rutaDestino = "uploads/" . $nombreNuevo;
            if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $rutaDestino)) {
                return $rutaDestino;
            }
        }
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'agregar') {
    $id_cita         = trim($_POST['id_cita']);
    $cliente         = trim($_POST['cliente']);
    $telefono        = trim($_POST['telefono']);
    $direccion       = trim($_POST['direccion']);
    $fecha           = trim($_POST['fecha']);
    $hora            = trim($_POST['hora']);
    $tipo_cita       = trim($_POST['tipo_cita']);
    $asesor_asignado = trim($_POST['asesor_asignado']);
    $viable          = trim($_POST['viable']);
    $checklist       = isset($_POST['checklist']) ? implode(", ", $_POST['checklist']) : "Pendiente";

    $rutaEstudio = subirArchivo('estudio_area');
    $rutaPlanos  = subirArchivo('planos_casa');

    if (!empty($id_cita) && !empty($cliente) && !empty($fecha)) {
        try {
            $sql = "INSERT INTO citas (id_cita, cliente, telefono, direccion, fecha, hora, tipo_cita, asesor_asignado, estudio_area, planos_casa, viable, checklist) 
                    VALUES (:id_cita, :cliente, :telefono, :direccion, :fecha, :hora, :tipo_cita, :asesor_asignado, :estudio_area, :planos_casa, :viable, :checklist)";
            
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ':id_cita'         => $id_cita,
                ':cliente'         => $cliente,
                ':telefono'        => $telefono,
                ':direccion'       => $direccion,
                ':fecha'           => $fecha,
                ':hora'            => $hora,
                ':tipo_cita'       => $tipo_cita,
                ':asesor_asignado' => $asesor_asignado,
                ':estudio_area'    => $rutaEstudio,
                ':planos_casa'     => $rutaPlanos,
                ':viable'          => $viable,
                ':checklist'       => $checklist
            ]);

            $mensaje = "¡Cita y documentos registrados con éxito!";
            $tipo_mensaje = "exito";
        } catch (PDOException $e) {
            $mensaje = "Error al guardar: verifica que el ID de cita no esté repetido.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Completa al menos el ID, Cliente y Fecha.";
        $tipo_mensaje = "error";
    }
}

$stmt_citas = $conexion->query("SELECT * FROM citas ORDER BY fecha DESC, hora ASC");
$lista_citas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Citas - SolarControl</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e9ecef; margin: 0; padding: 20px; }
        .container { max-width: 1050px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .header { background-color: #1a365d; color: white; padding: 20px; text-align: center; position: relative; }
        .header h1 { margin: 0; font-size: 24px; }
        .user-info { position: absolute; right: 20px; top: 20px; font-size: 14px; }
        .user-info a { color: #fbd38d; text-decoration: none; margin-left: 10px; font-weight: bold; }
        .alert { padding: 12px; margin: 15px; border-radius: 4px; text-align: center; font-weight: bold; }
        .alert-exito { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 180px; gap: 20px; padding: 20px; }
        .section-title { grid-column: span 2; background-color: #edf2f7; padding: 8px 12px; font-weight: bold; color: #2d3748; border-left: 4px solid #1a365d; margin-bottom: 10px; }
        .field-group { margin-bottom: 12px; }
        .field-group label { display: block; font-size: 12px; font-weight: bold; color: #4a5568; margin-bottom: 4px; }
        .field-group input[type="text"], .field-group input[type="date"], .field-group select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #cbd5e0; border-radius: 4px; }
        .checkbox-group { display: flex; gap: 15px; font-size: 13px; margin-top: 5px; }
        .buttons-column { display: flex; flex-direction: column; gap: 10px; justify-content: center; }
        .btn-action { background-color: #1a365d; color: white; border: none; padding: 12px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-clear { background-color: #718096; }
        .table-container { padding: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; font-size: 13px; }
        th { background-color: #1a365d; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>SOLARCONTROL - REGISTRO DE CITA</h1>
        <div class="user-info">
            <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?> | 
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert <?php echo ($tipo_mensaje == 'exito') ? 'alert-exito' : 'alert-error'; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <form action="dashboard.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="accion" value="agregar">
        
        <div class="form-grid">
            <div>
                <div class="section-title">DATOS DEL CLIENTE</div>
                <div class="field-group">
                    <label>ID DE CITA</label>
                    <input type="text" name="id_cita" required placeholder="Ej. CITA-001">
                </div>
                <div class="field-group">
                    <label>CLIENTE</label>
                    <input type="text" name="cliente" required placeholder="Nombre del cliente">
                </div>
                <div class="field-group">
                    <label>TELÉFONO</label>
                    <input type="text" name="telefono" placeholder="Número de contacto">
                </div>
                <div class="field-group">
                    <label>DIRECCIÓN</label>
                    <input type="text" name="direccion" placeholder="Dirección del predio">
                </div>
            </div>

            <div>
                <div class="section-title">DATOS DE LA CITA</div>
                <div class="field-group">
                    <label>FECHA</label>
                    <input type="date" name="fecha" required>
                </div>
                <div class="field-group">
                    <label>HORA</label>
                    <select name="hora">
                        <option value="08:00:00">08:00 AM</option>
                        <option value="09:00:00">09:00 AM</option>
                        <option value="10:00:00">10:00 AM</option>
                        <option value="14:00:00">02:00 PM</option>
                        <option value="15:00:00">03:00 PM</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>TIPO DE CITA</label>
                    <select name="tipo_cita" id="tipo_cita" onchange="actualizarAsesores()">
                        <option value="Técnica">Técnica</option>
                        <option value="Comercial">Comercial</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>ASESOR ASIGNADO</label>
                    <select name="asesor_asignado" id="asesor_asignado"></select>
                </div>
            </div>

            <div class="buttons-column">
                <button type="submit" class="btn-action">Agregar</button>
                <button type="reset" class="btn-action btn-clear" onclick="setTimeout(actualizarAsesores, 10)">Limpiar</button>
            </div>

            <div style="grid-column: span 2;">
                <div class="section-title">ESTUDIOS Y VIABILIDAD</div>
                <div style="display: flex; gap: 15px; margin-bottom: 12px;">
                    <div class="field-group" style="flex: 1;">
                        <label>ESTUDIO DEL ÁREA (PDF o Imagen)</label>
                        <input type="file" name="estudio_area" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="field-group" style="flex: 1;">
                        <label>PLANOS DE LA CASA (PDF o Imagen)</label>
                        <input type="file" name="planos_casa" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="field-group" style="flex: 1;">
                        <label>¿PROYECTO VIABLE?</label>
                        <select name="viable">
                            <option value="Sí">Sí, es viable</option>
                            <option value="No">No es viable</option>
                            <option value="En revisión">En revisión</option>
                        </select>
                    </div>
                    <div class="field-group" style="flex: 2;">
                        <label>CHECKLIST DE VERIFICACIÓN</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="checklist[]" value="Techo revisado"> Techo revisado</label>
                            <label><input type="checkbox" name="checklist[]" value="Recibo de luz"> Recibo de luz</label>
                            <label><input type="checkbox" name="checklist[]" value="Planos listos"> Planos listos</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="table-container">
        <h2>Citas Registradas</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Tipo / Asesor</th>
                    <th>Estudio Área</th>
                    <th>Planos</th>
                    <th>Viable</th>
                    <th>Checklist</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($lista_citas) > 0): ?>
                    <?php foreach ($lista_citas as $cita): ?>
                        <tr>
                            <td><b><?php echo htmlspecialchars($cita['id_cita']); ?></b></td>
                            <td><?php echo htmlspecialchars($cita['cliente']); ?></td>
                            <td><?php echo htmlspecialchars($cita['fecha']); ?></td>
                            <td><?php echo htmlspecialchars($cita['tipo_cita'] . " - " . $cita['asesor_asignado']); ?></td>
                            <td>
                                <?php if ($cita['estudio_area']): ?>
                                    <a href="<?php echo htmlspecialchars($cita['estudio_area']); ?>" target="_blank">Ver archivo</a>
                                <?php else: ?> Sin archivo <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cita['planos_casa']): ?>
                                    <a href="<?php echo htmlspecialchars($cita['planos_casa']); ?>" target="_blank">Ver plano</a>
                                <?php else: ?> Sin plano <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($cita['viable']); ?></td>
                            <td><?php echo htmlspecialchars($cita['checklist']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center;">No hay citas registradas todavía.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const asesoresPorTipo = {
        "Técnica": ["Ing. Carlos Méndez", "Téc. Andrés de los Ríos"],
        "Comercial": ["Asesora Yulieth Maldonado", "Asesora Sara Moncada"],
        "Mantenimiento": ["Téc. Luis Gómez", "Téc. Jorge Ramírez"]
    };

    function actualizarAsesores() {
        const tipo = document.getElementById("tipo_cita").value;
        const selectAsesor = document.getElementById("asesor_asignado");
        selectAsesor.innerHTML = "";

        asesoresPorTipo[tipo].forEach(asesor => {
            const option = document.createElement("option");
            option.value = asesor;
            option.textContent = asesor;
            selectAsesor.appendChild(option);
        });
    }

    window.onload = actualizarAsesores;
</script>

</body>
</html>