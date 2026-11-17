import { ref } from 'vue';

export interface ToastItem {
    id: number;
    title?: string;
    description?: string;
    variant?: 'success' | 'error' | 'warning' | 'info';
}

const toasts = ref<ToastItem[]>([]);
let nextId = 1;

function push(toast: Omit<ToastItem, 'id'>) {
    const id = nextId++;
    toasts.value.push({ id, ...toast });
    return id;
}

function dismiss(id: number) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

export function useToast() {
    return {
        toasts,
        dismiss,
        success: (title: string, description?: string) => push({ title, description, variant: 'success' }),
        error: (title: string, description?: string) => push({ title, description, variant: 'error' }),
        warning: (title: string, description?: string) => push({ title, description, variant: 'warning' }),
        info: (title: string, description?: string) => push({ title, description, variant: 'info' }),
    };
}
