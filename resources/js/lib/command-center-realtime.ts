type CommandCenterListener = () => void;

const listeners = new Set<CommandCenterListener>();

export function subscribeToCommandCenterEvents(listener: CommandCenterListener): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function dispatchCommandCenterUpdate(): void {
    listeners.forEach((listener) => listener());
}

export function handleCommandCenterBroadcast(eventName: string): void {
    if (eventName === 'command-center.updated') {
        dispatchCommandCenterUpdate();
    }
}
