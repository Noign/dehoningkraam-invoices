const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    timeZone: 'UTC',
});

const quantityFormatter = new Intl.NumberFormat('nl-NL', {
    maximumFractionDigits: 2,
});

export function formatDate(isoDate) {
    return dateFormatter.format(new Date(isoDate));
}

export function formatQuantity(quantity) {
    return quantity === null ? '' : quantityFormatter.format(Number(quantity));
}
