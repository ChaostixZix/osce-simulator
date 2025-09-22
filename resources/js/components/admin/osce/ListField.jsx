import React from 'react';

const createPlaceholderItems = (values) => {
    if (values.length === 0) {
        return [''];
    }

    return values;
};

const sanitizeValues = (values) =>
    values
        .map((item) => (typeof item === 'string' ? item.trim() : ''))
        .filter((item) => item !== '');

export default function ListField({
    label,
    helper,
    values = [],
    onChange,
    placeholder = 'Add item…',
    addLabel = 'Add item',
    id,
}) {
    const [items, setItems] = React.useState(createPlaceholderItems(values));

    React.useEffect(() => {
        setItems(createPlaceholderItems(Array.isArray(values) ? values : []));
    }, [JSON.stringify(values)]);

    const handleItemChange = (index, nextValue) => {
        const updated = [...items];
        updated[index] = nextValue;
        setItems(updated);

        if (onChange) {
            onChange(sanitizeValues(updated));
        }
    };

    const handleAdd = () => {
        const updated = [...items, ''];
        setItems(updated);
    };

    const handleRemove = (index) => {
        const updated = items.filter((_, itemIndex) => itemIndex !== index);
        const next = updated.length > 0 ? updated : [''];
        setItems(next);

        if (onChange) {
            onChange(sanitizeValues(updated));
        }
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
                {items.map((item, index) => (
                    <div key={`${index}`} className="flex items-start gap-2">
                        <input
                            id={index === 0 ? id : undefined}
                            type="text"
                            value={item}
                            onChange={(event) => handleItemChange(index, event.target.value)}
                            placeholder={placeholder}
                            className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        />
                        <button
                            type="button"
                            onClick={() => handleRemove(index)}
                            className="clean-button px-3 py-2 text-sm"
                            aria-label={`Remove ${label} item ${index + 1}`}
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
