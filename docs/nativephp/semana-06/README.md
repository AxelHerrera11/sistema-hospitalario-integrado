# Semana 6 — Primera evaluación parcial

**Módulo:** ASII-29 — Investigación y prototipo NativePHP  
**Estudiante:** ORELLANA VÉLIZ, GERSON GIOVANNI

## Tesis arquitectónica

El prototipo evalúa una futura extensión NativePHP sin afirmar que exista una aplicación NativePHP productiva. La acción local se valida, se conserva por tenant, se sincroniza mediante un gateway y deja telemetría de cada intento.

## Matriz decisión→evidencia

| Decisión defendida | Evidencia | Criterio verificable |
|---|---|---|
| Separar la cola local del transporte | LocalActionRepository y SyncGateway | El caso de uso funciona con dobles |
| Proteger aislamiento por tenant | clave compuesta y validación de entradas | Un tenant no modifica otro |
| Diseñar reintentos | estados pendiente/fallido/sincronizado | Un rechazo conserva motivo y vuelve a intentarse |
| Mantener el lote resiliente | captura de excepciones por acción | Un fallo no oculta el resto del lote |
| Delimitar la viabilidad | README y prototipo vanilla | No se confunde simulación con NativePHP real |

## Cambio práctico defendido

Si el gateway está disponible pero rechaza una acción, esta queda fallida con motivo y puede reintentarse. Si una acción se sincroniza correctamente, pasa a sincronizada y no se duplica en el siguiente lote. Si el gateway lanza una excepción, se registra el fallo y el lote continúa.

## Validación

    php micro-his/nativephp-extension-prototype/tests/run.php
    OK: preparacion local, regla offline y error de persistencia verificados.

## Restricción explícita

La ejecución real de NativePHP, sus binarios y una API productiva quedan fuera del prototipo; la viabilidad técnica se considera preliminar y condicionada a validar persistencia, idempotencia y empaquetado.

