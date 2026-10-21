# Micro-HIS Recepción de muestras

PHP 8.2 vanilla, sin Composer. Desde la raíz: `php micro-his/recepcion-muestras/tests/run.php`.
Domain valida requisitos de blood/urine/swab bajo un mismo contrato (LSP).
Application coordina recepción; Repository abstrae persistencia; PDO usa parámetros preparados.
El adaptador InMemory permite ejecutar éxito, rechazo y fallo de persistencia sin infraestructura.
`schema.sql` define el almacenamiento PostgreSQL del adaptador PDO y de la implementación Laravel.

La integración debe cargar una proyección confiable de órdenes y requisitos desde ASII-16/17.
El cliente solo informa observaciones: nunca impone identidad esperada ni mínimos del catálogo.
La recepción bloquea la fila, guarda muestra, cambia estado y encola rechazo en una transacción.
El outbox conserva el aviso; su entrega a ASII-21 requiere un consumidor, aún pendiente.
El código aleatorio de 96 bits tiene dígito de control y restricción UNIQUE; una colisión revierte
la operación. No se declara implementado el reintento selectivo propuesto en semana 1.
La impresión usa el código persistido; nunca vuelve a ejecutar la recepción.
No hay comandos de borrado/edición de muestras. La inmutabilidad frente a administradores SQL
requiere privilegios del despliegue y no está certificada aquí.
