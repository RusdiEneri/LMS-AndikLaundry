import { cva, type VariantProps } from 'class-variance-authority';

export const badgeVariants = cva(
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold select-none transition-colors',
    {
        variants: {
            variant: {
                default: 'bg-[#E6E9FD] text-[#26338A]',
                secondary: 'bg-slate-100 text-slate-700',
                destructive: 'bg-red-50 text-red-700 border border-red-200',
                outline: 'border border-slate-200 text-slate-700 bg-white',
                success: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                warning: 'bg-amber-50 text-amber-800 border border-amber-200',
                teal: 'bg-teal-50 text-teal-800 border border-teal-200',
                cyan: 'bg-sky-50 text-sky-800 border border-sky-200',
                amber: 'bg-amber-50 text-amber-800 border border-amber-200',
                rose: 'bg-rose-50 text-rose-700 border border-rose-200',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
export { default as Badge } from './Badge.vue';
