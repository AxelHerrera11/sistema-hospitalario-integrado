from pathlib import Path

import yaml

CONTRATO = Path(__file__).resolve().parents[1] / "semana-05" / "openapi.yaml"

ESPERADO = {
    ("/api/v1/lab-orders", "post"): {"200", "201", "400", "401", "403", "422", "503"},
    ("/api/v1/lab-orders", "get"): {"200", "400", "401", "403", "422", "503"},
    ("/api/v1/lab-orders/{labOrderId}", "get"): {"200", "400", "401", "403", "404", "503"},
    ("/api/v1/lab-orders/{labOrderId}/status", "patch"): {"200", "400", "401", "403", "404", "409", "422", "503"},
    ("/api/v1/lab-orders/{labOrderId}/cancel", "post"): {"200", "400", "401", "403", "404", "409", "422", "503"},
}


def main() -> None:
    documento = yaml.safe_load(CONTRATO.read_text(encoding="utf-8"))
    fallos = 0
    for (ruta, metodo), esperados in ESPERADO.items():
        respuestas = documento["paths"][ruta][metodo]["responses"]
        obtenidos = {str(codigo) for codigo in respuestas}
        ok = obtenidos == esperados
        print(f"{'PASS' if ok else 'FAIL'}  {metodo.upper()} {ruta}: {sorted(obtenidos)}")
        if not ok:
            print(f"      esperado: {sorted(esperados)}")
            fallos += 1

    motivo = documento["paths"]["/api/v1/lab-orders/{labOrderId}/cancel"]["post"]["requestBody"]["content"]["application/json"]["schema"]["properties"]["reason"]
    ok_motivo = motivo.get("minLength") == 5 and motivo.get("maxLength") == 250
    print(f"{'PASS' if ok_motivo else 'FAIL'}  reason declara minLength 5 y maxLength 250")
    fallos += 0 if ok_motivo else 1

    print(f"OK: contrato OpenAPI consistente ({len(ESPERADO) + 1} verificaciones)" if fallos == 0 else f"FALLARON {fallos} verificaciones")
    raise SystemExit(0 if fallos == 0 else 1)


if __name__ == "__main__":
    main()
