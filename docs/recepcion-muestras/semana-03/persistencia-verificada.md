# Persistencia PostgreSQL verificada — complemento S03

Se prueba el mismo dominio y PdoReceptionRepository de #690, sin reimplementar el micro ni crear tablas de órdenes. Base exclusiva his_reception_test, PostgreSQL 16, servidor 55433; cada ejecución crea y elimina únicamente un esquema temporal rec_test_*.

Comando PowerShell: `$env:RECEPTION_TEST_PORT='55433'; php micro-his/recepcion-muestras/tests/postgres.php`. Se usa la variable que el test ya leía; no se cambia la instalación al puerto privado predeterminado.

La ejecución comprueba aceptación, lectura con tipos y timestamp estables, replay, evento UUID único, consulta preparada, UUID/numeric reales, ausencia de tenant_id, 409 por clave incompatible, rechazo y evento, colisión con salida al primer éxito y rollback si falla outbox.

La prueba utiliza un doble de órdenes. No demuestra integración con ASII-16 ni aislamiento del middleware JWT; la conexión clínica real se integra en S05 y se verifica por HTTP en S11. Las FK clínicas no se sustituyen por este esquema didáctico.

[Fuentes del diagrama](./fuentes/postgres.mmd) · [SVG](./postgres.svg) · [Evidencia](./EVIDENCIA.md).
