"""Class diagram sistem (12 entitas + 7 service) dalam tata letak lebar untuk halaman landscape.
Jalankan: python cd_wide.py  -> ../Class-Diagram.svg  (PNG dibuat dari SVG dengan Chrome headless, lihat README di folder ini)."""
import os
from xml.sax.saxutils import escape
from cd_data import CLS, size, LH

DY = 10
POS = {  # nama: (x, y) pojok kiri atas
    "PembayaranService": (30, 34), "BookingService": (265, 34), "RekapService": (430, 34), "PenugasanService": (1127, 34),
    "Pembayaran": (40, 180), "Booking": (293, 180), "User": (602, 180), "Supir": (855, 180), "JobOffer": (1108, 180),
    "JemputService": (40, 362),
    "LaporanKondisi": (40, 458), "BookingDetail": (311, 430), "Mobil": (583, 430), "MobilImage": (899, 430),
    "LaporanKondisiService": (31, 622),
    "PengembalianService": (40, 720), "Pengembalian": (289, 666), "MasterMobil": (640, 666), "TipeMobil": (840, 674),
}
B = {n: (POS[n][0], POS[n][1]) + size(n) for n in CLS}   # x, y, w, h
W, H = 1320, 830
GARIS = "#5b6785"


def sisi(n, s, f=0.5):
    x, y, w, h = B[n]
    return {"t": (x + w * f, y), "b": (x + w * f, y + h), "l": (x, y + h * f), "r": (x + w, y + h * f)}[s]


