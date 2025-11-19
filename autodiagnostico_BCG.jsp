<%@page contentType="text/html" pageEncoding="UTF-8"%>
<%@page import="negocio.ClsNPeti, entidad.ClsEPeti"%>
<%@page import="java.util.Map"%>
<%@page import="java.io.*"%>

<%
    // Verificar si el usuario está logueado
    String usuario = (String) session.getAttribute("usuario");
    if (usuario == null) {
        response.sendRedirect("../index.jsp");
        return;
    }
    
    // Obtener información del grupo desde la sesión
    String grupoActual = (String) session.getAttribute("grupoActual");
    String rolUsuario = (String) session.getAttribute("rolUsuario");
    Integer usuarioId = (Integer) session.getAttribute("usuarioId");
    Integer grupoId = (Integer) session.getAttribute("grupoId");
    
    // Verificar modo colaborativo
    boolean modoColaborativo = grupoActual != null && grupoId != null;
    
    // Obtener información del usuario para mostrar en el dashboard
    String userEmail = (String) session.getAttribute("email");
    if (userEmail == null) {
        userEmail = "usuario@ejemplo.com";
    }
    
    // Generar iniciales del usuario
    String userInitials = "U";
    if (usuario != null && usuario.length() > 0) {
        userInitials = usuario.substring(0, 1).toUpperCase();
        if (usuario.contains(" ") && usuario.length() > usuario.indexOf(" ") + 1) {
            userInitials += usuario.substring(usuario.indexOf(" ") + 1, usuario.indexOf(" ") + 2).toUpperCase();
        }
    }
    
    // Variables para los datos BCG
    String datosNombresProductos = "";
    String datosPeriodos = "";
    String datosVentas = "";
    String datosTCM = "";
    String datosCompetidores = "";
    String datosPRM = "";
    String datosDemanda = "";
    String datosFoda = "";
    String mensaje = "";
    String tipoMensaje = "";
    
    // Procesar guardado si viene del formulario
    if ("POST".equals(request.getMethod()) && modoColaborativo) {
        String accion = request.getParameter("accion");
        
        ClsNPeti negocioPeti = new ClsNPeti();
        boolean exito = false;
        
        try {
            if ("guardar_datos".equals(accion)) {
                String nombresProductosData = request.getParameter("nombres_productos_data");
                String periodosData = request.getParameter("periodos_data");
                String ventasData = request.getParameter("ventas_data");
                String tcmData = request.getParameter("tcm_data");
                String competidoresData = request.getParameter("competidores_data");
                String prmData = request.getParameter("prm_data");
                String demandaData = request.getParameter("demanda_data");
                String fodaData = request.getParameter("foda_data");
                
                // Debug logs
                out.println("<!-- DEBUG: TCM Data recibido = " + (tcmData != null ? tcmData.substring(0, Math.min(100, tcmData.length())) + "..." : "null") + " -->");
                out.println("<!-- DEBUG: TCM Data length = " + (tcmData != null ? tcmData.length() : 0) + " -->");
                out.println("<!-- DEBUG: TCM Data isEmpty = " + (tcmData != null ? tcmData.trim().isEmpty() : "null") + " -->");
                
                boolean nombresGuardados = true;
                boolean periodosGuardados = true;
                boolean ventasGuardadas = true;
                boolean tcmGuardado = true;
                boolean competidoresGuardados = true;
                boolean prmGuardado = true;
                boolean demandaGuardada = true;
                boolean fodaGuardado = true;
                
                // Guardar nombres de productos
                if (nombresProductosData != null && !nombresProductosData.trim().isEmpty()) {
                    ClsEPeti datoNombres = new ClsEPeti(grupoId, "bcg", "nombres_productos", nombresProductosData.trim(), usuarioId);
                    nombresGuardados = negocioPeti.guardarDato(datoNombres);
                }
                
                // Guardar periodos y años
                if (periodosData != null && !periodosData.trim().isEmpty()) {
                    ClsEPeti datoPeriodos = new ClsEPeti(grupoId, "bcg", "periodos", periodosData.trim(), usuarioId);
                    periodosGuardados = negocioPeti.guardarDato(datoPeriodos);
                }
                
                if (ventasData != null && !ventasData.trim().isEmpty()) {
                    ClsEPeti datoVentas = new ClsEPeti(grupoId, "bcg", "ventas", ventasData.trim(), usuarioId);
                    ventasGuardadas = negocioPeti.guardarDato(datoVentas);
                }
                
                if (tcmData != null && !tcmData.trim().isEmpty()) {
                    ClsEPeti datoTCM = new ClsEPeti(grupoId, "bcg", "tcm", tcmData.trim(), usuarioId);
                    tcmGuardado = negocioPeti.guardarDato(datoTCM);
                    out.println("<!-- DEBUG: TCM guardado = " + tcmGuardado + " -->");
                } else {
                    out.println("<!-- DEBUG: TCM NO guardado (null o vacío) -->");
                }
                
                if (competidoresData != null && !competidoresData.trim().isEmpty()) {
                    ClsEPeti datoCompetidores = new ClsEPeti(grupoId, "bcg", "competidores", competidoresData.trim(), usuarioId);
                    competidoresGuardados = negocioPeti.guardarDato(datoCompetidores);
                }
                
                if (prmData != null && !prmData.trim().isEmpty()) {
                    ClsEPeti datoPRM = new ClsEPeti(grupoId, "bcg", "prm", prmData.trim(), usuarioId);
                    prmGuardado = negocioPeti.guardarDato(datoPRM);
                }
                
                if (demandaData != null && !demandaData.trim().isEmpty()) {
                    ClsEPeti datoDemanda = new ClsEPeti(grupoId, "bcg", "demanda", demandaData.trim(), usuarioId);
                    demandaGuardada = negocioPeti.guardarDato(datoDemanda);
                    out.println("<!-- DEBUG: Demanda guardada = " + demandaGuardada + " -->");
                } else {
                    out.println("<!-- DEBUG: Demanda NO guardada (null o vacía) -->");
                }
                
                if (fodaData != null && !fodaData.trim().isEmpty()) {
                    ClsEPeti datoFoda = new ClsEPeti(grupoId, "bcg", "foda", fodaData.trim(), usuarioId);
                    fodaGuardado = negocioPeti.guardarDato(datoFoda);
                }
                
                exito = nombresGuardados && periodosGuardados && ventasGuardadas && tcmGuardado && competidoresGuardados && prmGuardado && demandaGuardada && fodaGuardado;
            }
            
            if (exito) {
                mensaje = "Datos guardados exitosamente (incluye Nombres, Periodos, Ventas, TCM, Competidores, PRM, Demanda y FODA)";
                tipoMensaje = "success";
            } else if (accion != null) {
                mensaje = "Error al guardar los datos";
                tipoMensaje = "error";
            }
        } catch (Exception e) {
            mensaje = "Error interno: " + e.getMessage();
            tipoMensaje = "error";
            e.printStackTrace();
        }
    }
    
    // Cargar datos existentes
    if (modoColaborativo) {
        try {
            ClsNPeti negocioPeti = new ClsNPeti();
            Map<String, String> datosBCG = negocioPeti.obtenerDatosSeccion(grupoId, "bcg");
            
            if (datosBCG.containsKey("nombres_productos")) {
                datosNombresProductos = datosBCG.get("nombres_productos");
            }
            if (datosBCG.containsKey("periodos")) {
                datosPeriodos = datosBCG.get("periodos");
            }
            if (datosBCG.containsKey("ventas")) {
                datosVentas = datosBCG.get("ventas");
            }
            if (datosBCG.containsKey("tcm")) {
                datosTCM = datosBCG.get("tcm");
            }
            if (datosBCG.containsKey("competidores")) {
                datosCompetidores = datosBCG.get("competidores");
            }
            if (datosBCG.containsKey("prm")) {
                datosPRM = datosBCG.get("prm");
            }
            if (datosBCG.containsKey("demanda")) {
                datosDemanda = datosBCG.get("demanda");
            }
            if (datosBCG.containsKey("foda")) {
                datosFoda = datosBCG.get("foda");
            }
        } catch (Exception e) {
            //System.err.println("Error al cargar datos BCG: " + e.getMessage());
        }
    }
