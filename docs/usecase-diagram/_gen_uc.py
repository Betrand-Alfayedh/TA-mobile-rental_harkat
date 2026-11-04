"""Use case diagram gabungan. Setiap use case yang butuh login memakai «include» ke UC2 Login.
Jalankan: python _gen_uc.py  -> UC-Gabungan.svg (PNG dibuat dengan Chrome headless)."""
import math, os

NAMA = {
    1: 'Registrasi Akun', 2: 'Login', 3: 'Mengelola Profil', 4: 'Logout',
    5: 'Melihat Katalog dan Detail Mobil', 6: 'Mengecek Ketersediaan dan Estimasi Biaya',
    7: 'Memesan Mobil', 8: 'Mengunggah Bukti Pembayaran', 9: 'Melihat Pesanan dan Riwayat',
    10: 'Membatalkan Pesanan', 11: 'Melihat Dashboard', 12: 'Memantau Pesanan',
    13: 'Memverifikasi Pembayaran', 14: 'Menolak Bukti Pembayaran', 15: 'Mencatat Pembayaran Langsung',
    16: 'Menugaskan Sopir', 17: 'Mengonfirmasi Jemput Kendaraan', 18: 'Mencatat Pengembalian dan Denda',
    19: 'Melaporkan Kondisi Kendaraan', 20: 'Mengelola Armada', 21: 'Mengelola Sopir',
    22: 'Mengelola Penyewa', 23: 'Melihat Rekap Bulanan', 24: 'Mengatur Status Ketersediaan',
    25: 'Melihat Tawaran Tugas', 26: 'Menerima Tugas', 27: 'Menolak Tugas', 28: 'Melihat Tugas dan Riwayat',
}
PUBLIK = {1, 2, 5, 6}                       # tidak butuh login
KIRI_PEL = [1, 5, 6, 7, 8, 9, 10]           # kolom kiri atas (pelanggan)
KIRI_SOP = [24, 26, 25, 27, 28, 19]         # kolom kiri bawah (sopir)
KANAN = [11, 12, 14, 13, 15, 16, 17, 18, 20, 21, 22, 23]
TENGAH_ATAS, TENGAH_BAWAH = 3, 4            # profil dan logout di atas/bawah Login
RELASI = [(7, 6, 'include'), (14, 13, 'extend'), (15, 13, 'extend'), (26, 25, 'include'), (27, 25, 'include')]
PEL = [1, 5, 6, 7, 8, 9, 10, 3, 4]
SOP = [24, 25, 26, 27, 28, 19, 3, 4]
ADM = [11, 12, 13, 16, 17, 18, 20, 21, 22, 23, 3, 4, 10, 19]

FS, RX, RY = 14.5, 148, 27
RXT = 92                                   # elips profil/logout lebih sempit agar jalur garis ke Login bebas
RXL, RYL = 150, 88                          # elips Login
INK, LINE = '#4a4a4a', '#5b6785'


def baris(teks, rx=RX):
    if len(teks) * FS * 0.53 <= rx * 1.6:
        return [teks]
    kata = teks.split(' ')
    k = min(range(1, len(kata)), key=lambda i: abs(len(' '.join(kata[:i])) - len(' '.join(kata[i:]))))
    return [' '.join(kata[:k]), ' '.join(kata[k:])]


def tepi(cx, cy, rx, ry, tx, ty):
    dx, dy = tx - cx, ty - cy
    k = 1 / math.sqrt((dx / rx) ** 2 + (dy / ry) ** 2)
    return cx + dx * k, cy + dy * k


KOR_KIRI, KOR_KANAN = 262, 135               # jarak koridor dari Login: ruang lewat garis aktor ke use case tengah
XL, XC, XR, XADM = 262, 655, 1048, 1245
PITCH = 80


def susun():
    pos = {}
    ym = 880.0
    # kolom kiri: pelanggan di atas koridor, sopir di bawah
    y = ym - KOR_KIRI
    for u in reversed(KIRI_PEL):
        pos[u] = (XL, y); y -= PITCH
    y = ym + KOR_KIRI
    for u in [19, 24, 26, 25, 27, 28]:
        pos[u] = (XL, y); y += PITCH
    # kolom kanan: admin, koridor di tengah
    atas, bawah = [11, 12, 14, 13, 15], [16, 17, 18, 20, 21, 22, 23]
    y = ym - KOR_KANAN
    for u in reversed(atas):
        pos[u] = (XR, y)
        y -= PITCH + (14 if u in (13, 15) else 0)
    y = ym + KOR_KANAN
    for u in bawah:
        pos[u] = (XR, y); y += PITCH
    pos[2] = (XC, ym)
    pos[TENGAH_ATAS] = (XC, ym - RYL - RY - 22)
    pos[TENGAH_BAWAH] = (XC, ym + RYL + RY + 22)
    return pos, max(p[1] for p in pos.values()), min(p[1] for p in pos.values())


