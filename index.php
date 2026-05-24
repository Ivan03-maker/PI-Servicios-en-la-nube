<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo Animado con jQuery UI y PHP - PI</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/cupertino/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.jqueryui.min.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 15px; background-color: #f4f7f9; margin: 0; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); display: none; max-width: 1200px; margin: 0 auto; }
        h2 { color: #2e6e9e; text-align: center; font-size: 1.6em; }
        .btn-action { padding: 6px 10px; margin: 2px; cursor: pointer; font-size: 0.9em; }
        label, input { display:block; margin-bottom: 10px; width: 95%; }
        input { padding: 8px; }
        .ui-dialog { box-shadow: 0 5px 15px rgba(0,0,0,0.3); width: 90% !important; max-width: 500px; }
        .error-resaltado { border: 2px solid #cd0a0a !important; background: #fef1ec; }
        
        /* DISEÑO RESPONSIVO: Adaptación para tablas en móviles */
        .tabla-responsiva { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-top: 15px; }
        
        /* SECCIÓN DE MEDIOS DIGITALES EN LA NUBE */
        .cloud-media-section { background: #eef4f9; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #2e6e9e; }
        .cloud-media-section h3 { margin-top: 0; color: #2e6e9e; }
        .media-links { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px; }
        .media-card { background: white; padding: 10px 15px; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); flex: 1; min-width: 250px; text-align: center; }
        .media-card a { color: #2e6e9e; font-weight: bold; text-decoration: none; display: inline-block; margin-top: 5px; }
        .media-card a:hover { text-decoration: underline; }

        /* PIE DE PÁGINA / PORTADA INSTITUCIONAL */
        .main-footer { background-color: #2e6e9e; color: white; text-align: center; padding: 20px; margin-top: 30px; border-radius: 8px; font-size: 0.95em; line-height: 1.5; }
        .main-footer strong { color: #ffeb3b; }

        @media (max-width: 600px) {
            h2 { font-size: 1.2em; }
            .btn-action { display: block; width: 100%; text-align: center; margin: 5px 0; }
        }
    </style>
</head>
<body>

<div class="container ui-widget" id="contenedorPrincipal">
    <h2 class="ui-widget-header ui-corner-all" style="padding: 10px;">PI - Catálogo de Películas</h2>
    <br>
    <button id="btnNuevaPelicula" class="ui-button ui-widget ui-corner-all">Agregar Nueva Película</button>
    <br><br>
    
    <div class="tabla-responsiva">
        <table id="tablaPeliculas" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Director</th>
                    <th>Año</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <section class="cloud-media-section">
        <h3>☁️ Recursos Multimedia en Servicios Cloud</h3>
        <p>Accede a las plataformas de almacenamiento externo en la nube:</p>
        <div class="media-links">
            <div class="media-card">
                <strong>Almacenamiento en la nube de Google</strong><br>
                <a href="https://drive.google.com" target="_blank">Ver Google Drive ↗</a>
            </div>
            <div class="media-card">
                <strong>Almacenamiento en la nube de Microsoft</strong><br>
                <a href="https://onedrive.live.com" target="_blank">Ver OneDrive ↗</a>
            </div>
        </div>
    </section>

    <footer class="main-footer">
        <p><strong>Materia:</strong> Conceptualización de servicios en la nube</p>
        <p><strong>Actividad:</strong> Producto integrador: Aplicación web dinámica en un servicio de la nube</p>
        <p><strong>Alumno:</strong> Iván Alejandro Hernández Hernández &copy; 2026</p>
    </footer>
</div>

<div id="dialogoFormulario" title="Datos de la Película" style="display:none;">
    <form id="formPelicula">
        <input type="hidden" id="formId">
        <label for="formTitulo">Título *</label>
        <input type="text" id="formTitulo" class="ui-widget-content ui-corner-all">
        <label for="formDirector">Director *</label>
        <input type="text" id="formDirector" class="ui-widget-content ui-corner-all">
        <label for="formAnio">Año (1800 - 2026) *</label>
        <input type="number" id="formAnio" class="ui-widget-content ui-corner-all">
    </form>
</div>

<div id="dialogoConfirmarBorrar" title="¿Confirmar eliminación?" style="display:none;">
    <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Esta acción eliminará de forma permanente el registro en MySQL. ¿Deseas continuar?</p>
</div>

<div id="dialogoNotificacion" title="Notificación del Sistema" style="display:none;">
    <p id="mensajeNotificacion"></p>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.jqueryui.min.js"></script>

<script>
$(document).ready(function() {
    let authToken = "";
    let tabla;
    let idParaEliminar = null;
    let filaParaEliminar = null;

    $("#contenedorPrincipal").show("blind", { direction: "vertical" }, 1000);

    $.ajax({
        type: "GET",
        url: "api.php?action=login",
        success: function(response) {
            authToken = response.token;
            inicializarTabla();
        },
        error: function() {
            mostrarNotificacion("Error crítico de seguridad al enlazar el servidor.", true);
        }
    });

    function inicializarTabla() {
        tabla = $('#tablaPeliculas').DataTable({
            "jqueryUI": true,
            "processing": true,
            "serverSide": true,
            "pageLength": 3,
            "lengthMenu": [1, 3, 5],
            "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            "ajax": {
                "url": "api.php",
                "type": "GET",
                "headers": { "Authorization": "Bearer " + authToken }
            },
            "columns": [
                { 
                    "data": null,
                    "orderable": false,
                    "searchable": false,
                    "render": function (data, type, row, meta) {
                        return meta.settings._iDisplayStart + meta.row + 1;
                    }
                },
                { "data": "titulo" },
                { "data": "director" },
                { "data": "anio" },
                { 
                    "data": null,
                    "orderable": false,
                    "render": function (data, type, row) {
                        return `
                            <button class="ui-button ui-widget ui-corner-all btn-action btn-editar" data-id="${row.id}" data-titulo="${row.titulo}" data-director="${row.director}" data-anio="${row.anio}">Editar</button>
                            <button class="ui-button ui-widget ui-corner-all btn-action btn-eliminar ui-state-error" data-id="${row.id}">Borrar</button>
                        `;
                    }
                }
            ],
            "drawCallback": function(settings) {
                $('#tablaPeliculas tbody tr').effect("highlight", {color: "#d0e5f5"}, 800);
            }
        });
    }

    $("#dialogoFormulario").dialog({
        autoOpen: false,
        modal: true,
        show: { effect: "fade", duration: 400 },
        hide: { effect: "fade", duration: 400 },
        buttons: {
            "Guardar": function() {
                let titulo = $("#formTitulo").val().trim();
                let director = $("#formDirector").val().trim();
                let anio = parseInt($("#formAnio").val());

                $("input").removeClass("error-resaltado");

                if (titulo === "" || director === "" || isNaN(anio)) {
                    $("#dialogoFormulario").parent().effect("shake", { times: 3, distance: 10 }, 400);
                    if(titulo === "") $("#formTitulo").addClass("error-resaltado");
                    if(director === "") $("#formDirector").addClass("error-resaltado");
                    if(isNaN(anio)) $("#formAnio").addClass("error-resaltado");
                    return;
                }

                if(anio < 1800 || anio > 2026) {
                    $("#dialogoFormulario").parent().effect("shake", { times: 3, distance: 10 }, 400);
                    $("#formAnio").addClass("error-resaltado");
                    return;
                }

                let id = $("#formId").val();
                let datos = { titulo: titulo, director: director, anio: anio };
                let tipoMetodo = id ? "PUT" : "POST";
                if(id) datos.id = id;

                $.ajax({
                    url: 'api.php',
                    type: tipoMetodo,
                    headers: { "Authorization": "Bearer " + authToken },
                    contentType: 'application/json',
                    data: JSON.stringify(datos),
                    success: function(res) {
                        // Primero disparamos la alerta de éxito en la pantalla
                        mostrarNotificacion(res.message, false);
                        
                        // Inmediatamente cerramos el formulario
                        $("#dialogoFormulario").dialog("close");
                        
                        // Recargamos los registros asíncronamente de fondo
                        tabla.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Error operacional.";
                        mostrarNotificacion(errorMsg, true);
                    }
                });
            },
            "Cancelar": function() { $(this).dialog("close"); }
        }
    });

    $("#dialogoConfirmarBorrar").dialog({
        autoOpen: false,
        modal: true,
        resizable: false,
        height: "auto",
        buttons: {
            "Eliminar Registro": function() {
                let self = this;
                
                $.ajax({
                    url: 'api.php?id=' + idParaEliminar,
                    type: 'DELETE',
                    headers: { "Authorization": "Bearer " + authToken },
                    success: function(res) {
                        // Cerramos el cuadro de confirmación
                        $(self).dialog("close");
                        
                        // Mostramos la notificación del sistema inmediatamente
                        mostrarNotificacion(res.message, false);
                        
                        // Aplicamos el efecto de desaparición a la fila vieja de forma limpia
                        $(filaParaEliminar).hide("drop", { direction: "left" }, 600, function() {
                            tabla.ajax.reload(null, false);
                        });
                    },
                    error: function() {
                        $(self).dialog("close");
                        mostrarNotificacion("No se pudo eliminar el registro en la nube.", true);
                    }
                });
            },
            "Cancelar": function() { $(this).dialog("close"); }
        }
    });

    function mostrarNotificacion(mensaje, esError) {
        $("#mensajeNotificacion").text(mensaje);
        if(esError) {
            $("#dialogoNotificacion").dialog("option", "title", "❌ Error en la Operación");
            $("#mensajeNotificacion").css("color", "#cd0a0a");
        } else {
            $("#dialogoNotificacion").dialog("option", "title", "✔️ Éxito");
            $("#mensajeNotificacion").css("color", "#2e6e9e");
        }
        $("#dialogoNotificacion").dialog({
            modal: true,
            buttons: { "Aceptar": function() { $(this).dialog("close"); } }
        }).dialog("open");
    }

    $("#btnNuevaPelicula").on("click", function() {
        $("#formPelicula")[0].reset();
        $("input").removeClass("error-resaltado");
        $("#formId").val("");
        $("#dialogoFormulario").dialog("option", "title", "Agregar Nueva Película");
        $("#dialogoFormulario").dialog("open");
    });

    $('#tablaPeliculas').on('click', '.btn-editar', function() {
        $("input").removeClass("error-resaltado");
        $("#formId").val($(this).data('id'));
        $("#formTitulo").val($(this).data('titulo'));
        $("#formDirector").val($(this).data('director'));
        $("#formAnio").val($(this).data('anio'));
        $("#dialogoFormulario").dialog("option", "title", "Modificar Película");
        $("#dialogoFormulario").dialog("open");
    });

    $('#tablaPeliculas').on('click', '.btn-eliminar', function() {
        idParaEliminar = $(this).data('id');
        filaParaEliminar = $(this).closest('tr');
        $("#dialogoConfirmarBorrar").dialog("open");
    });
});
</script>
</body>
</html>