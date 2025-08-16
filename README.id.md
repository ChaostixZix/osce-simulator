# sistem pelatihan medis

platform pendidikan medis komprehensif yang menggabungkan kemampuan chat bertenaga ai dengan pelatihan osce (objective structured clinical examination) terstruktur untuk mahasiswa kedokteran.

## 🚀 mulai cepat

1. **instal dependensi**
   ```bash
   npm install
   ```

2. **konfigurasi lingkungan**
   buat berkas `.env` dengan kredensial api openrouter anda:
   ```env
   API_URL=https://openrouter.ai/api/v1/chat/completions
   API_KEY=masukkan_api_key_openrouter_anda_di_sini
   API_MODEL=anthropic/claude-3.5-sonnet
   ```

3. **jalankan aplikasi**
   ```bash
   node app.js
   ```

4. **mulai pelatihan**
   - ketik `start osce` untuk memulai pelatihan kasus medis
   - ketik pesan apa pun untuk mengobrol dengan ai
   - ketik `help` untuk perintah rinci

## 🏥 fitur

### mode chat
- **percakapan bertenaga ai**: interaksi bahasa alami dengan model ai canggih
- **memori percakapan**: manajemen riwayat otomatis dengan ringkasan cerdas
- **pemulihan kesalahan**: logika ulang dan penanganan error yang tangguh
- **pelacakan sesi**: pantau aktivitas dan statistik chat anda

### mode osce
- **kasus klinis terstruktur**: latih diri dengan skenario medis realistis
- **simulasi pasien ai**: respons pasien interaktif berdasarkan data kasus
- **pelacakan kinerja**: pemantauan checklist otomatis dan pelacakan progres
- **penilaian cerdas**: sistem penilaian berbobot dengan umpan balik detail
- **berbagai kasus**: beragam kondisi medis dan spesialisasi

## 📚 kasus yang tersedia

### stemi-001: sindrom koroner akut
- **skenario**: pria 58 tahun dengan nyeri dada akut
- **fokus**: kardiologi darurat, interpretasi ekg, manajemen waktu kritis
- **keterampilan yang dilatih**: anamnesis, pemeriksaan fisik, penalaran diagnostik
- **tujuan pembelajaran**: pengenalan stemi, protokol darurat, stratifikasi risiko

*kasus lainnya tersedia di direktori `cases/`*

## 🎯 cara menggunakan mode osce

### memulai kasus
1. ketik `start osce` untuk masuk mode osce
2. pilih kasus dari daftar yang tersedia
3. baca keluhan utama dan mulai pemeriksaan

### berinteraksi dengan pasien
- **ajukan pertanyaan**: "bisakah anda jelaskan nyeri dada anda?"
- **minta pemeriksaan**: "saya ingin memeriksa tanda vital anda"
- **pesan tes**: "tolong lakukan ekg" atau "saya perlu enzim jantung"
- **berikan diagnosis**: "saya pikir ini stemi"

### perintah osce
- `score` atau `progress` - cek kinerja saat ini
- `case info` - lihat detail dan tujuan kasus
- `help` - tampilkan perintah khusus osce
- `end case` - selesaikan kasus dan dapatkan hasil
- `new case` - mulai kasus berbeda
- `exit osce` - kembali ke mode chat

### memahami skor anda
- **anamnesis (30%)**: wawancara pasien komprehensif
- **pemeriksaan fisik (20%)**: pemeriksaan klinis yang sesuai
- **investigasi (25%)**: memesan tes yang relevan dan menginterpretasi hasil
- **diagnosis (15%)**: penalaran diagnostik yang akurat
- **manajemen (10%)**: keputusan terapi yang sesuai

## 💬 perintah mode chat

### perintah dasar
- `help` - tampilkan informasi bantuan lengkap
- `exit` - keluar dari aplikasi dengan ringkasan sesi
- `stats` - lihat statistik sesi saat ini

### perintah sistem
- `system status` - periksa kesehatan dan performa aplikasi
- `health check` - lakukan pemeriksaan diagnostik komprehensif

## 📊 pelacakan sesi

sistem secara otomatis melacak kemajuan belajar anda:

- **pesan chat**: jumlah interaksi dengan ai
- **sesi osce**: kasus medis yang diselesaikan
- **tren kinerja**: peningkatan skor dari waktu ke waktu
- **manajemen waktu**: durasi sesi dan waktu penyelesaian kasus
- **pemantauan error**: masalah sistem dan saran pemulihan

## 🔧 persyaratan sistem