def box(n):
    st, at, me = CLS[n]
    x, y, w, h = B[n]
    o = [f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="url(#gk)" stroke="#8f7f72" stroke-width="1.2" filter="url(#sh)"/>']
    ty = y + 17
    if st:
        o.append(f'<text x="{x + w / 2:.1f}" y="{ty - 1}" text-anchor="middle" font-size="10.5" fill="#555">«{st}»</text>')
        ty += 12
    o.append(f'<text x="{x + w / 2:.1f}" y="{ty}" text-anchor="middle" font-weight="700" font-size="12.5" fill="#333">{n}</text>')
    yy = ty + 7
    o.append(f'<line x1="{x}" y1="{yy}" x2="{x + w}" y2="{yy}" stroke="#b8a99a"/>')
    yy += 15
    last = yy - LH
    for a in at:
        o.append(f'<text x="{x + 8}" y="{yy}" fill="#8a6d3b">+</text><text x="{x + 21}" y="{yy}" fill="#3a3a3a">{escape(a)}</text>')
        last = yy
        yy += LH
    if me:
        if at:
            sep = last + 6
            o.append(f'<line x1="{x}" y1="{sep}" x2="{x + w}" y2="{sep}" stroke="#b8a99a"/>')
            yy = sep + 15
        for m in me:
            o.append(f'<text x="{x + 8}" y="{yy}" fill="#8a6d3b">+</text><text x="{x + 21}" y="{yy}" fill="#3a3a3a">{escape(m)}</text>')
            yy += LH
    return "\n".join(o)


def poly(pts, dash=False, marker=False, start=None):
    p = " ".join(f"{x:.1f},{y:.1f}" for x, y in pts)
    extra = ' stroke-dasharray="6 4"' if dash else ""
    mk = ' marker-end="url(#pt)"' if marker else ""
    o = f'<polyline points="{p}" fill="none" stroke="{GARIS}" stroke-width="1.1"{extra}{mk}/>'
    if start == "diamond":
        (x0, y0), (x1, y1) = pts[0], pts[1]
        dx, dy = (x1 - x0), (y1 - y0)
        L = (dx * dx + dy * dy) ** 0.5
        ux, uy = dx / L, dy / L
        px, py = -uy, ux
        a = (x0, y0); b = (x0 + ux * 9 + px * 5, y0 + uy * 9 + py * 5)
        c = (x0 + ux * 18, y0 + uy * 18); d = (x0 + ux * 9 - px * 5, y0 + uy * 9 - py * 5)
        o += f'<polygon points="{a[0]:.1f},{a[1]:.1f} {b[0]:.1f},{b[1]:.1f} {c[0]:.1f},{c[1]:.1f} {d[0]:.1f},{d[1]:.1f}" fill="{GARIS}" stroke="{GARIS}" stroke-width="1"/>'
    return o


LBL = []


def mult(pt, direction, text):
    """Kardinalitas di dekat ujung garis. direction = arah garis meninggalkan titik (l/r/u/d)."""
    x, y = pt
    off = {"r": (6, -5, "start"), "l": (-6, -5, "end"), "d": (6, 14, "start"), "u": (6, -6, "start")}[direction]
    LBL.append(f'<text x="{x + off[0]:.1f}" y="{y + off[1]:.1f}" text-anchor="{off[2]}" font-size="11" fill="#2f4a8a" stroke="#fff" stroke-width="3" paint-order="stroke">{text}</text>')


E = []
# --- asosiasi ---
def lurus_h(a, sa, b, sb, ya, ka, kb, comp=False):
    p, q = sisi(a, sa, 0), sisi(b, sb, 0)
    # titik pada tinggi y bersama (ya)
    pa, qb = (p[0], ya), (q[0], ya)
    E.append(poly([pa, qb], start="diamond" if comp else None))
    ra = "r" if pa[0] < qb[0] else "l"
    rb = "l" if ra == "r" else "r"
    mult(pa, ra, ka) if not comp else mult((pa[0] + 14, pa[1]), ra, ka)
    mult(qb, rb, kb)


def lurus_v(a, b, xa, ka, kb, comp=False, sa="b", sb="t"):
    pa = (xa, sisi(a, sa)[1]); qb = (xa, sisi(b, sb)[1])
    E.append(poly([pa, qb], start="diamond" if comp else None))
    mult((pa[0], pa[1] + (14 if comp else 0)), "d", ka)
    mult(qb, "u", kb)


by = lambda n: B[n][1]
# Booking - Pembayaran, User - Booking, User - Supir, Supir - JobOffer
lurus_h("Pembayaran", "r", "Booking", "l", 270 + DY * 0, "0..*", "1")
lurus_h("Booking", "r", "User", "l", 270, "0..*", "1")
lurus_h("User", "r", "Supir", "l", 245, "1", "0..1")
lurus_h("Supir", "r", "JobOffer", "l", 245, "1", "0..*")
# Booking (komposisi) - BookingDetail
lurus_v("Booking", "BookingDetail", 407, "1", "1..*", comp=True)
# BookingDetail - Mobil, BookingDetail - LaporanKondisi, Mobil - MobilImage (komposisi)
lurus_h("BookingDetail", "r", "Mobil", "l", 480, "0..*", "1")
lurus_h("LaporanKondisi", "r", "BookingDetail", "l", 540, "0..2", "1")
lurus_h("Mobil", "r", "MobilImage", "l", 470, "1", "0..*", comp=True)
# BookingDetail - Pengembalian, Mobil - MasterMobil, MasterMobil - TipeMobil
lurus_v("BookingDetail", "Pengembalian", 407, "1", "0..1")
lurus_v("Mobil", "MasterMobil", 700, "0..*", "1")
lurus_h("MasterMobil", "r", "TipeMobil", "l", 710, "0..*", "1")
# BookingDetail - Supir (lajur y=376) dan BookingDetail - JobOffer (lajur y=402)
bd_top = B["BookingDetail"][1]
sup = sisi("Supir", "b")[0]; job = sisi("JobOffer", "b")[0]
E.append(poly([(455, bd_top), (455, 376), (sup, 376), (sup, B["Supir"][1] + B["Supir"][3])]))
mult((455, bd_top), "u", "0..*"); mult((sup, B["Supir"][1] + B["Supir"][3]), "d", "0..1")
E.append(poly([(485, bd_top), (485, 402), (job, 402), (job, B["JobOffer"][1] + B["JobOffer"][3])]))
LBL.append(f'<text x="491" y="{bd_top - 6}" font-size="11" fill="#2f4a8a" stroke="#fff" stroke-width="3" paint-order="stroke">1</text>')
mult((job, B["JobOffer"][1] + B["JobOffer"][3]), "d", "0..*")

# --- ketergantungan service -> entitas (garis putus-putus, panah terbuka) ---
def dep_v(svc, ent, xs, xe, sa="b", sb="t"):
    a = sisi(svc, sa)[1]; e = sisi(ent, sb)[1]
    E.append(poly([(xs, a), (xe, e)], dash=True, marker=True))


dep_v("PembayaranService", "Pembayaran", 126, 126)
dep_v("BookingService", "Booking", 345, 345)
dep_v("RekapService", "Booking", 497, 497)
dep_v("PenugasanService", "JobOffer", 1194, 1194)
dep_v("LaporanKondisiService", "LaporanKondisi", 126, 126, sa="t", sb="b")
# Pengembalian service (kiri) -> Pengembalian
ps = B["PengembalianService"]; pg = B["Pengembalian"]
yh = pg[1] + pg[3] / 2
E.append(poly([(ps[0] + ps[2], yh), (pg[0], yh)], dash=True, marker=True))
# Jemput service -> BookingDetail (siku)
js = B["JemputService"]
E.append(poly([(js[0] + js[2], js[1] + js[3] / 2), (285, js[1] + js[3] / 2), (285, 476), (B["BookingDetail"][0], 476)], dash=True, marker=True))

svg = [f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" width="{W}" height="{H}" font-family="Calibri, \'Segoe UI\', Arial, sans-serif" font-size="11.5">',
       '<defs><linearGradient id="gk" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fffbf7"/><stop offset="1" stop-color="#f2e3d3"/></linearGradient>'
       '<filter id="sh" x="-5%" y="-5%" width="115%" height="120%"><feDropShadow dx="2.5" dy="2.5" stdDeviation="1.4" flood-color="#000" flood-opacity="0.25"/></filter>'
       '<marker id="pt" viewBox="0 0 12 12" refX="11" refY="6" markerWidth="12" markerHeight="12" markerUnits="userSpaceOnUse" orient="auto"><path d="M1,1.5 L11,6 L1,10.5" fill="none" stroke="#5b6785" stroke-width="1.2"/></marker></defs>',
       f'<rect width="{W}" height="{H}" fill="#fff"/>',
       f'<rect x="0.5" y="0.5" width="{W - 1}" height="{H - 1}" fill="#fff" stroke="#3a3a3a" stroke-width="1"/>',
       '<path d="M0.5,0.5 H161 V16 L151,24.5 H0.5 Z" fill="#fff" stroke="#3a3a3a" stroke-width="1"/>',
       '<text x="8" y="17" font-weight="700" font-size="13" fill="#222">class Class Diagram</text>']
svg += E + [box(n) for n in CLS] + LBL + ["</svg>"]
out = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "Class-Diagram.svg")
open(out, "w", encoding="utf8").write("\n".join(svg))
print("ok", W, H)
