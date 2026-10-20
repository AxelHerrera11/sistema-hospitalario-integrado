# Confirmación de tablas CENTRAL — 20 de octubre de 2026

Fuente: CONFIRMACION_TABLAS_AUTH_AUDIT.md, entregado por el usuario.
Verificación atribuida a Javier Alexander Fajardo López sobre origin/develop
6e3278d / b87bca1. Este registro resume su informe; no constituye aprobación del grupo.

| Tabla | Propiedad y acceso |
|---|---|
| auth_credentials | ASII-25 escribe; ASII-25/ASII-02 consultan |
| auth_sessions | ASII-25 escribe; ASII-25/ASII-02 consultan |
| auth_refresh_tokens | ASII-25 escribe y consulta |
| audit_log | ASII-25/ASII-02 escriben; ASII-22 consulta y gobierna |
| audit_chain_state | ASII-25 escribe; ASII-25/ASII-02 consultan; estado auxiliar chain_id=1 |

Las cinco tablas pertenecen a CENTRAL. Javier confirma nombres sin colisiones.
Responsable de usuarios/auth y gobernanza/auditoría: Cindy Maytté Ruano Calderón.
Corrección del usuario (20/10/2026): ASII-22 también corresponde a Cindy, no a Boris.
Se conservan los identificadores técnicos de los módulos; preparación auth de Mar-03.

Numeración propuesta por Javier y aplicada en las ramas, pendiente de acuerdo final:
- #502: 2026_08_11_000500_create_auth_credentials_and_sessions.php
- #502: 2026_08_11_000600_create_auth_refresh_tokens.php
- #507: 2026_08_11_000700_create_audit_log.php
- #507: 2026_08_11_000800_enrich_audit_log.php

Evidencia reportada por Javier en PostgreSQL 16: cuatro migraciones y rollback
correctos, inserciones correctas y trigger que rechaza UPDATE sobre audit_log.
Con las cuatro migraciones: integridad 58/59; sin ellas: 59/59, 738 aserciones.
Yamilet también reportó validación conjunta en PostgreSQL 17 en ambos PR.
Estas verificaciones externas no equivalen a ejecutar la suite sobre los nuevos commits.

Ajuste solicitado por Yamilet, preparado en #507: CentralSyncIntegrityTest revierte
solo el archivo de sincronización 000400, sin asumir que sea la última migración;
comprueba que las demás migraciones sigan registradas y reinstala sincronización.

Pendiente: aprobación explícita del grupo/Javier/rortizs (coordinación Yamilet/Josué)
para CENTRAL 15 → 20, actualización de central-control-schema, propiedad y numeración;
Cindy, responsable también de ASII-22, debe revisar el contrato final de auditoría. No se declara listo para merge.