- **node.js**: versi 16 atau lebih tinggi
- **koneksi internet**: diperlukan untuk akses api ai
- **api key openrouter**: untuk akses model ai
- **terminal/command line**: untuk menjalankan aplikasi

## 📁 struktur proyek

```
medical-training-system/
├── app.js                 # titik masuk aplikasi utama
├── lib/                   # modul inti aplikasi
│   ├── OSCEController.js  # kontroler logika osce utama
│   ├── CaseManager.js     # manajemen dan pemuatan kasus
│   ├── PatientSimulator.js # simulasi pasien ai
│   ├── PerformanceTracker.js # pelacakan progres
│   ├── ScoringEngine.js   # penilaian dan umpan balik
│   └── ErrorHandler.js    # penanganan error
├── cases/                 # berkas kasus medis
│   ├── case-schema.json   # skema validasi kasus
│   ├── stemi-001.json     # contoh kasus stemi
│   └── README.md          # dokumentasi kasus
├── test/                  # berkas uji
├── utils/                 # fungsi utilitas
└── docs/                  # dokumentasi tambahan
```

## 🧪 pengujian

jalankan rangkaian uji untuk memverifikasi fungsionalitas sistem:

```bash
# jalankan semua tes
npm test

# jalankan tes dalam mode watch
npm run test:watch

# jalankan kategori tes spesifik
npm test -- --grep "osce"
npm test -- --grep "integration"
```

## 🔍 pemecahan masalah

### masalah umum

**kesalahan koneksi api**
- pastikan berkas `.env` anda berisi kredensial api yang valid
- periksa koneksi internet anda
- pastikan api_url sudah benar

**kesalahan memuat kasus**
- pastikan berkas kasus dalam format json yang benar
- periksa bahwa direktori `cases/` berisi berkas kasus yang valid
- jalankan validasi kasus: `node utils/caseValidator.js`

**masalah performa**
- gunakan `system status` untuk memeriksa kesehatan sistem
- jalankan `health check` untuk diagnostik komprehensif
- pantau statistik sesi dengan perintah `stats`

### mendapatkan bantuan

1. **bantuan dalam aplikasi**: ketik `help` untuk bantuan kontekstual
2. **diagnostik sistem**: gunakan `system status` dan `health check`
3. **informasi sesi**: cek `stats` untuk data sesi saat ini
4. **pemulihan error**: ikuti saran pemulihan untuk error

## 🎓 manfaat pendidikan

### untuk mahasiswa kedokteran
- **latihan realistis**: berinteraksi dengan pasien ai dalam skenario terstruktur
- **umpan balik langsung**: dapatkan penilaian kinerja dan poin pembelajaran langsung
- **belajar fleksibel**: berlatih kapan saja, di mana saja dengan berbagai tipe kasus
- **pelacakan progres**: pantau peningkatan dari waktu ke waktu dengan analitik detail

### untuk pendidik medis
- **penilaian terstandar**: kriteria evaluasi konsisten untuk semua mahasiswa
- **cakupan komprehensif**: kasus mencakup keterampilan dan pengetahuan klinis penting
- **analitik kinerja**: lacak progres mahasiswa dan identifikasi kekurangan belajar
- **pelatihan skalabel**: dukung banyak mahasiswa dengan penilaian otomatis

## 🔮 pengembangan mendatang

- **kasus medis tambahan**: perluas pustaka kasus dengan lebih banyak spesialisasi
- **mode multipemain**: penyelesaian kasus kolaboratif dengan rekan
- **analitik lanjutan**: wawasan kinerja detail dan rekomendasi
- **dukungan seluler**: antarmuka berbasis web untuk perangkat seluler
- **integrasi**: integrasi lms untuk institusi pendidikan

## 📄 lisensi

proyek ini dilisensikan di bawah lisensi isc. lihat berkas license untuk detailnya.

## 🤝 kontribusi

kami menerima kontribusi untuk meningkatkan sistem pelatihan medis:

1. fork repositori
2. buat branch fitur
3. lakukan perubahan anda
4. tambahkan tes untuk fungsi baru
5. ajukan pull request

## 📞 dukungan

untuk dukungan teknis atau pertanyaan:
- periksa bagian pemecahan masalah di atas
- gunakan perintah diagnostik dalam aplikasi
- tinjau dokumentasi di direktori `docs/`
- laporkan isu untuk bug atau permintaan fitur

---

**selamat belajar! 🏥📚**

*tingkatkan keterampilan klinis anda dengan pelatihan medis bertenaga ai.*
