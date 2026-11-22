#!/usr/bin/env bash
# Skrip pembantu untuk menghasilkan placeholder video 480p faststart jika ffmpeg tersedia
# Sesuai SPEC A8: H.264+AAC di public/media/videos/, 480p, faststart.

TARGET_DIR="public/media/videos"
mkdir -p "$TARGET_DIR"

VIDEOS=("kenalan_gigi" "makanan_gigi" "cara_sikat" "dokter_gigi")
COLORS=("blue" "green" "orange" "purple")

if command -v ffmpeg &> /dev/null; then
    echo "FFmpeg ditemukan! Membuat 4 placeholder video 480p..."
    for i in "${!VIDEOS[@]}"; do
        NAME="${VIDEOS[$i]}"
        COLOR="${COLORS[$i]}"
        echo "Membuat $NAME.mp4..."
        ffmpeg -y -f lavfi -i color=c=$COLOR:s=854x480:d=10 \
               -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 \
               -c:v libx264 -tune stillimage -pix_fmt yuv420p -movflags +faststart \
               -c:a aac -shortest "$TARGET_DIR/$NAME.mp4"
    done
    echo "Placeholder video berhasil dibuat!"
else
    echo "FFmpeg tidak ditemukan. Video dapat diunggah manual ke $TARGET_DIR/"
fi
