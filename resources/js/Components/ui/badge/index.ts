import { cva, type VariantProps } from 'class-variance-authority';

export const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 select-none',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-teal-600 text-white hover:bg-teal-700',
                secondary: 'border-transparent bg-slate-100 text-slate-900 hover:bg-slate-200',
                destructive: 'border-transparent bg-rose-600 text-white hover:bg-rose-700',
                outline: 'border-slate-300 text-slate-800',
                success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                warning: 'border-amber-200 bg-amber-50 text-amber-800',
                teal: 'border-teal-200 bg-teal-50 text-teal-800',
                cyan: 'border-cyan-200 bg-cyan-50 text-cyan-800',
                amber: 'border-amber-200 bg-amber-50 text-amber-800',
                rose: 'border-rose-200 bg-rose-50 text-rose-700',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
export { default as Badge } from './Badge.vue';
