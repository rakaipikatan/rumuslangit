# -*- coding: utf-8 -*-
"""
Perakit video promosi Rumus Langit.

Membaca klip mentah di  produksi/NN/  lalu menghasilkan  hasil/NN-slug.mp4  (4:5, 1080x1350, 30 fps):
  - tiap adegan dipotong/diperpanjang sesuai durasi di data/NN-slug.json
  - teks layar tiap adegan, logo kecil (S2-S7), end card dengan logo besar + tagline + URL + disclaimer (S8)
  - voice-over (satu berkas, atau per adegan) + musik latar yang otomatis mengecil saat ada suara

Cara pakai (lihat PETUNJUK.md di folder produksi):
  python rakit.py            -> rakit semua folder yang ada di produksi/
  python rakit.py 01         -> hanya video 01
  python rakit.py 01 --pratinjau        -> cepat, resolusi setengah (untuk cek)
  python rakit.py 01 --suara            -> buat voice-over bahasa Indonesia otomatis (perempuan); --suara ardi = laki-laki
  python rakit.py 01 --izinkan-kosong   -> adegan yang belum ada klipnya diisi kartu penanda
"""
import argparse
import asyncio
import json
import re
import subprocess
import sys
import tempfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

try:
    import imageio_ffmpeg
    FFMPEG = imageio_ffmpeg.get_ffmpeg_exe()
except Exception:  # cadangan: ffmpeg sudah terpasang di sistem
    FFMPEG = 'ffmpeg'

ROOT = Path(__file__).resolve().parent
LEBAR, TINGGI, FPS = 1080, 1350, 30
EMAS, UNGU, NAVY = (245, 158, 11), (124, 58, 237), (6, 6, 20)
FONT = ROOT / 'aset' / 'font' / 'DejaVuSans-Bold.ttf'
LOGO = ROOT / 'aset' / 'logo-rumus-langit.png'
EKS_VIDEO = ('.mp4', '.mov', '.m4v', '.webm', '.mkv', '.avi')
EKS_GAMBAR = ('.png', '.jpg', '.jpeg', '.webp')
EKS_AUDIO = ('.mp3', '.wav', '.m4a', '.aac', '.ogg')


