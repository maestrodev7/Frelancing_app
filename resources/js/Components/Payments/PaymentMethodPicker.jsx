function PaymentMethodIcon({ method, color }) {
    if (method === 'orange_money') {
        return (
            <svg viewBox="0 0 48 48" className="h-12 w-12" aria-hidden="true">
                <circle cx="24" cy="24" r="22" fill={color ?? '#FF6600'} />
                <text
                    x="24"
                    y="29"
                    textAnchor="middle"
                    fill="white"
                    fontSize="11"
                    fontWeight="bold"
                    fontFamily="Arial, sans-serif"
                >
                    OM
                </text>
            </svg>
        );
    }

    if (method === 'mtn_money') {
        return (
            <svg viewBox="0 0 48 48" className="h-12 w-12" aria-hidden="true">
                <circle cx="24" cy="24" r="22" fill={color ?? '#FFCC00'} />
                <text
                    x="24"
                    y="28"
                    textAnchor="middle"
                    fill="#1A1A1A"
                    fontSize="10"
                    fontWeight="bold"
                    fontFamily="Arial, sans-serif"
                >
                    MTN
                </text>
            </svg>
        );
    }

    return (
        <svg viewBox="0 0 48 48" className="h-12 w-12" aria-hidden="true">
            <rect x="4" y="12" width="40" height="24" rx="4" fill={color ?? '#1E3A5F'} />
            <rect x="4" y="18" width="40" height="6" fill="#2C5282" />
            <rect x="8" y="28" width="14" height="3" rx="1" fill="#CBD5E1" />
            <rect x="26" y="28" width="10" height="3" rx="1" fill="#F59E0B" />
        </svg>
    );
}

export default function PaymentMethodPicker({ methods, value, onChange, error }) {
    return (
        <div>
            <p className="mb-3 text-sm font-medium text-gray-700">Mode de paiement</p>
            <div className="grid gap-3 sm:grid-cols-3">
                {methods.map((method) => {
                    const selected = value === method.value;

                    return (
                        <button
                            key={method.value}
                            type="button"
                            onClick={() => onChange(method.value)}
                            className={`flex flex-col items-center rounded-xl border-2 p-4 transition ${
                                selected
                                    ? 'border-[#007A5E] bg-[#E8F5E9] shadow-md ring-2 ring-[#007A5E]/20'
                                    : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm'
                            }`}
                        >
                            <PaymentMethodIcon
                                method={method.value}
                                color={method.color}
                            />
                            <span className="mt-2 text-center text-xs font-semibold text-gray-800">
                                {method.short}
                            </span>
                            <span className="mt-0.5 text-center text-[10px] text-gray-500">
                                {method.label}
                            </span>
                        </button>
                    );
                })}
            </div>
            {error && <p className="mt-2 text-sm text-red-600">{error}</p>}
        </div>
    );
}
