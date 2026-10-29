// Tanggal dari server berformat YYYY-MM-DD. Ditambah jam 00:00 supaya dibaca sebagai
// waktu lokal dan tanggalnya tidak bergeser karena zona waktu.
export function formatDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

// Tanggal + jam dari server berformat YYYY-MM-DDTHH:mm (jam dinding WIB, tanpa zona waktu),
// sama dengan nilai input datetime-local. Dibaca sebagai waktu lokal, jadi jamnya tidak bergeser.
export function formatDateTime(dateTime: string): string {
    return new Date(dateTime).toLocaleString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
