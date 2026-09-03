/** Format a Date for `<input type="datetime-local">` using local timezone fields. */
export function toLocalDateTimeInputValue(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/** Default task due date on create: now + 1 hour in the user's local timezone. */
export function defaultTaskDueAtInputValue(from: Date = new Date()): string {
    return toLocalDateTimeInputValue(new Date(from.getTime() + 60 * 60 * 1000));
}
