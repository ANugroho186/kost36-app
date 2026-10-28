// Error unggahan bisa ada di "photos" (jumlah) atau "photos.0", "photos.1", dst. (per file).
// Fungsi ini mengambil error pertama untuk field itu, apa pun indeksnya.
export function firstErrorFor(
    errors: Partial<Record<string, string>>,
    field: string,
): string | undefined {
    return Object.entries(errors).find(
        ([key]) => key === field || key.startsWith(`${field}.`),
    )?.[1];
}
