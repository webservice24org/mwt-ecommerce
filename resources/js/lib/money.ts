export function formatMoney(amount: number, currency = 'BDT', locale = 'en-BD'): string {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount / 100)
}