def jalankan(cmd):
    r = subprocess.run([str(c) for c in cmd], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    if r.returncode != 0:
        pesan = r.stderr.decode('utf-8', 'replace')[-1800:]
        raise RuntimeError('FFmpeg gagal:\n' + pesan)


def cari(folder, awalan, ekstensi):
    for e in ekstensi:
        for nama in (awalan + e, awalan.upper() + e):
            f = folder / nama
            if f.exists():
                return f
    # toleransi: nama diawali "s1_" / "s1-" dst.
    for f in sorted(folder.iterdir()):
        n = f.stem.lower()
        if f.suffix.lower() in ekstensi and (n == awalan or n.startswith(awalan + '_') or n.startswith(awalan + '-') or n.startswith(awalan + ' ')):
            return f
    return None


# ───────────────────────── gambar teks / kartu ─────────────────────────

def font(ukuran):
    try:
        return ImageFont.truetype(str(FONT), ukuran)
    except Exception:
        return ImageFont.load_default()


def bungkus(draw, teks, f, lebar_maks):
    kata, baris, cur = teks.split(), [], ''
    for k in kata:
        coba = (cur + ' ' + k).strip()
        if draw.textlength(coba, font=f) <= lebar_maks or not cur:
            cur = coba
        else:
            baris.append(cur)
            cur = k
    if cur:
        baris.append(cur)
    return baris


def png_teks(teks, path, ukuran=56, y_tengah=0.74, kotak=True, warna=(255, 255, 255)):
    """Teks bertumpu di area aman (jauh dari tepi), dengan kotak gelap transparan agar terbaca di klip apa pun."""
    im = Image.new('RGBA', (LEBAR, TINGGI), (0, 0, 0, 0))
    d = ImageDraw.Draw(im)
    f = font(ukuran)
    baris = bungkus(d, teks, f, LEBAR - 2 * 130)
    tinggi_baris = int(ukuran * 1.3)
    h = tinggi_baris * len(baris)
    lebar_teks = max(d.textlength(b, font=f) for b in baris)
    y0 = int(TINGGI * y_tengah - h / 2)
    if kotak:
        pad = 28
        d.rounded_rectangle((LEBAR / 2 - lebar_teks / 2 - pad, y0 - pad + 6, LEBAR / 2 + lebar_teks / 2 + pad, y0 + h + pad - 6),
                            radius=26, fill=NAVY + (175,))
    for i, b in enumerate(baris):
        w = d.textlength(b, font=f)
        d.text(((LEBAR - w) / 2, y0 + i * tinggi_baris), b, font=f, fill=warna + (255,))
    im.save(path)


def png_label(teks, path):
    im = Image.new('RGBA', (LEBAR, TINGGI), (0, 0, 0, 0))
    d = ImageDraw.Draw(im)
    f = font(28)
    w = d.textlength(teks, font=f)
    d.rounded_rectangle((100, TINGGI - 190, 100 + w + 36, TINGGI - 190 + 52), radius=14, fill=NAVY + (150,))
    d.text((118, TINGGI - 182), teks, font=f, fill=(255, 255, 255, 215))
    im.save(path)


def png_logo_kecil(path):
    im = Image.new('RGBA', (LEBAR, TINGGI), (0, 0, 0, 0))
    logo = Image.open(LOGO).convert('RGBA')
    w = 170
    logo = logo.resize((w, int(logo.height * w / logo.width)), Image.LANCZOS)
    a = logo.getchannel('A').point(lambda v: int(v * 0.45))
    logo.putalpha(a)
    im.paste(logo, (LEBAR - w - 110, 110), logo)
    im.save(path)


def png_endcard_latar(path):
    """Latar S8 bila klipnya belum ada: gradasi biru malam → ungu dengan bintik halus."""
    import random
    random.seed(7)
    im = Image.new('RGB', (LEBAR, TINGGI), NAVY)
    px = im.load()
    for y in range(TINGGI):
        t = y / TINGGI
        for x in range(0, LEBAR):
            r = int(NAVY[0] + (45 - NAVY[0]) * t * 0.9)
            g = int(NAVY[1] + (22 - NAVY[1]) * t * 0.9)
            b = int(NAVY[2] + (92 - NAVY[2]) * t * 0.9)
            px[x, y] = (r, g, b)
    d = ImageDraw.Draw(im)
    for _ in range(90):
        x, y, rr = random.randint(0, LEBAR), random.randint(0, TINGGI), random.choice((1, 1, 2, 3))
        d.ellipse((x - rr, y - rr, x + rr, y + rr), fill=(245, 200, 120))
    im.save(path)


def png_endcard(data, path):
    im = Image.new('RGBA', (LEBAR, TINGGI), (0, 0, 0, 0))
    logo = Image.open(LOGO).convert('RGBA')
    w = 640
    logo = logo.resize((w, int(logo.height * w / logo.width)), Image.LANCZOS)
    im.paste(logo, ((LEBAR - w) // 2, 150), logo)
    d = ImageDraw.Draw(im)
    y = 150 + logo.height + 40
    for teks, ukuran, warna in ((data['tagline'], 40, EMAS), ('Coba gratis: ' + data['url'], 52, (255, 255, 255))):
        for b in bungkus(d, teks, font(ukuran), LEBAR - 2 * 110):
            w2 = d.textlength(b, font=font(ukuran))
            d.text(((LEBAR - w2) / 2, y), b, font=font(ukuran), fill=warna + (255,))
            y += int(ukuran * 1.4)
        y += 22
    kecil = data['disclaimer']
    w3 = d.textlength(kecil, font=font(28))
    d.text(((LEBAR - w3) / 2, TINGGI - 250), kecil, font=font(28), fill=(255, 255, 255, 170))
    im.save(path)


def png_penanda(kode, nama, path):
    im = Image.new('RGB', (LEBAR, TINGGI), (30, 30, 82))
    d = ImageDraw.Draw(im)
    for i, (t, u) in enumerate(((f'{kode}', 150), ('KLIP BELUM ADA', 56), (nama, 40))):
        w = d.textlength(t, font=font(u))
        d.text(((LEBAR - w) / 2, 480 + i * 190), t, font=font(u), fill=EMAS if i == 0 else (255, 255, 255))
    im.save(path)


# ───────────────────────── suara (voice-over bahasa Indonesia) ─────────────────────────

SUARA = {'gadis': 'id-ID-GadisNeural', 'ardi': 'id-ID-ArdiNeural'}  # perempuan / laki-laki


def durasi_audio(path):
    r = subprocess.run([str(FFMPEG), '-i', str(path)], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    m = re.search(r'Duration:\s*(\d+):(\d+):(\d+(?:\.\d+)?)', r.stderr.decode('utf-8', 'replace'))
    return int(m.group(1)) * 3600 + int(m.group(2)) * 60 + float(m.group(3)) if m else None


def buat_suara(data, folder, nama_suara):
    """Membuat v1.mp3 … v8.mp3 dari naskah tiap adegan (hanya yang belum ada, jadi bisa diganti rekaman sendiri)."""
    try:
        import edge_tts
    except ImportError:
        raise SystemExit('Pustaka edge-tts belum terpasang. Jalankan: python -m pip install -r requirements.txt')
    voice = SUARA[nama_suara]

    async def satu(teks, path):
        await edge_tts.Communicate(teks, voice, rate='+0%').save(str(path))

    for a in data['adegan']:
        n = 'v' + a['kode'][1:]
        if cari(folder, n, EKS_AUDIO):
            continue
        path = folder / f'{n}.mp3'
        try:
            asyncio.run(satu(a['vo'], path))
        except Exception as e:
            raise SystemExit(f'Gagal membuat suara {a["kode"]} (perlu internet): {e}')
        print(f'  suara {a["kode"]}: dibuat ({voice})')


# ───────────────────────── proses satu video ─────────────────────────

def rakit(data_path, args):
    data = json.loads(data_path.read_text(encoding='utf-8'))
    nomor, slug = data['nomor'], data['slug']
    folder = ROOT / 'produksi' / nomor
    keluar = ROOT / 'hasil' / f'{nomor}-{slug}.mp4'
    skala = 0.5 if args.pratinjau else 1.0
    W, H = int(LEBAR * skala) // 2 * 2, int(TINGGI * skala) // 2 * 2
    preset, crf = ('ultrafast', '30') if args.pratinjau else ('medium', '19')

    if not folder.is_dir():
        print(f'[{nomor}] lewati: folder produksi/{nomor}/ belum ada')
        return False
    keluar.parent.mkdir(exist_ok=True)

    print(f'\n=== {nomor} {data["judul"]} ===')
    with tempfile.TemporaryDirectory() as tmp:
        tmp = Path(tmp)
        total = float(data['total'])
        segmen, masalah = [], []

        # 1) Normalkan tiap adegan → klip 4:5, fps tetap, durasi pas
        for a in data['adegan']:
            kode, dur = a['kode'], float(a['durasi'])
            n = kode.lower()
            sumber = cari(folder, n, EKS_VIDEO + EKS_GAMBAR)
            if sumber is None:
                if kode == 'S8':
                    sumber = tmp / 'latar.png'
                    png_endcard_latar(sumber)
                    print(f'  {kode}: klip tidak ada → latar end card otomatis')
                elif args.izinkan_kosong:
                    sumber = tmp / f'penanda_{n}.png'
                    png_penanda(kode, a['nama'], sumber)
                    masalah.append(f'{kode} diisi kartu penanda')
                else:
                    raise SystemExit(f'[{nomor}] Klip {kode} belum ada di produksi/{nomor}/ (beri nama {n}.mp4). '
                                     f'Pakai --izinkan-kosong untuk pratinjau tanpa klip lengkap.')
            seg = tmp / f'seg_{n}.mp4'
            vf = (f'scale={W}:{H}:force_original_aspect_ratio=increase,crop={W}:{H},fps={FPS},'
                  f'tpad=stop_mode=clone:stop_duration={dur},setsar=1,format=yuv420p')
            pre = ['-loop', '1'] if sumber.suffix.lower() in EKS_GAMBAR else []
            jalankan([FFMPEG, '-y', '-v', 'error', *pre, '-i', sumber, '-vf', vf, '-t', dur, '-an',
                      '-c:v', 'libx264', '-preset', 'ultrafast', '-crf', '14', seg])
            segmen.append(seg)
            print(f'  {kode}: {sumber.name} → {dur:g} dtk')

        daftar = tmp / 'daftar.txt'
        daftar.write_text('\n'.join(f"file '{s.as_posix()}'" for s in segmen), encoding='utf-8')

        # 2) PNG overlay
        overlay = []  # (png, mulai, akhir)
        for a in data['adegan']:
            kode, m, dur = a['kode'], float(a['mulai']), float(a['durasi'])
            if kode == 'S8':
                p = tmp / 'endcard.png'
                png_endcard(data, p)
                overlay.append((p, m + 0.5, m + dur))
                continue
            teks = a['teks'] if isinstance(a['teks'], str) else ' '.join(a['teks'])
            p = tmp / f'teks_{kode}.png'
            png_teks(teks, p)
            overlay.append((p, m + 0.4, m + dur - 0.2))
            if kode in ('S5', 'S6'):
                pl = tmp / f'label_{kode}.png'
                png_label('Contoh tampilan', pl)
                overlay.append((pl, m + 0.4, m + dur - 0.2))
        pwm = tmp / 'logo_kecil.png'
        png_logo_kecil(pwm)
        awal_wm = float(data['adegan'][1]['mulai'])
        akhir_wm = float(data['adegan'][-1]['mulai'])
        overlay.append((pwm, awal_wm, akhir_wm))

        # 3) Audio
        suara_utuh = cari(folder, 'voice', EKS_AUDIO) or cari(folder, 'suara', EKS_AUDIO)
        if args.suara and not suara_utuh:
            buat_suara(data, folder, args.suara)
        musik = cari(folder, 'musik', EKS_AUDIO) or cari(folder, 'music', EKS_AUDIO)
        suara_adegan = []
        if not suara_utuh:
            for a in data['adegan']:
                f = cari(folder, 'v' + a['kode'][1:], EKS_AUDIO)
                if f:
                    dur_a = durasi_audio(f)
                    batas = float(a['durasi']) - 0.4
                    tempo = 1.0
                    if dur_a and dur_a > batas:
                        tempo = min(dur_a / batas, 1.35)   # percepat sedikit agar muat di adegannya
                        if dur_a / tempo > batas + 0.05:
                            print(f'  ! suara {a["kode"]} masih lebih panjang dari adegannya ({dur_a:.1f} dtk > {batas:.1f} dtk): persingkat naskah/rekaman')
                    suara_adegan.append((f, float(a['mulai']), tempo))

        cmd = [FFMPEG, '-y', '-v', 'error', '-f', 'concat', '-safe', '0', '-i', daftar]
        idx = 1
        for p, _, _ in overlay:
            cmd += ['-i', p]
            idx += 1
        a_voice, a_music = [], None
        if suara_utuh:
            cmd += ['-i', suara_utuh]; a_voice.append((idx, 0.25, 1.0)); idx += 1
        for f, m, tempo in suara_adegan:
            cmd += ['-i', f]; a_voice.append((idx, m + 0.2, tempo)); idx += 1
        if musik:
            cmd += ['-stream_loop', '-1', '-i', musik]; a_music = idx; idx += 1
        if not a_voice and not a_music:
            cmd += ['-f', 'lavfi', '-i', 'anullsrc=r=44100:cl=stereo']; a_null = idx; idx += 1

        # video chain
        fc, prev = [], '0:v'
        fc.append(f'[0:v]scale={W}:{H}[b0]'); prev = 'b0'
        for i, (p, m, e) in enumerate(overlay, start=1):
            sc = '' if skala == 1.0 else f'scale={W}:{H},'
            fc.append(f'[{i}:v]{sc}format=rgba[o{i}]')
            fc.append(f"[{prev}][o{i}]overlay=0:0:format=auto:enable='between(t,{m:.2f},{e:.2f})'[b{i}]")
            prev = f'b{i}'
        fc.append(f'[{prev}]format=yuv420p[vout]')

        # audio chain
        if a_voice:
            sufiks = []
            for k, (ai, mulai_s, tempo) in enumerate(a_voice):
                ms = int(mulai_s * 1000)
                at = f',atempo={tempo:.3f}' if tempo > 1.001 else ''
                fc.append(f'[{ai}:a]aformat=sample_rates=44100:channel_layouts=stereo{at},adelay={ms}|{ms}[vo{k}]')
                sufiks.append(f'[vo{k}]')
            if len(sufiks) > 1:
                fc.append(''.join(sufiks) + f'amix=inputs={len(sufiks)}:normalize=0[vmix]')
            else:
                fc.append(f'{sufiks[0]}anull[vmix]')
            if a_music is not None:
                fc.append('[vmix]asplit=2[vkeluar][vkunci]')
                fc.append(f'[{a_music}:a]aformat=sample_rates=44100:channel_layouts=stereo,volume=0.35[m0]')
                fc.append('[m0][vkunci]sidechaincompress=threshold=0.03:ratio=10:attack=20:release=600[mduck]')
                fc.append('[vkeluar][mduck]amix=inputs=2:normalize=0[amix]')
                ahir = '[amix]'
            else:
                ahir = '[vmix]'
        elif a_music is not None:
            fc.append(f'[{a_music}:a]aformat=sample_rates=44100:channel_layouts=stereo,volume=0.6[amix]')
            ahir = '[amix]'
        else:
            fc.append(f'[{a_null}:a]anull[amix]')
            ahir = '[amix]'
        fc.append(f'{ahir}atrim=0:{total},afade=t=out:st={total - 1.5}:d=1.5,alimiter=limit=0.95[aout]')

        cmd += ['-filter_complex', ';'.join(fc), '-map', '[vout]', '-map', '[aout]', '-t', f'{total}',
                '-c:v', 'libx264', '-preset', preset, '-crf', crf, '-pix_fmt', 'yuv420p', '-r', FPS,
                '-c:a', 'aac', '-b:a', '192k', '-movflags', '+faststart', keluar]
        jalankan(cmd)

    ukuran = keluar.stat().st_size / 1_000_000
    print(f'  ✔ {keluar.relative_to(ROOT)}  ({ukuran:.1f} MB, {total:g} dtk' + (', PRATINJAU' if args.pratinjau else '') + ')')
    for m in masalah:
        print('  ! ' + m)
    return True


def main():
    ap = argparse.ArgumentParser(description='Rakit video promosi Rumus Langit (4:5, 1080x1350).')
    ap.add_argument('nomor', nargs='*', help='nomor video, mis. 01 (kosong = semua yang foldernya ada)')
    ap.add_argument('--pratinjau', action='store_true', help='resolusi setengah + cepat, untuk pengecekan')
    ap.add_argument('--suara', nargs='?', const='gadis', choices=sorted(SUARA), help='buat voice-over bahasa Indonesia otomatis: gadis (perempuan, bawaan) atau ardi (laki-laki)')
    ap.add_argument('--izinkan-kosong', action='store_true', help='adegan tanpa klip diisi kartu penanda')
    args = ap.parse_args()

    semua = sorted((ROOT / 'data').glob('*.json'))
    if args.nomor:
        pilih = [d for d in semua if d.name.split('-')[0] in {n.zfill(2) for n in args.nomor}]
        if not pilih:
            sys.exit('Nomor video tidak dikenal. Contoh: 00, 01, ... 12')
    else:
        pilih = [d for d in semua if (ROOT / 'produksi' / d.name.split('-')[0]).is_dir()]
        if not pilih:
            sys.exit('Belum ada folder di produksi/. Baca produksi/PETUNJUK.md')

    berhasil = 0
    for d in pilih:
        try:
            berhasil += 1 if rakit(d, args) else 0
        except RuntimeError as e:
            print(f'  ✘ gagal: {e}')
    print(f'\nSelesai: {berhasil}/{len(pilih)} video dirakit. Hasil ada di folder "hasil".')


if __name__ == '__main__':
    main()
