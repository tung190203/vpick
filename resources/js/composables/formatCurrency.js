export const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value).replace('₫', '')
}

export const formatSpecialCurrency = (value) => {
    const units = [
        { value: 1e12, symbol: 'T' },
        { value: 1e9, symbol: 'B' },
        { value: 1e6, symbol: 'M' },
        { value: 1e3, symbol: 'K' },
    ]

    for (const unit of units) {
        if (value >= unit.value) {
            return (
                (value / unit.value)
                    .toFixed(1)
                    .replace(/\.0$/, '') +
                unit.symbol
            )
        }
    }

    return new Intl.NumberFormat('vi-VN').format(value)
}