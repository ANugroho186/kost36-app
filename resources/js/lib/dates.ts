// Tanggal dari server berformat YYYY-MM-DD. Ditambah jam 00:00 supaya dibaca sebagai
// waktu lokal dan tanggalnya tidak bergeser karena zona waktu.
export function formatDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
