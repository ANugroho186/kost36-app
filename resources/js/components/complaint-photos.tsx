// URL foto selalu lewat route complaints.photos.show, yang mengecek hak akses di server.
export type ComplaintPhoto = { id: number; url: string };

export function ComplaintPhotos({
    photos,
    title,
}: {
    photos: ComplaintPhoto[];
    title: string;
}) {
    if (photos.length === 0) {
        return null;
    }

    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {photos.map((photo, index) => (
                <a
                    key={photo.id}
                    href={photo.url}
                    target="_blank"
                    rel="noreferrer"
                >
                    <img
                        src={photo.url}
                        alt={`Photo ${index + 1} of “${title}”`}
                        className="aspect-square w-full rounded-md border object-cover"
                    />
                </a>
            ))}
        </div>
    );
}
