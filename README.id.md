# Sistem Pelatihan Medis

Platform pendidikan medis komprehensif yang menggabungkan kemampuan chat bertenaga AI dengan pelatihan OSCE (Objective Structured Clinical Examination) terstruktur untuk mahasiswa kedokteran.

## 🚀 Mulai Cepat

1. **Instal Dependensi**
   ```bash
   npm install
   ```

2. **Konfigurasi Lingkungan**
   Buat berkas `.env` dengan kredensial API OpenRouter Anda:
   ```env
   API_URL=https://openrouter.ai/api/v1/chat/completions
   API_KEY=masukkan_api_key_openrouter_anda_di_sini
   API_MODEL=anthropic/claude-3.5-sonnet
   ```

3. **Jalankan Aplikasi**
   ```bash
   node app.js
   ```

4. **Mulai Pelatihan**
   - Ketik `start osce` untuk memulai pelatihan kasus medis
   - Ketik pesan apa pun untuk mengobrol dengan AI
   - Ketik `help` untuk perintah rinci

## 🏥 Fitur

### Mode Chat
- **Percakapan Bertenaga AI**: Interaksi bahasa alami dengan model AI canggih
- **Memori Percakapan**: Manajemen riwayat otomatis dengan ringkasan cerdas
- **Pemulihan Kesalahan**: Logika ulang dan penanganan error yang tangguh
- **Pelacakan Sesi**: Pantau aktivitas dan statistik chat Anda

### Mode OSCE
- **Kasus Klinis Terstruktur**: Latih diri dengan skenario medis realistis
- **Simulasi Pasien AI**: Respons pasien interaktif berdasarkan data kasus
- **Pelacakan Kinerja**: Pemantauan checklist otomatis dan pelacakan progres
- **Penilaian Cerdas**: Sistem penilaian berbobot dengan umpan balik detail
- **Berbagai Kasus**: Beragam kondisi medis dan spesialisasi

## 📚 Kasus yang Tersedia

### STEMI-001: Sindrom Koroner Akut
- **Skenario**: Pria 58 tahun dengan nyeri dada akut
- **Fokus**: Kardiologi darurat, interpretasi EKG, manajemen waktu kritis
- **Keterampilan yang Dilatih**: Anamnesis, pemeriksaan fisik, penalaran diagnostik
- **Tujuan Pembelajaran**: Pengenalan STEMI, protokol darurat, stratifikasi risiko

*Kasus lainnya tersedia di direktori `cases/`*

## 🎯 Cara Menggunakan Mode OSCE

### Memulai Kasus
1. Ketik `start osce` untuk masuk mode OSCE
2. Pilih kasus dari daftar yang tersedia
3. Baca keluhan utama dan mulai pemeriksaan

### Berinteraksi dengan Pasien
- **Ajukan Pertanyaan**: "Bisakah Anda jelaskan nyeri dada Anda?"
- **Minta Pemeriksaan**: "Saya ingin memeriksa tanda vital Anda"
- **Pesan Tes**: "Tolong lakukan EKG" atau "Saya perlu enzim jantung"
- **Berikan Diagnosis**: "Saya pikir ini STEMI"

### Perintah OSCE
- `score` atau `progress` - Cek kinerja saat ini
- `case info` - Lihat detail dan tujuan kasus
- `help` - Tampilkan perintah khusus OSCE
- `end case` - Selesaikan kasus dan dapatkan hasil
- `new case` - Mulai kasus berbeda
- `exit osce` - Kembali ke mode chat

### Memahami Skor Anda
- **Anamnesis (30%)**: Wawancara pasien komprehensif
- **Pemeriksaan Fisik (20%)**: Pemeriksaan klinis yang sesuai
- **Investigasi (25%)**: Memesan tes yang relevan dan menginterpretasi hasil
- **Diagnosis (15%)**: Penalaran diagnostik yang akurat
- **Manajemen (10%)**: Keputusan terapi yang sesuai

## 💬 Perintah Mode Chat

### Perintah Dasar
- `help` - Tampilkan informasi bantuan lengkap
- `exit` - Keluar dari aplikasi dengan ringkasan sesi
- `stats` - Lihat statistik sesi saat ini

### Perintah Sistem
- `system status` - Periksa kesehatan dan performa aplikasi
- `health check` - Lakukan pemeriksaan diagnostik komprehensif

## 📊 Pelacakan Sesi

Sistem secara otomatis melacak kemajuan belajar Anda:

- **Pesan Chat**: Jumlah interaksi dengan AI
- **Sesi OSCE**: Kasus medis yang diselesaikan
- **Tren Kinerja**: Peningkatan skor dari waktu ke waktu
- **Manajemen Waktu**: Durasi sesi dan waktu penyelesaian kasus
- **Pemantauan Error**: Masalah sistem dan saran pemulihan

## 🔧 Persyaratan Sistem