def gambar():
    pos, bawah, _ = susun()
    W, H = 1305, bawah + 100
    xl, yl = pos[2]
    o = [f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H:.0f}" width="{W}" height="{H:.0f}" font-family="Calibri, \'Segoe UI\', Arial, sans-serif" font-size="{FS}">',
         '<defs><linearGradient id="gu" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#eef6ff"/><stop offset="1" stop-color="#b5d3f4"/></linearGradient>'
         '<linearGradient id="gl" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff4dc"/><stop offset="1" stop-color="#f1cf8f"/></linearGradient>'
         '<filter id="sh" x="-10%" y="-10%" width="130%" height="150%"><feDropShadow dx="2.5" dy="2.5" stdDeviation="1.6" flood-color="#000" flood-opacity="0.28"/></filter>'
         f'<marker id="p" viewBox="0 0 12 12" refX="11" refY="6" markerWidth="11" markerHeight="11" markerUnits="userSpaceOnUse" orient="auto"><path d="M1,1.5 L11,6 L1,10.5" fill="none" stroke="{LINE}" stroke-width="1.2"/></marker></defs>',
         f'<rect width="{W}" height="{H:.0f}" fill="#fff"/><rect x="0.5" y="0.5" width="{W-1}" height="{H-1:.0f}" fill="#fff" stroke="#3a3a3a"/>',
         '<path d="M0.5,0.5 H405 V18 L394,27 H0.5 Z" fill="#fff" stroke="#3a3a3a"/>',
         '<text x="8" y="19" font-weight="700" font-size="14" fill="#222">uc Use Case Sistem Informasi Manajemen Rental Mobil Harkat</text>',
         f'<rect x="102" y="52" width="{1223-102}" height="{H-52-24:.0f}" fill="none" stroke="#6b6b6b" stroke-width="1.3"/>',
         '<text x="114" y="74" font-size="13" fill="#666">Sistem Informasi Manajemen Rental Mobil Harkat</text>']
    rx_of = lambda u: RXT if u in (TENGAH_ATAS, TENGAH_BAWAH) else RX
    # aktor
    ay = {'Pelanggan': yl - 120, 'Sopir': yl + 120, 'Admin': yl}
    ax = {'Pelanggan': 52, 'Sopir': 52, 'Admin': 1258}
    # asosiasi aktor
    for nama, daftar in (('Pelanggan', PEL), ('Sopir', SOP), ('Admin', ADM)):
        for u in daftar:
            cx, cy = pos[u]
            kiri = ax[nama] < cx
            sx = ax[nama] + (22 if kiri else -22)
            ex = cx - rx_of(u) if kiri else cx + rx_of(u)
            o.append(f'<line x1="{sx}" y1="{ay[nama]:.1f}" x2="{ex:.1f}" y2="{cy:.1f}" stroke="{LINE}" stroke-width="1.1"/>')
    # pemeriksa: garis asosiasi tidak boleh menembus elips lain
    for nama, daftar in (('Pelanggan', PEL), ('Sopir', SOP), ('Admin', ADM)):
        for u in daftar:
            cx, cy = pos[u]; kiri = ax[nama] < cx
            x1, y1 = ax[nama] + (22 if kiri else -22), ay[nama]
            x2, y2 = (cx - rx_of(u) if kiri else cx + rx_of(u)), cy
            for v, (vx, vy) in pos.items():
                if v == u: continue
                a_, b_ = (RXL, RYL) if v == 2 else (rx_of(v), RY)
                for k in range(0, 101):
                    t = k / 100; px, py = x1 + (x2 - x1) * t, y1 + (y2 - y1) * t
                    if ((px - vx) / (a_ + 4)) ** 2 + ((py - vy) / (b_ + 4)) ** 2 < 1:
                        print('TABRAK', nama, 'UC%d' % u, '-> UC%d' % v); break
    # include ke Login
    kiri = sorted([u for u in KIRI_PEL + KIRI_SOP if u not in PUBLIK], key=lambda u: pos[u][1])
    kanan = sorted([u for u in KANAN if u not in PUBLIK], key=lambda u: pos[u][1])
    lab = []

    def sebar(daftar, sisi):
        n = len(daftar)
        for i, u in enumerate(daftar):
            cx, cy = pos[u]
            ty = yl - 66 + 132 * i / (n - 1)
            tx = xl + sisi * RXL * math.sqrt(1 - ((ty - yl) / RYL) ** 2)
            sx = cx - sisi * RX
            o.append(f'<line x1="{sx:.1f}" y1="{cy:.1f}" x2="{tx:.1f}" y2="{ty:.1f}" stroke="{LINE}" stroke-width="1.1" stroke-dasharray="6 4" marker-end="url(#p)"/>')
            t = 0.18
            lab.append((sx + (tx - sx) * t + (6 if sisi < 0 else -6), cy + (ty - cy) * t - 4, '«include»', 'start' if sisi < 0 else 'end'))
    sebar(kiri, -1)
    sebar(kanan, +1)
    for u, arah in ((TENGAH_ATAS, 1), (TENGAH_BAWAH, -1)):
        cy = pos[u][1]
        o.append(f'<line x1="{xl}" y1="{cy+arah*RY:.1f}" x2="{xl}" y2="{yl-arah*RYL:.1f}" stroke="{LINE}" stroke-width="1.1" stroke-dasharray="6 4" marker-end="url(#p)"/>')
        lab.append((xl + 8, (cy + arah * RY + yl - arah * RYL) / 2 + 4, '«include»', 'start'))
    # relasi antar use case
    for a, b, jenis in RELASI:
        (ax1, ay1), (bx, by) = pos[a], pos[b]
        if ax1 == bx:
            arah = 1 if by > ay1 else -1
            o.append(f'<line x1="{ax1}" y1="{ay1+arah*RY:.1f}" x2="{bx}" y2="{by-arah*RY:.1f}" stroke="{LINE}" stroke-width="1.1" stroke-dasharray="6 4" marker-end="url(#p)"/>')
            lab.append((ax1 + 10, (ay1 + by) / 2 + 4, f'«{jenis}»', 'start'))
    # use case
    for u in list(pos):
        cx, cy = pos[u]
        if u == 2:
            o.append(f'<ellipse cx="{cx}" cy="{cy:.1f}" rx="{RXL}" ry="{RYL}" fill="url(#gu)" stroke="#7f8da6" stroke-width="1.2" filter="url(#sh)"/>')
            o.append(f'<text x="{cx}" y="{cy+5:.1f}" text-anchor="middle" font-weight="700" fill="{INK}">UC2 Login</text>')
            continue
        rx = rx_of(u)
        o.append(f'<ellipse cx="{cx}" cy="{cy:.1f}" rx="{rx}" ry="{RY}" fill="url(#gu)" stroke="#7f8da6" stroke-width="1.2" filter="url(#sh)"/>')
        t = baris(f'UC{u} {NAMA[u]}', rx)
        for i, s in enumerate(t):
            o.append(f'<text x="{cx}" y="{cy+(i-(len(t)-1)/2)*16+5:.1f}" text-anchor="middle" font-weight="700" fill="{INK}">{s}</text>')
    for x, y, s, anc in lab:
        o.append(f'<text x="{x:.1f}" y="{y:.1f}" text-anchor="{anc}" font-size="11.5" fill="#444" stroke="#fff" stroke-width="4" paint-order="stroke">{s}</text>')
    for nama in ay:
        x, y = ax[nama], ay[nama]
        o.append(f'<circle cx="{x}" cy="{y-24:.1f}" r="13" fill="#fde3b8" stroke="#9b7b6b" stroke-width="1.3"/>'
                 f'<path d="M{x},{y-11:.1f} V{y+26:.1f} M{x-22},{y:.1f} H{x+22} M{x},{y+26:.1f} L{x-18},{y+52:.1f} M{x},{y+26:.1f} L{x+18},{y+52:.1f}" stroke="#9b7b6b" stroke-width="1.5" fill="none"/>'
                 f'<text x="{x}" y="{y+72:.1f}" text-anchor="middle" font-weight="700" font-size="14" fill="#555">{nama}</text>')
    o.append('</svg>')
    return '\n'.join(o), W, H


if __name__ == '__main__':
    svg, w, h = gambar()
    open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'UC-Gabungan.svg'), 'w', encoding='utf-8').write(svg)
    print(w, round(h))
