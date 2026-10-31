from pathlib import Path

from PIL import Image


def main() -> None:
    carpeta = Path(__file__).parent / "capturas"
    for origen in sorted(carpeta.glob("*.png")):
        destino = origen.with_suffix(".jpg")
        imagen = Image.open(origen).convert("RGB")
        imagen.save(destino, "JPEG", quality=95, subsampling=0, optimize=True)
        origen.unlink()
        print(f"OK {destino.name}")


if __name__ == "__main__":
    main()