- **Node.js**: Versi 16 atau lebih tinggi
- **Koneksi Internet**: Diperlukan untuk akses API AI
- **API Key OpenRouter**: Untuk akses model AI
- **Terminal/Command Line**: Untuk menjalankan aplikasi

## 📁 Struktur Proyek

```
medical-training-system/
├── app.js                 # Titik masuk aplikasi utama
├── lib/                   # Modul inti aplikasi
│   ├── OSCEController.js  # Kontroler logika OSCE utama
│   ├── CaseManager.js     # Manajemen dan pemuatan kasus
│   ├── PatientSimulator.js # Simulasi pasien AI
│   ├── PerformanceTracker.js # Pelacakan progres
│   ├── ScoringEngine.js   # Penilaian dan umpan balik
│   └── ErrorHandler.js    # Penanganan error
├── cases/                 # Berkas kasus medis
│   ├── case-schema.json   # Skema validasi kasus
│   ├── stemi-001.json     # Contoh kasus STEMI
│   └── README.md          # Dokumentasi kasus
├── test/                  # Berkas uji
├── utils/                 # Fungsi utilitas
└── docs/                  # Dokumentasi tambahan
```

## 🧪 Pengujian

Jalankan rangkaian uji untuk memverifikasi fungsionalitas sistem:

```bash
# Jalankan semua tes
npm test

# Jalankan tes dalam mode watch
npm run test:watch

# Jalankan kategori tes spesifik
npm test -- --grep "OSCE"
npm test -- --grep "Integration"
```

## 🔍 Pemecahan Masalah

### Masalah Umum

**Kesalahan Koneksi API**
- Pastikan berkas `.env` Anda berisi kredensial API yang valid
- Periksa koneksi internet Anda
- Pastikan API_URL sudah benar

**Kesalahan Memuat Kasus**
- Pastikan berkas kasus dalam format JSON yang benar
- Periksa bahwa direktori `cases/` berisi berkas kasus yang valid
- Jalankan validasi kasus: `node utils/caseValidator.js`

**Masalah Performa**
- Gunakan `system status` untuk memeriksa kesehatan sistem
- Jalankan `health check` untuk diagnostik komprehensif
- Pantau statistik sesi dengan perintah `stats`

### Mendapatkan Bantuan

1. **Bantuan dalam Aplikasi**: Ketik `help` untuk bantuan kontekstual
2. **Diagnostik Sistem**: Gunakan `system status` dan `health check`
3. **Informasi Sesi**: Cek `stats` untuk data sesi saat ini
4. **Pemulihan Error**: Ikuti saran pemulihan untuk error

## 🎓 Manfaat Pendidikan

### Untuk Mahasiswa Kedokteran
- **Latihan Realistis**: Berinteraksi dengan pasien AI dalam skenario terstruktur
- **Umpan Balik Langsung**: Dapatkan penilaian kinerja dan poin pembelajaran langsung
- **Belajar Fleksibel**: Berlatih kapan saja, di mana saja dengan berbagai tipe kasus
- **Pelacakan Progres**: Pantau peningkatan dari waktu ke waktu dengan analitik detail

### Untuk Pendidik Medis
- **Penilaian Terstandar**: Kriteria evaluasi konsisten untuk semua mahasiswa
- **Cakupan Komprehensif**: Kasus mencakup keterampilan dan pengetahuan klinis penting
- **Analitik Kinerja**: Lacak progres mahasiswa dan identifikasi kekurangan belajar
- **Pelatihan Skalabel**: Dukung banyak mahasiswa dengan penilaian otomatis

## 🔮 Pengembangan Mendatang

- **Kasus Medis Tambahan**: Perluas pustaka kasus dengan lebih banyak spesialisasi
- **Mode Multipemain**: Penyelesaian kasus kolaboratif dengan rekan
- **Analitik Lanjutan**: Wawasan kinerja detail dan rekomendasi
- **Dukungan Seluler**: Antarmuka berbasis web untuk perangkat seluler
- **Integrasi**: Integrasi LMS untuk institusi pendidikan

## 📄 Lisensi

Proyek ini dilisensikan di bawah LISENSI ISC. Lihat berkas LICENSE untuk detailnya.

## 🤝 Kontribusi

Kami menerima kontribusi untuk meningkatkan Sistem Pelatihan Medis:

1. Fork repositori
2. Buat branch fitur
3. Lakukan perubahan Anda
4. Tambahkan tes untuk fungsi baru
5. Ajukan pull request

## 📞 Dukungan

Untuk dukungan teknis atau pertanyaan:
- Periksa bagian pemecahan masalah di atas
- Gunakan perintah diagnostik dalam aplikasi
- Tinjau dokumentasi di direktori `docs/`
- Laporkan isu untuk bug atau permintaan fitur

---

**Selamat Belajar! 🏥📚**

*Tingkatkan keterampilan klinis Anda dengan pelatihan medis bertenaga AI.*
