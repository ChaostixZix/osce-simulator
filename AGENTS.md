# AGENTS.md

Ringkasan aturan untuk AI agents dan kontributor di repo ini.

Migration notice: Frontend sedang migrasi dari Vue 3 ke React menggunakan Inertia + Vibe UI KIT. Fitur baru → React; halaman Vue tetap stabil sampai dimigrasi.

## 1) Aturan Inti (Do / Don’t / Pengecualian)

Do
- Gunakan React + Inertia untuk fitur baru; Vue hanya untuk halaman legacy.
- Ikuti Minimal Design System: gunakan komponen/pola "clean" (clean-card, clean-button), tipografi dan spacing konsisten, warna via CSS variables.
- Interaksi client: gunakan @inertiajs/react (router.* atau useForm) untuk navigasi dan mutasi.
- CSS: pakai variabel tema (—*), bukan hard-coded colors.
- Struktur Laravel standar; update controller/route/model sesuai konvensi proyek.

Don’t
- Jangan edit vendor/ dan node_modules/.
- Jangan pakai fetch/axios langsung untuk navigasi/mutasi (lihat pengecualian di bawah).
- Jangan pakai kelas/gaya “cyber-*” atau efek dekoratif berlebihan.
- Jangan menumpuk shadow/glow di atas clean-card tanpa alasan kuat.

Pengecualian
- Boleh panggil JSON API langsung hanya bila menampilkan JSON di layar tanpa navigasi (render data lokal saja).

## 2) Frontend Minimal Design System (Ringkas)
- Filosofi UI: SaaS-like, elegan, modern, dan mengedepankan user experience (UX-first).
- Komponen/UI: Vibe UI KIT untuk React (default). shadcn-vue hanya untuk legacy Vue (jangan dirombak kecuali migrasi penuh).
- Pola layout: space-y-6 (vertikal), grid gap-4, flex gap-3. Header section: border-b + pb-3 + mb-6.
- Card: gunakan `clean-card` (sudah ada hover lift). Hindari menumpuk shadow lain.
- Tipografi:
  - Page title: `text-2xl font-semibold text-foreground`
  - Section: `text-lg font-medium`
  - Body: `text-muted-foreground`
  - Small: `text-sm text-muted-foreground`
- Detail lengkap: lihat DESIGN_SYSTEM.md.

## 3) Interaksi Client (Inertia-first)
- Navigasi: `<Link>`, `router.visit(url, opts)`.
- Mutasi: `router.post/put/patch/delete` atau `useForm`.
- Upload: gunakan `useForm` + `transform` → `FormData`.
- Partial reload: `router.reload({ only: [...] })`.
- CSRF ditangani Inertia (hindari plumbing manual).

## 4) Backend (Laravel)
- Tambahkan controller di `app/Http/Controllers` + route di `routes/web.php`.
- Model di `app/Models` dengan relasi Eloquent standar.
- Testing: prioritaskan PHPUnit/Pest untuk logic baru.

## 5) Tooling Policy (Prioritas dan Peran)
- Dokumentasi sumber tunggal: gunakan documentation.md (jangan gunakan ByteRover).
- Analisis kode/fitur: gunakan Gemini CLI terlebih dahulu (fokus pada file/fitur spesifik, hindari analisis seluruh repo bila tidak perlu).
- Operasi runtime Laravel (artisan, routes, database/schema, logs, config): gunakan Laravel Boost MCP.
- Fallback manual: jika tool gagal atau tidak relevan, baca file terkait langsung.

## 6) Kanban (Vibe) — Ringkas
- Trigger: hanya buat task jika pengguna menyebut “cTask”.
- Scope: saat diminta “create task(s)”, jangan implementasi kode—hanya task Kanban.
- PRD: untuk fitur baru, buat 1 task PRD dengan judul elaboratif + objective bernomor; simpan PRD di `.claude/kanban/<feature-slug>.prd.md` dan tautkan di task body.
- Sertakan `project_id` dan `task_id` untuk semua operasi task; tautkan artefak terkait.

## 7) Perintah Umum (jalankan dari webapp/)
- `bun run dev` — Vite dev (frontend)
- `composer run dev` — PHP server, queues, Reverb, Vite via bun
- `bun run build` — build produksi
- `bun run lint` / `bun run format`
- `composer install` / `bun install`
- `php artisan test`

## 8) Quality Checklist (sebelum submit)
- [ ] Pakai komponen/pola desain minimal (clean-card/clean-button, tipografi/spacing)
- [ ] Warna via CSS variables; theme-aware (`text-foreground`, `text-muted-foreground`)
- [ ] Interaksi via Inertia (no raw fetch, kecuali pengecualian JSON-only)
- [ ] Rute/Controller/Model sesuai konvensi; tes berjalan untuk logic baru
- [ ] Tidak ada gaya “cyber-*” atau efek visual berlebihan

## 9) Pemeliharaan Aturan
- Tambah/ubah aturan hanya jika pola baru muncul konsisten atau mencegah bug umum.
- Aturan harus singkat, spesifik, dan bisa dipraktikkan. Referensikan kode nyata bila perlu.
- Pindahkan detail panjang ke dokumen khusus (DESIGN_SYSTEM.md, TOOLING.md, KANBAN.md). AGENTS.md harus tetap ringkas.
