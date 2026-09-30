from pathlib import Path
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import arabic_reshaper
from bidi.algorithm import get_display


ROOT = Path(__file__).resolve().parents[1]
SOURCE = Path(r"C:\Users\Parsihamrah\.codex\generated_images\01a0f203-c02b-7883-abf4-c5835054547a\exec-f688fd9e-0b7f-4c71-8e7b-ee8b0f070eb1.png")
OUTPUT = ROOT / "assets" / "digistock-logo.png"

CANVAS = (1400, 480)
RED = "#eb073f"
CHARCOAL = "#232226"


def font(path: str, size: int):
    return ImageFont.truetype(path, size)


def main():
    icon = Image.open(SOURCE).convert("RGBA")
    bbox = icon.getchannel("A").getbbox()
    icon = icon.crop(bbox)
    icon.thumbnail((390, 390), Image.Resampling.LANCZOS)

    canvas = Image.new("RGBA", CANVAS, (0, 0, 0, 0))

    # A restrained soft shadow keeps the mark readable on light website backgrounds.
    shadow = Image.new("RGBA", CANVAS, (0, 0, 0, 0))
    shadow_icon = Image.new("RGBA", icon.size, (35, 34, 38, 0))
    shadow_icon.putalpha(icon.getchannel("A").point(lambda a: int(a * 0.22)))
    shadow.alpha_composite(shadow_icon, (70, 68))
    shadow = shadow.filter(ImageFilter.GaussianBlur(14))
    canvas.alpha_composite(shadow)

    canvas.alpha_composite(icon, (60, 42))

    draw = ImageDraw.Draw(canvas)
    persian = get_display(arabic_reshaper.reshape("دیجی استوک"))
    persian_font = font(r"C:\Windows\Fonts\tahomabd.ttf", 86)
    latin_font = font(r"C:\Windows\Fonts\segoeuib.ttf", 34)

    # Draw the bilingual wordmark with precise brand colors.
    persian_box = draw.textbbox((0, 0), persian, font=persian_font)
    persian_width = persian_box[2] - persian_box[0]
    persian_x = 1330 - persian_width
    draw.text((persian_x, 126), persian, font=persian_font, fill=CHARCOAL)

    draw.text((830, 238), "DigiStock.Com", font=latin_font, fill=RED)

    # Small accent bar gives the wordmark a subtle branded gradient cue.
    bar = Image.new("RGBA", (185, 8), (0, 0, 0, 0))
    bar_draw = ImageDraw.Draw(bar)
    for x in range(bar.width):
        t = x / max(1, bar.width - 1)
        c = (235, int(7 + 35 * t), int(63 + 55 * t), 255)
        bar_draw.line((x, 0, x, bar.height), fill=c)
    canvas.alpha_composite(bar, (830, 300))

    canvas.save(OUTPUT, "PNG", optimize=True)
    print(OUTPUT)


if __name__ == "__main__":
    main()
