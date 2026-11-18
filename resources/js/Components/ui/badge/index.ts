import { cva, type VariantProps } from 'class-variance-authority';

export const badgeVariants = cva(
    'inline-flex items-center gap-1.5 rounded-pill px-3 py-1 text-xs font-semibold select-none',
    {
        variants: {
            variant: {
                default: 'bg-primary-100 text-primary-700',
                secondary: 'bg-bg text-text-muted border border-border',
                outline: 'border border-border text-text-muted bg-surface',
                success: 'bg-success/15 text-success',
                warning: 'bg-warning/15 text-warning',
                danger: 'bg-danger/10 text-danger',
                destructive: 'bg-danger/10 text-danger',
                info: 'bg-primary-100 text-primary-700',
                teal: 'bg-accent-100 text-accent-700',
                amber: 'bg-warning/15 text-warning',
                rose: 'bg-danger/10 text-danger',
                emerald: 'bg-success/15 text-success',
                sky: 'bg-primary-100 text-primary-700',
                cyan: 'bg-primary-100 text-primary-700',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
export { default as Badge } from './Badge.vue';
