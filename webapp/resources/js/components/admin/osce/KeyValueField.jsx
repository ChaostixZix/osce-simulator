import React from 'react';

const createRow = (key = '', value = '') => ({
    id: crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
    key,
    value,
});

const toRows = (value) => {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return [createRow('', '')];
    }

    const entries = Object.entries(value).map(([key, entryValue]) => {
        const normalizedValue = typeof entryValue === 'string' || typeof entryValue === 'number'
            ? String(entryValue)
            : JSON.stringify(entryValue);
        return createRow(String(key), normalizedValue ?? '');
    });

    return entries.length > 0 ? entries : [createRow('', '')];
};

const sanitizeRows = (rows) =>
    rows.reduce((acc, row) => {
        const key = (row.key ?? '').trim();
        const value = (row.value ?? '').trim();
        if (key !== '' && value !== '') {
            acc[key] = value;
        }
        return acc;
    }, {});

export default function KeyValueField({
    label,
    helper,
    value = {},
    onChange,
    keyPlaceholder = 'Name',
    valuePlaceholder = 'Value',
    addLabel = 'Add item',
    id,
}) {
    const [rows, setRows] = React.useState(toRows(value));

    React.useEffect(() => {
        setRows(toRows(value));
    }, [JSON.stringify(value)]);

    const updateRows = (updater) => {
        setRows((current) => {
            const next = updater(current);
            if (onChange) {
                onChange(sanitizeRows(next));
            }
            return next;
        });
    };

    const handleChange = (index, field, nextValue) => {
        updateRows((current) => {
            const next = [...current];
            next[index] = { ...next[index], [field]: nextValue };
            return next;
        });
    };

    const handleAdd = () => {
        updateRows((current) => [...current, createRow('', '')]);
    };

    const handleRemove = (index) => {
        updateRows((current) => {
            const next = current.filter((_, rowIndex) => rowIndex !== index);
            return next.length > 0 ? next : [createRow('', '')];
        });
    };

    return (
        <div className="space-y-2">
            <div>
                <label className="text-sm font-medium text-foreground" htmlFor={id}>
                    {label}
                </label>
                {helper && <p className="text-sm text-muted-foreground">{helper}</p>}
            </div>

            <div className="space-y-2">
                {rows.map((row, index) => (
                    <div key={row.id} className="grid gap-2 sm:grid-cols-[1fr_auto_1fr_auto]">
                        <input
                            id={index === 0 ? id : undefined}
                            type="text"
                            value={row.key}
                            placeholder={keyPlaceholder}
                            onChange={(event) => handleChange(index, 'key', event.target.value)}
                            className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        />
                        <span className="hidden sm:block self-center text-sm text-muted-foreground">→</span>
                        <input
                            type="text"
                            value={row.value}
                            placeholder={valuePlaceholder}
                            onChange={(event) => handleChange(index, 'value', event.target.value)}
                            className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        />
                        <button
                            type="button"
                            onClick={() => handleRemove(index)}
                            className="clean-button px-3 py-2 text-sm"
                            aria-label={`Remove ${label} row ${index + 1}`}
                        >
                            −
                        </button>
                    </div>
                ))}

                <button type="button" onClick={handleAdd} className="clean-button px-4 py-2 text-sm">
                    {addLabel}
                </button>
            </div>
        </div>
    );
}
