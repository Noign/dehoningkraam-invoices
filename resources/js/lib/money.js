const euroFormatter = new Intl.NumberFormat('nl-NL', {
    style: 'currency',
    currency: 'EUR',
});

export function formatCents(cents) {
    return euroFormatter.format(cents / 100);
}