%>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autodiagnóstico BCG - PETI System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d3748;
            --accent-color: #3182ce;
            --success-color: #38a169;
            --warning-color: #d69e2e;
            --danger-color: #e53e3e;
            --light-bg: #f7fafc;
            --card-bg: #ffffff;
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: var(--light-bg);
            height: 100vh;
            overflow: hidden;
            color: var(--text-primary);
        }

        .dashboard-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        .dashboard-sidebar {
            width: 280px;
            min-width: 280px;
            background: var(--primary-color);
            color: white;
            padding: 0;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-lg);
            border-right: 1px solid var(--border-color);
            height: 100vh;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
        }

        .company-logo {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .company-logo i {
            font-size: 28px;
            margin-right: 12px;
            color: var(--accent-color);
        }

        .company-logo h2 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .user-profile {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-top: 8px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--accent-color);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            font-weight: 600;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .user-info h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .user-info p {
            font-size: 12px;
            opacity: 0.7;
        }

        .dashboard-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .nav-section {
            margin-bottom: 24px;
        }

        .nav-section-title {
            padding: 0 20px 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.6);
        }

        .dashboard-nav ul {
            list-style: none;
            padding: 0;
        }

        .dashboard-nav li {
            margin-bottom: 2px;
        }

        .dashboard-nav a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
            position: relative;
        }

        .dashboard-nav a i {
            margin-right: 12px;
            width: 18px;
            text-align: center;
            font-size: 16px;
        }

        .dashboard-nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .dashboard-nav li.active a {
            background: var(--accent-color);
            color: white;
        }

        .dashboard-nav li.active a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: white;
        }

        .dashboard-content {
            flex: 1;
            background: var(--light-bg);
            overflow-y: auto;
            height: 100vh;
        }

        .dashboard-header {
            background: var(--card-bg);
            padding: 20px 32px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }

        .dashboard-header h1 {
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.025em;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn-primary {
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: #2c5282;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary i {
            margin-right: 8px;
        }

        .dashboard-main {
            padding: 32px;
        }

        /* Estilos para las tablas BCG */
        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin: 20px 0;
        }

        .table-header {
            background: #c8e6c9;
            padding: 16px;
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            border-bottom: 2px solid var(--border-color);
            font-size: 13px;
            color: var(--text-primary);
        }

        .data-table td {
            padding: 10px 12px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
        }

        .data-table input {
            width: 120px;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 14px;
            text-align: center;
            transition: all 0.2s ease;
        }

        .data-table input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 5px rgba(49, 130, 206, 0.3);
        }

        /* Colores de productos - Paleta profesional corporativa */
        .product-1 { background-color: #4A90E2; } /* Azul corporativo */
        .product-2 { background-color: #7B68EE; } /* Púrpura profesional */
        .product-3 { background-color: #50C878; } /* Verde esmeralda */
        .product-4 { background-color: #F4A460; } /* Naranja suave */
        .product-5 { background-color: #708090; } /* Gris pizarra */


        .total-row {
            background: #f0f0f0;
            font-weight: bold;
            border-top: 2px solid #333;
        }

        .percentage-cell {
            font-weight: bold;
            color: var(--text-primary);
            font-size: 13px;
        }

        .data-table th[rowspan] {
            vertical-align: middle;
            background: #e8e8e8;
        }

        .data-table input[type="number"] {
            width: 100px;
            margin-right: 5px;
        }

        .data-table th input[type="number"] {
            width: 80px;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 6px 8px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            background: white;
        }

        .data-table th input[type="number"]:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 3px rgba(49, 130, 206, 0.3);
        }

        /* Matriz BCG Visual */
        .bcg-matrix-section {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-top: 30px;
        }

        .bcg-matrix-section h3 {
            text-align: center;
            color: var(--text-primary);
            margin-bottom: 30px;
            font-size: 20px;
            font-weight: 700;
        }

        .bcg-matrix-container {
            display: flex;
            justify-content: center;
        }

        #bcg-matrix {
            position: relative;
            width: 600px;
            height: 400px;
            border: 3px solid #333;
            background: linear-gradient(to right, #f0f0f0 50%, #e0e0e0 50%), 
                        linear-gradient(to bottom, #f0f0f0 50%, #e0e0e0 50%);
            background-size: 100% 100%;
            border-radius: 8px;
        }

        .matrix-line-vertical {
            position: absolute;
            left: 50%;
            top: 0;
            width: 2px;
            height: 100%;
            background-color: #666;
            z-index: 1;
        }

        .matrix-line-horizontal {
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 2px;
            background-color: #666;
            z-index: 1;
        }

        .matrix-label {
            position: absolute;
            font-weight: bold;
            font-size: 12px;
            color: var(--text-primary);
        }

        .matrix-icon {
            position: absolute;
            font-size: 30px;
        }

        .product-bubble {
            position: absolute;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 10px;
            color: white;
            text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
            transition: all 0.3s ease;
            z-index: 10;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .product-bubble:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .legend-container {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--light-bg);
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        .legend-color {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .legend-text {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        /* Mejoras adicionales de uniformidad */
        .data-table input:focus,
        .data-table textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        .data-table textarea {
            resize: vertical;
            font-family: inherit;
            line-height: 1.5;
        }
        
        /* Estilos para alertas */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 6px;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }
        
        .alert i {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        /* Estilos para inputs de nombres de productos y años */
        input[id^="nombre_producto"],
        input[id^="periodo"],
        input[id^="anio"] {
            transition: all 0.2s ease;
        }
        
        input[id^="nombre_producto"]:focus,
        input[id^="periodo"]:focus,
        input[id^="anio"]:focus {
            border-color: var(--accent-color) !important;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
            outline: none;
        }

        /* MEDIA QUERIES RESPONSIVE - MEJORADOS PARA UNIFORMIDAD */
        
        /* Tablets y dispositivos medianos (768px - 1024px) */
        @media (max-width: 1024px) {
            .dashboard-sidebar {
                width: 240px;
                min-width: 240px;
            }
            
            .dashboard-header h1 {
                font-size: 20px;
            }
            
            .dashboard-main {
                padding: 24px;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .data-table {
                min-width: 650px;
            }
            
            #bcg-matrix {
                width: 500px;
                height: 350px;
            }
        }
        
        /* Tablets vertical y móviles grandes (max-width: 768px) */
        @media (max-width: 768px) {
            body {
                height: auto;
                overflow-y: auto;
            }
            
            .dashboard-container {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
            }
            
            .dashboard-sidebar {
                width: 100%;
                min-width: 100%;
                height: auto;
                position: relative;
                max-height: none;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
            
            .sidebar-header {
                padding: 16px;
            }
            
            .company-logo {
                margin-bottom: 12px;
            }
            
            .company-logo i {
                font-size: 24px;
            }
            
            .company-logo h2 {
                font-size: 18px;
            }
            
            .user-profile {
                flex-wrap: nowrap;
                padding: 10px 12px;
            }
            
            .user-avatar {
                width: 38px;
                height: 38px;
                font-size: 15px;
                margin-right: 10px;
            }
            
            .user-info h3 {
                font-size: 13px;
            }
            
            .user-info p {
                font-size: 11px;
            }
            
            .dashboard-nav {
                max-height: 280px;
                overflow-y: auto;
                padding: 16px 0;
            }
            
            .nav-section {
                margin-bottom: 12px;
            }
            
            .nav-section-title {
                padding: 0 16px 6px;
                font-size: 10px;
            }
            
            .dashboard-nav a {
                padding: 10px 16px;
                font-size: 13px;
            }
            
            .dashboard-nav a i {
                font-size: 15px;
                width: 16px;
            }
            
            .dashboard-content {
                height: auto;
                overflow-y: visible;
            }
            
            .dashboard-header {
                padding: 14px 16px;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .dashboard-header h1 {
                font-size: 18px;
                width: 100%;
            }
            
            .dashboard-header > div {
                font-size: 12px !important;
                width: 100%;
            }
            
            .header-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .btn-primary {
                font-size: 13px;
                padding: 9px 14px;
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }
            
            .btn-primary i {
                margin-right: 6px;
                font-size: 13px;
            }
            
            .dashboard-main {
                padding: 16px;
            }
            
            .table-container {
                margin: 14px 0;
                border-radius: 8px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                box-shadow: var(--shadow-sm);
            }
            
            .table-header {
                padding: 12px;
                font-size: 13px;
                font-weight: 600;
                white-space: normal;
                line-height: 1.4;
            }
            
            .data-table {
                min-width: 650px;
                font-size: 12px;
            }
            
            .data-table th {
                padding: 10px 8px;
                font-size: 11px;
                white-space: normal;
                line-height: 1.3;
            }
            
            .data-table td {
                padding: 8px 6px;
                font-size: 12px;
                white-space: nowrap;
            }
            
            .data-table input,
            .data-table textarea {
                width: 100%;
                max-width: 100px;
                padding: 7px 8px;
                font-size: 12px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }
            
            .data-table input[type="number"] {
                width: 85px;
                text-align: center;
            }
            
            .data-table th input[type="number"] {
                width: 75px;
                padding: 6px 7px;
                font-size: 11px;
            }
            
            .data-table textarea {
                min-height: 60px;
                max-width: 100%;
                font-size: 12px;
                line-height: 1.4;
            }
            
            /* Ajustes para inputs de nombres y periodos en tablets */
            input[id^="nombre_producto"] {
                max-width: 140px;
                font-size: 12px;
            }
            
            input[id^="periodo"] {
                max-width: 100px;
                font-size: 11px;
            }
            
            input[id^="anio"] {
                max-width: 65px;
                font-size: 11px;
            }
            
            .bcg-matrix-section {
                padding: 18px 14px;
                margin-top: 18px;
            }
            
            .bcg-matrix-section h3 {
                font-size: 17px;
                margin-bottom: 18px;
            }
            
            .bcg-matrix-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding: 12px 0;
                display: flex;
                justify-content: center;
            }
            
            #bcg-matrix {
                width: 100%;
                max-width: 500px;
                min-width: 400px;
                height: 340px;
                margin: 0 auto;
            }
            
            .matrix-label {
                font-size: 10px;
                font-weight: 600;
            }
            
            .matrix-icon {
                font-size: 24px;
            }
            
            .product-bubble {
                font-size: 10px;
                font-weight: 600;
            }
            
            .legend-container {
                gap: 10px;
                margin-top: 18px;
                justify-content: center;
            }
            
            .legend-item {
                padding: 7px 12px;
                font-size: 12px;
            }
            
            .legend-color {
                width: 22px;
                height: 22px;
            }
            
            .legend-text {
                font-size: 12px;
                font-weight: 500;
            }
            
            .alert {
                font-size: 13px;
                padding: 12px 14px;
                border-radius: 6px;
                margin-bottom: 16px;
            }
        }
        
        /* Móviles pequeños (max-width: 480px) */
        @media (max-width: 480px) {
            .sidebar-header {
                padding: 12px;
            }
            
            .company-logo i {
                font-size: 22px;
                margin-right: 10px;
            }
            
            .company-logo h2 {
                font-size: 16px;
            }
            
            .user-profile {
                padding: 8px 10px;
            }
            
            .user-avatar {
                width: 34px;
                height: 34px;
                font-size: 13px;
                margin-right: 8px;
            }
            
            .user-info h3 {
                font-size: 12px;
            }
            
            .user-info p {
                font-size: 10px;
            }
            
            .dashboard-nav {
                max-height: 240px;
                padding: 12px 0;
            }
            
            .nav-section {
                margin-bottom: 10px;
            }
            
            .nav-section-title {
                padding: 0 12px 5px;
                font-size: 9px;
            }
            
            .dashboard-nav a {
                padding: 9px 12px;
                font-size: 12px;
            }
            
            .dashboard-nav a i {
                font-size: 13px;
                width: 14px;
                margin-right: 10px;
            }
            
            .dashboard-header {
                padding: 12px;
            }
            
            .dashboard-header h1 {
                font-size: 16px;
                line-height: 1.3;
            }
            
            .dashboard-header > div {
                font-size: 11px !important;
                line-height: 1.4;
            }
            
            .header-actions {
                gap: 6px;
            }
            
            .btn-primary {
                font-size: 12px;
                padding: 8px 12px;
                flex: 1 1 auto;
                min-width: 110px;
            }
            
            .btn-primary i {
                margin-right: 5px;
                font-size: 12px;
            }
            
            .dashboard-main {
                padding: 12px;
            }
            
            .table-container {
                margin: 12px 0;
                border-radius: 6px;
            }
            
            .table-header {
                padding: 10px 12px;
                font-size: 12px;
                line-height: 1.3;
            }
            
            .data-table {
                min-width: 550px;
                font-size: 11px;
            }
            
            .data-table th {
                padding: 8px 5px;
                font-size: 10px;
                line-height: 1.2;
            }
            
            .data-table td {
                padding: 6px 4px;
                font-size: 11px;
            }
            
            .data-table input,
            .data-table textarea {
                max-width: 85px;
                padding: 6px 7px;
                font-size: 11px;
            }
            
            .data-table input[type="number"] {
                width: 75px;
            }
            
            .data-table th input[type="number"] {
                width: 65px;
                padding: 5px 6px;
                font-size: 10px;
            }
            
            .data-table textarea {
                min-height: 55px;
                font-size: 11px;
            }
            
            /* Ajustes para inputs de nombres y periodos en móviles */
            input[id^="nombre_producto"] {
                max-width: 120px;
                font-size: 11px;
                padding: 6px 7px;
            }
            
            input[id^="periodo"] {
                max-width: 90px;
                font-size: 10px;
                padding: 5px 6px;
            }
            
            input[id^="anio"] {
                max-width: 60px;
                font-size: 10px;
                padding: 5px 6px;
            }
            
            .bcg-matrix-section {
                padding: 14px 10px;
                margin-top: 14px;
            }
            
            .bcg-matrix-section h3 {
                font-size: 15px;
                margin-bottom: 14px;
            }
            
            .bcg-matrix-container {
                padding: 10px 0;
            }
            
            #bcg-matrix {
                max-width: 100%;
                min-width: 320px;
                height: 280px;
            }
            
            .matrix-label {
                font-size: 8px;
                font-weight: 600;
            }
            
            .matrix-icon {
                font-size: 18px;
            }
            
            .product-bubble {
                font-size: 9px;
                font-weight: 600;
            }
            
            .legend-container {
                gap: 6px;
                margin-top: 14px;
            }
            
            .legend-item {
                padding: 6px 10px;
                font-size: 11px;
                min-width: 0;
                flex: 1 1 auto;
            }
            
            .legend-color {
                width: 18px;
                height: 18px;
                flex-shrink: 0;
            }
            
            .legend-text {
                font-size: 11px;
                white-space: nowrap;
            }
            
            .alert {
                font-size: 12px;
                padding: 10px 12px;
                margin-bottom: 12px;
            }
        }
        
        /* Móviles muy pequeños (max-width: 360px) */
        @media (max-width: 360px) {
            .company-logo h2 {
                font-size: 15px;
            }
            
            .dashboard-header h1 {
                font-size: 15px;
            }
            
            .btn-primary {
                font-size: 11px;
                padding: 7px 10px;
                min-width: 100px;
            }
            
            .data-table {
                min-width: 500px;
            }
            
            .data-table input {
                max-width: 70px;
                padding: 5px 6px;
                font-size: 10px;
            }
            
            .data-table input[type="number"] {
                width: 65px;
            }
            
            #bcg-matrix {
                min-width: 280px;
                height: 240px;
            }
            
            .legend-item {
                padding: 5px 8px;
                font-size: 10px;
            }
            
            .legend-text {
                font-size: 10px;
            }
        }
        
        /* Ajustes para scroll horizontal en tablas - MEJORADO */
        @media (max-width: 768px) {
            .table-container::-webkit-scrollbar {
                height: 10px;
            }
            
            .table-container::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 5px;
                margin: 0 10px;
            }
            
            .table-container::-webkit-scrollbar-thumb {
                background: var(--accent-color);
                border-radius: 5px;
            }
            
            .table-container::-webkit-scrollbar-thumb:hover {
                background: #2c5282;
            }
            
            /* Indicador visual de scroll horizontal */
            .table-container::after {
                content: '← Desliza para ver más →';
                display: block;
                text-align: center;
                padding: 8px;
                font-size: 11px;
                color: var(--text-secondary);
                background: linear-gradient(to right, rgba(255,255,255,0.9), rgba(255,255,255,0.95), rgba(255,255,255,0.9));
                border-top: 1px solid var(--border-color);
                position: sticky;
                bottom: 0;
                left: 0;
                width: 100%;
                font-weight: 500;
                letter-spacing: 0.5px;
            }
            
            /* Ocultar indicador cuando no hay scroll */
            .table-container.no-scroll::after {
                display: none;
            }
            
            .bcg-matrix-container::-webkit-scrollbar {
                height: 8px;
            }
            
            .bcg-matrix-container::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }
            
            .bcg-matrix-container::-webkit-scrollbar-thumb {
                background: var(--accent-color);
                border-radius: 4px;
            }
            
            .bcg-matrix-container::-webkit-scrollbar-thumb:hover {
                background: #2c5282;
            }
        }
        
        /* Ajustes para orientación horizontal en móviles */
        @media (max-height: 500px) and (orientation: landscape) {
            .dashboard-sidebar {
                display: none;
            }
            
            .dashboard-header {
                padding: 10px 12px;
            }
            
            .dashboard-main {
                padding: 10px 12px;
            }
            
            .table-container {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <div class="company-logo">
                    <i class="fas fa-building"></i>
                    <h2>PETI System</h2>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <span><%= userInitials %></span>
                    </div>
                    <div class="user-info">
                        <h3><%= usuario %></h3>
                        <p><%= userEmail %></p>
                        <% if (modoColaborativo) { %>
                            <p style="font-size: 11px; color: rgba(255,255,255,0.8); margin-top: 2px;">
                                <i class="fas fa-users"></i> <%= grupoActual %>
                            </p>
                        <% } %>
                    </div>
                </div>
            </div>
            <nav class="dashboard-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <ul>
                        <li><a href="dashboard.jsp"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    </ul>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Planificacion Estrategica</div>
                    <ul>
                        <li><a href="empresa_colaborativo.jsp"><i class="fas fa-building"></i> Informacion Empresarial</a></li>
                        <li><a href="mision_colaborativo.jsp"><i class="fas fa-bullseye"></i> Mision Corporativa</a></li>
                        <li><a href="vision_colaborativo.jsp"><i class="fas fa-eye"></i> Vision Estrategica</a></li>
                        <li><a href="valores_colaborativo.jsp"><i class="fas fa-heart"></i> Valores Organizacionales</a></li>
                        <li><a href="objetivos_colaborativo.jsp"><i class="fas fa-target"></i> Objetivos Estrategicos</a></li>
                    </ul>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Analisis Estrategico</div>
                    <ul>
                        <li><a href="analisis_externo_colaborativo.jsp"><i class="fas fa-search"></i> Analisis del Entorno</a></li>
                        <li><a href="analisis_interno_colaborativo.jsp"><i class="fas fa-chart-bar"></i> Analisis Organizacional</a></li>
                    </ul>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Herramientas de Gestion</div>
                    <ul>
                        <li><a href="cadena_valor_colaborativo.jsp"><i class="fas fa-link"></i> Cadena de Valor</a></li>
                        <li><a href="matriz_participacion_colaborativo.jsp"><i class="fas fa-users"></i> Matriz de Participacion</a></li>
                        <li class="active"><a href="autodiagnostico_BCG.jsp"><i class="fas fa-chart-pie"></i> Autodiagnostico BCG</a></li>
                        <li><a href="resumen-ejecutivo-colaborativo.jsp"><i class="fas fa-file-alt"></i> Resumen Ejecutivo</a></li>
                    </ul>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Sistema</div>
                    <ul>
                        <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Cerrar Sesi�n</a></li>
                    </ul>
                </div>
            </nav>
        </div>

        <!-- Contenido Principal -->
        <div class="dashboard-content">
            <header class="dashboard-header">
                <h1>Autodiagnóstico BCG</h1>
                <% if (modoColaborativo) { %>
                    <div style="display: flex; align-items: center; gap: 12px; font-size: 14px; color: var(--text-secondary);">
                        <i class="fas fa-users"></i>
                        <span>Grupo: <strong><%= grupoActual %></strong></span>
                        <span>|</span>
                        <span>Rol: <strong><%= rolUsuario %></strong></span>
                    </div>
                <% } %>
                <div class="header-actions">
                    <% if (modoColaborativo) { %>
                        <button class="btn-primary" onclick="guardarDatos()">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    <% } %>
                    <button class="btn-primary" onclick="exportarDatos()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </header>

            <main class="dashboard-main">
                <% if (!mensaje.isEmpty()) { %>
                    <div class="alert alert-<%= tipoMensaje %>" style="margin-bottom: 20px; padding: 12px 16px; border-radius: 6px; 
                         background: <%= "success".equals(tipoMensaje) ? "var(--success-color)" : "var(--danger-color)" %>; 
                         color: white; font-weight: 500;">
                        <i class="fas fa-<%= "success".equals(tipoMensaje) ? "check-circle" : "exclamation-triangle" %>"></i>
                        <%= mensaje %>
                    </div>
                <% } %>
                <!-- Tabla 1: Prevision de Ventas -->
                <div class="table-container">
                    <div class="table-header">PREVISION DE VENTAS</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>PRODUCTOS</th>
                                <th>VENTAS</th>
                                <th>% S/ TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="product-1">
                                <td><input type="text" id="nombre_producto1" value="Producto 1" placeholder="Nombre producto 1" style="border: 1px solid #ccc; padding: 8px; border-radius: 4px; font-weight: bold; width: 150px;"></td>
                                <td><input type="number" id="ventas1" value="0" min="0"></td>
                                <td class="percentage-cell" id="percent1">0.00%</td>
                            </tr>
                            <tr class="product-2">
                                <td><input type="text" id="nombre_producto2" value="Producto 2" placeholder="Nombre producto 2" style="border: 1px solid #ccc; padding: 8px; border-radius: 4px; font-weight: bold; width: 150px;"></td>
                                <td><input type="number" id="ventas2" value="0" min="0"></td>
                                <td class="percentage-cell" id="percent2">0.00%</td>
                            </tr>
                            <tr class="product-3">
                                <td><input type="text" id="nombre_producto3" value="Producto 3" placeholder="Nombre producto 3" style="border: 1px solid #ccc; padding: 8px; border-radius: 4px; font-weight: bold; width: 150px;"></td>
                                <td><input type="number" id="ventas3" value="0" min="0"></td>
                                <td class="percentage-cell" id="percent3">0.00%</td>
                            </tr>
                            <tr class="product-4">
                                <td><input type="text" id="nombre_producto4" value="Producto 4" placeholder="Nombre producto 4" style="border: 1px solid #ccc; padding: 8px; border-radius: 4px; font-weight: bold; width: 150px;"></td>
                                <td><input type="number" id="ventas4" value="0" min="0"></td>
                                <td class="percentage-cell" id="percent4">0.00%</td>
                            </tr>
                            <tr class="product-5">
                                <td><input type="text" id="nombre_producto5" value="Producto 5" placeholder="Nombre producto 5" style="border: 1px solid #ccc; padding: 8px; border-radius: 4px; font-weight: bold; width: 150px;"></td>
                                <td><input type="number" id="ventas5" value="0" min="0"></td>
                                <td class="percentage-cell" id="percent5">0.00%</td>
                            </tr>
                            <tr class="total-row">
                                <td><strong>TOTAL</strong></td>
                                <td><strong id="totalVentas">0</strong></td>
                                <td class="percentage-cell"><strong id="totalPercent">0.00%</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tabla 2: Tasas de Crecimiento del Mercado -->
                <div class="table-container">
                    <div class="table-header">TASAS DE CRECIMIENTO DEL MERCADO (TCM)</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th rowspan="2">PERIODOS</th>
                                <th colspan="5">MERCADOS</th>
                            </tr>
                            <tr>
                                <th class="product-1"><span id="header_producto1">Producto 1</span></th>
                                <th class="product-2"><span id="header_producto2">Producto 2</span></th>
                                <th class="product-3"><span id="header_producto3">Producto 3</span></th>
                                <th class="product-4"><span id="header_producto4">Producto 4</span></th>
                                <th class="product-5"><span id="header_producto5">Producto 5</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="text" id="periodo1" value="2012 - 2013" placeholder="Ej: 2020-2021" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 110px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="tcm1_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="tcm1_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="tcm1_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="tcm1_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="tcm1_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                            <tr>
                                <td><input type="text" id="periodo2" value="2013 - 2014" placeholder="Ej: 2021-2022" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 110px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="tcm2_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="tcm2_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="tcm2_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="tcm2_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="tcm2_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                            <tr>
                                <td><input type="text" id="periodo3" value="2014 - 2015" placeholder="Ej: 2022-2023" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 110px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="tcm3_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="tcm3_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="tcm3_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="tcm3_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="tcm3_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                            <tr>
                                <td><input type="text" id="periodo4" value="2015 - 2016" placeholder="Ej: 2023-2024" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 110px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="tcm4_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="tcm4_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="tcm4_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="tcm4_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="tcm4_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                            <tr>
                                <td><input type="text" id="periodo5" value="2016 - 2017" placeholder="Ej: 2024-2025" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 110px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="tcm5_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="tcm5_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="tcm5_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="tcm5_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="tcm5_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tabla 3: Resumen BCG -->
                <div class="table-container">
                    <div class="table-header">RESUMEN BCG</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>BCG</th>
                                <th class="product-1"><span id="header2_producto1">Producto 1</span></th>
                                <th class="product-2"><span id="header2_producto2">Producto 2</span></th>
                                <th class="product-3"><span id="header2_producto3">Producto 3</span></th>
                                <th class="product-4"><span id="header2_producto4">Producto 4</span></th>
                                <th class="product-5"><span id="header2_producto5">Producto 5</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>TCM</strong></td>
                                <td class="product-1" id="tcm_avg_1">0.00%</td>
                                <td class="product-2" id="tcm_avg_2">0.00%</td>
                                <td class="product-3" id="tcm_avg_3">0.00%</td>
                                <td class="product-4" id="tcm_avg_4">0.00%</td>
                                <td class="product-5" id="tcm_avg_5">0.00%</td>
                            </tr>
                            <tr>
                                <td><strong>PRM</strong></td>
                                <td class="product-1"><input type="number" id="prm1" value="0" min="0" step="0.01"></td>
                                <td class="product-2"><input type="number" id="prm2" value="0" min="0" step="0.01"></td>
                                <td class="product-3"><input type="number" id="prm3" value="0" min="0" step="0.01"></td>
                                <td class="product-4"><input type="number" id="prm4" value="0" min="0" step="0.01"></td>
                                <td class="product-5"><input type="number" id="prm5" value="0" min="0" step="0.01"></td>
                            </tr>
                            <tr>
                                <td><strong>% S/VTAS</strong></td>
                                <td class="product-1" id="svtas1">0%</td>
                                <td class="product-2" id="svtas2">0%</td>
                                <td class="product-3" id="svtas3">0%</td>
                                <td class="product-4" id="svtas4">0%</td>
                                <td class="product-5" id="svtas5">0%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tabla 4: Evoluci�n de la Demanda Global -->
                <div class="table-container">
                    <div class="table-header">EVOLUCION DE LA DEMANDA GLOBAL SECTOR (en miles de soles)</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>AÑOS</th>
                                <th colspan="5">MERCADOS</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th class="product-1"><span id="header3_producto1">Producto 1</span></th>
                                <th class="product-2"><span id="header3_producto2">Producto 2</span></th>
                                <th class="product-3"><span id="header3_producto3">Producto 3</span></th>
                                <th class="product-4"><span id="header3_producto4">Producto 4</span></th>
                                <th class="product-5"><span id="header3_producto5">Producto 5</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="text" id="anio1" value="2012" placeholder="Ej: 2020" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 70px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="demanda_2012_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="demanda_2012_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="demanda_2012_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="demanda_2012_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="demanda_2012_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                            <tr>
                                <td><input type="text" id="anio2" value="2013" placeholder="Ej: 2021" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 70px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="demanda_2013_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="demanda_2013_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="demanda_2013_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="demanda_2013_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="demanda_2013_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                            <tr>
                                <td><input type="text" id="anio3" value="2014" placeholder="Ej: 2022" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 70px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="demanda_2014_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="demanda_2014_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="demanda_2014_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="demanda_2014_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="demanda_2014_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                            <tr>
                                <td><input type="text" id="anio4" value="2015" placeholder="Ej: 2023" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 70px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="demanda_2015_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="demanda_2015_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="demanda_2015_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="demanda_2015_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="demanda_2015_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                            <tr>
                                <td><input type="text" id="anio5" value="2016" placeholder="Ej: 2024" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 70px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="demanda_2016_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="demanda_2016_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="demanda_2016_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="demanda_2016_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="demanda_2016_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                            <tr>
                                <td><input type="text" id="anio6" value="2017" placeholder="Ej: 2025" style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-weight: bold; width: 70px; text-align: center;"></td>
                                <td class="product-1"><input type="number" id="demanda_2017_1" value="0" min="0" step="0.01">%</td>
                                <td class="product-2"><input type="number" id="demanda_2017_2" value="0" min="0" step="0.01">%</td>
                                <td class="product-3"><input type="number" id="demanda_2017_3" value="0" min="0" step="0.01">%</td>
                                <td class="product-4"><input type="number" id="demanda_2017_4" value="0" min="0" step="0.01">%</td>
                                <td class="product-5"><input type="number" id="demanda_2017_5" value="0" min="0" step="0.01">%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tabla 5: Niveles de Venta de Competidores -->
                <div class="table-container">
                    <div class="table-header">NIVELES DE VENTA DE LOS COMPETIDORES DE CADA PRODUCTO</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="product-1">Producto 1</th>
                                <th class="product-2">Producto 2</th>
                                <th class="product-3">Producto 3</th>
                                <th class="product-4">Producto 4</th>
                                <th class="product-5">Producto 5</th>
                            </tr>
                            <tr>
                                <th><strong>EMPRESA</strong> <span id="empresa_display_1">2</span></th>
                                <th><strong>EMPRESA</strong> <span id="empresa_display_2">13</span></th>
                                <th><strong>EMPRESA</strong> <span id="empresa_display_3">15</span></th>
                                <th><strong>EMPRESA</strong> <span id="empresa_display_4">15</span></th>
                                <th><strong>EMPRESA</strong> <span id="empresa_display_5">16</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="product-1">
                                    <strong>Competidor</strong><br>
                                    <span style="font-size: 11px;">CP1-1</span>
                                </td>
                                <td class="product-2">
                                    <strong>Competidor</strong><br>
                                    <span style="font-size: 11px;">CP2-1</span>
                                </td>
                                <td class="product-3">
                                    <strong>Competidor</strong><br>
                                    <span style="font-size: 11px;">CP3-1</span>
                                </td>
                                <td class="product-4">
                                    <strong>Competidor</strong><br>
                                    <span style="font-size: 11px;">CP4-1</span>
                                </td>
                                <td class="product-5">
                                    <strong>Competidor</strong><br>
                                    <span style="font-size: 11px;">CP5-1</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="product-1">
                                    <strong>Ventas</strong><br>
                                    <input type="number" id="comp1_1" value="0" min="0" step="0.01" style="width: 100px;">
                                </td>
                                <td class="product-2">
                                    <strong>Ventas</strong><br>
                                    <input type="number" id="comp2_1" value="0" min="0" step="0.01" style="width: 100px;">
                                </td>
                                <td class="product-3">
                                    <strong>Ventas</strong><br>
                                    <input type="number" id="comp3_1" value="0" min="0" step="0.01" style="width: 100px;">
                                </td>
                                <td class="product-4">
                                    <strong>Ventas</strong><br>
                                    <input type="number" id="comp4_1" value="0" min="0" step="0.01" style="width: 100px;">
                                </td>
                                <td class="product-5">
                                    <strong>Ventas</strong><br>
                                    <input type="number" id="comp5_1" value="0" min="0" step="0.01" style="width: 100px;">
                                </td>
                            </tr>
                            <tr>
                                <td class="product-1"><span style="font-size: 11px;">CP1-2</span></td>
                                <td class="product-2"><span style="font-size: 11px;">CP2-2</span></td>
                                <td class="product-3"><span style="font-size: 11px;">CP3-2</span></td>
                                <td class="product-4"><span style="font-size: 11px;">CP4-2</span></td>
                                <td class="product-5"><span style="font-size: 11px;">CP5-2</span></td>
                            </tr>
                            <tr>
                                <td class="product-1"><input type="number" id="comp1_2" value="0" min="0" step="0.01" style="width: 100px;"></td>
                                <td class="product-2"><input type="number" id="comp2_2" value="0" min="0" step="0.01" style="width: 100px;"></td>
                                <td class="product-3"><input type="number" id="comp3_2" value="0" min="0" step="0.01" style="width: 100px;"></td>
                                <td class="product-4"><input type="number" id="comp4_2" value="0" min="0" step="0.01" style="width: 100px;"></td>
                                <td class="product-5"><input type="number" id="comp5_2" value="0" min="0" step="0.01" style="width: 100px;"></td>
                            </tr>
                            <tr>
                                <td class="product-1"><span style="font-size: 11px;">CP1-3</span></td>
                                <td class="product-2"><span style="font-size: 11px;">CP2-3</span></td>
                                <td class="product-3"><span style="font-size: 11px;">CP3-3</span></td>
                                <td class="product-4"><span style="font-size: 11px;">CP4-3</span></td>
                                <td class="product-5"><span style="font-size: 11px;">CP5-3</span></td>
                            </tr>
                            <tr>
                                <td class="product-1"><input type="number" id="comp1_3" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-2"><input type="number" id="comp2_3" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-3"><input type="number" id="comp3_3" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-4"><input type="number" id="comp4_3" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-5"><input type="number" id="comp5_3" value="0" min="0" step="0.01" style="width: 70px;"></td>
                            </tr>
                            <tr>
                                <td class="product-1"><span style="font-size: 11px;">CP1-4</span></td>
                                <td class="product-2"><span style="font-size: 11px;">CP2-4</span></td>
                                <td class="product-3"><span style="font-size: 11px;">CP3-4</span></td>
                                <td class="product-4"><span style="font-size: 11px;">CP4-4</span></td>
                                <td class="product-5"><span style="font-size: 11px;">CP5-4</span></td>
                            </tr>
                            <tr>
                                <td class="product-1"><input type="number" id="comp1_4" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-2"><input type="number" id="comp2_4" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-3"><input type="number" id="comp3_4" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-4"><input type="number" id="comp4_4" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-5"><input type="number" id="comp5_4" value="0" min="0" step="0.01" style="width: 70px;"></td>
                            </tr>
                            <tr>
                                <td class="product-1"><span style="font-size: 11px;">CP1-5</span></td>
                                <td class="product-2"><span style="font-size: 11px;">CP2-5</span></td>
                                <td class="product-3"><span style="font-size: 11px;">CP3-5</span></td>
                                <td class="product-4"><span style="font-size: 11px;">CP4-5</span></td>
                                <td class="product-5"><span style="font-size: 11px;">CP5-5</span></td>
                            </tr>
                            <tr>
                                <td class="product-1"><input type="number" id="comp1_5" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-2"><input type="number" id="comp2_5" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-3"><input type="number" id="comp3_5" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-4"><input type="number" id="comp4_5" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-5"><input type="number" id="comp5_5" value="0" min="0" step="0.01" style="width: 70px;"></td>
                            </tr>
                            <tr>
                                <td class="product-1"><span style="font-size: 11px;">CP1-6</span></td>
                                <td class="product-2"><span style="font-size: 11px;">CP2-6</span></td>
                                <td class="product-3"><span style="font-size: 11px;">CP3-6</span></td>
                                <td class="product-4"><span style="font-size: 11px;">CP4-6</span></td>
                                <td class="product-5"><span style="font-size: 11px;">CP5-6</span></td>
                            </tr>
                            <tr>
                                <td class="product-1"><input type="number" id="comp1_6" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-2"><input type="number" id="comp2_6" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-3"><input type="number" id="comp3_6" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-4"><input type="number" id="comp4_6" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-5"><input type="number" id="comp5_6" value="0" min="0" step="0.01" style="width: 70px;"></td>
                            </tr>
                            <tr>
                                <td class="product-1"><span style="font-size: 11px;">CP1-7</span></td>
                                <td class="product-2"><span style="font-size: 11px;">CP2-7</span></td>
                                <td class="product-3"><span style="font-size: 11px;">CP3-7</span></td>
                                <td class="product-4"><span style="font-size: 11px;">CP4-7</span></td>
                                <td class="product-5"><span style="font-size: 11px;">CP5-7</span></td>
                            </tr>
                            <tr>
                                <td class="product-1"><input type="number" id="comp1_7" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-2"><input type="number" id="comp2_7" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-3"><input type="number" id="comp3_7" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-4"><input type="number" id="comp4_7" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-5"><input type="number" id="comp5_7" value="0" min="0" step="0.01" style="width: 70px;"></td>
                            </tr>
                            <tr>
                                <td class="product-1"><span style="font-size: 11px;">CP1-8</span></td>
                                <td class="product-2"><span style="font-size: 11px;">CP2-8</span></td>
                                <td class="product-3"><span style="font-size: 11px;">CP3-8</span></td>
                                <td class="product-4"><span style="font-size: 11px;">CP4-8</span></td>
                                <td class="product-5"><span style="font-size: 11px;">CP5-8</span></td>
                            </tr>
                            <tr>
                                <td class="product-1"><input type="number" id="comp1_8" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-2"><input type="number" id="comp2_8" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-3"><input type="number" id="comp3_8" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-4"><input type="number" id="comp4_8" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-5"><input type="number" id="comp5_8" value="0" min="0" step="0.01" style="width: 70px;"></td>
                            </tr>
                            <tr>
                                <td class="product-1"><span style="font-size: 11px;">CP1-9</span></td>
                                <td class="product-2"><span style="font-size: 11px;">CP2-9</span></td>
                                <td class="product-3"><span style="font-size: 11px;">CP3-9</span></td>
                                <td class="product-4"><span style="font-size: 11px;">CP4-9</span></td>
                                <td class="product-5"><span style="font-size: 11px;">CP5-9</span></td>
                            </tr>
                            <tr>
                                <td class="product-1"><input type="number" id="comp1_9" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-2"><input type="number" id="comp2_9" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-3"><input type="number" id="comp3_9" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-4"><input type="number" id="comp4_9" value="0" min="0" step="0.01" style="width: 70px;"></td>
                                <td class="product-5"><input type="number" id="comp5_9" value="0" min="0" step="0.01" style="width: 70px;"></td>
                            </tr>
                            <tr style="background-color: #f0f0f0; font-weight: bold;">
                                <td class="product-1">
                                    <strong>Mayor</strong><br>
                                    <span id="mayor1">0</span>
                                </td>
                                <td class="product-2">
                                    <strong>Mayor</strong><br>
                                    <span id="mayor2">0</span>
                                </td>
                                <td class="product-3">
                                    <strong>Mayor</strong><br>
                                    <span id="mayor3">0</span>
                                </td>
                                <td class="product-4">
                                    <strong>Mayor</strong><br>
                                    <span id="mayor4">0</span>
                                </td>
                                <td class="product-5">
                                    <strong>Mayor</strong><br>
                                    <span id="mayor5">0</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Matriz BCG Visual -->
                <div class="bcg-matrix-section">
                    <h3>MATRIZ BCG INTERACTIVA</h3>
                    
                    <div class="bcg-matrix-container">
                        <div id="bcg-matrix">
                            <!-- L�neas divisorias -->
                            <div class="matrix-line-vertical"></div>
                            <div class="matrix-line-horizontal"></div>
                            
                            <!-- Etiquetas de los ejes -->
                            <div class="matrix-label" style="bottom: -30px; left: 25%; transform: translateX(-50%);">BAJO PRM</div>
                            <div class="matrix-label" style="bottom: -30px; right: 25%; transform: translateX(50%);">ALTO PRM</div>
                            <div class="matrix-label" style="left: -80px; top: 25%; transform: translateY(-50%) rotate(-90deg);">ALTO CRECIMIENTO</div>
                            <div class="matrix-label" style="left: -80px; bottom: 25%; transform: translateY(50%) rotate(-90deg);">BAJO CRECIMIENTO</div>
                            
                            <!-- Iconos en los cuadrantes -->
                            <div class="matrix-icon" style="top: 20px; left: 20px; color: #8B4513;">?</div>
                            <div class="matrix-icon" style="top: 20px; right: 20px; color: #4169E1;">?</div>
                            <div class="matrix-icon" style="bottom: 20px; right: 20px; color: #228B22;">?</div>
                            <div class="matrix-icon" style="bottom: 20px; left: 20px; color: #696969;">?</div>
                            
                            <!-- Burbujas de productos -->
                            <div id="bubble1" class="product-bubble" style="width: 60px; height: 60px; background-color: #4A90E2;">0%</div>
                            <div id="bubble2" class="product-bubble" style="width: 60px; height: 60px; background-color: #7B68EE;">0%</div>
                            <div id="bubble3" class="product-bubble" style="width: 60px; height: 60px; background-color: #50C878;">0%</div>
                            <div id="bubble4" class="product-bubble" style="width: 60px; height: 60px; background-color: #F4A460;">0%</div>
                            <div id="bubble5" class="product-bubble" style="width: 60px; height: 60px; background-color: #708090;">0%</div>
                        </div>
                    </div>
                    
                    <!-- Leyenda de productos -->
                    <div class="legend-container">
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #4A90E2;"></div>
                            <span class="legend-text" id="legend_producto1">Producto 1</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #7B68EE;"></div>
                            <span class="legend-text" id="legend_producto2">Producto 2</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #50C878;"></div>
                            <span class="legend-text" id="legend_producto3">Producto 3</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #F4A460;"></div>
                            <span class="legend-text" id="legend_producto4">Producto 4</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #708090;"></div>
                            <span class="legend-text" id="legend_producto5">Producto 5</span>
                        </div>
                    </div>
                </div>

                <!-- Secci�n FODA -->
                <div class="table-container" style="margin-top: 40px;">
                    <div style="background: #d4e6f1; padding: 16px; text-align: center; border-radius: 12px 12px 0 0; border-bottom: 2px solid var(--border-color);">
                        <p style="margin: 0; font-size: 14px; color: var(--text-secondary); line-height: 1.6;">
                            <em>Realice una reflexi�n general sobre sus productos y servicios e identifique las fortalezas y amenazas m�s significativas de su empresa. La informaci�n aportada servir� para completar la matriz FODA.</em>
                        </p>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th colspan="2" style="background: #c8e6c9; font-size: 15px; padding: 14px;">FORTALEZAS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="width: 50px; font-weight: bold; background: #f0f0f0;">F3:</td>
                                <td style="text-align: left; padding: 12px;">
                                    <textarea id="fortaleza3" rows="2" style="width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 10px; font-size: 14px; font-family: inherit; resize: vertical;" placeholder="Describa la fortaleza 3..."></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 50px; font-weight: bold; background: #f0f0f0;">F4:</td>
                                <td style="text-align: left; padding: 12px;">
                                    <textarea id="fortaleza4" rows="2" style="width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 10px; font-size: 14px; font-family: inherit; resize: vertical;" placeholder="Describa la fortaleza 4..."></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-hand-point-down" style="font-size: 32px; color: var(--accent-color);"></i>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th colspan="2" style="background: #ffcccc; font-size: 15px; padding: 14px;">DEBILIDADES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="width: 50px; font-weight: bold; background: #f0f0f0;">D3:</td>
                                <td style="text-align: left; padding: 12px;">
                                    <textarea id="debilidad3" rows="2" style="width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 10px; font-size: 14px; font-family: inherit; resize: vertical;" placeholder="Describa la debilidad 3..."></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 50px; font-weight: bold; background: #f0f0f0;">D4:</td>
                                <td style="text-align: left; padding: 12px;">
                                    <textarea id="debilidad4" rows="2" style="width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 10px; font-size: 14px; font-family: inherit; resize: vertical;" placeholder="Describa la debilidad 4..."></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Variables globales
        const modoColaborativo = <%= modoColaborativo %>;
        const grupoId = <%= grupoId != null ? grupoId : "null" %>;
        const matrixWidth = 600;
        const matrixHeight = 400;
        
        // Función para sincronizar nombres de productos en todas las tablas
        function sincronizarNombresProductos() {
            for (let i = 1; i <= 5; i++) {
                const inputNombre = document.getElementById('nombre_producto' + i);
                if (inputNombre) {
                    const nombreProducto = inputNombre.value || 'Producto ' + i;
                    
                    // Actualizar headers en tabla TCM
                    const header1 = document.getElementById('header_producto' + i);
                    if (header1) header1.textContent = nombreProducto;
                    
                    // Actualizar headers en tabla Resumen BCG
                    const header2 = document.getElementById('header2_producto' + i);
                    if (header2) header2.textContent = nombreProducto;
                    
                    // Actualizar headers en tabla Demanda
                    const header3 = document.getElementById('header3_producto' + i);
                    if (header3) header3.textContent = nombreProducto;
                    
                    // Actualizar leyenda
                    const legend = document.getElementById('legend_producto' + i);
                    if (legend) legend.textContent = nombreProducto;
                }
            }
        }
        
        function calcularPorcentajes() {
            let total = 0;
            const ventas = [];
            
            for (let i = 1; i <= 5; i++) {
                const input = document.getElementById('ventas' + i);
                const valor = parseFloat(input.value) || 0;
                ventas.push(valor);
                total += valor;
                
                const empresaDisplay = document.getElementById('empresa_display_' + i);
                if (empresaDisplay) {
                    empresaDisplay.textContent = valor;
                }
            }
            
            document.getElementById('totalVentas').textContent = total;
            
            for (let i = 1; i <= 5; i++) {
                const porcentaje = total > 0 ? ((ventas[i-1] / total) * 100).toFixed(2) : '0.00';
                const porcentajeTexto = porcentaje + '%';
                
                document.getElementById('percent' + i).textContent = porcentajeTexto;
                document.getElementById('svtas' + i).textContent = porcentajeTexto;
            }
            
            document.getElementById('totalPercent').textContent = total > 0 ? '100.00%' : '0.00%';
        }
        
        function calcularTCMPromedio() {
            for (let producto = 1; producto <= 5; producto++) {
                let suma = 0;
                let contador = 0;
                
                for (let periodo = 1; periodo <= 5; periodo++) {
                    const input = document.getElementById('tcm' + periodo + '_' + producto);
                    if (input) {
                        const valor = parseFloat(input.value) || 0;
                        // Solo contar valores diferentes de cero para el promedio
                        if (valor !== 0) {
                            suma += valor;
                            contador++;
                        }
                    }
                }
                
                const promedio = contador > 0 ? (suma / contador).toFixed(2) : '0.00';
                const elementoPromedio = document.getElementById('tcm_avg_' + producto);
                if (elementoPromedio) {
                    elementoPromedio.textContent = promedio + '%';
                }
            }
        }
        
        function calcularPRM() {
            for (let producto = 1; producto <= 5; producto++) {
                let mayorVenta = 0;
                
                for (let comp = 1; comp <= 9; comp++) {
                    const input = document.getElementById('comp' + producto + '_' + comp);
                    if (input) {
                        const valor = parseFloat(input.value) || 0;
                        if (valor > mayorVenta) {
                            mayorVenta = valor;
                        }
                    }
                }
                
                const mayorElement = document.getElementById('mayor' + producto);
                if (mayorElement) {
                    mayorElement.textContent = mayorVenta;
                }
                
                const ventasProducto = parseFloat(document.getElementById('ventas' + producto).value) || 0;
                let prm = 0;
                
                if (mayorVenta > 0) {
                    const ratio = ventasProducto / mayorVenta;
                    prm = ratio > 2 ? 2 : ratio;
                }
                
                const prmInput = document.getElementById('prm' + producto);
                if (prmInput) {
                    prmInput.value = prm.toFixed(6);
                }
            }
            
            actualizarMatrizBCG();
        }
        
        function actualizarMatrizBCG() {
            for (let producto = 1; producto <= 5; producto++) {
                const bubble = document.getElementById('bubble' + producto);
                if (!bubble) continue;
                
                const prmInput = document.getElementById('prm' + producto);
                const prm = parseFloat(prmInput.value) || 0;
                
                const tcmElement = document.getElementById('tcm_avg_' + producto);
                const tcm = parseFloat(tcmElement.textContent) || 0;
                
                const porcentajeParticipacion = parseFloat(document.getElementById('percent' + producto).textContent) || 0;
                const ventasProducto = parseFloat(document.getElementById('ventas' + producto).value) || 0;
                
                const maxVentas = Math.max(...[1,2,3,4,5].map(p => parseFloat(document.getElementById('ventas' + p).value) || 0));
                const minSize = 50;
                const maxSize = 120;
                const bubbleSize = maxVentas > 0 ? 
                    minSize + ((ventasProducto / maxVentas) * (maxSize - minSize)) : 
                    minSize;
                
                let posX;
                const margenX = 40;
                const anchoUtil = matrixWidth - (2 * margenX) - bubbleSize;
                
                const todosPRM = [1,2,3,4,5].map(p => {
                    const prmEl = document.getElementById('prm' + p);
                    return parseFloat(prmEl.value) || 0;
                });
                const maxPRM = Math.max(...todosPRM);
                const hayDatosPRM = maxPRM > 0;
                
                // Eje X: PRM (Participación Relativa del Mercado)
                // PRM bajo (< 1.0) → Derecha | PRM alto (> 1.0) → Izquierda
                // Línea divisoria en PRM = 1.0 (centro)
                if (!hayDatosPRM || maxPRM < 0.001) {
                    // Sin datos: distribuir uniformemente en forma de arco
                    const espacioTotal = matrixWidth - (2 * margenX) - bubbleSize;
                    const paso = espacioTotal / 6;
                    posX = margenX + (paso * producto);
                } else {
                    if (prm <= 0.01) {
                        posX = matrixWidth - margenX - bubbleSize;  // Extremo derecha (PRM muy bajo)
                    } else if (prm >= 2) {
                        posX = margenX;  // Extremo izquierda (PRM muy alto)
                    } else {
                        // Usar escala logarítmica para mejor distribución
                        // PRM de 0.01 a 2.0 se mapea logarítmicamente
                        const logPRM = Math.log10(prm);
                        const logMin = Math.log10(0.01);  // -2
                        const logMax = Math.log10(2);      // 0.301
                        const logCenter = 0;               // log10(1) = 0 (centro)
                        
                        // Normalizar entre 0 y 1
                        const factorX = (logPRM - logMin) / (logMax - logMin);
                        // Invertir para que mayor PRM vaya a la izquierda
                        posX = matrixWidth - margenX - bubbleSize - (factorX * anchoUtil);
                    }
                }
                
                let posY;
                const margenY = 40;
                const altoUtil = matrixHeight - (2 * margenY) - bubbleSize;
                
                const todosTCM = [1,2,3,4,5].map(p => {
                    const tcmEl = document.getElementById('tcm_avg_' + p);
                    return parseFloat(tcmEl.textContent) || 0;
                });
                const maxTCM = Math.max(...todosTCM);
                const minTCM = Math.min(...todosTCM.filter(t => t > 0));
                const hayDatosTCM = maxTCM > 0;
                const rangoTCM = maxTCM - minTCM;
                
                // Eje Y: TCM (Tasa de Crecimiento del Mercado)
                // TCM alto → Arriba | TCM bajo → Abajo
                if (!hayDatosTCM || rangoTCM < 0.1) {
                    // Sin datos o valores muy similares: distribuir en zigzag
                    const espacioVertical = matrixHeight - (2 * margenY) - bubbleSize;
                    const pasoY = espacioVertical / 6;
                    // Crear patrón diagonal para evitar solapamiento
                    const offset = (producto % 2 === 0) ? 0 : pasoY * 0.3;
                    posY = margenY + (pasoY * producto) + offset;
                } else {
                    // Factor normalizado: 0 (TCM bajo) a 1 (TCM alto)
                    const factorY = (tcm - minTCM) / rangoTCM;
                    // TCM alto (factorY = 1) → Arriba (margenY)
                    // TCM bajo (factorY = 0) → Abajo (margenY + altoUtil)
                    posY = margenY + ((1 - factorY) * altoUtil);
                }
                
                posX = Math.max(10, Math.min(posX, matrixWidth - bubbleSize - 10));
                posY = Math.max(10, Math.min(posY, matrixHeight - bubbleSize - 10));
                
                bubble.style.left = posX + 'px';
                bubble.style.top = posY + 'px';
                bubble.style.width = bubbleSize + 'px';
                bubble.style.height = bubbleSize + 'px';
                bubble.textContent = porcentajeParticipacion.toFixed(1) + '%';
                
                const fontSize = Math.max(10, Math.min(16, bubbleSize / 6));
                bubble.style.fontSize = fontSize + 'px';
            }
        }
        
        window.addEventListener('DOMContentLoaded', function() {
            // Detectar si las tablas necesitan scroll horizontal
            if (window.innerWidth <= 768) {
                const tableContainers = document.querySelectorAll('.table-container');
                tableContainers.forEach(container => {
                    const table = container.querySelector('.data-table');
                    if (table && table.scrollWidth <= container.clientWidth) {
                        container.classList.add('no-scroll');
                    }
                });
            }
            
            for (let i = 1; i <= 5; i++) {
                const input = document.getElementById('ventas' + i);
                input.addEventListener('input', function() {
                    calcularPorcentajes();
                    calcularPRM();
                });
                input.addEventListener('change', function() {
                    calcularPorcentajes();
                    calcularPRM();
                });
            }
            
            for (let periodo = 1; periodo <= 5; periodo++) {
                for (let producto = 1; producto <= 5; producto++) {
                    const input = document.getElementById('tcm' + periodo + '_' + producto);
                    if (input) {
                        input.addEventListener('input', function() {
                            calcularTCMPromedio();
                            actualizarMatrizBCG();
                        });
                        input.addEventListener('change', function() {
                            calcularTCMPromedio();
                            actualizarMatrizBCG();
                        });
                    }
                }
            }
            
            for (let producto = 1; producto <= 5; producto++) {
                for (let comp = 1; comp <= 9; comp++) {
                    const input = document.getElementById('comp' + producto + '_' + comp);
                    if (input) {
                        input.addEventListener('input', calcularPRM);
                        input.addEventListener('change', calcularPRM);
                    }
                }
            }
            
            for (let i = 1; i <= 5; i++) {
                const prmInput = document.getElementById('prm' + i);
                if (prmInput) {
                    prmInput.addEventListener('input', actualizarMatrizBCG);
                    prmInput.addEventListener('change', actualizarMatrizBCG);
                }
            }
            
            // Agregar listeners para sincronizar nombres de productos
            for (let i = 1; i <= 5; i++) {
                const nombreInput = document.getElementById('nombre_producto' + i);
                if (nombreInput) {
                    nombreInput.addEventListener('input', sincronizarNombresProductos);
                    nombreInput.addEventListener('change', sincronizarNombresProductos);
                }
            }
            
            // Inicializar sincronización de nombres
            sincronizarNombresProductos();
            
            cargarDatosGuardados();
            calcularPorcentajes();
            calcularTCMPromedio();
            calcularPRM();
            actualizarMatrizBCG();
            
            // Inicializar funciones colaborativas
            if (modoColaborativo) {
                sincronizarDatos();
                validarPermisos();
            }
        });
        
        function logout() {
            if (confirm('¿Está seguro que desea cerrar sesión?')) {
                window.location.href = 'logout.jsp';
            }
        }
        
        function guardarDatos() {
            if (!<%= modoColaborativo %>) {
                alert('Función disponible solo en modo colaborativo');
                return;
            }
            
            // Recopilar nombres de productos
            const nombresProductos = {};
            for (let i = 1; i <= 5; i++) {
                const nombreInput = document.getElementById('nombre_producto' + i);
                if (nombreInput) {
                    nombresProductos['nombre_producto' + i] = nombreInput.value;
                }
            }
            
            // Recopilar periodos/años
            const periodosData = {};
            for (let i = 1; i <= 5; i++) {
                const periodoInput = document.getElementById('periodo' + i);
                if (periodoInput) {
                    periodosData['periodo' + i] = periodoInput.value;
                }
            }
            
            // Recopilar años individuales
            for (let i = 1; i <= 6; i++) {
                const anioInput = document.getElementById('anio' + i);
                if (anioInput) {
                    periodosData['anio' + i] = anioInput.value;
                }
            }
            
            // Recopilar datos de ventas
            const ventasData = {
                producto1: document.getElementById('ventas1').value,
                producto2: document.getElementById('ventas2').value,
                producto3: document.getElementById('ventas3').value,
                producto4: document.getElementById('ventas4').value,
                producto5: document.getElementById('ventas5').value
            };
            
            // Recopilar datos TCM - Método alternativo usando querySelectorAll
            const tcmData = {};
            let tcmCount = 0;
            const tcmInputs = document.querySelectorAll('input[id^="tcm"]');
            console.log('🔍 TCM inputs encontrados:', tcmInputs.length);
            tcmInputs.forEach(input => {
                if (input.id.match(/^tcm\d+_\d+$/)) {  // Solo los inputs tcm1_1, tcm2_3, etc.
                    const value = input.value;
                    tcmData[input.id] = value;
                    console.log(`📝 TCM campo ${input.id} = ${value}`);
                    if (value && value !== '0') {
                        tcmCount++;
                    }
                }
            });
            console.log('💾 TCM a guardar:', tcmData);
            console.log('💾 TCM con valores != 0:', tcmCount);
            console.log('💾 TCM JSON:', JSON.stringify(tcmData));
            
            // Recopilar datos de competidores
            const competidoresData = {};
            const competidorInputs = document.querySelectorAll('input[id*="comp"]');
            competidorInputs.forEach(input => {
                competidoresData[input.id] = input.value;
            });
            
            // Recopilar datos de PRM
            const prmData = {};
            for (let i = 1; i <= 5; i++) {
                const prmInput = document.getElementById('prm' + i);
                if (prmInput) {
                    prmData['prm' + i] = prmInput.value;
                }
            }
            
            // Recopilar datos de demanda
            const demandaData = {};
            const demandaInputs = document.querySelectorAll('input[id*="demanda"]');
            demandaInputs.forEach(input => {
                demandaData[input.id] = input.value;
            });
            console.log('💾 Demanda a guardar:', demandaData);
            console.log('💾 Demanda JSON:', JSON.stringify(demandaData));
            
            // Recopilar datos FODA
            const fodaData = {
                fortaleza3: document.getElementById('fortaleza3').value,
                fortaleza4: document.getElementById('fortaleza4').value,
                debilidad3: document.getElementById('debilidad3').value,
                debilidad4: document.getElementById('debilidad4').value
            };
            
            // Enviar datos al servidor
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // Agregar nombres de productos
            const nombresInput = document.createElement('input');
            nombresInput.name = 'nombres_productos_data';
            nombresInput.value = JSON.stringify(nombresProductos);
            form.appendChild(nombresInput);
            
            // Agregar periodos/años
            const periodosInput = document.createElement('input');
            periodosInput.name = 'periodos_data';
            periodosInput.value = JSON.stringify(periodosData);
            form.appendChild(periodosInput);
            
            // Agregar datos de ventas
            const ventasInput = document.createElement('input');
            ventasInput.name = 'ventas_data';
            ventasInput.value = JSON.stringify(ventasData);
            form.appendChild(ventasInput);
            
            // Agregar datos TCM
            const tcmInput = document.createElement('input');
            tcmInput.name = 'tcm_data';
            tcmInput.value = JSON.stringify(tcmData);
            form.appendChild(tcmInput);
            
            // Agregar datos de competidores
            const compInput = document.createElement('input');
            compInput.name = 'competidores_data';
            compInput.value = JSON.stringify(competidoresData);
            form.appendChild(compInput);
            
            // Agregar datos de PRM
            const prmInput = document.createElement('input');
            prmInput.name = 'prm_data';
            prmInput.value = JSON.stringify(prmData);
            form.appendChild(prmInput);
            
            // Agregar datos de demanda
            const demandaInput = document.createElement('input');
            demandaInput.name = 'demanda_data';
            demandaInput.value = JSON.stringify(demandaData);
            form.appendChild(demandaInput);
            
            // Agregar datos FODA
            const fodaInput = document.createElement('input');
            fodaInput.name = 'foda_data';
            fodaInput.value = JSON.stringify(fodaData);
            form.appendChild(fodaInput);
            
            // Agregar acción
            const accionInput = document.createElement('input');
            accionInput.name = 'accion';
            accionInput.value = 'guardar_datos';
            form.appendChild(accionInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Función para sincronizar datos en tiempo real
        function sincronizarDatos() {
            if (!modoColaborativo) return;
            
            // Implementar sincronización automática cada 30 segundos
            setInterval(function() {
                // Verificar cambios y sincronizar si es necesario
                console.log('Sincronizando datos colaborativos...');
            }, 30000);
        }
        
        // Función para validar permisos de usuario
        function validarPermisos() {
            const rolUsuario = '<%= rolUsuario %>';
            const esLider = rolUsuario === 'Líder';
            
            // Habilitar/deshabilitar funciones según el rol
            const botonGuardar = document.querySelector('.btn-primary');
            if (botonGuardar && !esLider) {
                botonGuardar.style.opacity = '0.6';
                botonGuardar.title = 'Solo el líder puede guardar cambios';
            }
        }
        
        function exportarDatos() {
            // Implementar exportación de datos
            alert('Función de exportación en desarrollo');
        }
        
        function cargarDatosGuardados() {
            console.log('🔄 Cargando datos guardados...');
            console.log('Modo colaborativo:', <%= modoColaborativo %>);
            
            // Cargar nombres de productos
            <% if (modoColaborativo && !datosNombresProductos.isEmpty()) { %>
                try {
                    const nombresJSON = '<%= datosNombresProductos.replace("'", "\\'").replace("\"", "\\\"") %>';
                    console.log('📝 Nombres de productos JSON:', nombresJSON);
                    const nombresGuardados = JSON.parse(nombresJSON);
                    Object.keys(nombresGuardados).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.value = nombresGuardados[key];
                            console.log('✅ Nombre producto cargado:', key, '=', nombresGuardados[key]);
                        }
                    });
                    sincronizarNombresProductos();
                } catch (e) {
                    console.error('❌ Error cargando nombres de productos:', e);
                }
            <% } %>
            
            // Cargar periodos y años
            <% if (modoColaborativo && !datosPeriodos.isEmpty()) { %>
                try {
                    const periodosJSON = '<%= datosPeriodos.replace("'", "\\'").replace("\"", "\\\"") %>';
                    console.log('📅 Periodos JSON:', periodosJSON);
                    const periodosGuardados = JSON.parse(periodosJSON);
                    Object.keys(periodosGuardados).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.value = periodosGuardados[key];
                            console.log('✅ Periodo/Año cargado:', key, '=', periodosGuardados[key]);
                        }
                    });
                } catch (e) {
                    console.error('❌ Error cargando periodos:', e);
                }
            <% } %>
            
            // Cargar datos colaborativos si están disponibles
            <% if (modoColaborativo && !datosVentas.isEmpty()) { %>
                try {
                    const ventasJSON = '<%= datosVentas.replace("'", "\\'").replace("\"", "\\\"") %>';
                    console.log('📊 Ventas JSON:', ventasJSON);
                    const ventasGuardadas = JSON.parse(ventasJSON);
                    Object.keys(ventasGuardadas).forEach(key => {
                        const input = document.getElementById(key.replace('producto', 'ventas'));
                        if (input) {
                            input.value = ventasGuardadas[key];
                            console.log('✅ Cargado ventas:', key, '=', ventasGuardadas[key]);
                        }
                    });
                    calcularPorcentajes();
                } catch (e) {
                    console.error('❌ Error cargando datos de ventas:', e);
                }
            <% } else { %>
                console.log('⚠️ No hay datos de ventas para cargar');
            <% } %>
            
            <% if (modoColaborativo && !datosTCM.isEmpty()) { %>
                try {
                    const tcmJSON = '<%= datosTCM.replace("'", "\\'").replace("\"", "\\\"") %>';
                    console.log('📈 TCM JSON:', tcmJSON);
                    const tcmGuardados = JSON.parse(tcmJSON);
                    let tcmCargados = 0;
                    Object.keys(tcmGuardados).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.value = tcmGuardados[key];
                            tcmCargados++;
                        }
                    });
                    console.log('✅ TCM cargados:', tcmCargados, 'campos');
                    calcularTCMPromedio();
                } catch (e) {
                    console.error('❌ Error cargando datos TCM:', e);
                }
            <% } else { %>
                console.log('⚠️ No hay datos de TCM para cargar');
            <% } %>
            
            <% if (modoColaborativo && !datosCompetidores.isEmpty()) { %>
                try {
                    const compGuardados = JSON.parse('<%= datosCompetidores.replace("'", "\\'") %>');
                    Object.keys(compGuardados).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.value = compGuardados[key];
                        }
                    });
                    calcularPRM();
                } catch (e) {
                    console.log('Error cargando datos de competidores:', e);
                }
            <% } %>
            
            <% if (modoColaborativo && !datosPRM.isEmpty()) { %>
                try {
                    const prmGuardados = JSON.parse('<%= datosPRM.replace("'", "\\'") %>');
                    Object.keys(prmGuardados).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.value = prmGuardados[key];
                        }
                    });
                    actualizarMatrizBCG();
                } catch (e) {
                    console.log('Error cargando datos PRM:', e);
                }
            <% } %>
            
            <% if (modoColaborativo && !datosDemanda.isEmpty()) { %>
                try {
                    const demandaGuardados = JSON.parse('<%= datosDemanda.replace("'", "\\'") %>');
                    Object.keys(demandaGuardados).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.value = demandaGuardados[key];
                        }
                    });
                } catch (e) {
                    console.log('Error cargando datos de demanda:', e);
                }
            <% } %>
            
            <% if (modoColaborativo && !datosFoda.isEmpty()) { %>
                try {
                    const fodaGuardados = JSON.parse('<%= datosFoda.replace("'", "\\'") %>');
                    Object.keys(fodaGuardados).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.value = fodaGuardados[key];
                        }
                    });
                } catch (e) {
                    console.log('Error cargando datos FODA:', e);
                }
            <% } %>
            
            // Mostrar información del modo actual
            if (<%= modoColaborativo %>) {
                console.log('Modo colaborativo activo - Grupo ID: ' + <%= grupoId != null ? grupoId : "null" %>);
            } else {
                console.log('Modo individual - Solo lectura');
            }
        }
    </script>
</body>
</html>