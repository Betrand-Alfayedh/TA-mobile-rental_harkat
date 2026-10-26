"""AD-7 Memesan Mobil (versi sederhana): satu keputusan, tanpa panah balik. Hasil: AD-7.svg (PNG dibuat dengan Chrome headless)."""
import os
from xml.sax.saxutils import escape

W, H = 942, 850
GARIS = "#5b6785"
o = []


def tx(x, y, s, anchor="middle", bold=True, size=None, fill="#4a4a4a", halo=False):
    a = f' font-size="{size}"' if size else ""
    w = ' font-weight="700"' if bold else ""
    h = ' stroke="#fff" stroke-width="3" paint-order="stroke"' if halo else ""
    o.append(f'<text x="{x}" y="{y}" text-anchor="{anchor}"{w}{a} fill="{fill}"{h}>{escape(s)}</text>')


def aksi(cx, cy, w, h, lines):
    o.append(f'<rect x="{cx - w / 2}" y="{cy - h / 2}" width="{w}" height="{h}" rx="11" fill="url(#ga)" stroke="#a39382" stroke-width="1.2" filter="url(#sh)"/>')
    y0 = cy - (len(lines) - 1) * 8 + 4.5
    for i, s in enumerate(lines):
        tx(cx, y0 + i * 16, s)


def panah(d):
    o.append(f'<path d="{d}" fill="none" stroke="{GARIS}" stroke-width="1.1" marker-end="url(#p)"/>')


def akhir(cx, cy):
    o.append(f'<circle cx="{cx}" cy="{cy}" r="13" fill="#c9dea0" stroke="#7d7d6d" stroke-width="1.5"/>')
    o.append(f'<circle cx="{cx}" cy="{cy}" r="6.5" fill="#9c8383" stroke="#7d6a6a" stroke-width="1"/>')
    tx(cx, cy + 30, "Selesai", bold=False, fill="#444")


PX, SX, RX = 225, 600, 815          # pusat lajur Pelanggan, kolom utama Sistem, kolom penolakan
o.append(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" width="{W}" height="{H}" font-family="Calibri, \'Segoe UI\', Arial, sans-serif" font-size="12.5">')
o.append('<defs><linearGradient id="ga" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fffaf0"/><stop offset="1" stop-color="#f9dfaa"/></linearGradient>'
         '<linearGradient id="gd" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d6e6b4"/><stop offset="1" stop-color="#bdd68f"/></linearGradient>'
         '<filter id="sh" x="-10%" y="-10%" width="130%" height="140%"><feDropShadow dx="2.5" dy="2.5" stdDeviation="1.6" flood-color="#000" flood-opacity="0.28"/></filter>'
         '<marker id="p" viewBox="0 0 12 12" refX="11" refY="6" markerWidth="12" markerHeight="12" markerUnits="userSpaceOnUse" orient="auto"><path d="M1,1.5 L11,6 L1,10.5" fill="none" stroke="#5b6785" stroke-width="1.2"/></marker></defs>')
o.append(f'<rect width="{W}" height="{H}" fill="#fff"/>')
o.append(f'<rect x="0.5" y="0.5" width="{W - 1}" height="{H - 1}" fill="#fff" stroke="#3a3a3a" stroke-width="1"/>')
o.append('<path d="M0.5,0.5 H148 V16 L138,24.5 H0.5 Z" fill="#fff" stroke="#3a3a3a" stroke-width="1"/>')
o.append('<text x="8" y="17" font-weight="700" font-size="13" fill="#222">act Memesan Mobil</text>')
o.append('<g transform="translate(14,0)">')
o.append('<rect x="0" y="34" width="450" height="32" fill="#fff" stroke="#8e8e8e" stroke-width="1.5"/><rect x="450" y="34" width="464" height="32" fill="#fff" stroke="#8e8e8e" stroke-width="1.5"/>')
tx(225, 54, "Pelanggan", bold=False, size=13, fill="#333"); tx(682, 54, "Sistem", bold=False, size=13, fill="#333")
for x in (0, 450, 914):
    o.append(f'<line x1="{x}" y1="34" x2="{x}" y2="{H - 6}" stroke="#8e8e8e" stroke-width="3"/>')

# garis alur
panah(f"M{PX},117 V144")
panah(f"M{PX},212 V268 H485")
panah(f"M{SX},294 V358 H335")
panah(f"M{PX},392 V470 H524")
panah(f"M676,470 H730")
panah(f"M{RX},504 V560")
panah(f"M{SX},506 V550")
panah(f"M{SX},602 V638")
panah(f"M{SX},690 V732")
panah(f"M485,766 H238")

# simpul
o.append(f'<circle cx="{PX}" cy="103" r="14" fill="#9c8383" stroke="#7d6a6a" stroke-width="1"/>')
tx(PX + 20, 107.5, "Mulai", anchor="start", bold=False, fill="#444")
aksi(PX, 178, 220, 68, ["Memilih mobil, tanggal", "sewa, tanggal kembali, dan", "layanan"])
aksi(SX, 268, 230, 52, ["Menampilkan estimasi biaya", "dan uang muka"])
aksi(PX, 358, 220, 68, ["Mengisi asal kota dan", "jaminan lalu menekan", "'Pesan'"])
o.append(f'<polygon points="{SX},434 676,470 {SX},506 524,470" fill="url(#gd)" stroke="#7f8f63" stroke-width="1.2" filter="url(#sh)"/>')
tx(SX, 404, "Profil lengkap, jadwal sewa valid,", bold=False, fill="#333", halo=True)
tx(SX, 420, "dan semua mobil tersedia?", bold=False, fill="#333", halo=True)
aksi(RX, 470, 170, 68, ["Menolak pesanan dan", "menampilkan alasan", "penolakan"])
aksi(SX, 576, 230, 52, ["Menyimpan pesanan", "berstatus Booked"])
aksi(SX, 664, 230, 52, ["Membuat tagihan uang muka", "dan batas pembayaran"])
aksi(SX, 766, 230, 68, ["Menampilkan pesan 'Booking", "berhasil! Segera bayar DP'", "beserta batas pembayaran"])
akhir(RX, 575)
akhir(PX, 766)
tx(684, 462, "Tidak", anchor="start", bold=False, size=12, fill="#333", halo=True)
tx(608, 526, "Ya", anchor="start", bold=False, size=12, fill="#333", halo=True)
o.append("</g></svg>")
# panah berakhir di lingkaran: geser ujung agar menyentuh tepi (garis berhenti 1-2 px sebelum lingkaran)
open(os.path.join(os.path.dirname(os.path.abspath(__file__)), "AD-7.svg"), "w", encoding="utf8").write("\n".join(o))
print("ok")
