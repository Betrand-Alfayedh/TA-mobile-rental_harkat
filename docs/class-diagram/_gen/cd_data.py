# Isi kelas untuk class diagram landscape (atribut inti saja; kamus data lengkap ada di ERD dan tabel Bab III)
CLS = {
 "Pembayaran": (None, ["id: int","booking_id: int","jumlah: decimal","jenis: int","status_pembayaran: int","metode_pembayaran: int","foto_bukti: string","jatuh_tempo: datetime"], []),
 "Booking": (None, ["id: int","user_id: int","tanggal_booking: datetime","status: int","uang_muka: decimal","total_harga: decimal"], ["canBeCanceledByCustomer(): bool"]),
 "BookingDetail": (None, ["id: int","booking_id: int","mobil_id: int","supir_id: int","pakai_supir: bool","tanggal_sewa: datetime","tanggal_kembali: datetime","status: int"], ["bebaskanSupir(): void"]),
 "JobOffer": (None, ["id: int","booking_detail_id: int","supir_id: int","status: int"], []),
 "Pengembalian": (None, ["id: int","booking_detail_id: int","tanggal_kembali_aktual: datetime","kondisi: int","nominal_denda: int","gambar: string"], []),
 "LaporanKondisi": (None, ["id: int","booking_detail_id: int","jenis: int","kondisi: int","catatan: string","foto: string"], []),
 "User": (None, ["id: int","name: string","email: string","role: int","no_hp: string","aktif: bool"], ["getRoleName(): string","isRole(roleName): bool"]),
 "Supir": (None, ["id: int","user_id: int","status: int","status_approval: int"], ["getIsAvailable(): bool"]),
 "Mobil": (None, ["id: int","master_mobil_id: int","plat_nomor: string","merk: string","tahun: int","harga_sewa: decimal","status: int"], ["getHargaPerHari(pakaiSupir): int","isAvailableFor(start, end): bool"]),
 "MasterMobil": (None, ["id: int","nama: string","tipe_id: int"], []),
 "MobilImage": (None, ["id: int","mobil_id: int","image_path: string"], []),
 "TipeMobil": (None, ["id: int","nama_tipe: string"], []),
 "PembayaranService": ("Service", [], ["verifikasi(): Pembayaran","tandaiLunas(): Pembayaran","tolak(): Pembayaran","kirimInvoice(): string"]),
 "BookingService": ("Service", [], ["create(): Booking","cancel(): void"]),
 "RekapService": ("Service", [], ["bulanan(): array"]),
 "JemputService": ("Service", [], ["konfirmasi(): BookingDetail"]),
 "PenugasanService": ("Service", [], ["tawarkan(): int","terima(): void","tolak(): void"]),
 "PengembalianService": ("Service", [], ["catat(): Pengembalian"]),
 "LaporanKondisiService": ("Service", [], ["simpan(): LaporanKondisi"]),
}
CW, LH = 6.3, 16
def size(name):
    st, at, me = CLS[name]
    w = max([len(name) * 7.2 + 16] + [len(s) * CW + 30 for s in at + me]) + 4
    w = max(w, 120)
    h = 26 + (12 if st else 0) + (len(at) * LH + 8 if at else 0) + (len(me) * LH + 8 if me else 0)
    if not at and not me: h += 8
    return round(w), round(h)
if __name__ == "__main__":
    tot = 0
    for n in CLS:
        w, h = size(n); tot += w * h; print(n, w, h)
    print(tot)
