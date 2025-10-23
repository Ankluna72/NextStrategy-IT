<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}
if (!isset($_SESSION['id_empresa_actual'])) {
    header('Location: dashboard.php');
    exit();
}

$pageStyles = ['css/modules.css']; 
require_once 'includes/db_connection.php';
require_once 'includes/header.php';
?>

<style>
.porter-diagram {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 2.5rem 0;
    font-family: Arial, sans-serif;
}

.porter-force {
    border: 2px solid var(--brand-blue);
    background-color: #f0f5fa;
    color: var(--brand-dark);
    padding: 0.75rem;
    border-radius: 8px;
    font-weight: 600;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    margin: 0.5rem;
    position: relative;
    z-index: 10;
}

.porter-force-top,
.porter-force-bottom {
    width: 60%;
}

.porter-middle-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.porter-center {
    background: var(--brand-blue);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    flex-grow: 1;
    margin: 0 1rem;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    z-index: 5;
    border: 2px solid var(--brand-dark);
}

.porter-force-side {
    width: 25%;
}

.porter-arrow {
    font-size: 2.5rem;
    color: var(--brand-green);
    font-weight: bold;
    z-index: 1;
}

.porter-arrow-v {
    margin: -10px 0;
}
.porter-arrow-h {
    margin: 0 -10px;
}

.porter-explanation {
    margin-top: 2rem;
}
.porter-explanation h5 {
    color: var(--brand-blue);
    font-weight: 700;
    border-bottom: 2px solid var(--brand-green);
    padding-bottom: 5px;
    margin-top: 1.5rem;
}
.porter-explanation ul {
    list-style-type: disc;
    padding-left: 20px;
}
.porter-explanation li {
    margin-bottom: 0.5rem;
}
</style>

<div class="container mt-4">
    <div class="module-container">
        <div class="module-header">
            <h2 class="module-title">8. ANÁLISIS EXTERNO MICROENTORNO: MATRIZ DE PORTER</h2>
        </div>

        <div class="module-content">
            <div class="explanation-box p-4 mb-5">

                <p>El Modelo de las 5 Fuerzas de Porter estudia un determinado negocio en función de la amenaza de nuevos competidores y productos sustitutivos, así como el poder de negociación de los proveedores y clientes, teniendo en cuenta el grado de competencia del sector.</p>
                <p>Esto proporciona una clara imagen de la situación competitiva de un mercado en concreto. El conjunto de las cinco fuerzas determina la intensidad competitiva, la rentabilidad del sector y, de forma derivada, las posibilidades futuras de éste.</p>

                <div class="porter-diagram">
                    <div class="porter-force porter-force-top">
                        1. Amenaza de entrada de nuevos competidores
                    </div>
                    <div class="porter-arrow porter-arrow-v">↓</div>
                    <div class="porter-middle-row">
                        <div class="porter-force porter-force-side">
                            4. Poder de negociación de proveedores
                        </div>
                        <div class="porter-arrow porter-arrow-h">→</div>
                        <div class="porter-center">
                            <h5>2. Rivalidad entre las Empresas del sector</h5>
                        </div>
                        <div class="porter-arrow porter-arrow-h">←</div>
                        <div class="porter-force porter-force-side">
                            5. Poder de negociación de clientes
                        </div>
                    </div>
                    <div class="porter-arrow porter-arrow-v">↑</div>
                    <div class="porter-force porter-force-bottom">
                        3. Amenaza de llegada de nuevos productos sustitutivos
                    </div>
                </div>

                <div class="porter-explanation">
                    <h5>1. Amenaza de nuevos entrantes</h5>
                    <p>La aparición de nuevas empresas en el sector supone un incremento de recursos, de capacidad y, en fin, un intento de obtener una participación en el mercado a costa de otros que ya la tenían. La posibilidad de entrar en un sector depende fundamentalmente de dos factores: la capacidad de reacción de las empresas que ya están (tecnológica, financiera, productiva, etc.) y las denominadas barreras de entrada (obstáculos para el ingreso).</p>
                    
                    <h5>2. Rivalidad de los competidores</h5>
                    <p>La rivalidad aparece cuando uno o varios competidores sienten la presión o ven la oportunidad de mejorar. El grado de rivalidad depende de una serie de factores estructurales, entre los que podemos destacar: gran número de competidores, crecimiento lento en el mercado, costes fijos altos, baja diferenciación de productos, intereses estratégicos y barreras de salida.</p>

                    <h5>3. Presión de los productos sustitutivos</h5>
                    <p>El nivel de precio/calidad de los productos sustitutivos limita el nivel de precios de la industria. Los productos sustitutivos pueden ser fabricados por empresas pertenecientes o ajenas al sector (situación peligrosa). Se debe prestar mucha atención a los "sustitutivos no evidentes" (ejemplo, videoconferencia contra hotel más avión).</p>

                    <h5>4. Poder de negociación de los proveedores</h5>
                    <p>Los proveedores poderosos pueden amenazar con subir los precios y/o disminuir la calidad. Su poder aumenta si: está más concentrado que el sector que compra, no están obligados a competir con sustitutivos, el comprador no es cliente importante, el producto es importante para el comprador, el producto está diferenciado o representan una amenaza de integración.</p>

                    <h5>5. Poder de negociación de los compradores/clientes</h5>
                    <p>Los compradores fuerzan los precios a la baja y la calidad al alza, en perjuicio del beneficio de la industria. Su poder aumenta si: están concentrados (compran grandes volúmenes), el coste de la materia prima es importante, los productos no son diferenciados, el coste de cambio de proveedor es pequeño o tienen información total.</p>
                </div>

                <hr>
                <p class="text-center fst-italic mt-4">Según Porter, estas fuerzas se encuentran en interacción y cambio permanente. Nuestro objetivo será situar a nuestra empresa en una posición en la que se pueda defender de las amenazas que las fuerzas competitivas plantean.</p>

            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="matriz_bcg.php" class="btn btn-nav">&laquo; Anterior: Matriz BCG</a>
                <a href="dashboard.php" class="btn btn-nav-outline">Volver al Índice</a>
                <a href="autodiagnostico_porter.php" class="btn btn-save">Siguiente: Autodiagnóstico Porter &raquo;</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>